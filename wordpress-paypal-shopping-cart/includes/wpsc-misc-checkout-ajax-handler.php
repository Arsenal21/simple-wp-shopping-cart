<?php 

if ( wp_doing_ajax() ) {
	add_action( 'wp_ajax_wpsc_process_pp_smart_checkout', 'wpsc_process_pp_smart_checkout' );
	add_action( 'wp_ajax_nopriv_wpsc_process_pp_smart_checkout', 'wpsc_process_pp_smart_checkout' );

	add_action( 'wp_ajax_wpsc_stripe_create_checkout_session', 'wpsc_stripe_create_checkout_session' );
	add_action( 'wp_ajax_nopriv_wpsc_stripe_create_checkout_session', 'wpsc_stripe_create_checkout_session' );

	add_action( 'wp_ajax_wpsc_manual_payment_checkout', 'wpsc_manual_payment_checkout' );
	add_action( 'wp_ajax_nopriv_wpsc_manual_payment_checkout', 'wpsc_manual_payment_checkout' );
}

/**
 * Create a Stripe checkout session (when the Stripe checkout button is clicked in the cart).
 */
function wpsc_stripe_create_checkout_session() {
	//When the Stripe checkout button is clicked in the cart, it sends an AJAX request to the server. 
	//We handle this request and create a Stripe checkout session in this function.

	$wspsc_cart = WPSC_Cart::get_instance();
	$wspsc_cart->calculate_cart_totals_and_postage();

	$cart_id = $wspsc_cart->get_cart_id();
	$cart_cpt_id = $wspsc_cart->get_cart_cpt_id();
	$currency = get_option( 'cart_payment_currency' );
	$symbol = get_option( 'cart_currency_symbol' );
	$return_url = get_option( 'cart_return_from_paypal_url' );
	$cancel_url = get_option( 'cart_cancel_from_paypal_url' );
	$secret_key = get_option( 'wp_shopping_cart_enable_sandbox' ) ? get_option( 'wpspc_stripe_test_secret_key' ) : get_option( 'wpspc_stripe_live_secret_key' );

	$force_collect_address = get_option( 'wpspc_stripe_collect_address' );

	//Get the shipping address collection preference.
	// $all_items_digital = $wspsc_cart->all_cart_items_digital();
	// if( $all_items_digital ){
	// 	//This will only happen if the shortcode attribute 'digital' is set to '1' for all the items in the cart. 
	// 	//So we don't need to check postage cost.
	// 	$shipping_preference = 'no_shipping';
	// } else {
	// 	//At least one item is not digital. Get the customer to provide shipping address on the Stripe checkout page.
	// 	$shipping_preference = 'required';
	// }
	//wpsc_log_payment_debug("Shipping preference based on the 'all items digital' flag: " . $shipping_preference, true);

	//Custom field data. 
	//Decode the custom field before sanitizing.
	$custom_input = isset( $_POST['custom'] ) ? $_POST['custom'] : '';
	$decoded_custom = urldecode( $custom_input );
	$decoded_custom = sanitize_text_field( stripslashes( $decoded_custom ) );
	//wpsc_log_payment_debug('Stripe custom field input value: ' . $decoded_custom, true);

	$postage_cost = $wspsc_cart->get_postage_cost();

	if ( ! wpsc_is_zero_cents_currency( $currency ) ) {
		$postage_cost = wpsc_amount_in_cents( $postage_cost );
	}else{
		$postage_cost = round( $postage_cost ); // To make sure there is no decimal place number for zero cents currency.
	}

	// Extracting individual parameters
	$custom_metadata = array();
	parse_str( $decoded_custom, $custom_metadata );

	if ( empty( $currency ) ) {
		$currency = __( 'USD', 'wordpress-simple-paypal-shopping-cart' );
	}

	if ( empty( $symbol ) ) {
		$symbol = __( '$', 'wordpress-simple-paypal-shopping-cart' );
	}

	$query_args = array( 'simple_cart_stripe_ipn' => '1', 'ref_id' => $wspsc_cart->get_cart_id() );
	$stripe_ipn_url = add_query_arg( $query_args, WP_CART_SITE_URL );

	wpsc_load_stripe_lib();

	try {

		\Stripe\Stripe::setApiKey( $secret_key );
		\Stripe\Stripe::setApiVersion( "2024-06-20" );

		$opts = array(
			'client_reference_id' => $cart_id,
			'billing_address_collection' => $force_collect_address ? 'required' : 'auto',
			'mode' => 'payment',
			'success_url' => $stripe_ipn_url
		);

		/*
		* We are not specifying any payment method types. Stripe will automatically display the payment methods that the merchant has enabled in their Stripe account.
		*/
		//$opts['payment_method_types'] = array( 'card' );

		//Other options for the checkout session.
		$force_collect_shipping_address = sanitize_text_field(get_option( 'wpsc_stripe_collect_shipping_address' ));
		$allowed_shipping_countries = sanitize_text_field(get_option( 'wpsc_stripe_allowed_shipping_countries' ));
		$allowed_shipping_countries = process_allowed_shipping_countries($allowed_shipping_countries);
        if( !empty($force_collect_shipping_address) ){
            $opts['shipping_address_collection'] = array(
                'allowed_countries' => $allowed_shipping_countries,
            );
        } else {
            $all_items_digital = $wspsc_cart->all_cart_items_digital();
            if( $all_items_digital ){
                //All items are digital. No need to collect shipping address.
            } else {
                //At least one item is not digital. Get the customer to provide shipping address on the Stripe checkout page.
                $opts['shipping_address_collection'] = array(
                    'allowed_countries' => $allowed_shipping_countries,
                );
            }
        }
		
		//TODO - add a settings option to allow the site admin to set the allowed countries.
		// if ( $shipping_preference == 'required' ) {
		// 	$opts['shipping_address_collection'] = array(
		// 		'allowed_countries' => array( 'US', 'CA', 'GB', 'AU' ),
		// 	);
		// }

		if ( ! empty( $cancel_url ) ) {
			$opts["cancel_url"] = $cancel_url;
		}

		if ( sizeof( $custom_metadata ) > 0 ) {
			$opts["metadata"] = $custom_metadata;
		}

		$lineItems = array();

		foreach ( $wspsc_cart->get_items() as $item ) {
			$item_price = $item->get_price();

			if ( ! wpsc_is_zero_cents_currency( $currency ) ) {
				$item_price = wpsc_amount_in_cents( $item_price );
			}else{
				$item_price = round( $item_price ); // To make sure there is no decimal place number for zero cents currency.
			}

			$lineItem = array(
				'price_data' => array(
					'currency' => $currency,
					'unit_amount' => $item_price,
					'product_data' => array(
						'name' => $item->get_name(),
					),
				),
				'quantity' => $item->get_quantity()
			);

			$lineItems[] = $lineItem;
		}

		$opts["line_items"] = $lineItems;

		// Add shipping options
		if ( $postage_cost > 0 ) {
			$opts["shipping_options"] = array(
				array(
					'shipping_rate_data' => array(
						'type' => 'fixed_amount',
						'fixed_amount' => array(
							'amount' => $postage_cost,
							'currency' => $currency,
						),
						'display_name' => 'shipping',
					),
				),
			);
		}

		// Collect automatic tax if enabled.
		if ( get_option('wpsc_enable_stripe_automatic_tax') ){
			$opts["automatic_tax"] = array(
				'enabled' => true,
			);
		}

		$opts = apply_filters( 'wpspsc_stripe_sca_session_opts', $opts, $cart_id );// TODO: Old hook. Need to remove this.
		$opts = apply_filters( 'wpsc_stripe_sca_session_opts', $opts, $cart_id );

		$session = \Stripe\Checkout\Session::create( $opts );
	} catch (Exception $e) {
		$err = $e->getMessage();
		wp_send_json( array( 'error' => 'Error occurred: ' . $err ) );
	}
	wp_send_json( array( 'session_id' => $session->id ) );

}

/**
 * Process the payment data received from the smart checkout.
 */
function wpsc_process_pp_smart_checkout() {
	if ( isset( $_POST['wpspsc_payment_data'] ) ) {
		$data = $_POST['wpspsc_payment_data'];
	}
	if ( empty( $data ) ) {
		wp_send_json( array( 'success' => false, 'errMsg' => __( 'Empty payment data received.', "wordpress-simple-paypal-shopping-cart" ) ) );
	}

	//We don't use the session anymore. So no need to start it.
	// if ( session_status() == PHP_SESSION_NONE ) {
	// 	session_start();
	// }

	include_once( WP_CART_PATH . 'paypal.php');

	$ipn_handler_instance = new paypal_ipn_handler();

	$ipn_data_success = $ipn_handler_instance->create_ipn_from_smart_checkout( $data );

	if ( $ipn_data_success !== true ) {
		//error occured during IPN array creation
		wp_send_json( array( 'success' => false, 'errMsg' => $ipn_data_success ) );
	}

	$debug_enabled = false;
	$debug = get_option( 'wp_shopping_cart_enable_debug' );
	if ( $debug ) {
		$debug_enabled = true;
	}

	if ( $debug_enabled ) {
		$ipn_handler_instance->ipn_log = true;
	}

	$res = $ipn_handler_instance->validate_ipn_smart_checkout();

	if ( $res !== true ) {
		wp_send_json( array( 'success' => false, 'errMsg' => $res ) );
	}

	$res = $ipn_handler_instance->validate_and_dispatch_product();

	if ( $res === true ) {
		wp_send_json( array( 'success' => true ) );
	} else {
		wp_send_json( array( 'success' => false, 'errMsg' => __( 'Error occured during payment processing. Check debug log for additional details.', "wordpress-simple-paypal-shopping-cart" ) ) );
	}
}

function wpsc_manual_payment_checkout(){

	$json = file_get_contents('php://input');

	$post = json_decode($json, true);

	if ( !isset($post['nonce']) || !wp_verify_nonce( $post['nonce'], 'wpsc_manual_payment_form_nonce_action') ){
		wp_send_json_error(array(
			"message" => __( 'Nonce verification failed!', 'wordpress-simple-paypal-shopping-cart' ),
			"data" => $post
		));
	}

	// Check if manual checkout is enabled or not.
	$is_manual_checkout_enabled = get_option( 'wpsc_enable_manual_checkout' );
	if ( empty($is_manual_checkout_enabled) ){
		wp_send_json_error(array(
			"message" => __( 'Manual Checkout is not enabled!', 'wordpress-simple-paypal-shopping-cart' ),
		));
	}

	include_once( WP_CART_PATH . 'manual-checkout.php');

	if ( ! WPSC_Manual_checkout::validate_checkout_form($post) ){
		wp_send_json_error(array(
			"message" => __( 'Checkout form validation error!', 'wordpress-simple-paypal-shopping-cart' ),
		));
	}

	// Process and place order
	try {
		$wpsc_manual_checkout = new WPSC_Manual_Checkout();
		$wpsc_manual_checkout->process_and_create_order($post);
	} catch (\Exception $e){
		wp_send_json_error(array(
			"message" => $e->getMessage(),
		));
	}

	$return_url = get_option('cart_return_from_paypal_url');
	$return_url = add_query_arg(
		array(
			'cart_id' => $wpsc_manual_checkout->data['cart_id'],
			'_wpnonce' => wp_create_nonce('wpsc_thank_you_nonce_action'),
		),
		$return_url
	);

	wp_send_json_success(array(
		"message" => __( 'Order Placed Successfully!', 'wordpress-simple-paypal-shopping-cart' ),
		'redirect_to' => $return_url,
	));
}