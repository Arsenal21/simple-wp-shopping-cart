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
		$ipn_data['ip_address'] = isset($custom_values['ip']) ? $custom_values['ip'] : '';
		$ipn_data['applied_coupon_code'] = isset($custom_values['coupon_code']) ? $custom_values['coupon_code'] : '';
		$ipn_data['ap_id'] = isset($custom_values['ap_id']) ? $custom_values['ap_id'] : '';

		wspsc_log_payment_debug('Custom values', true);
		wspsc_log_debug_array($custom_values, true);
		wspsc_log_payment_debug('Order post id: ' . $ipn_data['post_id'], true);

		/**
		 * Process product details data.
		 */
		$ipn_data['product_details'] = "";
		$ipn_data['shipping'] = 0;
		$item_counter = 1;
		$currency_symbol = get_option('cart_currency_symbol');
		$orig_cart_items = get_post_meta($ipn_data['post_id'], 'wpsc_cart_items', true);
		//wspsc_log_payment_debug( 'Original cart items from the order post below.', true );
		//wspsc_log_payment_debug_array( $orig_cart_items, true );
		if ($orig_cart_items) {
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
		}

		$ipn_data['cart_items'] = $orig_cart_items;

		/**
		 * Process shipping costs data.
		 */
		if (empty($ipn_data['shipping'])) {
			$ipn_data['shipping'] = "0.00";
		} else {
			$baseShipping = get_option('cart_base_shipping_cost');
			$ipn_data['shipping'] = floatval($ipn_data['shipping']) + floatval($baseShipping);
			$ipn_data['shipping'] = wpspsc_number_format_price($ipn_data['shipping']);
		}

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

		$post_id = $ipn_data['post_id'];
		$updated_wpsc_order = array(
			'ID' => $post_id,
			'post_status' => 'publish',
			'post_type' => 'wpsc_cart_orders',
		);
		wp_update_post($updated_wpsc_order); // TODO: Maybe need to check if successful.

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
		update_post_meta($post_id, 'wpspsc_items_ordered', $ipn_data['product_details']);
		update_post_meta($post_id, 'wpsc_order_status', "Paid");

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

		wspsc_log_payment_debug('Applying filter - wspsc_buyer_notification_email_body', true);
		$body = apply_filters('wspsc_buyer_notification_email_body', $body, $ipn_data, $ipn_data['cart_items']);

		$buyer_email = $ipn_data['payer_email'];

		$headers = 'From: ' . $from_email . "\r\n";
		if (!empty($buyer_email)) {
			if (get_option('wpspc_send_buyer_email')) {
				wp_mail($buyer_email, $subject, $body, $headers);
				wspsc_log_payment_debug('Product Email successfully sent to ' . $buyer_email, true);
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

		if (!empty($notify_email)) {
			if (get_option('wpspc_send_seller_email')) {
				wp_mail($notify_email, $seller_email_subject, $seller_email_body, $headers);
				wspsc_log_payment_debug('Notify Email successfully sent to ' . $notify_email, true);
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
		wspsc_log_payment_debug('Updating Affiliate Database Table with Sales Data if Using the WP Affiliate Platform Plugin.', true);
		
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
}
