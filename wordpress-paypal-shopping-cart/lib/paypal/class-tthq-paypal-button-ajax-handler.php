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

		$button_id = isset( $data['button_id'] ) ? sanitize_text_field( $data['button_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		PayPal_Utility_Functions::log( 'pp_create_order ajax request received for createOrder. Button ID: '.$button_id.', On Page Button ID: ' . $on_page_button_id, true );

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
		
		//Get the Item name for this button. This will be used as the item name in the IPN.
		$button_cpt = get_post($button_id); //Retrieve the CPT for this button
		$item_name = htmlspecialchars($button_cpt->post_title);
		$item_name = substr($item_name, 0, 127);//Limit the item name to 127 characters (PayPal limit)
		//Get the payment amount for this button.
		$payment_amount = get_post_meta($button_id, 'payment_amount', true);
		//Get the currency for this button.
		$currency = get_post_meta( $button_id, 'payment_currency', true );
		$quantity = 1;
		$digital_goods_enabled = 1;

		// Create the order using the PayPal API.
		// https://developer.paypal.com/docs/api/orders/v2/#orders_create
		$data = array(
			'item_name' => $item_name,
			'payment_amount' => $payment_amount,
			'currency' => $currency,
			'quantity' => $quantity,
			'digital_goods_enabled' => $digital_goods_enabled,
		);
		
		//Set the additional args for the API call.
		$additional_args = array();
		$additional_args['return_response_body'] = true;

		//Create the order using the PayPal API.
		$api_injector = new PayPal_Request_API_Injector();
		$response = $api_injector->create_paypal_order_by_url_and_args( $data, $additional_args );
            
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

		$button_id = isset( $data['button_id'] ) ? sanitize_text_field( $data['button_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		PayPal_Utility_Functions::log( 'Received request - pp_capture_order. Order ID: ' . $order_id . ', Button ID: '.$button_id.', On Page Button ID: ' . $on_page_button_id, true );

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
			$paypal_capture_id = isset( $txn_data['id'] ) ? $txn_data['id'] : '';
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

		PayPal_Utility_Functions::log( 'PayPal Capture ID (Transaction ID): ' . $paypal_capture_id, true );

		//--
		// PayPal_Utility_Functions::log_array($data, true);//Debugging purpose.
		// PayPal_Utility_Functions::log_array($txn_data, true);//Debugging purpose.
		//--

		//Create the IPN data array from the transaction data.
		//Need to have the following values in the $data array.
		//['order_id']['button_id']['on_page_button_id']['item_name']['custom_field']		
		$ipn_data = PayPal_Utility_IPN_Related::create_ipn_data_array_from_capture_order_txn_data( $data, $txn_data );
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
		 * TODO: This is a plugin specific method,
		 * 
		 * FIXME: This need to rework or remove if needed.
		 */
		// PayPal_Utility_IPN_Related::handle_save_txn_data( $data, $txn_data, $ipn_data );

		/**
		 * Trigger the IPN processed action hook (so other plugins can can listen for this event).
		 * * Remember to use plugin shortname as prefix as tag when hooking to this hook.
		 */ 
		do_action( PayPal_Utility_Functions::hook('paypal_buy_now_checkout_ipn_processed'), $ipn_data );
		do_action( PayPal_Utility_Functions::hook('payment_ipn_processed'), $ipn_data );

		//Everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'order_id' => $order_id, 'capture_id' => $paypal_capture_id, 'txn_data' => $txn_data ) );
		exit;
	}	

}
