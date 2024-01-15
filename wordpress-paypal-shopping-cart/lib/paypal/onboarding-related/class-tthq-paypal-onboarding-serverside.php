<?php

namespace TTHQ\WPSC\Lib\PayPal\Onboarding;

use TTHQ\WPSC\Lib\PayPal\PayPal_Utility_Functions;
use TTHQ\WPSC\Lib\PayPal\PayPal_Request_API;
use TTHQ\WPSC\Lib\PayPal\PayPal_Bearer;
use TTHQ\WPSC\Lib\PayPal\PayPal_PPCP_Config;

/**
 * Handles server side tasks during PPCP onboarding.
 */
class PayPal_PPCP_Onboarding_Serverside {

	public function __construct() {

		//Setup AJAX request handler for the onboarding process.
		add_action( PayPal_Utility_Functions::hook('handle_onboarded_callback_data', true), array(&$this, 'handle_onboarded_callback_data' ) );
		add_action( PayPal_Utility_Functions::hook('handle_onboarded_callback_data', true, true), array(&$this, 'handle_onboarded_callback_data' ) );

	}

	public function handle_onboarded_callback_data(){
		//Handle the data sent by PayPal after the onboarding process.
		//The get_option('<prefix>_ppcp_connect_query_args_'.$environment_mode) will give you the query args that you sent to the PayPal onboarding page

		PayPal_Utility_Functions::log( 'Onboarding step: handle_onboarded_callback_data.', true );

		//Get the data from the request
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Empty data received.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}

        $data_array = json_decode($data, true);
		//TODO - Debugging purpose only
        //PayPal_Utility_Functions::log_array( $data_array, true );

		//Check nonce.
        $nonce_string = PayPal_PPCP_Onboarding::$account_connect_string;
		if ( ! check_ajax_referer( $nonce_string, '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}

		//Get the environment mode.
		$environment_mode = isset( $data_array['environment'] ) ? $data_array['environment'] : 'production';

		//=== Generate the access token using the shared id and auth code. ===
        $access_token = $this->generate_token_using_shared_id( $data_array['sharedId'], $data_array['authCode'], $environment_mode);
		if ( ! $access_token ) {
			//Failed to generate token.
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Failed to generate access token. check debug log file for any error message.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}

		//=== Get the seller API credentials using the access token. ===
		//PayPal_Utility_Functions::log( 'Onboarding step: access token generated successfully. Token: ' . $access_token, true );//Debug purpose only
		$seller_api_credentials = $this->get_seller_api_credentials_using_token( $access_token, $environment_mode );
		if ( ! $seller_api_credentials ) {
			//Failed to get seller API credentials.
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Failed to get seller API credentials. check debug log file for any error message.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}

		//Save the credentials to the database.
		$this->save_seller_api_credentials( $seller_api_credentials, $environment_mode);

		//=== Bearer token ===
		//Let's use the already generated access token throughout the onboarding process.
		$bearer_token = $access_token;
		//PayPal_Utility_Functions::log( 'Onboarding step: using access token from the previous step. Token: ' . $bearer_token, true );//Debug purpose only

		//=== Seller account status ===
		$seller_account_status = $this->get_seller_account_status_data_using_bearer_token($bearer_token, $seller_api_credentials, $environment_mode );
		PayPal_Utility_Functions::log_array( $seller_account_status, true );
		if( ! $seller_account_status ){
			//Failed to get seller account status.
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Failed to get seller account status. check debug log file for any error message.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);			
		}

		//Save the seller paypal email to the database.
		//The paypal email address of the seller will be available in the 'tracking_id' field of the seller_account_status array.
		$seller_paypal_email = isset( $seller_account_status['tracking_id'] )? $seller_account_status['tracking_id'] : '';
		$this->save_seller_paypal_email( $seller_paypal_email, $environment_mode );

		//Check if the seller account is limited or not.
		if( ! $seller_account_status['payments_receivable'] ){
			//Seller account is limited. Show a message to the seller.
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Your PayPal account is limited so you cannot accept payment. Contact PaPal support or check your PayPal account inbox for an email from PayPal for the next steps to remove the account limit.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);			
		}
		if( ! $seller_account_status['primary_email_confirmed'] ){
			//Seller account is limited. Show a message to the seller.
			wp_send_json(
				array(
					'success' => false,
					'msg'  => __( 'Your PayPal account email is not confirmed. Check your PayPal account inbox for an email from PayPal to confirm your PayPal email address.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);			
		}
		PayPal_Utility_Functions::log( 'PPCP_STANDARD vetting_status: ' . $seller_account_status['ppcp_standard_vetting_status'], true );
		PayPal_Utility_Functions::log( 'CUSTOM_CARD_PROCESSING status: ' . $seller_account_status['custom_card_processing_status'], true );

		//Webhooks will be created (if not already created) when the admin creates subsription payment buttons

		//Save the onboarding complete and other related flag to the database.
		$settings = PayPal_PPCP_Config::get_instance();
		$settings->set_value('paypal-ppcp-vetting-status-'.$environment_mode, $seller_account_status['ppcp_standard_vetting_status']); // ACDC Related - PPCP_STANDARD vetting_status
		$settings->set_value('paypal-ppcp-custom-card-processing-status-'.$environment_mode, $seller_account_status['custom_card_processing_status']); // ACDC Related - CUSTOM_CARD_PROCESSING Status
		$settings->set_value('paypal-ppcp-onboarding-'.$environment_mode, 'completed');
		$settings->save();

		//Delete any cached token using the old credentials (so it is forced to generate and cache a new one after onboarding (when new API call is made)))
		PayPal_Bearer::delete_cached_token();
				
        PayPal_Utility_Functions::log( 'Successfully processed the handle_onboarded_callback_data. Environment mode: '.$environment_mode, true );

		//If everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'msg' => 'Succedssfully processed the handle_onboarded_callback_data.' ) );
		exit;

	}


	/*
	 * Gets the seller's account status data. So we can check if payments_receivable flag is true and primary_email_confirmed flag is true
	 * Returns an array with client_id and client_secret or false otherwise.
	 */
	public function get_seller_account_status_data_using_bearer_token($bearer_token, $seller_api_credentials, $environment_mode = 'production'){
		PayPal_Utility_Functions::log( 'Onboarding step: get_seller_account_status_data. Environment mode: ' . $environment_mode, true );

		$api_base_url = PayPal_Utility_Functions::get_api_base_url_by_environment_mode( $environment_mode );
		$partner_id = PayPal_Utility_Functions::get_partner_id_by_environment_mode( $environment_mode );

		$url = trailingslashit( $api_base_url ) . 'v1/customer/partners/' . $partner_id . '/merchant-integrations/' . $seller_api_credentials['payer_id'];	
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $bearer_token,
				'PayPal-Partner-Attribution-Id' => 'TipsandTricks_SP_PPCP',
			),
		);
		//Debug purpose only
		//PayPal_Utility_Functions::log( 'PayPal API request headers for getting seller account status: ', true );
		//PayPal_Utility_Functions::log_array( $args, true);		

		$response = PayPal_Request_API::send_request_by_url_and_args( $url, $args );

		if ( is_wp_error( $response ) ) {
			//WP could not post the request.
			$error_msg = $response->get_error_message();//Get the error from the WP_Error object.
			PayPal_Utility_Functions::log( 'Failed to post the request to the PayPal API. Error: ' . $error_msg, false );
			return false;
		}

		$json = json_decode( wp_remote_retrieve_body( $response ) );
		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			//PayPal API returned an error.
			$response_body = wp_remote_retrieve_body( $response );
			PayPal_Utility_Functions::log( 'PayPal API returned an error. Status Code: ' . $status_code . ' Response Body: ' . $response_body, false );
			return false;
		}

		if ( ! isset( $json->payments_receivable ) || ! isset( $json->primary_email_confirmed ) ) {
			//Seller status not found. Log error.
			if (isset( $json->error )) {
				//Try to get the error descrption (if present)
				$error_msg = isset($json->error_description)? $json->error_description : $json->error;
			} else {
				$error_msg = 'The payments_receivable and primary_email_confirmed flags are not set.';
			}
			PayPal_Utility_Functions::log( 'Failed to get seller PayPal account status. Status code: '.$status_code.', Error msg: ' . $error_msg, false );
			return false;
		}

		// ACDC Related - Check 'PPCP_STANDARD' vetting_status & 'CUSTOM_CARD_PROCESSING' Status
		// Check if products is set and is an array
		$ppcp_standard_vetting_status = '';
		if (isset($json->products) && is_array($json->products)) {
			foreach ($json->products as $product) {
				// Check if the name property exists and equals 'PPCP_STANDARD'
				if (isset($product->name) && $product->name === 'PPCP_STANDARD') {
					// Check if vetting_status property exists
					if (isset($product->vetting_status)) {
						$ppcp_standard_vetting_status = $product->vetting_status;
						// Break the loop if the desired product is found
						break;
					}
				}
			}
		}

		// Check if capabilities is set and is an array
		$custom_card_processing_status = '';
		if (isset($json->capabilities) && is_array($json->capabilities)) {
			foreach ($json->capabilities as $capability) {
				// Check if the name property exists and equals 'CUSTOM_CARD_PROCESSING'
				if (isset($capability->name) && $capability->name === 'CUSTOM_CARD_PROCESSING') {
					// Check if status property exists
					if (isset($capability->status)) {
						$custom_card_processing_status = $capability->status;
						// We found the status, no need to continue the loop
						break;
					}
				}
			}
		}

		//Success. return the data we will use.
		return array(
			'merchant_id' => $json->merchant_id,
			'tracking_id' => $json->tracking_id,/* This will be the paypal account email address */
			'payments_receivable' => $json->payments_receivable,
			'primary_email_confirmed' => $json->primary_email_confirmed,
			'ppcp_standard_vetting_status' => $ppcp_standard_vetting_status, // ACDC Related - PPCP_STANDARD vetting_status
			'custom_card_processing_status' => $custom_card_processing_status, // ACDC Related - CUSTOM_CARD_PROCESSING Status
		);

	}

	public function save_seller_paypal_email( $seller_paypal_email, $environment_mode = 'production' ) {
		//This is saved as a separate method because the seller paypal email is not available in the get seller api credentials call.
		//The seller paypal email is available in the get seller account status call.
		$settings = PayPal_PPCP_Config::get_instance();

		if( $environment_mode == 'sandbox' ){
			$settings->set_value('paypal-sandbox-seller-paypal-email', $seller_paypal_email);
		} else {
			$settings->set_value('paypal-live-seller-paypal-email', $seller_paypal_email);
		}

		$settings->save();
		PayPal_Utility_Functions::log( 'Seller PayPal email address ('.$seller_paypal_email.') saved successfully (environment mode: '.$environment_mode.').', true );
	}

	public function save_seller_api_credentials( $seller_api_credentials, $environment_mode = 'production' ) {
		// Save the API credentials to the database.
		$settings = PayPal_PPCP_Config::get_instance();

		if( $environment_mode == 'sandbox' ){
			//Sandobx mode
			$settings->set_value('paypal-sandbox-client-id', $seller_api_credentials['client_id']);
			$settings->set_value('paypal-sandbox-secret-key', $seller_api_credentials['client_secret']);
			$settings->set_value('paypal-sandbox-seller-merchant-id', $seller_api_credentials['payer_id']);//Seller Merchant ID
		} else {
			//Production mode
			$settings->set_value('paypal-live-client-id', $seller_api_credentials['client_id']);
			$settings->set_value('paypal-live-secret-key', $seller_api_credentials['client_secret']);
			$settings->set_value('paypal-live-seller-merchant-id', $seller_api_credentials['payer_id']);//Seller Merchant ID
		}

		$settings->save();
		PayPal_Utility_Functions::log( 'Seller API credentials (environment mode: '.$environment_mode.') saved successfully.', true );
	}

	public static function reset_seller_api_credentials( $environment_mode = 'production' ) {
		// Save the API credentials to the database.
		$settings = PayPal_PPCP_Config::get_instance();

		if( $environment_mode == 'sandbox' ){
			//Sandobx mode
			$settings->set_value('paypal-sandbox-client-id', '');
			$settings->set_value('paypal-sandbox-secret-key', '');
			$settings->set_value('paypal-sandbox-seller-merchant-id', '');//Seller Merchant ID
			$settings->set_value('paypal-sandbox-seller-paypal-email', '');//Seller PayPal Email
		} else {
			//Production mode
			$settings->set_value('paypal-live-client-id', '');
			$settings->set_value('paypal-live-secret-key', '');
			$settings->set_value('paypal-live-seller-merchant-id', '');//Seller Merchant ID
			$settings->set_value('paypal-live-seller-paypal-email', '');//Seller PayPal Email
		}

		//Reset the onboarding complete flag (for the corresponding mode) to the database.
		$settings->set_value('paypal-ppcp-onboarding-'.$environment_mode, '');

		//Save the settings
		$settings->save();
		PayPal_Utility_Functions::log( 'Seller API credentials (environment mode: '.$environment_mode.') reset/removed successfully.', true );

		//Delete any cached token using the old credentials (so it is forced to generate and cache a new one after onboarding (when new API call is made)))
		PayPal_Bearer::delete_cached_token();
		PayPal_Utility_Functions::log( 'Executed delete PayPal bearer cached token function (to clean it up).', true );
	}

	/**
	 * Generates a token using the shared_id and auth_token and seller_nonce. Used during the onboarding process.
	 *
	 * @param string $shared_id The shared id.
	 * @param string $auth_code The auth code.
	 * @param string $environment_mode The environment mode. sandbox or production.
	 * 
	 * Returns the token or false otherwise.
	 */
	public function generate_token_using_shared_id( $shared_id, $auth_code, $environment_mode = 'production' ) {
		PayPal_Utility_Functions::log( 'Onboarding step: generate_token_using_shared_id. Environment mode: ' . $environment_mode, true );

		if( isset($environment_mode) && $environment_mode == 'sandbox' ){
			$query_args = PayPal_Utility_Functions::get_option('ppcp_connect_query_args_'.$environment_mode);
			$seller_nonce = isset($query_args['sellerNonce']) ? $query_args['sellerNonce'] : '';
		} else {
			$query_args = PayPal_Utility_Functions::get_option('ppcp_connect_query_args_'.$environment_mode);
			$seller_nonce = isset($query_args['sellerNonce']) ? $query_args['sellerNonce'] : '';
		}
		PayPal_Utility_Functions::log( 'Seller nonce value: ' . $seller_nonce, true );

		$api_base_url = PayPal_Utility_Functions::get_api_base_url_by_environment_mode( $environment_mode );

		$url = trailingslashit( $api_base_url ) . 'v1/oauth2/token/';

		//Note: we don't have the seller merchant ID yet. So cannot use the auth assertion header.
		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $shared_id . ':' ),
			),
			'body' => array(
				'grant_type' => 'authorization_code',
				'code' => $auth_code,
				'code_verifier' => $seller_nonce,
			),
		);

		//PayPal_Utility_Functions::log_array( $args, true);//Debugging purpose
		$response = PayPal_Request_API::send_request_by_url_and_args( $url, $args );
		//PayPal_Utility_Functions::log_array( $response, true);//Debugging purpose

		if ( is_wp_error( $response ) ) {
			//WP could not post the request.
			$error_msg = $response->get_error_message();//Get the error from the WP_Error object.
			PayPal_Utility_Functions::log( 'Failed to post the request to the PayPal API. Error: ' . $error_msg, false );
			return false;
		}

		$json = json_decode( $response['body'] );
		$status_code = (int) wp_remote_retrieve_response_code( $response );//HTTP response code (ex: 400)
		if ( ! isset( $json->access_token ) ) {
			//No token found. Log error.
			if (isset( $json->error )) {
				//Try to get the error descrption (if present)
				$error_msg = isset($json->error_description) ? $json->error_description : $json->error;
			} else {
				$error_msg = 'No token found.';
			}
			PayPal_Utility_Functions::log( 'Failed to generate token. Status code: '.$status_code.', Error msg: ' . $error_msg, false );
			return false;
		}

		//Success. return the token.
		return (string) $json->access_token;
	}

	/*
	 * Gets the seller's API credentials using the access token.
	 * Returns an array with client_id and client_secret or false otherwise.
	 */
	public function get_seller_api_credentials_using_token($access_token, $environment_mode = 'production'){
		PayPal_Utility_Functions::log( 'Onboarding step: get_seller_api_credentials_using_token. Environment mode: ' . $environment_mode, true );

		$api_base_url = PayPal_Utility_Functions::get_api_base_url_by_environment_mode( $environment_mode );
		$partner_merchant_id = PayPal_Utility_Functions::get_partner_id_by_environment_mode( $environment_mode );

		$url = trailingslashit( $api_base_url ) . 'v1/customer/partners/' . $partner_merchant_id . '/merchant-integrations/credentials/';
		
		//Note: we don't have the seller merchant ID yet. So cannot use the auth assertion header.
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
		);

		$response = PayPal_Request_API::send_request_by_url_and_args( $url, $args );

		if ( is_wp_error( $response ) ) {
			//WP could not post the request.
			$error_msg = $response->get_error_message();//Get the error from the WP_Error object.
			PayPal_Utility_Functions::log( 'Failed to post the request to the PayPal API. Error: ' . $error_msg, false );
			return false;
		}

		$json = json_decode( $response['body'] );
		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( ! isset( $json->client_id ) || ! isset( $json->client_secret ) ) {
			//Seller API credentials not found. Log error.
			if (isset( $json->error )) {
				//Try to get the error descrption (if present)
				$error_msg = isset($json->error_description)? $json->error_description : $json->error;
			} else {
				$error_msg = 'No client_id or client_secret found.';
			}
			PayPal_Utility_Functions::log( 'Failed to get seller API credentials. Status code: '.$status_code.', Error msg: ' . $error_msg, false );
			return false;
		}

		//Success. return the credentials.
		return array(
			'client_id' => $json->client_id,
			'client_secret' => $json->client_secret,
			'payer_id' => $json->payer_id,
		);

	}

}