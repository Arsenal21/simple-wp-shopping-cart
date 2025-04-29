<?php

class WPSC_Manual_Checkout {

	public $data = array();

	public static function validate_checkout_form( $post ){
		if ( !isset($post['first_name']) || empty(trim($post['first_name'])) ) return false;
		if ( !isset($post['email']) || empty(trim($post['email'])) || !is_email(trim($post['email'])) ) return false;

		if ( ! WPSC_Cart::get_instance()->all_cart_items_digital()){
			if ( !isset($post['address']['street']) || empty(trim($post['address']['street'])) ) return false;
			if ( !isset($post['address']['city']) || empty(trim($post['address']['city'])) ) return false;
			if ( !isset($post['address']['state']) || empty(trim($post['address']['state'])) ) return false;
		}

		return true;
	}

	public function process_and_create_order( $post_data ) {
		$wpsc_cart = WPSC_Cart::get_instance();

		$wpsc_cart->calculate_cart_totals_and_postage();

		$cart_items = $wpsc_cart->get_items();

		$post_id = $wpsc_cart->get_cart_cpt_id();

		//Check if cart items are empty
		if(empty($cart_items)){
			throw new \Exception(__( 'No cart items found. Cannot place this order!', 'wordpress-simple-paypal-shopping-cart' ));
		}

		// Process payment data.
		$this->process_payment_data($post_data, $wpsc_cart);

		//Update the post to publish status
		$updated_wpsc_order = array(
			'ID' => $post_id,
			'post_status' => 'publish',
			'post_type' => WPSC_Cart::POST_TYPE,
			'post_date' => current_time('Y-m-d H:i:s')
		);
		wp_update_post( $updated_wpsc_order );

		update_post_meta( $post_id, 'wpsc_first_name', $this->data['first_name'] );
		update_post_meta( $post_id, 'wpsc_last_name', $this->data['last_name'] );
		update_post_meta( $post_id, 'wpsc_email_address', $this->data['buyer_email'] );
		update_post_meta( $post_id, 'wpsc_txn_id', $this->data['txn_id'] );
		update_post_meta( $post_id, 'wpsc_total_amount', $this->data['payment_amount'] );
		update_post_meta( $post_id, 'wpsc_ipaddress', $this->data['ip_address'] );
		update_post_meta( $post_id, 'wpsc_address', $this->data['address'] ); // Using shipping address in wpsc_address post meta. This meta-key hasn't changed for backward compatibility.
		update_post_meta( $post_id, 'wpsc_billing_address', $this->data['billing_address'] );
		update_post_meta( $post_id, 'wpspsc_phone', $this->data['phone']);
		update_post_meta( $post_id, 'wpsc_applied_coupon', $this->data['applied_coupon_code'] );
		update_post_meta( $post_id, 'wpsc_payment_gateway', $this->data['gateway'] );
		update_post_meta( $post_id, 'wpsc_tax_amount', $this->data['tax_amount'] );
		update_post_meta( $post_id, 'wpsc_shipping_amount', $this->data['shipping'] );
		update_post_meta( $post_id, 'wpsc_shipping_region', $this->data['shipping_region'] );
		update_post_meta( $post_id, 'wpspsc_items_ordered', $this->data['product_details'] );
		update_post_meta( $post_id, 'wpsc_order_status', $this->data['status'] );

		// Send notification emails
		WPSC_Email_Handler::send_manual_checkout_notification_emails($post_id);

		// Empty any incomplete old cart orders.
		wpsc_clean_incomplete_old_cart_orders();

		// Reset/clear the cart.
		$wpsc_cart->reset_cart_after_txn();
	}

	public function process_payment_data( $post_data, $cart_obj ){
		$cart_items = $cart_obj->get_items();

		$post_id = $cart_obj->get_cart_cpt_id();
		$cart_id = $cart_obj->get_cart_id();

		$this->data['post_id'] = $post_id;
		$this->data['cart_id'] = $cart_id;
		$this->data['first_name'] = sanitize_text_field($post_data['first_name']);
		$this->data['last_name'] = sanitize_text_field($post_data['last_name']);
		$this->data['buyer_email'] = sanitize_email($post_data['email']);
		$this->data['txn_id'] = uniqid('manual_');
		$this->data['payment_amount'] = $cart_obj->get_grand_total_formatted();

		$this->data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->data['ip_address'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		$this->data['address'] = '';

		if (isset($post_data['address']) && is_array($post_data['address'])){
			// Sanitize all address fields.
			$address = array_map('sanitize_text_field', $post_data['address']);

			$street = isset($address['street']) ? $address['street'] : '';
			$city = isset($address['city']) ? $address['city'] : '';
			$country = isset($address['country']) ? wpsc_get_country_name_by_country_code($address['country']) : '';
			$state = isset($address['state']) ? $address['state'] : '';
			$postal_code = isset($address['postal_code']) ? $address['postal_code'] : '';

			$address_fields = array_filter(array( $street , $city , $state , $postal_code , $country )); // Removes empty fields

			// Get full address.
			$this->data['address'] = implode(", ", $address_fields);
		}

		$this->data['billing_address'] = ''; // TODO
		$this->data['phone'] = '';

		$coupon = WPSPSC_Coupons_Collection::get_instance();
		$this->data['applied_coupon_code'] = $coupon->get_applied_coupon_code($cart_obj->get_cart_id());

		$this->data['gateway'] = 'manual';
		$this->data['tax_amount'] = 0; // At the moment we don't have tax calculation. So set it to 0.

		$currency_symbol = get_option( 'cart_currency_symbol' );

		$this->data['shipping_region'] = '';
		$selected_shipping_region = check_shipping_region_str($cart_obj->get_selected_shipping_region());
		if ($selected_shipping_region) {
			$this->data['shipping_region'] = $selected_shipping_region['type'] == '0' ? wpsc_get_country_name_by_country_code($selected_shipping_region['loc']) : $selected_shipping_region['loc'];
		}

		$this->data['product_details'] = '';

		$item_counter = 1;
		if ($cart_items) {
			foreach ( $cart_items as $item ) {
				if ($item_counter != 1) {
					$this->data['product_details'] .= "\n";
				}
				$item_total = $item->get_price() * $item->get_quantity();
				$this->data['product_details'] .= $item->get_name() . " x " . $item->get_quantity() . " - " . $currency_symbol . wpsc_number_format_price( $item_total ) . "\n";
				if ($item->get_file_url()) {
					$file_url = $item->get_file_url();
					$this->data['product_details'] .= "Download Link: " . $file_url . "\n";
				}
				$item_counter++;
			}
		}

		$shipping = $cart_obj->get_total_shipping_cost();
		$this->data['shipping'] = !empty( $shipping ) ? wpsc_number_format_price( $shipping ) : "0.00";

		$this->data['status'] = "Pending";
	}
}
