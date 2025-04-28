<?php

namespace TTHQ\WPSC\Lib\PayPal;

/**
 * This clcass handles the ajax requests from the PayPal button's createOrder, captureOrder functions.
 * On successful onApprove event, it creates the required $ipn_data array from the transaction so it can be fed into the existing IPN handler functions easily.
 */
class PayPal_Button_Ajax_Hander {

	public function __construct() {
		//Handle it at 'wp_loaded' hook since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_ajax_request_actions' ) );
	}

	/**
	 * Setup the ajax request actions.
	 */
	public function setup_ajax_request_actions() {
		//Handle the create-order ajax request for 'Buy Now' type buttons.
		add_action( PayPal_Utility_Functions::hook('pp_create_order', true), array(&$this, 'pp_create_order' ) );
		add_action( PayPal_Utility_Functions::hook('pp_create_order', true, true), array(&$this, 'pp_create_order' ) );
		
		//Handle the capture-order ajax request for 'Buy Now' type buttons.
		add_action( PayPal_Utility_Functions::hook('pp_capture_order', true), array(&$this, 'pp_capture_order' ) );
		add_action( PayPal_Utility_Functions::hook('pp_capture_order', true, true), array(&$this, 'pp_capture_order' ) );	
	}

	/**
	 * Handle the pp_create_order ajax request for 'Buy Now' type buttons.
	 */
	 public function pp_create_order(){
		//Get the data from the request
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}
		
		if( !is_array( $data ) ){
			//Convert the JSON string to an array (Vanilla JS AJAX data will be in JSON format).
			$data = json_decode( $data, true);		
		}

		$cart_id = isset( $data['cart_id'] ) ? sanitize_text_field( $data['cart_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		PayPal_Utility_Functions::log( 'pp_create_order ajax request received for createOrder. Cart ID: '.$cart_id.', On Page Button ID: ' . $on_page_button_id, true );

		// Check nonce.
		if ( ! check_ajax_referer( $on_page_button_id, '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}
		
		//***************************** */
		//FIXME - Get the cart details such as payment amount, currency, item name, etc from the cart object.
		//Some of the details can be saved in the cart order CPT and then we can use it here.
		//***************************** */

		//FIXME - Check if we can specify all the cart items in the PayPal API data array.

		$wspsc_cart = \WPSC_Cart::get_instance();
		$cart_cpt_id = $wspsc_cart->get_cart_cpt_id();

		//Get the cart and item details.
		$order_cpt = get_post($cart_cpt_id); //Retrieve the CPT for this.
		$description = 'Simple Cart Order ID: ' . $cart_cpt_id;//Default description.
		$description = htmlspecialchars($description);
		$description = substr($description, 0, 127);//Limit the item name to 127 characters (PayPal limit)

		//Get the payment amount
		$wspsc_cart->calculate_cart_totals_and_postage();
		$formatted_sub_total = $wspsc_cart->get_sub_total_formatted();
		$formatted_postage_cost = $wspsc_cart->get_postage_cost_formatted();
		$formatted_grand_total = $wspsc_cart->get_grand_total_formatted();
		//$payment_amount = $formatted_grand_total;

		//Get the currency
		$currency = !empty(get_option( 'cart_payment_currency' )) ? get_option( 'cart_payment_currency' ) : 'USD';

		//Get the cart items to create the purchase units items array.
		$cart_items = $wspsc_cart->get_items();
		$pu_items = PayPal_Utility_Functions::create_purchase_units_items_list( $cart_items );

		//Get the shipping preference.
		$all_items_digital = $wspsc_cart->all_cart_items_digital();
		if( $all_items_digital ){
			//This will only happen if the shortcode attribute 'digital' is set to '1' for all the items in the cart. 
			//So we don't need to check postage cost.
			$shipping_preference = 'NO_SHIPPING';
		} else {
			//At least one item is not digital. Get the customer-provided shipping address on the PayPal site.
			$shipping_preference = 'GET_FROM_FILE';//This is also the default value for the shipping preference.
		}
		PayPal_Utility_Functions::log("Shipping preference based on the 'all items digital' flag: " . $shipping_preference, true);

		// Create the order using the PayPal API.
		// https://developer.paypal.com/docs/api/orders/v2/#orders_create
		$data = array(
			'description' => $description,
			'grand_total' => $formatted_grand_total,
			'sub_total' => $formatted_sub_total,
			'postage_cost' => $formatted_postage_cost,
			'tax' => '0.00', //Currently we are not using tax.
			'currency' => $currency,
			'shipping_preference' => $shipping_preference,
		);

		//Set the additional args for the API call.
		$additional_args = array();
		$additional_args['return_response_body'] = true;

		//Create the order using the PayPal API.
		$api_injector = new PayPal_Request_API_Injector();
		$response = $api_injector->create_paypal_order_by_url_and_args( $data, $additional_args, $pu_items );
            
		//We requested the response body to be returned, so we need to JSON decode it.
		if( $response !== false ){
			$order_data = json_decode( $response, true );
			$paypal_order_id = isset( $order_data['id'] ) ? $order_data['id'] : '';
		} else {
			//Failed to create the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to create the order using PayPal API. Enable the debug logging feature to get more details.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}

        PayPal_Utility_Functions::log( 'PayPal Order ID: ' . $paypal_order_id, true );

		//Save the grand total and currency in the order CPT (we will match it with the PayPal response later in verification stage).
		update_post_meta( $cart_cpt_id, 'expected_payment_amount', $formatted_grand_total );
		update_post_meta( $cart_cpt_id, 'expected_currency', $currency );

		//Save the current cart items with the PayPal order ID in the order CPT (we will use this one to process in the IPN processing stage).
		$cart_items = get_post_meta($cart_cpt_id, 'wpsc_cart_items', true);
		$wpsc_cart_items_pp_order_id_key = 'wpsc_cart_items_' . $paypal_order_id;
		update_post_meta( $cart_cpt_id, $wpsc_cart_items_pp_order_id_key, $cart_items );

		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'order_id' => $paypal_order_id, 'order_data' => $order_data ) );
		exit;
    }


	/**
	 * Handles the order capture for standard 'Buy Now' type buttons.
	 */
	public function pp_capture_order(){

		//Get the data from the request
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}
		
		if( !is_array( $data ) ){
			//Convert the JSON string to an array (Vanilla JS AJAX data will be in JSON format).
			$data = json_decode( $data, true);		
		}

		//Get the order_id from data
		$order_id = isset( $data['order_id'] ) ? sanitize_text_field($data['order_id']) : '';
		if ( empty( $order_id ) ) {
			PayPal_Utility_Functions::log( 'pp_capture_order - empty order ID received.', false );
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty order ID received.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}

		$cart_id = isset( $data['cart_id'] ) ? sanitize_text_field( $data['cart_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		PayPal_Utility_Functions::log( 'Received request - pp_capture_order. PayPal Order ID: ' . $order_id . ', Cart ID: '.$cart_id.', On Page Button ID: ' . $on_page_button_id, true );

		// Check nonce.
		if ( ! check_ajax_referer( $on_page_button_id, '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}

		//Set the additional args for the API call.
		$additional_args = array();
		$additional_args['return_response_body'] = true;

		// Capture the order using the PayPal API.
		// https://developer.paypal.com/docs/api/orders/v2/#orders_capture
		$api_injector = new PayPal_Request_API_Injector();
		$response = $api_injector->capture_paypal_order( $order_id, $additional_args );

		//We requested the response body to be returned, so we need to JSON decode it.
		if($response !== false){
			$txn_data = json_decode( $response, true );//JSON decode the response body that we received.
		} else {
			//Failed to capture the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to capture the order. Enable the debug logging feature to get more details.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}

		//--
		// PayPal_Utility_Functions::log_array($data, true);//Debugging purpose.
		// PayPal_Utility_Functions::log_array($txn_data, true);//Debugging purpose.
		//--

		$data['cart_cpt_id'] = wpsc_get_cart_cpt_id_by_cart_id($cart_id);

		//Create the IPN data array from the transaction data.
		//Need to include the following values in the $data array.
		$data['custom_field'] = get_post_meta( $cart_id, 'wpsc_cart_custom_values', true );//We saved the custom field in the cart CPT.
		
		$ipn_data = PayPal_Utility_IPN_Related::create_ipn_data_array_from_capture_order_txn_data( $data, $txn_data );
		$paypal_capture_id = isset( $ipn_data['txn_id'] ) ? $ipn_data['txn_id'] : '';
		PayPal_Utility_Functions::log( 'PayPal Capture ID (Transaction ID): ' . $paypal_capture_id, true );
		PayPal_Utility_Functions::log_array( $ipn_data, true );//Debugging purpose.
		
		/* Since this capture is done from server side, the validation is not required but we are doing it anyway. */
		//Validate the buy now txn data before using it.
		$validation_response = PayPal_Utility_IPN_Related::validate_buy_now_checkout_txn_data( $data, $txn_data );
		if( $validation_response !== true ){
			//Debug logging will reveal more details.
			wp_send_json(
				array(
					'success' => false,
					'error_detail'  => $validation_response,/* it contains the error message */
				)
			);
			exit;
		}
		
		//Process the IPN data array
		PayPal_Utility_Functions::log( 'Validation passed. Going to create/update record and save transaction data.', true );
		
		/**
		 * TODO: This is a plugin specific method.
		 */
		PayPal_Utility_IPN_Related::complete_post_payment_processing( $data, $txn_data, $ipn_data );

		/**
		 * Trigger the IPN processed action hook (so other plugins can can listen for this event).
		 * Remember to use plugin shortname as prefix when searching for this hook.
		 */ 
		do_action( PayPal_Utility_Functions::hook('paypal_checkout_ipn_processed'), $ipn_data );
		do_action( PayPal_Utility_Functions::hook('payment_ipn_processed'), $ipn_data );

		//Everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'order_id' => $order_id, 'capture_id' => $paypal_capture_id, 'txn_data' => $txn_data ) );
		exit;
	}	

}
