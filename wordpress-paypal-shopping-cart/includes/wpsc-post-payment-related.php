<?php

/**
 * Handles post payment related operations for PayPal PPCP checkout.
 */
class WPSC_Post_Payment_Related
{

	/**
	 * Process, decode and sanitize ipn related data.
	 *
	 * @param array $ipn_data The ipn data array to add new data to.
	 * @return void
	 */
	public static function add_additional_data_to_ipn_data(&$ipn_data)
	{
		/**
		 * Process address.
		 */
		if (empty($ipn_data['address_street']) && empty($ipn_data['address_city'])) {
			// No address value present.
			$ipn_data['address'] = "";
		} else {
			// An address value is present.
			$ipn_data['address'] = $ipn_data['address_street'] . ", " . $ipn_data['address_city'] . ", " . $ipn_data['address_state'] . ", " . $ipn_data['address_zip'] . ", " . $ipn_data['address_country'];
		}

		/**
		 * Get custom values from string.
		 */
		$custom_value_str = urldecode($ipn_data['custom']); // urldecode is harmless.
		wspsc_log_payment_debug('Custom field value in the IPN: ' . $custom_value_str, true);
		$custom_values = wp_cart_get_custom_var_array($custom_value_str);

		$ipn_data['post_id'] = $custom_values['wp_cart_id'];
		$ipn_data['applied_coupon_code'] = isset($custom_values['coupon_code']) ? $custom_values['coupon_code'] : '';
		$ipn_data['ap_id'] = isset($custom_values['ap_id']) ? $custom_values['ap_id'] : '';

		wspsc_log_payment_debug('Custom values: ', true);
		wspsc_log_debug_array($custom_values, true);
		wspsc_log_payment_debug('Order post id: ' . $ipn_data['post_id'], true);

		/**
		 * Process product details data.
		 */
		$ipn_data['product_details'] = "";
		$ipn_data['shipping'] = 0;
		$item_counter = 1;
		$currency_symbol = get_option('cart_currency_symbol');

		// Get the original cart items from the order post meta (that we saved after the paypal order was created)
		$paypal_order_id = $ipn_data['paypal_order_id'];
		$wpsc_cart_items_pp_order_id_key = 'wpsc_cart_items_' . $paypal_order_id;
		$orig_cart_items = get_post_meta($ipn_data['post_id'], $wpsc_cart_items_pp_order_id_key, true);
		//$orig_cart_items = get_post_meta($ipn_data['post_id'], 'wpsc_cart_items', true);
		//wspsc_log_payment_debug( 'Original cart items from the order post below.', true );
		//wspsc_log_debug_array( $orig_cart_items, true );

		if (is_array($orig_cart_items) && !empty($orig_cart_items)) {
			foreach ($orig_cart_items as $item) {
				if ($item_counter != 1) {
					$ipn_data['product_details'] .= "\n";
				}
				$item_total = $item->get_price() * $item->get_quantity();
				$ipn_data['product_details'] .= $item->get_name() . " x " . $item->get_quantity() . " - " . $currency_symbol . wpspsc_number_format_price($item_total) . "\n";
				if ($item->get_file_url()) {
					$file_url = base64_decode($item->get_file_url());
					$ipn_data['product_details'] .= "Download Link: " . $file_url . "\n";
				}
				if (!empty($item->get_shipping())) {
					$ipn_data['shipping'] += floatval($item->get_shipping()) * $item->get_quantity();
				}
				$item_counter++;
			}
		} else {
			wspsc_log_payment_debug('Error: Original cart items array is empty. Cannot process this transaction.', false);
			return;
		}

		$ipn_data['cart_items'] = $orig_cart_items;

		$orig_cart_postmeta = WSPSC_Cart::get_cart_from_postmeta($ipn_data['post_id']);
		
		/**
		 * Check if shipping region was used. If so, calculate the total shipping cost and also add the shipping region in the ipn data.
		 */
		$ipn_data['regional_shipping_cost'] = 0;
		$ipn_data['shipping_region'] = '';
		$selected_shipping_region = check_shipping_region_str($orig_cart_postmeta->selected_shipping_region);
		if ($selected_shipping_region) {
			wspsc_log_payment_debug('Selected shipping region option: ', true);
			wspsc_log_debug_array($selected_shipping_region, true);

			$ipn_data['regional_shipping_cost'] = $selected_shipping_region['amount'];
			$ipn_data['shipping_region'] = $selected_shipping_region['type'] == '0' ? wpsc_get_country_name_by_country_code($selected_shipping_region['loc']) : $selected_shipping_region['loc'];
		}

		/**
		 * Process shipping costs data.
		 */
		if (empty($ipn_data['shipping'])) {
			$ipn_data['shipping'] = "0.00";
		} else {
			$baseShipping = get_option('cart_base_shipping_cost');
			$ipn_data['shipping'] = floatval($ipn_data['shipping']) + floatval($baseShipping) + floatval($ipn_data['regional_shipping_cost']);
			$ipn_data['shipping'] = wpspsc_number_format_price($ipn_data['shipping']);
		}
		wspsc_log_payment_debug('Total shipping cost: '.$ipn_data['shipping'], true);
	}

	/**
	 * Update and save final transaction record to the database.
	 *
	 * @param array $ipn_data Processed ipn data.
	 * @return bool
	 */
	public static function save_txn_record(&$ipn_data)
	{
		wspsc_log_payment_debug('Executing WPSC_Post_Payment_Related::save_txn_record()', true);

		// Publish/Update the order post.
		$post_id = isset($ipn_data['post_id']) ? $ipn_data['post_id'] : '';
		if(empty($post_id)){
			wspsc_log_payment_debug('Error: Order post id value is empty. Cannot save transaction record.', false);
			return false;
		}
		$updated_wpsc_order = array(
			'ID' => $post_id,
			'post_status' => 'publish',
			'post_type' => 'wpsc_cart_orders',
		);
		wp_update_post($updated_wpsc_order);

		// Save transaction data to the order post meta.
		update_post_meta($post_id, 'wpsc_first_name', $ipn_data['first_name']);
		update_post_meta($post_id, 'wpsc_last_name', $ipn_data['last_name']);
		update_post_meta($post_id, 'wpsc_email_address', $ipn_data['payer_email']);
		update_post_meta($post_id, 'wpsc_txn_id', $ipn_data['txn_id']);
		update_post_meta($post_id, 'wpsc_total_amount', $ipn_data['mc_gross']);
		update_post_meta($post_id, 'wpsc_ipaddress', $ipn_data['ip_address']);
		update_post_meta($post_id, 'wpsc_address', $ipn_data['address']);
		update_post_meta($post_id, 'wpspsc_phone', $ipn_data['contact_phone']);
		update_post_meta($post_id, 'wpsc_applied_coupon', $ipn_data['applied_coupon_code']);
		update_post_meta($post_id, 'wpsc_shipping_amount', $ipn_data['shipping']);
		update_post_meta($post_id, 'wpsc_shipping_region', $ipn_data['shipping_region']);
		update_post_meta($post_id, 'wpspsc_items_ordered', $ipn_data['product_details']);
		update_post_meta($post_id, 'wpsc_order_status', "Paid");

		$gateway = isset( $ipn_data['gateway'] ) ? $ipn_data['gateway'] : '';
		update_post_meta( $post_id, 'wpsc_payment_gateway', $gateway );

		wspsc_log_payment_debug('Transaction data saved.', true);

		return true;
	}

	/**
	 * Send payment notification email to buyer and seller.
	 *
	 * @param array $ipn_data Processed ipn data.
	 * @return void
	 */
	public static function send_notification_email(&$ipn_data)
	{
		wspsc_log_payment_debug('Executing WPSC_Post_Payment_Related::send_notification_email()', true);

		$args = array();
		$args['product_details'] = $ipn_data['product_details'];
		$args['order_id'] = $ipn_data['post_id'];
		$args['coupon_code'] = $ipn_data['applied_coupon_code'];
		$args['address'] = $ipn_data['address'];
		$args['payer_email'] = $ipn_data['payer_email'];

		$from_email = get_option('wpspc_buyer_from_email');
		$subject = get_option('wpspc_buyer_email_subj');
		$subject = wpspc_apply_dynamic_tags_on_email($subject, $ipn_data, $args);

		$body = get_option('wpspc_buyer_email_body');
		$args['email_body'] = $body;
		$body = wpspc_apply_dynamic_tags_on_email($body, $ipn_data, $args);

		$is_html_content_type = get_option('wpsc_email_content_type') == 'html' ? true : false;

		wspsc_log_payment_debug('Applying filter - wspsc_buyer_notification_email_body', true);
		$body = apply_filters('wspsc_buyer_notification_email_body', $body, $ipn_data, $ipn_data['cart_items']);

		$buyer_email = $ipn_data['payer_email'];

		$headers = array();
		$headers[] = 'From: ' . $from_email . "\r\n";
		if ( $is_html_content_type ) {
			$headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
			$body = nl2br( $body );
		}
		if (!empty($buyer_email)) {
			if (get_option('wpspc_send_buyer_email')) {
				wp_mail($buyer_email, $subject, $body, $headers);
				wspsc_log_payment_debug('Buyer notification email successfully sent to: ' . $buyer_email, true);
				update_post_meta($ipn_data['post_id'], 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email);
			}
		}
		$notify_email = get_option('wpspc_notify_email_address');
		$seller_email_subject = get_option('wpspc_seller_email_subj');
		$seller_email_subject = wpspc_apply_dynamic_tags_on_email($seller_email_subject, $ipn_data, $args);

		$seller_email_body = get_option('wpspc_seller_email_body');
		$args['email_body'] = $seller_email_body;
		$seller_email_body = wpspc_apply_dynamic_tags_on_email($seller_email_body, $ipn_data, $args);

		wspsc_log_payment_debug('Applying filter - wspsc_seller_notification_email_body', true);
		$seller_email_body = apply_filters('wspsc_seller_notification_email_body', $seller_email_body, $ipn_data, $ipn_data['cart_items']);

		if ( $is_html_content_type ) {
			$seller_email_body = nl2br( $seller_email_body );
		}
		if (!empty($notify_email)) {
			if (get_option('wpspc_send_seller_email')) {
				wp_mail($notify_email, $seller_email_subject, $seller_email_body, $headers);
				wspsc_log_payment_debug('Seller notification email successfully sent to: ' . $notify_email, true);
			}
		}
	}

	/**
	 * Affiliate plugin related function
	 *
	 * @param array $ipn_data Processed ipn data.
	 * @return void
	 */
	public static function affiliate_plugin_integration(&$ipn_data)
	{
		wspsc_log_payment_debug('Updating affiliate database table with sales data (if the WP Affiliate Platform Plugin is used).', true);
		
		if (function_exists('wp_aff_platform_install')) {
			wspsc_log_payment_debug('WP Affiliate Platform is installed, registering sale...', true);
			$referrer = $ipn_data['ap_id'];
			$sale_amount = $ipn_data['mc_gross'];
			if (!empty($referrer)) {
				do_action('wp_affiliate_process_cart_commission', array(
					"referrer" => $referrer,
					"sale_amt" => $sale_amount,
					"txn_id" => $ipn_data['txn_id'],
					"buyer_email" => $ipn_data['payer_email'],
				));

				$message = 'The sale has been registered in the WP Affiliates Platform Database for referrer: ' . $referrer . ' for sale amount: ' . $sale_amount;
				wspsc_log_payment_debug($message, true);
			} else {
				wspsc_log_payment_debug('No Referrer Found. This is not an affiliate sale', true);
			}
		} else {
			wspsc_log_payment_debug('Not Using the WP Affiliate Platform Plugin.', true);
		}
	}

	public static function do_cleanup_after_txn(&$ipn_data)	{
		wspsc_log_payment_debug('Executing WPSC_Post_Payment_Related::do_cleanup_after_txn()', true);

		//Empty any incomplete old cart orders.
		wspsc_clean_incomplete_old_cart_orders();

		//Full reset the cart to clean it up.
		$wpsc_cart = WSPSC_Cart::get_instance();
		//Pass the cart id to so that it can reset the cart without calling the get_cart_id() function again.
		$cart_id = isset($ipn_data['cart_id']) ? $ipn_data['cart_id'] : '';
		$wpsc_cart->reset_cart_after_txn( $cart_id );
	}

}
