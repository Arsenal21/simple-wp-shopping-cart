<?php

namespace TTHQ\WPSC\Lib\PayPal;

/**
 * This clcass handles the ajax request from the PayPal OnApprove event (the onApprove event is triggered from the Button's JS code on successful transaction). 
 * It creates the required $ipn_data array from the transaction so it can be fed into the existing IPN handler functions easily.
 */
class PayPal_OnApprove_IPN_Handler {

	public $ipn_data  = array();

	public function __construct() {
		//Handle it at 'wp_loaded' since custom post types will also be available at that point.
		add_action( 'wp_loaded', array(&$this, 'setup_ajax_request_actions' ) );
	}

	/**
	 * Setup the ajax request actions.
	 */
	public function setup_ajax_request_actions() {
		//Handle the onApprove ajax request for 'Subscription' type buttons
		add_action( PayPal_Utility_Functions::hook('onapprove_create_subscription', true), array(&$this, 'onapprove_create_subscription' ) );
		add_action( PayPal_Utility_Functions::hook('onapprove_create_subscription', true, true), array(&$this, 'onapprove_create_subscription' ) );
	}

	/**
	 * Handle the onApprove ajax request for 'Subscription' type buttons
	 */
    public function onapprove_create_subscription(){

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
		//PayPal_Utility_Functions::log_array( $data, true );//Debugging only

		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		PayPal_Utility_Functions::log( 'OnApprove ajax request received for createSubscription. On Page Button ID: ' . $on_page_button_id, true );

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

		//Get the transaction data from the request
		$txn_data = isset( $_POST['txn_data'] ) ? stripslashes_deep( $_POST['txn_data'] ) : array();
		if ( empty( $txn_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty transaction data received.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}
		//PayPal_Utility_Functions::log_array( $txn_data, true );//Debugging only.

		//Create the IPN data array from the transaction data.
		$this->create_ipn_data_array_from_create_subscription_txn_data( $data, $txn_data );
		//PayPal_Utility_Functions::log_array( $this->ipn_data, true );//Debugging only.
		
		//Validate the subscription txn data before using it.
		$validation_response = $this->validate_subscription_checkout_txn_data( $data, $txn_data );
		if( $validation_response !== true ){
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => $validation_response,
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
		// PayPal_Utility_IPN_Related::complete_post_payment_processing( $data, $txn_data, $this->ipn_data );

		/**
		 * Trigger the IPN processed action hook (so other plugins can can listen for this event).
		 * * Remember to use plugin shortname as prefix as tag when hooking to this hook.
		 * * i. e. 'paypal_subscription_checkout_ipn_processed' is actually '<prefix>_paypal_subscription_checkout_ipn_processed'
		 */ 
		do_action( PayPal_Utility_Functions::hook('paypal_subscription_checkout_ipn_processed'), $this->ipn_data );
		do_action( PayPal_Utility_Functions::hook('payment_ipn_processed'), $this->ipn_data );

		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true ) );
		exit;
    }

	public function create_ipn_data_array_from_create_subscription_txn_data( $data, $txn_data ) {
		$ipn = array();

		//Get the custom field value from the request
		$custom = isset($data['custom_field']) ? $data['custom_field'] : '';
		$custom = urldecode( $custom );//Decode it just in case it was encoded.

		if(isset($data['orderID'])){
			//Add the PayPal API orderID value to the reference parameter. So it gets saved with custom field data. This will be used to also save it to the reference DB column field when saving the transaction.
			$data['custom_field'] = $custom . '&reference=' . $data['orderID'];
		}

		$customvariables = PayPal_Utility_Functions::parse_custom_var( $custom );

		$billing_info = isset($txn_data['billing_info']) ? $txn_data['billing_info'] : array();

		$address_street = isset($txn_data['subscriber']['shipping_address']['address']['address_line_1']) ? $txn_data['subscriber']['shipping_address']['address']['address_line_1'] : '';
		if ( isset ( $txn_data['subscriber']['shipping_address']['address']['address_line_2'] )){
			//If address line 2 is present, add it to the address.
			$address_street .= ", " . $txn_data['subscriber']['shipping_address']['address']['address_line_2'];
		}

		$ipn['gateway'] = 'paypal_subscription_checkout';
		$ipn['txn_type'] = 'pp_subscription_new';		
		$ipn['custom'] = isset($data['custom_field']) ? $data['custom_field'] : '';
		$ipn['item_number'] = isset($data['button_id']) ? $data['button_id'] : '';
		$ipn['item_name'] = isset($data['item_name']) ? $data['item_name'] : '';

		//This is the PayPal orderID value of the V2 order API. It's not the actual transaction ID of the payment. We can query the Orders API to retrieve the actual transaction ID (if needed).
		$ipn['txn_id'] = isset($data['orderID']) ? $data['orderID'] : '';
		$ipn['subscr_id'] = isset($data['subscriptionID']) ? $data['subscriptionID'] : '';

		$ipn['plan_id'] = isset($txn_data['plan_id']) ? $txn_data['plan_id'] : '';
		$ipn['create_time'] = isset($txn_data['create_time']) ? $txn_data['create_time'] : '';

		$ipn['status'] = __('subscription created', 'wordpress-simple-paypal-shopping-cart');
		$ipn['payment_status'] = __('subscription created', 'wordpress-simple-paypal-shopping-cart');
		$ipn['subscription_status'] = isset($txn_data['status']) ? $txn_data['status'] : '';//Can be used to check if the subscription is active or not (in the webhook handler)

		//Amount and currency.
		$ipn['mc_gross'] = isset($txn_data['billing_info']['last_payment']['amount']['value']) ? $txn_data['billing_info']['last_payment']['amount']['value'] : 0;
		$ipn['mc_currency'] = isset($txn_data['billing_info']['last_payment']['amount']['currency_code']) ? $txn_data['billing_info']['last_payment']['amount']['currency_code'] : '';
		if( $this->is_trial_payment( $billing_info )){
			//TODO: May need to get the trial amount from the 'cycle_executions' array
			$ipn['is_trial_txn'] = 'yes';
		}
		$ipn['quantity'] = 1;

		// customer info.
		$ipn['ip'] = isset($customvariables['ip']) ? $customvariables['ip'] : '';
		$ipn['first_name'] = isset($txn_data['subscriber']['name']['given_name']) ? $txn_data['subscriber']['name']['given_name'] : '';
		$ipn['last_name'] = isset($txn_data['subscriber']['name']['surname']) ? $txn_data['subscriber']['name']['surname'] : '';
		$ipn['payer_email'] = isset($txn_data['subscriber']['email_address']) ? $txn_data['subscriber']['email_address'] : '';
		$ipn['payer_id'] = isset($txn_data['subscriber']['payer_id']) ? $txn_data['subscriber']['payer_id'] : '';
		$ipn['address_street'] = $address_street;
		$ipn['address_city']    = isset($txn_data['subscriber']['shipping_address']['address']['admin_area_2']) ? $txn_data['subscriber']['shipping_address']['address']['admin_area_2'] : '';
		$ipn['address_state']   = isset($txn_data['subscriber']['shipping_address']['address']['admin_area_1']) ? $txn_data['subscriber']['shipping_address']['address']['admin_area_1'] : '';
		$ipn['address_zip']     = isset($txn_data['subscriber']['shipping_address']['address']['postal_code']) ? $txn_data['subscriber']['shipping_address']['address']['postal_code'] : '';
		$country_code = isset($txn_data['subscriber']['shipping_address']['address']['country_code']) ? $txn_data['subscriber']['shipping_address']['address']['country_code'] : '';
		$ipn['address_country'] = PayPal_Utility_Functions::get_country_name_by_country_code($country_code);
		//Additional variables
		//$ipn['reason_code'] = $txn_data['reason_code'];

		$this->ipn_data = $ipn;
	}

	public function is_trial_payment( $billing_info ) {
		if( isset( $billing_info['cycle_executions'][0]['tenure_type'] ) && ($billing_info['cycle_executions'][0]['tenure_type'] === 'TRIAL')){
			return true;
		}
		return false;
	}

	/**
	 * Validate that the subscription exists in PayPal and the price matches the price in the DB.
	 */
	public function validate_subscription_checkout_txn_data( $data, $txn_data ) {
		//Get the subscription details from PayPal API endpoint - v1/billing/subscriptions/{$subscription_id}
		$subscription_id = $data['subscriptionID'];
		$button_id = $data['button_id'];

		$validation_error_msg = '';

		//This is for on-site checkout only. So the 'mode' and API creds will be whatever is currently set in the settings.
		$api_injector = new PayPal_Request_API_Injector();
		$sub_details = $api_injector->get_paypal_subscription_details( $subscription_id );
		if( $sub_details !== false ){
			$billing_info = $sub_details->billing_info;
			if(is_object($billing_info)){
				//Convert the object to an array.
				$billing_info = json_decode(json_encode($billing_info), true);
			}
			//PayPal_Utility_Functions::log_array( $billing_info, true );//Debugging only.
			
			$tenure_type = isset($billing_info['cycle_executions'][0]['tenure_type']) ? $billing_info['cycle_executions'][0]['tenure_type'] : ''; //'REGULAR' or 'TRIAL'
			$sequence = isset($billing_info['cycle_executions'][0]['sequence']) ? $billing_info['cycle_executions'][0]['sequence'] : '';//1, 2, 3, etc.
			$cycles_completed = isset($billing_info['cycle_executions'][0]['cycles_completed']) ? $billing_info['cycle_executions'][0]['cycles_completed'] : '';//1, 2, 3, etc.
			PayPal_Utility_Functions::log( 'Subscription tenure type: ' . $tenure_type . ', Sequence: ' . $sequence . ', Cycles Completed: '. $cycles_completed, true );			

			//Tenure type - 'REGULAR' or 'TRIAL'
			$tenure_type = isset($billing_info['cycle_executions'][0]['tenure_type']) ? $billing_info['cycle_executions'][0]['tenure_type'] : 'REGULAR';
			//If tenure type is 'TRIAL', check that this button has a trial period.
			if( $tenure_type === 'TRIAL' ){
				PayPal_Utility_Functions::log('Trial payment detected.', true);//TODO - remove later.

				//Check that the button has a trial period.
				$trial_billing_cycle = get_post_meta( $button_id, 'trial_billing_cycle', true );
				if( empty($trial_billing_cycle) ){
					//This button does not have a trial period. So this is not a valid trial payment.
					$validation_error_msg = 'Validation Error! This is a trial payment but the button does not have a trial period configured. Button ID: ' . $button_id . ', Subscription ID: ' . $subscription_id;
					PayPal_Utility_Functions::log( $validation_error_msg, false );
					return $validation_error_msg;
				}
			} else {
				//This is a regular subscription checkout (without trial). Check that the price matches.
				$amount = isset($billing_info['last_payment']['amount']['value']) ? $billing_info['last_payment']['amount']['value'] : 0;
				$recurring_billing_amount = get_post_meta( $button_id, 'recurring_billing_amount', true );
				if( $amount < $recurring_billing_amount ){
					//The amount does not match.
					$validation_error_msg = 'Validation Error! The subscription amount does not match. Button ID: ' . $button_id . ', Subscription ID: ' . $subscription_id . ', Amount Received: ' . $amount . ', Amount Expected: ' . $recurring_billing_amount;
					PayPal_Utility_Functions::log( $validation_error_msg, false );
					return $validation_error_msg;
				}
				//Check that the Currency code matches
				$currency = isset($billing_info['last_payment']['amount']['currency_code']) ? $billing_info['last_payment']['amount']['currency_code'] : '';
				$currency_expected = get_post_meta( $button_id, 'payment_currency', true );
				if( $currency !== $currency_expected ){
					//The currency does not match.
					$validation_error_msg = 'Validation Error! The subscription currency does not match. Button ID: ' . $button_id . ', Subscription ID: ' . $subscription_id . ', Currency Received: ' . $currency . ', Currency Expected: ' . $currency_expected;
					PayPal_Utility_Functions::log( $validation_error_msg, false );
					return $validation_error_msg;
				}
			}

		} else {
			//Error getting subscription details.
			$validation_error_msg = 'Validation Error! Failed to get subscription details from the PayPal API. Subscription ID: ' . $subscription_id;
			//TODO - Show additional error details if available.
			PayPal_Utility_Functions::log( $validation_error_msg, false );
			return $validation_error_msg;
		}

		//All good. The data is valid.
		return true;
	}

}
