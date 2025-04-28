<?php

status_header( 200 );

class stripe_ipn_handler {

	var $last_error; // holds the last error encountered
	var $ipn_log; // bool: log IPN results to text file?
	var $ipn_log_file; // filename of the IPN log
	var $ipn_response; // holds the IPN response
	var $ipn_data; 
	var $sandbox_mode = false;
	var $secret_key = '';
	var $get_string = '';
    var $order_id=0;

    private $cart_id = 0;

	function __construct() {
		$this->secret_key = get_option("wpspc_stripe_live_secret_key");
		$this->last_error = '';
		$this->ipn_log_file = wpsc_get_log_file();
		$this->ipn_response = '';
        $this->order_id=0;
	}
	
	function validate_and_dispatch_product() {
		
        //Check Product Name, Price, Currency, Receiver email
		$this->debug_log( 'Executing validate_and_dispatch_product()', true );
		
        $wspsc_cart = WPSC_Cart::get_instance();
        
		$txn_id = $this->ipn_data["txn_id"];
        $transaction_type='cart';
		$payment_status = $this->ipn_data["payment_status"];		

        // Let's try to get first_name and last_name from full name.
		$first_name  = $this->ipn_data["first_name"];
		$last_name = $this->ipn_data["last_name"];
		$buyer_email = $this->ipn_data["payer_email"];
		$phone = $this->ipn_data["phone"];
		$shipping_address = $this->ipn_data["shipping_address"];
		$billing_address = $this->ipn_data["billing_address"];

        //$custom_values = json_decode(json_encode($this->ipn_data["custom"]), true);
		$custom_value_str = $this->ipn_data["custom"];
        $this->debug_log( 'Custom field value in the IPN data: ' . $custom_value_str, true );
		$custom_values = wp_cart_get_custom_var_array( $custom_value_str );	

		$this->debug_log( 'Payment Status: ' . $payment_status, true );
		if (strtolower($payment_status) == "succeeded" || strtolower($payment_status) == "processing") {
			//We will process this notification
		} else {
			$error_msg='This is not a payment complete notification. This IPN will not be processed.';
			$this->debug_log( $error_msg, true );
			wp_die(esc_html($error_msg));
		}
		if ($transaction_type == "cart") {
			$this->debug_log( 'Transaction Type: Shopping Cart', true );
			
            // Cart Items
			$cart_items = $wspsc_cart->get_items();
			$num_cart_items = 0;
			if( !empty($cart_items)){
				$num_cart_items = count($cart_items);
			}
			$this->debug_log( 'Number of Cart Items: ' . $num_cart_items, true );
        }
		
        $payment_amount = floatval( $this->ipn_data["mc_gross"] );
		$currency_code_payment  = strtoupper( $this->ipn_data["mc_currency"] );

		// $post_id = $custom_values['wp_cart_id']; // TODO: old code.
		$post_id = $wspsc_cart->get_cart_cpt_id();
		$orig_cart_items = $wspsc_cart->get_items();
		
		$ip_address = isset( $custom_values['ip'] ) ? $custom_values['ip'] : '';
		$applied_coupon_code = isset( $custom_values['coupon_code'] ) ? $custom_values['coupon_code'] : '';
		$currency_symbol = get_option( 'cart_currency_symbol' );
		$currency_code_settings = get_option( 'cart_payment_currency' );

		$this->debug_log( 'Custom values: ', true );
		$this->debug_log_array( $custom_values, true );
		$this->debug_log( 'Order post id: ' . $post_id, true );

		//*** Do security checks ***		
		if (empty( $post_id )) {
			$error_msg = 'Order ID: ' . $post_id . ', does not exist in the stripe notification. This request will not be processed.';
			$this->debug_log( $error_msg, false );
			wp_die(esc_html($error_msg));
		}

		if($currency_code_payment != $currency_code_settings)
		{
			// Fatal error. Currency code may have been tampered with.
			$error_msg='Fatal Error! Received currency code (' . $currency_code_payment . ') does not match with the original code (' . $currency_code_settings . ')';
			$this->debug_log( $error_msg, false );
			wp_die( esc_html( $error_msg ) );
		}

		$transaction_id = get_post_meta( $post_id, 'wpsc_txn_id', true );
		if (! empty( $transaction_id )) {
			if ($transaction_id == $txn_id) { //this transaction has been already processed once
				$error_msg = 'This transaction has been already processed once. Transaction ID: ' . $transaction_id;
				$this->debug_log($error_msg , false );
				wp_die( esc_html( $error_msg ) );
			}
		}

		//Validate prices
		$orig_individual_item_total = 0;
		foreach ( $orig_cart_items as $item ) {
			$orig_individual_item_total += $item->get_price() * $item->get_quantity();
		}

		$orig_individual_item_total = round( $orig_individual_item_total, 2 );
		$paid_total = round( $payment_amount, 2 );

		$this->debug_log( 'Checking price. Expected individual item total: ' . $orig_individual_item_total . '. Paid total amount: ' . $paid_total, true );

		if ($paid_total < $orig_individual_item_total) { //Paid price is less so block this transaction.
			$error_msg = 'Error! Post payment price validation failed. The price amount may have been altered. This transaction will not be processed.';
			$this->debug_log($error_msg , false );
			$this->debug_log( 'Expected individual item total: ' . $orig_individual_item_total . '. Paid total amount: ' . $paid_total, false );
			wp_die(esc_html($error_msg));
		}
		//*** End of security check ***

		//Update the post to publish status
		$updated_wpsc_order = array(
			'ID' => $post_id,
			'post_status' => 'publish',
			'post_type' => WPSC_Cart::POST_TYPE,
			'post_date' => current_time('Y-m-d H:i:s')
		);
		wp_update_post( $updated_wpsc_order );

		//Update the post meta
		update_post_meta( $post_id, 'wpsc_first_name', $first_name );
		update_post_meta( $post_id, 'wpsc_last_name', $last_name );
		update_post_meta( $post_id, 'wpsc_email_address', $buyer_email );
		update_post_meta( $post_id, 'wpsc_txn_id', $txn_id );
		$formatted_payment_amount = wpsc_number_format_price( $payment_amount );
		update_post_meta( $post_id, 'wpsc_total_amount', $formatted_payment_amount );
		update_post_meta( $post_id, 'wpsc_ipaddress', $ip_address );
		update_post_meta( $post_id, 'wpsc_address', $shipping_address ); // Using shipping address in wpsc_address post meta. This meta-key hasn't changed for backward compatibility.
		update_post_meta( $post_id, 'wpsc_billing_address', $billing_address );
		update_post_meta( $post_id, 'wpspsc_phone', $phone);
		update_post_meta( $post_id, 'wpsc_applied_coupon', $applied_coupon_code );
		$gateway = isset( $this->ipn_data['gateway'] ) ? $this->ipn_data['gateway'] : '';
        update_post_meta( $post_id, 'wpsc_payment_gateway', $gateway );

		$tax_amount = isset($this->ipn_data['tax_amount']) ? wpsc_number_format_price($this->ipn_data['tax_amount']) : '0.00';
		update_post_meta( $post_id, 'wpsc_tax_amount', $tax_amount );

		$product_details = "";
		$item_counter = 1;
		$shipping = 0;

		if ($orig_cart_items) {
			foreach ( $orig_cart_items as $item ) {
				if ($item_counter != 1) {
					$product_details .= "\n";
				}
				$item_total = $item->get_price() * $item->get_quantity();
				$product_details .= $item->get_name() . " x " . $item->get_quantity() . " - " . $currency_symbol . wpsc_number_format_price( $item_total ) . "\n";
				if ($item->get_file_url()) {
					$file_url = $item->get_file_url();
					$product_details .= "Download Link: " . $file_url . "\n";
				}
				if (! empty( $item->get_shipping() )) {
					$shipping += floatval( $item->get_shipping() ) * $item->get_quantity();
				}
				$item_counter++;
			}
		}

		$orig_cart_postmeta = WPSC_Cart::get_cart_from_postmeta($post_id);
		
		/**
		 * Check if shipping region was used. If so, calculate the total shipping cost and also add the shipping region in the ipn data.
		 */
		$this->ipn_data['regional_shipping_cost'] = 0;
		$this->ipn_data['shipping_region'] = '';
		$selected_shipping_region = check_shipping_region_str($orig_cart_postmeta->selected_shipping_region);
		if ($selected_shipping_region) {
			wpsc_log_payment_debug('Selected shipping region option: ', true);
			wpsc_log_debug_array($selected_shipping_region, true);

			$this->ipn_data['regional_shipping_cost'] = $selected_shipping_region['amount'];
			$this->ipn_data['shipping_region'] = $selected_shipping_region['type'] == '0' ? wpsc_get_country_name_by_country_code($selected_shipping_region['loc']) : $selected_shipping_region['loc'];
		}

		if (empty( $shipping )) {
			$shipping = "0.00";
		} else {
			$baseShipping = get_option( 'cart_base_shipping_cost' );
			$shipping = floatval( $shipping ) + floatval( $baseShipping ) + floatval( $this->ipn_data['regional_shipping_cost'] );
			$shipping = wpsc_number_format_price( $shipping );
		}

		wpsc_log_payment_debug( 'Total shipping cost: ' . $shipping, true);

		update_post_meta( $post_id, 'wpsc_shipping_amount', $shipping );
		update_post_meta( $post_id, 'wpsc_shipping_region', $this->ipn_data['shipping_region'] );
		update_post_meta( $post_id, 'wpspsc_items_ordered', $product_details );
		$status = "Paid";
		update_post_meta( $post_id, 'wpsc_order_status', $status );		

		$args = array();
		$args['product_details'] = $product_details;
		$args['order_id'] = $post_id;
		$args['coupon_code'] = $applied_coupon_code;
		$args['address'] = $shipping_address;
		$args['payer_email'] = $buyer_email;

		$from_email = get_option( 'wpspc_buyer_from_email' );
		$subject = get_option( 'wpspc_buyer_email_subj' );
		$subject = wpsc_apply_dynamic_tags_on_email( $subject, $this->ipn_data, $args );

		$body = get_option( 'wpspc_buyer_email_body' );
		$args['email_body'] = $body;
		$body = wpsc_apply_dynamic_tags_on_email( $body, $this->ipn_data, $args );

		$is_html_content_type = get_option('wpsc_email_content_type') == 'html' ? true : false;

		$this->debug_log( 'Applying filter - wspsc_buyer_notification_email_body', true );

		$body = apply_filters( 'wspsc_buyer_notification_email_body', $body, $this->ipn_data, $cart_items );// TODO: Old hook. Need to remove this.
		$body = apply_filters( 'wpsc_buyer_notification_email_body', $body, $this->ipn_data, $cart_items );

		$headers = array();
		$headers[] = 'From: ' . $from_email . "\r\n";
		if ( $is_html_content_type ) {
			$headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
			$body = nl2br( $body );
		}
		if (! empty( $buyer_email )) {
			if (get_option( 'wpspc_send_buyer_email' )) {
				wp_mail( $buyer_email, $subject, $body, $headers );
				$this->debug_log( 'Product Email successfully sent to ' . $buyer_email, true );
				update_post_meta( $post_id, 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email );
			}
		}
		$notify_email = get_option( 'wpspc_notify_email_address' );
		$seller_email_subject = get_option( 'wpspc_seller_email_subj' );
		$seller_email_subject = wpsc_apply_dynamic_tags_on_email( $seller_email_subject, $this->ipn_data, $args );

		$seller_email_body = get_option( 'wpspc_seller_email_body' );
		$args['email_body'] = $seller_email_body;
		$seller_email_body = wpsc_apply_dynamic_tags_on_email( $seller_email_body, $this->ipn_data, $args );

		$this->debug_log( 'Applying filter - wspsc_seller_notification_email_body', true );

		$seller_email_body = apply_filters( 'wspsc_seller_notification_email_body', $seller_email_body, $this->ipn_data, $cart_items ); // TODO: Old hook. Need to remove this.
		$seller_email_body = apply_filters( 'wpsc_seller_notification_email_body', $seller_email_body, $this->ipn_data, $cart_items );

		if ( $is_html_content_type ) {
			$seller_email_body = nl2br( $seller_email_body );
		}
		if (! empty( $notify_email )) {
			if (get_option( 'wpspc_send_seller_email' )) {
				wp_mail( $notify_email, $seller_email_subject, $seller_email_body, $headers );
				$this->debug_log( 'Notify Email successfully sent to ' . $notify_email, true );
			}
		}

		/* Affiliate plugin integratin */
		$this->debug_log( 'Updating Affiliate Database Table with Sales Data if Using the WP Affiliate Platform Plugin.', true );
		if (function_exists( 'wp_aff_platform_install' )) {
			$this->debug_log( 'WP Affiliate Platform is installed, registering sale...', true );
			$referrer = isset($custom_values['ap_id'])?$custom_values['ap_id']:'';
			$sale_amount = $payment_amount;
			if (! empty( $referrer )) {
				do_action( 'wp_affiliate_process_cart_commission', array( "referrer" => $referrer, "sale_amt" => $sale_amount, "txn_id" => $txn_id, "buyer_email" => $buyer_email ) );

				$message = 'The sale has been registered in the WP Affiliates Platform Database for referrer: ' . $referrer . ' for sale amount: ' . $sale_amount;
				$this->debug_log( $message, true );
			} else {
				$this->debug_log( 'No Referrer Found. This is not an affiliate sale', true );
			}
		} else {
			$this->debug_log( 'Not Using the WP Affiliate Platform Plugin.', true );
		}

		do_action( 'wpspc_stripe_ipn_processed', $this->ipn_data, $this );  // TODO: Old hook. Need to remove this.
		do_action( 'wpsc_stripe_ipn_processed', $this->ipn_data, $this );

		//Empty any incomplete old cart orders.
		wpsc_clean_incomplete_old_cart_orders();

		//Reset/clear the cart.
		$wspsc_cart->reset_cart_after_txn();

		return true;
	}


	function validate_ipn() {

        // $this->order_id = isset($_GET["ref_id"])?$_GET["ref_id"]:0; // TODO: old code. need to remove
		$this->cart_id = isset($_GET["ref_id"])?$_GET["ref_id"]:0;

		//IPN validation check
		if ($this->validate_ipn_using_client_reference_id()) {			
			return true;
		} else {
			return false;
		}
	}

	function validate_ipn_using_client_reference_id() {
		$this->debug_log( 'Checking if Stripe Checkout session is valid & completed by matching client_reference_id', true );

		wpsc_load_stripe_lib();
        try {
            \Stripe\Stripe::setApiKey( $this->secret_key );

            $events = \Stripe\Event::all(
				array(
					'type'    => 'checkout.session.completed',
					'created' => array(
						'gte' => time() - 60 * 60,
					),
				)
			);

            $sess = false;
            
            foreach ( $events->autoPagingIterator() as $event ) {
				$session = $event->data->object;
				if ( isset( $session->client_reference_id ) && $session->client_reference_id === $this->cart_id ) {
					$sess = $session;
					break;
				}
			}

            if ( false === $sess ) {
				// Can't find session.
				$error_msg = sprintf( "Error! Payment with ref_id %s (Cart ID) can't be found. This script should be accessed by Stripe's webhook only.", $this->cart_id );
				$this->debug_log( $error_msg, false );
				wp_die(esc_html($error_msg));				
			}

            //ref_id matched
            $pi_id = $sess->payment_intent;
            $pi = \Stripe\PaymentIntent::retrieve( $pi_id );
            
            $pi->custom_metadata = $sess->metadata;

	        $additional_data = array();

	        // Collect the automatic tax amount if there is any.
	        if (isset($sess['total_details']['amount_tax']) && !empty($sess['total_details']['amount_tax'])){
		        $additional_data['tax_amount'] = $sess['total_details']['amount_tax'];
	        }

			//formatting ipn_data
	        $this->create_ipn_from_stripe($pi, $additional_data);

        }  catch ( Exception $e ) {
			$error_msg = 'Error occurred: ' . $e->getMessage();
			$this->debug_log( $error_msg, false );
			wp_die(esc_html($error_msg));
		}
        
		//The following two lines can be used for debugging
		//$this->debug_log( 'IPN Request: ' . print_r( $params, true ) , true);
		//$this->debug_log( 'IPN Response: ' . print_r( $response, true ), true);		
		
        $this->debug_log( 'IPN successfully verified.', true );
		return true;
	}

	function create_ipn_from_stripe( $pi_object, $additional_data = array() ) {

		//converting the payment intent object to array
		$data = json_decode(json_encode($pi_object),TRUE) ;
		$ipn = array();

		//wpsc_log_payment_debug( 'Stripe Payment Intent Data: ', true );
		//wpsc_log_debug_array( $data, true );

		//Get the charge object based on the Stripe API version used in the payment intents object.
		if( isset ( $pi_object->latest_charge ) ){
			//Using the new Stripe API version 2022-11-15 or later
			wpsc_log_payment_debug( 'Using the Stripe API version 2022-11-15 or later for Payment Intents object. Need to retrieve the charge object.', true );
			$charge_id = $pi_object->latest_charge;
			//For Stripe API version 2022-11-15 or later, the charge object is not included in the payment intents object. It needs to be retrieved using the charge ID.
			try {
				//Retrieve the charge object using the charge ID
				$charge = \Stripe\Charge::retrieve($charge_id);
			} catch (\Stripe\Exception\ApiErrorException $e) {
				// Handle the error
				wpsc_log_payment_debug( 'Stripe error occurred trying to retrieve the charge object using the charge ID. ' . $e->getMessage(), false );
				exit;
			}
		} else {
			//Using OLD Stripe API version. Log an error and exit.
			$error_msg = 'Error! You are using the OLD Stripe API version. This version is not supported. Please update the Stripe API version to 2022-11-15 or later from your Stripe account.';
			wpsc_log_payment_debug( $error_msg, false );
			wp_die($error_msg);
		}

		//Conver the charge object to array
		$charge_array = json_decode(json_encode($charge),TRUE) ;
		//wpsc_log_payment_debug( 'Stripe Charge Data: ', true );
		//wpsc_log_debug_array( $charge_array, true );

		/**
		 * Retrieve Customer info from the charge object.
		 */
		$stripe_email = isset($charge_array['billing_details']['email']) ? $charge_array['billing_details']['email'] : '';
		$phone = isset($charge_array['billing_details']['phone']) ? $charge_array['billing_details']['phone'] : '';
		// Get name.
		$name = isset($charge_array['billing_details']['name']) ? trim($charge_array['billing_details']['name']) : '';
		$last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
		$first_name = trim( preg_replace( '#' . $last_name . '#', '', $name ) );
		// Get billing address
		$billing_address = $this->get_ipn_billing_address($charge_array);
		// Get shipping address
		$shipping_address = $this->get_ipn_shipping_address($data);

		$price_in_cents = floatval($data['amount_received']);
		$currency_code_payment = strtoupper($data['currency']);

		$payment_amount = 0;
		
		if (wpsc_is_zero_cents_currency($currency_code_payment)) {
			$payment_amount = $price_in_cents;
		} else {
			$payment_amount = $price_in_cents / 100;// The amount (in cents). This value is used in Stripe API.
		}


		$wspsc_cart =  WPSC_Cart::get_instance();

		$cart_id = $wspsc_cart->get_cart_id();
		$cart_post_id = $wspsc_cart->get_cart_cpt_id();
		$custom_field_values = get_post_meta( $cart_post_id, 'wpsc_cart_custom_values', true );
		// if( is_array($data['custom_metadata'])){
		// 	//Alternative way to get custom field values for Stripe checkout.
		// 	$custom_field_values = http_build_query($data['custom_metadata']);
		// }

		$ipn['custom'] = $custom_field_values;
		$ipn['pay_id'] = $data['id'];
		$ipn['create_time'] = $data['created'];
		$ipn['txn_id'] = $charge_id;
		$ipn['gateway'] = 'stripe';//Add the gateway type to the ipn_data array
		$ipn['txn_type'] = 'cart';
		$ipn['payment_status'] = ucfirst($data['status']);
		$ipn['transaction_subject'] = '';
		$ipn['mc_currency'] = $data['currency'];
		$ipn['mc_gross'] = $payment_amount;
		$ipn['first_name'] = $first_name;
		$ipn['last_name'] = $last_name;
		$ipn['phone'] = $phone;
		$ipn['payer_email'] = $stripe_email;
		$ipn['shipping_address'] = $shipping_address;
		$ipn['billing_address'] = $billing_address;

		// Items data.
		$cart_items = $wspsc_cart->get_items();
		$i = 1;
		foreach ( $cart_items as $item ) {
			$ipn[ 'item_number' . $i ] = '';
			$ipn[ 'item_name' . $i ] = $item->get_name();
			$ipn[ 'quantity' . $i ] = $item->get_quantity();
			$ipn[ 'mc_gross_' . $i ] = $item->get_price() * $item->get_quantity();
			$i++;
		}
		$ipn['num_cart_items'] = $i - 1;

		// Process additional data if there is any.
		if (!empty($additional_data)){

			// Process tax amount if there is any
			if (isset($additional_data['tax_amount'])){
				$tax_amount_in_cents = intval($additional_data['tax_amount']); // for stripe, amount should always be in integer (cents)
				if (wpsc_is_zero_cents_currency($currency_code_payment)) {
					$ipn['tax_amount'] = $tax_amount_in_cents;
				} else {
					$ipn['tax_amount'] = $tax_amount_in_cents / 100;// The amount (in cents). This value is used in Stripe API.
				}
			}
		}


		//Debug purpose.
		//wpsc_log_debug_array( $ipn, true );

		//Save the IPN data in the class variable
		$this->ipn_data = $ipn;
		return true;
	}

	public function get_ipn_shipping_address(&$pi_data){
		if (!isset($pi_data['shipping']['address'])) {
			return '';
		}

		$shipping_addr = $pi_data['shipping']['address'];
		$city = isset($shipping_addr['city']) ? $shipping_addr['city'] : '';
		$state = isset($shipping_addr['state']) ? $shipping_addr['state'] : '';
		$zip = isset($shipping_addr['postal_code']) ? $shipping_addr['postal_code'] : '';
		$country = isset($shipping_addr['country']) ? wpsc_get_country_name_by_country_code($shipping_addr['country']) : '';
		$line1 = isset($shipping_addr['line1']) ? $shipping_addr['line1'] : '';
		$line2 = isset($shipping_addr['line2']) ? $shipping_addr['line2'] : '';

		// Get full address.
		$address_array = array(
			$line1, $line2 , $city , $state , $zip , $country
		);
		$address = '';
		foreach ($address_array as $value) {
			if (!empty($value)) {
				$address .= $value . ', ';
			}
		}
		return rtrim($address, ', ');
	}

	public function get_ipn_billing_address(&$charge_array){
		if (!isset($charge_array['billing_details']['address'])) {
			return '';
		}

		$bd_addr = isset($charge_array['billing_details']['address']) ? $charge_array['billing_details']['address'] : array();
		$city = isset($bd_addr['city']) ? $bd_addr['city'] : '';
		$state = isset($bd_addr['state']) ? $bd_addr['state'] : '';
		$zip = isset($bd_addr['postal_code']) ? $bd_addr['postal_code'] : '';
		$country = isset($bd_addr['country']) ? wpsc_get_country_name_by_country_code($bd_addr['country']) : '';
		$line1 = isset($bd_addr['line1']) ? $bd_addr['line1'] : '';
		$line2 = isset($bd_addr['line2']) ? $bd_addr['line2'] : '';

		// Get full address.
		$address_array = array(
			$line1, $line2 , $city , $state , $zip , $country
		);
		$address = '';
		foreach ($address_array as $value) {
			if (!empty($value)) {
				$address .= $value . ', ';
			}
		}
		return rtrim($address, ', ');
	}

	// TODO: not used. need to remove this.
//	function log_ipn_results( $success ) {
//		if (! $this->ipn_log)
//			return; // is logging turned off?
//		// Timestamp
//		$text = '[' . date( 'm/d/Y g:i A' ) . '] - ';
//
//		// Success or failure being logged?
//		if ($success)
//			$text .= "SUCCESS!\n";
//		else
//			$text .= 'FAIL: ' . $this->last_error . "\n";
//
//		// Log the POST variables
//		$text .= "IPN POST vars from payment gateway:\n";
//		foreach ( $this->ipn_data as $key => $value ) {
//			$text .= "$key=$value, ";
//		}
//
//		// Log the response
//		$text .= "\nIPN Response from payment gateway Server:\n " . $this->ipn_response;
//
//		// Write to log
//		$fp = fopen( $this->ipn_log_file, 'a' );
//		fwrite( $fp, $text . "\n\n" );
//
//		fclose( $fp ); // close file
//	}

	function debug_log( $message, $success, $end = false ) {
		wpsc_log_payment_debug($message, $success, $end);
	}

	function debug_log_array( $array_to_write, $success, $end = false ) {
		wpsc_log_debug_array($array_to_write, $success, $end);
	}

}

// Start of Stripe IPN handling (script execution)
function wpc_handle_stripe_ipn() {
	$ipn_handler_instance = new stripe_ipn_handler();
	$return_url = get_option('cart_return_from_paypal_url');

	//Check if cart items are empty
	$wspsc_cart = WPSC_Cart::get_instance();
	$cart_items = $wspsc_cart->get_items();
	if( empty($cart_items)){
		$ipn_handler_instance->debug_log( 'Stripe IPN hook was accessed with empty cart items array.', true );
		wp_die('Stripe IPN hook was accessed with empty cart items array. Cannot process this.');
		return;
	}

	$sandbox = get_option( 'wp_shopping_cart_enable_sandbox' );
	if ($sandbox) { // Enable sandbox testing
		$ipn_handler_instance->secret_key = get_option("wpspc_stripe_test_secret_key");
		$ipn_handler_instance->sandbox_mode = true;
	}
	$ipn_handler_instance->debug_log( 'Stripe Class Initiated by ' . $_SERVER['REMOTE_ADDR'], true );
	// Validate the IPN
	if ($ipn_handler_instance->validate_ipn()) {
		//Process the IPN.
		$ipn_handler_instance->debug_log( 'Creating product Information to send.', true );
		if (! $ipn_handler_instance->validate_and_dispatch_product()) {
			$error_msg='IPN product validation failed.';
			$ipn_handler_instance->debug_log( $error_msg, false );
			wp_die(esc_html($error_msg));
		}
	}
	$ipn_handler_instance->debug_log( 'Stripe class finished.', true, true );	

	//Everything passed. Redirecting user to thank you page.
	if (empty($return_url)) {
		$return_url = WP_CART_SITE_URL . '/';		
	}	

	$cart_id = isset($_GET["ref_id"])?$_GET["ref_id"]:'';
	$redirect_url = add_query_arg( 'cart_id', $cart_id, $return_url );
	$redirect_url = add_query_arg('_wpnonce', wp_create_nonce('wpsc_thank_you_nonce_action'), $redirect_url);
	if ( ! headers_sent() ) {
		header( 'Location: ' . $redirect_url );
	} else {
		echo '<meta http-equiv="refresh" content="0;url=' . $redirect_url . '" />';
	}

}