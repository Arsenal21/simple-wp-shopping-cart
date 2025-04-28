<?php

namespace TTHQ\WPSC\Lib\PayPal;

class PayPal_Utility_IPN_Related {

	public static function create_ipn_data_array_from_capture_order_txn_data( $data, $txn_data ) {
		$ipn_data = array();

		//$purchase_units = isset($txn_data['purchase_units']) ? $txn_data['purchase_units'] : array();
		//The $data['order_id'] is the ID for the order created using createOrder API call. The Transaction ID is the ID for the captured payment.
		$txn_id = isset($txn_data['purchase_units'][0]['payments']['captures'][0]['id']) ? $txn_data['purchase_units'][0]['payments']['captures'][0]['id'] : '';
		$ipn_data['txn_id'] = $txn_id;

		//Get the PayPal Order ID and add to the IPN data array.
		if(isset($data['order_id'])){
			$ipn_data['paypal_order_id'] = $data['order_id'];
		} else {
			//We can read the order_id from the txn_data response from PayPal API (if available)
			$ipn_data['paypal_order_id'] = isset($txn_data['id']) ? $txn_data['id'] : '';
		}

		//Get the custom field value from the request
		$custom = isset($data['custom_field']) ? $data['custom_field'] : '';
		$custom = urldecode( $custom );//Decode it just in case it was encoded.
				
		//Add the PayPal API order_id value to the custom field. So it gets saved with custom field data. 
		//This can be used to also save it to the reference DB column field when saving the transaction.		
		$data['custom_field'] = $custom . '&paypal_order_id=' . $ipn_data['paypal_order_id'];

		//Parse the custom field to read the IP address.
		$customvariables = PayPal_Utility_Functions::parse_custom_var( $custom );

		//Save cart ID to the IPN data array (useful so we don't have to call get_cart_id function again).
		$ipn_data['cart_id'] = isset($customvariables['wp_cart_id']) ? $customvariables['wp_cart_id'] : '';

		$ipn_data['gateway'] = 'paypal_ppcp';
		$ipn_data['txn_type'] = 'paypal_ppcp_checkout';
		$ipn_data['custom'] = isset($data['custom_field']) ? $data['custom_field'] : '';
		$ipn_data['subscr_id'] = $txn_id;//Same as txn_id for one-time payments.

		$ipn_data['item_number'] = isset($data['button_id']) ? $data['button_id'] : '';
		$ipn_data['item_name'] = isset($data['item_name']) ? $data['item_name'] : '';

		$ipn_data['status'] = isset($txn_data['status']) ? ucfirst( strtolower($txn_data['status']) ) : '';
		$ipn_data['payment_status'] = isset($txn_data['status']) ? ucfirst( strtolower($txn_data['status']) ) : '';

		//Amount
		if ( isset($txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['value']) ){
			//This is for PayPal checkout serverside capture.
			$ipn_data['mc_gross'] = $txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
		} else {
			$ipn_data['mc_gross'] = 0;
		}

		//Currency
		if ( isset($txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code']) ){
			//This is for PayPal checkout serverside capture.
			$ipn_data['mc_currency'] = $txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
		} else {
			$ipn_data['mc_currency'] = 0;
		}

		//Default to 1 for quantity.
		$ipn_data['quantity'] = 1;

		// Customer info.
		$ipn_data['ip_address'] = isset($customvariables['ip']) ? $customvariables['ip'] : '';
		$ipn_data['first_name'] = isset($txn_data['payer']['name']['given_name']) ? $txn_data['payer']['name']['given_name'] : '';
		$ipn_data['last_name'] = isset($txn_data['payer']['name']['surname']) ? $txn_data['payer']['name']['surname'] : '';
		$ipn_data['payer_email'] = isset($txn_data['payer']['email_address']) ? $txn_data['payer']['email_address'] : '';
		$ipn_data['payer_id'] = isset($txn_data['payer']['payer_id']) ? $txn_data['payer']['payer_id'] : '';

		//Address
		$address_street = isset($txn_data['purchase_units'][0]['shipping']['address']['address_line_1']) ? $txn_data['purchase_units'][0]['shipping']['address']['address_line_1'] : '';
		if ( isset ( $txn_data['purchase_units'][0]['shipping']['address']['address_line_2'] )){
			//If address line 2 is present, add it to the address.
			$address_street .= ", " . $txn_data['purchase_units'][0]['shipping']['address']['address_line_2'];
		}		
		$ipn_data['address_street'] = $address_street;
		$ipn_data['address_city'] = isset($txn_data['purchase_units'][0]['shipping']['address']['admin_area_2']) ? $txn_data['purchase_units'][0]['shipping']['address']['admin_area_2'] : '';
		$ipn_data['address_state'] = isset($txn_data['purchase_units'][0]['shipping']['address']['admin_area_1']) ? $txn_data['purchase_units'][0]['shipping']['address']['admin_area_1'] : '';
		$ipn_data['address_zip'] = isset($txn_data['purchase_units'][0]['shipping']['address']['postal_code']) ? $txn_data['purchase_units'][0]['shipping']['address']['postal_code'] : '';
		$country_code = isset($txn_data['purchase_units'][0]['shipping']['address']['country_code']) ? $txn_data['purchase_units'][0]['shipping']['address']['country_code'] : '';
		$ipn_data['address_country'] = PayPal_Utility_Functions::get_country_name_by_country_code($country_code);
		
		//Additional variables
		//Phone can be retrieved (if available) from the payer object by making a separate API call to /v2/customer
		$ipn_data['contact_phone'] = isset($txn_data['contact_phone']) ? $txn_data['contact_phone'] : '';

		$ipn_data['post_id'] = isset($data['cart_cpt_id']) ? $data['cart_cpt_id'] : '';

		return $ipn_data;
	}


	/**
	 * Validate that the transaction/order exists in PayPal and the price matches the price in the DB.
	 */
	public static function validate_buy_now_checkout_txn_data( $data, $txn_data ) {
		//Get the transaction/order details from PayPal API endpoint - /v2/checkout/orders/{$order_id}
		$pp_orderID = isset($data['order_id']) ? $data['order_id'] : '';
		$cart_id = isset($data['cart_id']) ? $data['cart_id'] : '';
		$cart_cpt_id = isset($data['cart_cpt_id']) ? $data['cart_cpt_id'] : '';

		$validation_error_msg = '';

		//This is for on-site checkout only. So the 'mode' and API creds will be whatever is currently set in the settings.
		$api_injector = new PayPal_Request_API_Injector();
		$order_details = $api_injector->get_paypal_order_details( $pp_orderID );
		if( $order_details !== false ){
			//The order details were retrieved successfully.
			if(is_object($order_details)){
				//Convert the object to an array.
				$order_details = json_decode(json_encode($order_details), true);
			}

			// Debug purpose only.
			// PayPal_Utility_Functions::log( 'PayPal Order Details: ', true );
			// PayPal_Utility_Functions::log_array( $order_details, true );

			// Check that the order's capture status is COMPLETED.
			$status = '';
			// Check if the necessary keys and arrays exist and are not empty
			if (!empty($order_details['purchase_units']) && !empty($order_details['purchase_units'][0]['payments']) && !empty($order_details['purchase_units'][0]['payments']['captures'])) {
				// Access the first item in the 'captures' array
				$capture = $order_details['purchase_units'][0]['payments']['captures'][0];
				$capture_id = isset($capture['id']) ? $capture['id'] : '';
				// Check if 'status' is set for the capture
				if (isset($capture['status'])) {
					// Extract the 'status' value
					$status = $capture['status'];
				}
			}
			if ( strtolower($status) != strtolower('COMPLETED') ) {
				//The order is not completed yet.
				$validation_error_msg = 'Validation Error! The transaction status is not completed yet. Cart ID: ' . $cart_id . ', PayPal Capture ID: ' . $capture_id . ', Capture Status: ' . $status;
				PayPal_Utility_Functions::log( $validation_error_msg, false );
				return $validation_error_msg;
			}

			//Check that the amount matches with what we expect.
			$amount = isset($order_details['purchase_units'][0]['amount']['value']) ? $order_details['purchase_units'][0]['amount']['value'] : 0;

			$payment_amount_expected = get_post_meta( $cart_cpt_id, 'expected_payment_amount', true );
			if( floatval($amount) < floatval($payment_amount_expected) ){
				//The amount does not match.
				$validation_error_msg = 'Validation Error! The payment amount does not match. Cart ID: ' . $cart_id . ', PayPal Order ID: ' . $pp_orderID . ', Amount Received: ' . $amount . ', Amount Expected: ' . $payment_amount_expected;
				PayPal_Utility_Functions::log( $validation_error_msg, false );
				return $validation_error_msg;
			}

			//Check that the currency matches with what we expect.
			$currency = isset($order_details['purchase_units'][0]['amount']['currency_code']) ? $order_details['purchase_units'][0]['amount']['currency_code'] : '';
			$currency_expected = get_post_meta( $cart_cpt_id, 'expected_currency', true );
			if( $currency != $currency_expected ){
				//The currency does not match.
				$validation_error_msg = 'Validation Error! The payment currency does not match. Cart ID: ' . $cart_id . ', PayPal Order ID: ' . $pp_orderID . ', Currency Received: ' . $currency . ', Currency Expected: ' . $currency_expected;
				PayPal_Utility_Functions::log( $validation_error_msg, false );
				return $validation_error_msg;
			}

		} else {
			//Error getting subscription details.
			$validation_error_msg = 'Validation Error! Failed to get transaction/order details from the PayPal API. PayPal Order ID: ' . $pp_orderID;
			//TODO - Show additional error details if available.
			PayPal_Utility_Functions::log( $validation_error_msg, false );
			return $validation_error_msg;
		}

		//All good. The data is valid.
		return true;
	}

	/**
	 * TODO: This is a plugin specific method.
	 */
	public static function complete_post_payment_processing( $data, $txn_data, $ipn_data){
		//Check if this is a duplicate notification.
		if( PayPal_Utility_IPN_Related::is_txn_already_processed($ipn_data)){
			//This transaction notification has already been processed. So we don't need to process it again.
			return true;
		}

		\WPSC_Post_Payment_Related::add_additional_data_to_ipn_data( $ipn_data );

		\WPSC_Post_Payment_Related::save_txn_record( $ipn_data );

		\WPSC_Post_Payment_Related::send_notification_email( $ipn_data );

		\WPSC_Post_Payment_Related::affiliate_plugin_integration( $ipn_data );

		\WPSC_Post_Payment_Related::do_cleanup_after_txn( $ipn_data);

		return true;
	}

	public static function is_txn_already_processed( $ipn_data ){
		// Query the DB to check if we have already processed this transaction or not.
		global $wpdb;
		$txn_id = isset($ipn_data['txn_id']) ? $ipn_data['txn_id'] : '';
		$payer_email = isset($ipn_data['payer_email']) ? $ipn_data['payer_email'] : '';
		/**
		 * TODO: This query is plugin specific,
		 * 
		 * FIXME: This need to be changed/modified.
		 */
		//Need to retrieve the shopping cart's order id (for the cusotm post type 'wpsc_cart_orders').
		$order_id = isset($ipn_data['order_id']) ? $ipn_data['order_id'] : '';
		$processed = wpsc_is_txn_already_processed($order_id, $ipn_data);
		if ($processed) {
			// And if we have already processed it, do nothing and return true
			PayPal_Utility_Functions::log( "This transaction has already been processed (Txn ID: ".$txn_id.", Payer Email: ".$payer_email."). This looks to be a duplicate notification. Nothing to do here.", true );
			return true;
		}
		return false;
	}
        
}