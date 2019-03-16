<?php

status_header( 200 );

$debug_log = "ipn_handle_debug.txt"; // Debug log file name

class paypal_ipn_handler {

    var $last_error;   // holds the last error encountered
    var $ipn_log;      // bool: log IPN results to text file?
    var $ipn_log_file; // filename of the IPN log
    var $ipn_response; // holds the IPN response from paypal
    var $ipn_data	 = array();  // array contains the POST values for IPN
    var $fields		 = array();    // array holds the fields to submit to paypal
    var $sandbox_mode	 = false;

    function __construct() {
	$this->paypal_url	 = 'https://www.paypal.com/cgi-bin/webscr';
	$this->last_error	 = '';
	$this->ipn_log_file	 = WP_CART_PATH . 'ipn_handle_debug.txt';
	$this->ipn_response	 = '';
    }

    function validate_and_dispatch_product() {
	//Check Product Name, Price, Currency, Receiver email
	//Decode the custom field before sanitizing.
	$custom_field_value		 = urldecode( $this->ipn_data[ 'custom' ] ); //urldecode is harmless
	$this->ipn_data[ 'custom' ]	 = $custom_field_value;

	//Sanitize and read data.
	$array_temp		 = $this->ipn_data;
	$this->ipn_data		 = array_map( 'sanitize_text_field', $array_temp );
	$txn_id			 = $this->ipn_data[ 'txn_id' ];
	$transaction_type	 = $this->ipn_data[ 'txn_type' ];
	$payment_status		 = $this->ipn_data[ 'payment_status' ];
	$transaction_subject	 = $this->ipn_data[ 'transaction_subject' ];
	$first_name		 = $this->ipn_data[ 'first_name' ];
	$last_name		 = $this->ipn_data[ 'last_name' ];
	$buyer_email		 = $this->ipn_data[ 'payer_email' ];
	$street_address		 = $this->ipn_data[ 'address_street' ];
	$city			 = $this->ipn_data[ 'address_city' ];
	$state			 = $this->ipn_data[ 'address_state' ];
	$zip			 = $this->ipn_data[ 'address_zip' ];
	$country		 = $this->ipn_data[ 'address_country' ];
	$phone			 = isset( $this->ipn_data[ 'contact_phone' ] ) ? $this->ipn_data[ 'contact_phone' ] : '';

	if ( empty( $street_address ) && empty( $city ) ) {
	    //No address value present
	    $address = "";
	} else {
	    //An address value is present
	    $address = $street_address . ", " . $city . ", " . $state . ", " . $zip . ", " . $country;
	}

	$custom_value_str	 = $this->ipn_data[ 'custom' ];
	$this->debug_log( 'Custom field value in the IPN: ' . $custom_value_str, true );
	$custom_values		 = wp_cart_get_custom_var_array( $custom_value_str );

	$this->debug_log( 'Payment Status: ' . $payment_status, true );
	if ( $payment_status == "Completed" || $payment_status == "Processed" ) {
	    //We will process this notification
	} else {
	    $this->debug_log( 'This is not a payment complete notification. This IPN will not be processed.', true );
	    return true;
	}
	if ( $transaction_type == "cart" ) {
	    $this->debug_log( 'Transaction Type: Shopping Cart', true );
	    // Cart Items
	    $num_cart_items = $this->ipn_data[ 'num_cart_items' ];
	    $this->debug_log( 'Number of Cart Items: ' . $num_cart_items, true );

	    $i		 = 1;
	    $cart_items	 = array();
	    while ( $i < $num_cart_items + 1 ) {
		$item_number				 = $this->ipn_data[ 'item_number' . $i ];
		$item_name				 = urldecode( $this->ipn_data[ 'item_name' . $i ] );
		$this->ipn_data[ 'item_name' . $i ]	 = $item_name;
		$quantity				 = $this->ipn_data[ 'quantity' . $i ];
		$mc_gross				 = $this->ipn_data[ 'mc_gross_' . $i ];
		$mc_currency				 = $this->ipn_data[ 'mc_currency' ];

		$current_item = array(
		    'item_number'	 => $item_number,
		    'item_name'	 => $item_name,
		    'quantity'	 => $quantity,
		    'mc_gross'	 => $mc_gross,
		    'mc_currency'	 => $mc_currency,
		);

		array_push( $cart_items, $current_item );
		$i ++;
	    }
	    $this->debug_log( array( $cart_items ), true );
	} else {
	    $cart_items			 = array();
	    $this->debug_log( 'Transaction Type: Buy Now', true );
	    $item_number			 = $this->ipn_data[ 'item_number' ];
	    $item_name			 = urldecode( $this->ipn_data[ 'item_name' ] );
	    $this->ipn_data[ 'item_name' ]	 = $item_name;
	    $quantity			 = $this->ipn_data[ 'quantity' ];
	    $mc_gross			 = $this->ipn_data[ 'mc_gross' ];
	    $mc_currency			 = $this->ipn_data[ 'mc_currency' ];

	    $current_item = array(
		'item_number'	 => $item_number,
		'item_name'	 => $item_name,
		'quantity'	 => $quantity,
		'mc_gross'	 => $mc_gross,
		'mc_currency'	 => $mc_currency,
	    );
	    array_push( $cart_items, $current_item );
	}

	$payment_currency = get_option( 'cart_payment_currency' );

	$individual_paid_item_total = 0;
	foreach ( $cart_items as $current_cart_item ) {
	    $cart_item_data_num		 = $current_cart_item[ 'item_number' ];
	    $cart_item_data_name		 = $current_cart_item[ 'item_name' ];
	    $cart_item_data_quantity	 = $current_cart_item[ 'quantity' ];
	    $cart_item_data_total		 = $current_cart_item[ 'mc_gross' ];
	    $cart_item_data_currency	 = $current_cart_item[ 'mc_currency' ];
	    $individual_paid_item_total	 += $cart_item_data_total;

	    $this->debug_log( 'Item Number: ' . $cart_item_data_num, true );
	    $this->debug_log( 'Item Name: ' . $cart_item_data_name, true );
	    $this->debug_log( 'Item Quantity: ' . $cart_item_data_quantity, true );
	    $this->debug_log( 'Item Total: ' . $cart_item_data_total, true );
	    $this->debug_log( 'Item Currency: ' . $cart_item_data_currency, true );

	    // Compare the currency values to make sure it is correct.
	    if ( $payment_currency != $cart_item_data_currency ) {
		$this->debug_log( 'Invalid Product Currency : ' . $payment_currency, false );
		return false;
	    }
	}

	$post_id		 = $custom_values[ 'wp_cart_id' ];
	$orig_cart_items	 = get_post_meta( $post_id, 'wpsc_cart_items', true );
	$ip_address		 = $custom_values[ 'ip' ];
	$applied_coupon_code	 = isset( $custom_values[ 'coupon_code' ] ) ? $custom_values[ 'coupon_code' ] : '';
	$currency_symbol	 = get_option( 'cart_currency_symbol' );
	$this->debug_log( 'Custom values', true );
	$this->debug_log_array( $custom_values, true );
	$this->debug_log( 'Order post id: ' . $post_id, true );

	//*** Do security checks ***
	if ( empty( $post_id ) ) {
	    $this->debug_log( 'Order ID ' . $post_id . ' does not exist in the IPN notification. This request will not be processed.', false );
	    return;
	}

	if ( ! get_post_status( $post_id ) ) {
	    $this->debug_log( 'Order ID ' . $post_id . ' does not exist in the database. This is not a Simple PayPal Shopping Cart order', false );
	    return;
	}

	if ( get_option( 'wp_shopping_cart_strict_email_check' ) != '' ) {
	    $seller_paypal_email = get_option( 'cart_paypal_email' );
	    if ( $seller_paypal_email != $this->ipn_data[ 'receiver_email' ] ) {
		$error_msg .= 'Invalid Seller Paypal Email Address : ' . $this->ipn_data[ 'receiver_email' ];
		$this->debug_log( $error_msg, false );
		return;
	    } else {
		$this->debug_log( 'Seller Paypal Email Address is Valid: ' . $this->ipn_data[ 'receiver_email' ], true );
	    }
	}

	$transaction_id = get_post_meta( $post_id, 'wpsc_txn_id', true );
	if ( ! empty( $transaction_id ) ) {
	    if ( $transaction_id == $txn_id ) {  //this transaction has been already processed once
		$this->debug_log( 'This transaction has been already processed once. Transaction ID: ' . $transaction_id, false );
		return;
	    }
	}

	//Validate prices
	$orig_individual_item_total = 0;
	foreach ( $orig_cart_items as $item ) {
	    $orig_individual_item_total += $item[ 'price' ] * $item[ 'quantity' ];
	}

	$orig_individual_item_total	 = round( $orig_individual_item_total, 2 );
	$individual_paid_item_total	 = round( $individual_paid_item_total, 2 );
	$this->debug_log( 'Checking price. Original price: ' . $orig_individual_item_total . '. Paid price: ' . $individual_paid_item_total, true );
	if ( $individual_paid_item_total < $orig_individual_item_total ) {  //Paid price is less so block this transaction.
	    $this->debug_log( 'Error! Post payment price validation failed. The price amount may have been altered. This transaction will not be processed.', false );
	    $this->debug_log( 'Original total price: ' . $orig_individual_item_total . '. Paid total price: ' . $individual_paid_item_total, false );
	    return;
	}
	//*** End of security check ***

	$updated_wpsc_order = array(
	    'ID'		 => $post_id,
	    'post_status'	 => 'publish',
	    'post_type'	 => 'wpsc_cart_orders',
	);
	wp_update_post( $updated_wpsc_order );

	update_post_meta( $post_id, 'wpsc_first_name', $first_name );
	update_post_meta( $post_id, 'wpsc_last_name', $last_name );
	update_post_meta( $post_id, 'wpsc_email_address', $buyer_email );
	update_post_meta( $post_id, 'wpsc_txn_id', $txn_id );
	$mc_gross	 = $this->ipn_data[ 'mc_gross' ];
	update_post_meta( $post_id, 'wpsc_total_amount', $mc_gross );
	update_post_meta( $post_id, 'wpsc_ipaddress', $ip_address );
	update_post_meta( $post_id, 'wpsc_address', $address );
	update_post_meta( $post_id, 'wpspsc_phone', $phone );
	$status		 = "Paid";
	update_post_meta( $post_id, 'wpsc_order_status', $status );
	update_post_meta( $post_id, 'wpsc_applied_coupon', $applied_coupon_code );
	$product_details = "";
	$item_counter	 = 1;
	$shipping	 = "";
	if ( $orig_cart_items ) {
	    foreach ( $orig_cart_items as $item ) {
		if ( $item_counter != 1 ) {
		    $product_details .= "\n";
		}
		$item_total	 = $item[ 'price' ] * $item[ 'quantity' ];
		$product_details .= $item[ 'name' ] . " x " . $item[ 'quantity' ] . " - " . $currency_symbol . wpspsc_number_format_price( $item_total ) . "\n";
		if ( isset($item[ 'file_url' ]) ) {
		    $file_url	 = base64_decode( $item[ 'file_url' ] );
		    $product_details .= "Download Link: " . $file_url . "\n";
		}
		if ( ! empty( $item[ 'shipping' ] ) ) {
		    $shipping += $item[ 'shipping' ] * $item[ 'quantity' ];
		}
		$item_counter ++;
	    }
	}
	if ( empty( $shipping ) ) {
	    $shipping = "0.00";
	} else {
	    $baseShipping	 = get_option( 'cart_base_shipping_cost' );
	    $shipping	 = $shipping + $baseShipping;
	    $shipping	 = wpspsc_number_format_price( $shipping );
	}
	update_post_meta( $post_id, 'wpsc_shipping_amount', $shipping );
	update_post_meta( $post_id, 'wpspsc_items_ordered', $product_details );

	$args				 = array();
	$args[ 'product_details' ]	 = $product_details;
	$args[ 'order_id' ]		 = $post_id;
	$args[ 'coupon_code' ]		 = $applied_coupon_code;
	$args[ 'address' ]		 = $address;
	$args[ 'payer_email' ]		 = $buyer_email;

	$from_email	 = get_option( 'wpspc_buyer_from_email' );
	$subject	 = get_option( 'wpspc_buyer_email_subj' );
	$subject	 = wpspc_apply_dynamic_tags_on_email( $subject, $this->ipn_data, $args );

	$body			 = get_option( 'wpspc_buyer_email_body' );
	$args[ 'email_body' ]	 = $body;
	$body			 = wpspc_apply_dynamic_tags_on_email( $body, $this->ipn_data, $args );

	$this->debug_log( 'Applying filter - wspsc_buyer_notification_email_body', true );
	$body = apply_filters( 'wspsc_buyer_notification_email_body', $body, $this->ipn_data, $cart_items );

	$headers = 'From: ' . $from_email . "\r\n";
	if ( ! empty( $buyer_email ) ) {
	    if ( get_option( 'wpspc_send_buyer_email' ) ) {
		wp_mail( $buyer_email, $subject, $body, $headers );
		$this->debug_log( 'Product Email successfully sent to ' . $buyer_email, true );
		update_post_meta( $post_id, 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email );
	    }
	}
	$notify_email		 = get_option( 'wpspc_notify_email_address' );
	$seller_email_subject	 = get_option( 'wpspc_seller_email_subj' );
	$seller_email_subject	 = wpspc_apply_dynamic_tags_on_email( $seller_email_subject, $this->ipn_data, $args );

	$seller_email_body	 = get_option( 'wpspc_seller_email_body' );
	$args[ 'email_body' ]	 = $seller_email_body;
	$seller_email_body	 = wpspc_apply_dynamic_tags_on_email( $seller_email_body, $this->ipn_data, $args );

	$this->debug_log( 'Applying filter - wspsc_seller_notification_email_body', true );
	$seller_email_body = apply_filters( 'wspsc_seller_notification_email_body', $seller_email_body, $this->ipn_data, $cart_items );

	if ( ! empty( $notify_email ) ) {
	    if ( get_option( 'wpspc_send_seller_email' ) ) {
		wp_mail( $notify_email, $seller_email_subject, $seller_email_body, $headers );
		$this->debug_log( 'Notify Email successfully sent to ' . $notify_email, true );
	    }
	}


	/*	 * ** Affiliate plugin integratin *** */
	$this->debug_log( 'Updating Affiliate Database Table with Sales Data if Using the WP Affiliate Platform Plugin.', true );
	if ( function_exists( 'wp_aff_platform_install' ) ) {
	    $this->debug_log( 'WP Affiliate Platform is installed, registering sale...', true );
	    $referrer	 = $custom_values[ 'ap_id' ];
	    $sale_amount	 = $this->ipn_data[ 'mc_gross' ];
	    if ( ! empty( $referrer ) ) {
		do_action( 'wp_affiliate_process_cart_commission', array( "referrer" => $referrer, "sale_amt" => $sale_amount, "txn_id" => $txn_id, "buyer_email" => $buyer_email ) );

		$message = 'The sale has been registered in the WP Affiliates Platform Database for referrer: ' . $referrer . ' for sale amount: ' . $sale_amount;
		$this->debug_log( $message, true );
	    } else {
		$this->debug_log( 'No Referrer Found. This is not an affiliate sale', true );
	    }
	} else {
	    $this->debug_log( 'Not Using the WP Affiliate Platform Plugin.', true );
	}

	do_action( 'wpspc_paypal_ipn_processed', $this->ipn_data, $this );

	//Empty any incomplete old cart orders.
	wspsc_clean_incomplete_old_cart_orders();

	return true;
    }

    function validate_ipn() {
	//Generate the post string from the _POST vars aswell as load the _POST vars into an array
	$post_string = '';
	foreach ( $_POST as $field => $value ) {
	    $this->ipn_data[ "$field" ]	 = $value;
	    $post_string			 .= $field . '=' . urlencode( stripslashes( $value ) ) . '&';
	}

	$this->post_string = $post_string;
	$this->debug_log( 'Post string : ' . $this->post_string, true );

	//IPN validation check
	if ( $this->validate_ipn_using_remote_post() ) {
	    //We can also use an alternative validation using the validate_ipn_using_curl() function
	    return true;
	} else {
	    return false;
	}
    }

    function validate_ipn_using_remote_post() {
	$this->debug_log( 'Checking if PayPal IPN response is valid', true );

	// Get received values from post data
	$validate_ipn	 = array( 'cmd' => '_notify-validate' );
	$validate_ipn	 += wp_unslash( $_POST );

	// Send back post vars to paypal
	$params = array(
	    'body'		 => $validate_ipn,
	    'timeout'	 => 60,
	    'httpversion'	 => '1.1',
	    'compress'	 => false,
	    'decompress'	 => false,
	    'user-agent'	 => 'Simple PayPal Shopping Cart/' . WP_CART_VERSION
	);

	// Post back to get a response.
	$connection_url	 = $this->sandbox_mode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
	$this->debug_log( 'Connecting to: ' . $connection_url, true );
	$response	 = wp_safe_remote_post( $connection_url, $params );

	//The following two lines can be used for debugging
	//$this->debug_log( 'IPN Request: ' . print_r( $params, true ) , true);
	//$this->debug_log( 'IPN Response: ' . print_r( $response, true ), true);
	// Check to see if the request was valid.
	if ( ! is_wp_error( $response ) && strstr( $response[ 'body' ], 'VERIFIED' ) ) {
	    $this->debug_log( 'IPN successfully verified.', true );
	    return true;
	}

	// Invalid IPN transaction. Check the log for details.
	$this->debug_log( 'IPN validation failed.', false );
	if ( is_wp_error( $response ) ) {
	    $this->debug_log( 'Error response: ' . $response->get_error_message(), false );
	}
	return false;
    }

    function validate_ipn_smart_checkout() {

	$is_sandbox = get_option( 'wp_shopping_cart_enable_sandbox' );

	if ( $is_sandbox ) {
	    $client_id	 = get_option( 'wpspc_pp_test_client_id' );
	    $secret		 = get_option( 'wpspc_pp_test_secret' );
	    $api_base	 = 'https://api.sandbox.paypal.com';
	} else {
	    $client_id	 = get_option( 'wpspc_pp_live_client_id' );
	    $secret		 = get_option( 'wpspc_pp_live_secret' );
	    $api_base	 = 'https://api.paypal.com';
	}

	$wp_request_headers = array(
	    'Accept'	 => 'application/json',
	    'Authorization'	 => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
	);

	$res = wp_remote_request(
	$api_base . '/v1/oauth2/token', array(
	    'method'	 => 'POST',
	    'headers'	 => $wp_request_headers,
	    'body'		 => 'grant_type=client_credentials',
	)
	);

	$code = wp_remote_retrieve_response_code( $res );

	if ( $code !== 200 ) {
	    //Some error occured.
	    $body = wp_remote_retrieve_body( $res );
	    return sprintf( __( 'Error occured during payment verification. Error code: %d. Message: %s', "wordpress-simple-paypal-shopping-cart" ), $code, $body );
	}

	$body	 = wp_remote_retrieve_body( $res );
	$body	 = json_decode( $body );

	$token = $body->access_token;

	$wp_request_headers = array(
	    'Accept'	 => 'application/json',
	    'Authorization'	 => 'Bearer ' . $token,
	);

	$res = wp_remote_request(
	$api_base . '/v1/payments/payment/' . $this->ipn_data[ 'pay_id' ], array(
	    'method'	 => 'GET',
	    'headers'	 => $wp_request_headers,
	)
	);

	$code = wp_remote_retrieve_response_code( $res );

	if ( $code !== 200 ) {
	    //Some error occured.
	    $body = wp_remote_retrieve_body( $res );
	    return sprintf( __( 'Error occured during payment verification. Error code: %d. Message: %s', "wordpress-simple-paypal-shopping-cart" ), $code, $body );
	}

	$body	 = wp_remote_retrieve_body( $res );
	$body	 = json_decode( $body );

	//check payment details
	if ( $body->transactions[ 0 ]->amount->total === $this->ipn_data[ 'mc_gross' ] &&
	$body->transactions[ 0 ]->amount->currency === $this->ipn_data[ 'mc_currency' ] ) {
	    //payment is valid
	    return true;
	} else {
	    //payment is invalid
	    return sprintf( __( "Payment check failed: invalid amount received. Expected %s %s, got %s %s.", "wordpress-simple-paypal-shopping-cart" ), $this->ipn_data[ 'mc_gross' ], $this->ipn_data[ 'mc_currency' ], $body->transactions[ 0 ]->amount->total, $body->transactions[ 0 ]->amount->currency );
	}
    }

    function create_ipn_from_smart_checkout( $data ) {
	$ipn[ 'custom' ]		 = $_SESSION[ 'wp_cart_custom_values' ];
	$ipn[ 'pay_id' ]		 = $data[ 'id' ];
	$ipn[ 'create_time' ]		 = $data[ 'create_time' ];
	$ipn[ 'txn_id' ]		 = $data[ 'transactions' ][ 0 ][ 'related_resources' ][ 0 ][ 'sale' ][ 'id' ];
	$ipn[ 'txn_type' ]		 = 'cart';
	$ipn[ 'payment_status' ]	 = ucfirst( $data[ 'transactions' ][ 0 ][ 'related_resources' ][ 0 ][ 'sale' ][ 'state' ] );
	$ipn[ 'transaction_subject' ]	 = '';
	$ipn[ 'mc_currency' ]		 = $data[ 'transactions' ][ 0 ][ 'amount' ][ 'currency' ];
	$ipn[ 'mc_gross' ]		 = $data[ 'transactions' ][ 0 ][ 'amount' ][ 'total' ];
	$ipn[ 'receiver_email' ]	 = get_option( 'cart_paypal_email' );
	//customer info
	$ipn[ 'first_name' ]		 = $data[ 'payer' ][ 'payer_info' ][ 'first_name' ];
	$ipn[ 'last_name' ]		 = $data[ 'payer' ][ 'payer_info' ][ 'last_name' ];
	$ipn[ 'payer_email' ]		 = $data[ 'payer' ][ 'payer_info' ][ 'email' ];
	$ipn[ 'address_street' ]	 = $data[ 'payer' ][ 'payer_info' ][ 'shipping_address' ][ 'line1' ];
	$ipn[ 'address_city' ]		 = $data[ 'payer' ][ 'payer_info' ][ 'shipping_address' ][ 'city' ];
	$ipn[ 'address_state' ]		 = $data[ 'payer' ][ 'payer_info' ][ 'shipping_address' ][ 'state' ];
	$ipn[ 'address_zip' ]		 = $data[ 'payer' ][ 'payer_info' ][ 'shipping_address' ][ 'postal_code' ];
	$ipn[ 'address_country' ]	 = $data[ 'payer' ][ 'payer_info' ][ 'shipping_address' ][ 'country_code' ];
	//items data
	$i				 = 1;
	foreach ( $data[ 'transactions' ][ 0 ][ 'item_list' ][ 'items' ] as $item ) {
	    $ipn[ 'item_number' . $i ]	 = '';
	    $ipn[ 'item_name' . $i ]	 = $item[ 'name' ];
	    $ipn[ 'quantity' . $i ]		 = $item[ 'quantity' ];
	    $ipn[ 'mc_gross_' . $i ]	 = $item[ 'price' ] * $item[ 'quantity' ];
	    $i ++;
	}
	$ipn[ 'num_cart_items' ] = $i - 1;
	$this->ipn_data		 = $ipn;
	return true;
    }

    function log_ipn_results( $success ) {
	if ( ! $this->ipn_log )
	    return;  // is logging turned off?
	// Timestamp
	$text = '[' . date( 'm/d/Y g:i A' ) . '] - ';

	// Success or failure being logged?
	if ( $success )
	    $text	 .= "SUCCESS!\n";
	else
	    $text	 .= 'FAIL: ' . $this->last_error . "\n";

	// Log the POST variables
	$text .= "IPN POST Vars from Paypal:\n";
	foreach ( $this->ipn_data as $key => $value ) {
	    $text .= "$key=$value, ";
	}

	// Log the response from the paypal server
	$text .= "\nIPN Response from Paypal Server:\n " . $this->ipn_response;

	// Write to log
	$fp = fopen( $this->ipn_log_file, 'a' );
	fwrite( $fp, $text . "\n\n" );

	fclose( $fp );  // close file
    }

    function debug_log( $message, $success, $end = false ) {

	if ( ! $this->ipn_log )
	    return;  // is logging turned off?
	// Timestamp
	//check if need to convert array to string
	if ( is_array( $message ) ) {
	    $message = json_encode( $message );
	}
	$text = '[' . date( 'm/d/Y g:i A' ) . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . $message . "\n";

	if ( $end ) {
	    $text .= "\n------------------------------------------------------------------\n\n";
	}

	// Write to log
	$fp = fopen( $this->ipn_log_file, 'a' );
	fwrite( $fp, $text );
	fclose( $fp );  // close file
    }

    function debug_log_array( $array_to_write, $success, $end = false ) {
	if ( ! $this->ipn_log )
	    return;  // is logging turned off?
	$text	 = '[' . date( 'm/d/Y g:i A' ) . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . "\n";
	ob_start();
	print_r( $array_to_write );
	$var	 = ob_get_contents();
	ob_end_clean();
	$text	 .= $var;

	if ( $end ) {
	    $text .= "\n------------------------------------------------------------------\n\n";
	}
	// Write to log
	$fp = fopen( $this->ipn_log_file, 'a' );
	fwrite( $fp, $text );
	fclose( $fp );  // close filee
    }

}

// Start of IPN handling (script execution)
function wpc_handle_paypal_ipn() {
    $debug_log		 = "ipn_handle_debug.txt"; // Debug log file name    
    $ipn_handler_instance	 = new paypal_ipn_handler();

    $debug_enabled	 = false;
    $debug		 = get_option( 'wp_shopping_cart_enable_debug' );
    if ( $debug ) {
	$debug_enabled = true;
    }

    if ( $debug_enabled ) {
	echo 'Debug is enabled. Check the ' . $debug_log . ' file for debug output.';
	$ipn_handler_instance->ipn_log = true;
	//$ipn_handler_instance->ipn_log_file = realpath(dirname(__FILE__)).'/'.$debug_log;
    }
    $sandbox = get_option( 'wp_shopping_cart_enable_sandbox' );
    if ( $sandbox ) { // Enable sandbox testing
	$ipn_handler_instance->paypal_url	 = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	$ipn_handler_instance->sandbox_mode	 = true;
    }
    $ipn_handler_instance->debug_log( 'Paypal Class Initiated by ' . $_SERVER[ 'REMOTE_ADDR' ], true );
    // Validate the IPN
    if ( $ipn_handler_instance->validate_ipn() ) {
	$ipn_handler_instance->debug_log( 'Creating product Information to send.', true );
	if ( ! $ipn_handler_instance->validate_and_dispatch_product() ) {
	    $ipn_handler_instance->debug_log( 'IPN product validation failed.', false );
	}
    }
    $ipn_handler_instance->debug_log( 'Paypal class finished.', true, true );
}
