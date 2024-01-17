<?php

class WPSC_Post_Payment_Related
{

	/**
	 * Process, decode and sanitize ipn related data.
	 *
	 * @param array $ipn_data The ipn data that need to be processed for post payment usage.
	 * @return void
	 */
    public static function process_ipn_data($ipn_data)
    {
        $data = array();

        $array_temp = $ipn_data;

        $data[ 'ipn_data' ] = array_map('sanitize_text_field', $array_temp);
        $data[ 'txn_id' ] = $ipn_data[ 'txn_id' ];
        $data[ 'transaction_type' ] = $ipn_data[ 'txn_type' ];
        $data[ 'payment_status' ] = $ipn_data[ 'payment_status' ];
        $data[ 'transaction_subject' ] = $ipn_data[ 'transaction_subject' ];
        $data[ 'first_name' ] = $ipn_data[ 'first_name' ];
        $data[ 'last_name' ] = $ipn_data[ 'last_name' ];
        $data[ 'buyer_email' ] = $ipn_data[ 'payer_email' ];
        $data[ 'street_address' ] = isset($ipn_data[ 'address_street' ]) ? $ipn_data[ 'address_street' ] : '';
        $data[ 'city' ] = isset($ipn_data[ 'address_city' ]) ? $ipn_data[ 'address_city' ] : '';
        $data[ 'state' ] = isset($ipn_data[ 'address_state' ]) ? $ipn_data[ 'address_state' ] : '';
        $data[ 'zip' ] = isset($ipn_data[ 'address_zip' ]) ? $ipn_data[ 'address_zip' ] : '';
        $data[ 'country' ] = isset($ipn_data[ 'address_country' ]) ? $ipn_data[ 'address_country' ] : '';
        $data[ 'phone' ] = isset($ipn_data[ 'contact_phone' ]) ? $ipn_data[ 'contact_phone' ] : '';
        $data[ 'mc_gross' ] = $ipn_data[ 'mc_gross' ];
		
        /**
		 * Process address.
         */
        if (empty($data[ 'street_address' ]) && empty($data[ 'city' ])) {
			// No address value present.
            $data[ 'address' ] = "";
        } else {
			// An address value is present.
            $data[ 'address' ] = $data[ 'street_address' ] . ", " . $data[ 'city' ] . ", " . $data[ 'state' ] . ", " . $data[ 'zip' ] . ", " . $data[ 'country' ];
        }
		
        /**
		 * Get custom values from string.
         */
		$custom_value_str = urldecode($ipn_data[ 'custom' ]); // urldecode is harmless.
        wspsc_log_payment_debug('Custom field value in the IPN: ' . $custom_value_str, true);
        $custom_values = wp_cart_get_custom_var_array($custom_value_str);
		
        $data[ 'post_id' ] = $custom_values[ 'wp_cart_id' ];
        $data[ 'ip_address' ] = isset($custom_values[ 'ip' ]) ? $custom_values[ 'ip' ] : '';
        $data[ 'applied_coupon_code' ] = isset($custom_values[ 'coupon_code' ]) ? $custom_values[ 'coupon_code' ] : '';
        $data[ 'ap_id' ] = isset($custom_values[ 'ap_id' ]) ? $custom_values[ 'ap_id' ] : '';
		
		
        wspsc_log_payment_debug('Custom values', true);
        wspsc_log_debug_array($custom_values, true);
        wspsc_log_payment_debug('Order post id: ' . $data[ 'post_id' ], true);
		
        /**
		 * Process product details data.
         */
		$data[ 'product_details' ] = "";
        $data[ 'shipping' ] = 0;
        $item_counter = 1;
        $currency_symbol = get_option('cart_currency_symbol');
        $orig_cart_items = get_post_meta($data[ 'post_id' ], 'wpsc_cart_items', true);
        //wspsc_log_payment_debug( 'Original cart items from the order post below.', true );
        //wspsc_log_payment_debug_array( $orig_cart_items, true );
        if ($orig_cart_items) {
			foreach ($orig_cart_items as $item) {
				if ($item_counter != 1) {
					$data[ 'product_details' ] .= "\n";
                }
                $item_total = $item->get_price() * $item->get_quantity();
                $data[ 'product_details' ] .= $item->get_name() . " x " . $item->get_quantity() . " - " . $currency_symbol . wpspsc_number_format_price($item_total) . "\n";
                if ($item->get_file_url()) {
					$file_url = base64_decode($item->get_file_url());
                    $data[ 'product_details' ] .= "Download Link: " . $file_url . "\n";
                }
                if (!empty($item->get_shipping())) {
					$data[ 'shipping' ] += floatval($item->get_shipping()) * $item->get_quantity();
                }
                $item_counter++;
            }
        }

		$data[ 'cart_items' ] = $orig_cart_items;

        /**
         * Process shipping costs data.
         */
		if (empty($data[ 'shipping' ])) {
			$data[ 'shipping' ] = "0.00";
        } else {
			$baseShipping = get_option('cart_base_shipping_cost');
            $data[ 'shipping' ] = floatval($data[ 'shipping' ]) + floatval($baseShipping);
            $data[ 'shipping' ] = wpspsc_number_format_price($data[ 'shipping' ]);
        }
		
        return $data;
    }
	
    /**
     * Update and save final transaction record to the database.
     *
     * @param array $data Processed ipn data.
     * @return bool
     */
    public static function save_txn_record($data)
    {
        wspsc_log_payment_debug('Executing WPSC_Post_Payment_Related::save_txn_record()', true);

        extract($data);

        $updated_wpsc_order = array(
            'ID' => $post_id,
            'post_status' => 'publish',
            'post_type' => 'wpsc_cart_orders',
        );
        wp_update_post($updated_wpsc_order);
        update_post_meta($post_id, 'wpsc_first_name', $first_name);
        update_post_meta($post_id, 'wpsc_last_name', $last_name);
        update_post_meta($post_id, 'wpsc_email_address', $buyer_email);
        update_post_meta($post_id, 'wpsc_txn_id', $txn_id);
        update_post_meta($post_id, 'wpsc_total_amount', $mc_gross);
        update_post_meta($post_id, 'wpsc_ipaddress', $ip_address);
        update_post_meta($post_id, 'wpsc_address', $address);
        update_post_meta($post_id, 'wpspsc_phone', $phone);
        update_post_meta($post_id, 'wpsc_applied_coupon', $applied_coupon_code);
        update_post_meta($post_id, 'wpsc_shipping_amount', $shipping);
        update_post_meta($post_id, 'wpspsc_items_ordered', $product_details);
        update_post_meta($post_id, 'wpsc_order_status', "Paid");

        wspsc_log_payment_debug('Transaction data saved.', true);

        return true;
    }

	/**
	 * Undocumented function
	 *
     * @param array $data Processed ipn data.
	 * @return void
	 */
    public static function send_notification_email($data)
    {
        wspsc_log_payment_debug('Executing WPSC_Post_Payment_Related::send_notification_email()', true);

        extract($data);

        $args = array();
        $args[ 'product_details' ] = $product_details;
        $args[ 'order_id' ] = $post_id;
        $args[ 'coupon_code' ] = $applied_coupon_code;
        $args[ 'address' ] = $address;
        $args[ 'payer_email' ] = $buyer_email;

        $from_email = get_option('wpspc_buyer_from_email');
        $subject = get_option('wpspc_buyer_email_subj');
        $subject = wpspc_apply_dynamic_tags_on_email($subject, $ipn_data, $args);

        $body = get_option('wpspc_buyer_email_body');
        $args[ 'email_body' ] = $body;
        $body = wpspc_apply_dynamic_tags_on_email($body, $ipn_data, $args);

        wspsc_log_payment_debug('Applying filter - wspsc_buyer_notification_email_body', true);
        $body = apply_filters('wspsc_buyer_notification_email_body', $body, $ipn_data, $cart_items);

        $headers = 'From: ' . $from_email . "\r\n";
        if (!empty($buyer_email)) {
            if (get_option('wpspc_send_buyer_email')) {
                wp_mail($buyer_email, $subject, $body, $headers);
                wspsc_log_payment_debug('Product Email successfully sent to ' . $buyer_email, true);
                update_post_meta($post_id, 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email);
            }
        }
        $notify_email = get_option('wpspc_notify_email_address');
        $seller_email_subject = get_option('wpspc_seller_email_subj');
        $seller_email_subject = wpspc_apply_dynamic_tags_on_email($seller_email_subject, $ipn_data, $args);

        $seller_email_body = get_option('wpspc_seller_email_body');
        $args[ 'email_body' ] = $seller_email_body;
        $seller_email_body = wpspc_apply_dynamic_tags_on_email($seller_email_body, $ipn_data, $args);

        wspsc_log_payment_debug('Applying filter - wspsc_seller_notification_email_body', true);
        $seller_email_body = apply_filters('wspsc_seller_notification_email_body', $seller_email_body, $ipn_data, $cart_items);

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
     * @param array $data Processed ipn data.
	 * @return void
	 */
    public static function affiliate_plugin_integration($data)
    {
		extract($data);
		
        wspsc_log_payment_debug('Updating Affiliate Database Table with Sales Data if Using the WP Affiliate Platform Plugin.', true);
        if (function_exists('wp_aff_platform_install')) {
            wspsc_log_payment_debug('WP Affiliate Platform is installed, registering sale...', true);
            $referrer = $ap_id;
            $sale_amount = $mc_gross;
            if (!empty($referrer)) {
                do_action('wp_affiliate_process_cart_commission', array("referrer" => $referrer, "sale_amt" => $sale_amount, "txn_id" => $txn_id, "buyer_email" => $buyer_email));

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
