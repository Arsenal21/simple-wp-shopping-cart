<?php

namespace TTHQ\WPSC\Lib\PayPal;

/**
 * PayPal REST API request class.
 */
class PayPal_Request_API {

	protected static $instance;

	protected $client_id;
	protected $secret;
	protected $basic_auth_string;

	public $environment_mode = 'production'; //sandbox or production
	public $sandbox_api_base_url = 'https://api-m.sandbox.paypal.com';
	public $production_api_base_url = 'https://api-m.paypal.com';

	public $last_error;

	public $app_info = array(
		'name' => 'Simple Shopping Cart',
		'url' => 'https://wordpress.org/plugins/wordpress-simple-paypal-shopping-cart/',
	);

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function set_mode_and_api_credentials( $mode, $live_client_id, $live_secret, $sandbox_client_id, $sandbox_secret ) {
		$this->set_api_environment_mode( $mode );
		if ( $mode == 'production' ) {
			$client_id = $live_client_id;
			$secret = $live_secret;
		} else {
			$client_id = $sandbox_client_id;
			$secret = $sandbox_secret;
		}
		$this->set_api_credentials( $client_id, $secret );
	}

	public function set_api_credentials( $client_id, $secret ) {
		if( empty( $client_id ) || empty( $secret ) ){
			wp_die( "PayPal API credentials are not set. Missing Client ID or Secret Key. Please set them in the plugin's payment settings page." );
		}
		$this->client_id = $client_id;
		$this->secret = $secret;
		$this->basic_auth_string = base64_encode( $this->client_id . ":" . $this->secret );
	}

	public function set_api_environment_mode( $mode = 'production' ) {
		$this->environment_mode = $mode;
	}

	public function get_api_environment_mode() {
		return $this->environment_mode;
	}

	public function get_api_base_url() {
		if ($this->environment_mode == 'production') {
			return $this->production_api_base_url;
		} else {
			return $this->sandbox_api_base_url;
		}
	}

	/*
	 * Useful to encode params for HTTP GET request where an array can be in the URL.
	 */
	private function encode_params( $d ) {
		if (true === $d) {
			return 'true';
		}
		if (false === $d) {
			return 'false';
		}
		if (is_array( $d )) {
			$res = array();
			foreach ( $d as $k => $v ) {
				$res[ $k ] = $this->encode_params( $v );
			}
			return $res;
		}
		return $d;
	}

	private function before_request() {
		//Reset the last_error variable before making a request.
		$this->last_error = array();
	}

	public function get_last_error() {
		return $this->last_error;
	}

	/**
	 * Headers to use when making API requests using basic auth. We don't use this normally (as we use the bearer token method).
	 */
	private function get_headers() {
		$ua_string = $this->format_app_info_to_string( $this->app_info );

		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Basic ' . $this->basic_auth_string,
			'User-Agent' => $ua_string,
			'PayPal-Partner-Attribution-Id' => 'TipsandTricks_SP_PPCP',
		);
		return $headers;
	}

	/**
	 * Headers to use when making API requests using a bearer token.
	 */
	private function get_headers_using_bearer_token() {	
		//Get the bearer token at the time of the request (so if a cached token is used, it's validity gets checked before each request).
		$environment_mode = $this->get_api_environment_mode();

		//===Backwards compatibility. Check if the PPCP onboarding step is done for this environment mode.===
		$settings = PayPal_PPCP_Config::get_instance();
		$onboarding_status = $settings->get_value('paypal-ppcp-onboarding-'.$environment_mode);
		if( $onboarding_status != 'completed' ){
			//The PPCP onboarding step is not done for this environment mode. Do the fallback header method.
			return $this->get_headers();
		}
		//===End backwards compatibility===

		//Get the bearer/access token.
		$bearer = PayPal_Bearer::get_instance();
		$bearer_token = $bearer->get_bearer_token( $environment_mode );

		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $bearer_token,
			'PayPal-Partner-Attribution-Id' => 'TipsandTricks_SP_PPCP',
		);

		return $headers;
	}

	public static function get_paypal_auth_assertion_value($environment_mode){
		$partner_client_id = PayPal_Utility_Functions::get_partner_client_id_by_environment_mode( $environment_mode );
		$seller_merchant_id = PayPal_Utility_Functions::get_seller_merchant_id_by_environment_mode( $environment_mode );
		$jwt_header_data = array( 'alg' => 'none' );
		$jwt_payload = array( 'iss' => $partner_client_id, 'payer_id' => $seller_merchant_id );
		$pp_auth_assertion = base64_encode(json_encode($jwt_header_data)).'.'.base64_encode(json_encode($jwt_payload)).'.';//The signature is empty
		return $pp_auth_assertion;
	}

	/**
	 * Make GET API request
	 *
	 * @param  string $endpoint
	 * Endpoint to make request to. Example: '/v1/billing/plans'
	 * @param  array $params
	 * @return mixed
	 * `object` on success, `false` on error
	 */
	public function get( $endpoint, $params = array(), $additional_args = array() ) {

		$this->before_request();

		//$headers = $this->get_headers();//This can be used for Basic auth headers
		$headers = $this->get_headers_using_bearer_token();

		//The request URL.
		$api_base_url = $this->get_api_base_url();
		$request_url = $api_base_url . $endpoint; //Example: https://api-m.sandbox.paypal.com/v1/billing/plans

		//Add the request URL to the additional args so it can be logged (if needed).
		$additional_args['request_url'] = $request_url;

		//Create the arguments for the request.
		$args = array(
			'headers' => $headers,
			'body' => $this->encode_params( $params ),
		);

		//Set the default timeout to 60 seconds.
		$args['timeout'] = apply_filters( PayPal_Utility_Functions::hook('paypal_api_request_timeout'), 60 );

		//Execute the request.
		$res = wp_remote_get( $request_url, $args );

		//Check if we need to return the body or raw response
		if( isset($additional_args['return_raw_response']) && $additional_args['return_raw_response'] ){
			//Return the raw response
			return $res;
		} else if ( isset( $additional_args['return_response_body'] ) && $additional_args['return_response_body'] ){
			//Return the response body
			return wp_remote_retrieve_body( $res );
		}
		
		$status_code = isset( $additional_args['status_code'] ) ? $additional_args['status_code'] : 200;
		$return = $this->process_request_result( $res, $status_code, $additional_args );

		return $return;
	}

	/**
	 * Make POST API request
	 *
	 * @param  string $endpoint
	 * Endpoint to make request to. Example: '/v1/catalogs/products'
	 * @param  array $params
	 * Parameters to send
	 * @param string $method
	 * Request method. Default is 'POST'
	 * @return mixed
	 * `object` on success, `false` on error
	 */
	public function post( $endpoint, $params = array(), $additional_args = array() ) {

		$this->before_request();

		//Get the headers to use for the request.
		$headers = $this->get_headers_using_bearer_token();
		if( isset( $additional_args['PayPal-Request-Id'] ) ){
			//Add the PayPal-Request-Id header if it's set.
			$headers['PayPal-Request-Id'] = $additional_args['PayPal-Request-Id'];
		}

		//The request URL.
		$api_base_url = $this->get_api_base_url();
		$request_url = $api_base_url . $endpoint; //Example: https://api-m.sandbox.paypal.com/v1/catalogs/products

		//Add the request URL to the additional args so it can be logged (if needed).
		$additional_args['request_url'] = $request_url;

		//Create the arguments for the request.
		$args = array(
			'headers' => $headers,
			'body' => json_encode( $params ),
		);

		//Set the default timeout to 60 seconds.
		$args['timeout'] = apply_filters( PayPal_Utility_Functions::hook('paypal_api_request_timeout'), 60 );

		//=== Debug purposes (Useful for PayPal Tech Support) ===
		// PayPal_Utility_Functions::log_array( $args, true);
		//=== End of debug purposes ===

		//Make the request
		$res = wp_remote_post( $request_url, $args );

		//Check if we need to return the body or raw response
		if( isset($additional_args['return_raw_response']) && $additional_args['return_raw_response'] ){
			//Return the raw response
			return $res;
		} else if ( isset( $additional_args['return_response_body'] ) && $additional_args['return_response_body'] ){
			//Return the response body
			return wp_remote_retrieve_body( $res );
		}

		//POST success response status code is 201 by default
		$status_code = isset( $additional_args['status_code'] ) ? $additional_args['status_code'] : 201;
		$return = $this->process_request_result( $res, $status_code, $additional_args );

		return $return;
	}

	/**
	 * Make DELETE API request
	 * @param mixed $endpoint
	 * @param mixed $params
	 * @return mixed
	 */
	public function delete( $endpoint, $params = array(), $additional_args = array() ) {

		$this->before_request();

		$headers = $this->get_headers_using_bearer_token();

		//The request URL.
		$api_base_url = $this->get_api_base_url();
		$request_url = $api_base_url . $endpoint; //Example: https://api-m.sandbox.paypal.com/v1/catalogs/products

		//Add the request URL to the additional args so it can be logged (if needed).
		$additional_args['request_url'] = $request_url;

		//Create the arguments for the request.
		$args = array(
			'method' => 'DELETE',
			'headers' => $headers,
			'body' => json_encode( $params ),
		);

		//Set the default timeout to 60 seconds.
		$args['timeout'] = apply_filters( PayPal_Utility_Functions::hook('paypal_api_request_timeout'), 60 );

		//Execute the request.
		$res = wp_remote_request( $request_url, $args );

		//Check if we need to return the body or raw response
		if( isset($additional_args['return_raw_response']) && $additional_args['return_raw_response'] ){
			//Return the raw response
			return $res;
		} else if ( isset( $additional_args['return_response_body'] ) && $additional_args['return_response_body'] ){
			//Return the response body
			return wp_remote_retrieve_body( $res );
		}

		//DELETE action's success response status code is 204 by default
		$status_code = isset( $additional_args['status_code'] ) ? $additional_args['status_code'] : 204;
		$return = $this->process_request_result( $res, $status_code, $additional_args );

		return $return;
	}
	/*
	 * Checks the response and if it finds any error, it stores the error details in the last_error var then returns false.
	 * Minimizes the amount of response code check the source code has to do.
	 */
	private function process_request_result( $res, $status_code = 200, $additional_args = array() ) {
		if (is_wp_error( $res )) {
			$this->last_error['error_message'] = $res->get_error_message();
			$this->last_error['error_code'] = $res->get_error_code();
			//This usually means that WordPress failed to make the request to the API endpoint correctly. Log this error so the debug option can reveal the details.
			$this->log_api_error_response( $res, $this->last_error['error_message'], $additional_args );
			return false;
		}

		if ($status_code !== $res['response']['code']) {
			if (! empty( $res['body'] )) {
				$body = json_decode( $res['body'], true );
				if (isset( $body['error'] )) {
					$this->last_error['error_message'] = $body['error_description'];
					$this->last_error['error_code'] = $body['error'];//String error code (ex: "invalid_client")
					$this->last_error['http_code'] = $res['response']['code'];//HTTP error code (ex: 400)
				}
			} else {
				//Empty body response.
				$this->last_error['error_message'] = 'Error! The body of the response is empty. Check that the expected response status code is correct.';
			}
			//Log this error so the debug option can reveal the details.
			$this->log_api_error_response( $res, $this->last_error['error_message'], $additional_args );
			return false;
		}

		$response_body_json_decoded = json_decode( $res['body'] );

		//=== Debug purposes (Useful for PayPal Tech Support) ===
		// PayPal_Utility_Functions::log( '----- PayPal REST API response output -----', true );
		// $paypal_debug_id_and_info = PayPal_Request_API::get_paypal_debug_id_and_info($res, $additional_args);
		// PayPal_Utility_Functions::log( $paypal_debug_id_and_info, true );
		// PayPal_Utility_Functions::log_array( $response_body_json_decoded, true );
		//=== End of debug purposes ===

		return $response_body_json_decoded;
	}

	public function log_api_error_response( $response, $error_message, $additional_args = array() ) {
		//Log the error message.
		PayPal_Utility_Functions::log( 'Error occurred with the PayPal API request. Error message: ' . $error_message, false );
		//Log the PayPal debug id for PayPal API Error.
		$paypal_debug_id_and_info = PayPal_Request_API::get_paypal_debug_id_and_info($response, $additional_args);
		PayPal_Utility_Functions::log( $paypal_debug_id_and_info, false );
	}

	/*
	 * Returns the PayPal debug ID and info from the API response.
	 */
	public static function get_paypal_debug_id_and_info($response, $additional_args = array()) {
		$paypal_debug_id = wp_remote_retrieve_header( $response, 'paypal-debug-id' );
		$paypal_debug_id_and_info = 'PayPal Debug ID: ' . $paypal_debug_id;
		if (isset($additional_args['request_url'])){
			$paypal_debug_id_and_info .= ', Request URL: ' . $additional_args['request_url'];
		}
		return $paypal_debug_id_and_info;
	}

	private function format_app_info_to_string( $app_info ) {
		if (null !== $app_info) {
			$string = $app_info['name'];
			if (null !== $app_info['url']) {
				$string .= ' (' . $app_info['url'] . ')';
			}
			return $string;
		}
		return "";
	}

	/**
	 * Performs a direct request to the PayPal API using URL and arguments.
	 */
	public static function send_request_by_url_and_args( $url, $args ) {
		//Set the default timeout to 60 seconds.
		$args['timeout'] = apply_filters( PayPal_Utility_Functions::hook('paypal_api_request_timeout'), 60 );

		/**
		 * * Remember to use plugin shortname as prefix as tag when hooking to this hook.
		 * * Example: 'paypal_api_request_by_url_args' is actually '<prefix>_paypal_api_request_by_url_args'.
		 */ 
		$args = apply_filters( PayPal_Utility_Functions::hook('paypal_api_request_by_url_args'), $args, $url );
		if ( ! isset( $args['headers']['PayPal-Partner-Attribution-Id'] ) ) {
			$args['headers']['PayPal-Partner-Attribution-Id'] = 'TipsandTricks_SP_PPCP';
		}

		//=== Debug purposes (Useful for PayPal Tech Support) ===
		// PayPal_Utility_Functions::log( '----- PayPal API request header -----', true );
		// PayPal_Utility_Functions::log_array( $args, true );
		//=== End of debug purposes ===

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error($response) ) {
			// Error occurred with the API request. Lets log the error.
			$error_message = $response->get_error_message();
			PayPal_Utility_Functions::log( 'Error occurred with the PayPal API (by URL) request. Error message: ' . $error_message, false );
			// Log the PayPal debug id for PayPal API Error.
			$paypal_debug_id = wp_remote_retrieve_header( $response, 'paypal-debug-id' );
			PayPal_Utility_Functions::log( 'PayPal Debug ID: ' . $paypal_debug_id . ', Request URL: ' . $url , false );			
			return false;
		}

		//PayPal Debug ID
		$paypal_debug_id = wp_remote_retrieve_header( $response, 'paypal-debug-id' );
		PayPal_Utility_Functions::log( 'PayPal Debug ID from the REST API (by URL) request. Debug ID: ' . $paypal_debug_id . ', Request URL: ' . $url, true );
		
		//=== Debug purposes (Useful for PayPal Tech Support) ===
		// $response_body = wp_remote_retrieve_body( $response );
		// $response_body_json_decoded = json_decode( $response_body );
		// PayPal_Utility_Functions::log_array( $response_body_json_decoded, true );
		//-- Debug the full response (header and body) --
		//$response_full_var_exported = var_export( $response, true );
		//PayPal_Utility_Functions::log( 'PayPal API (by URL) response body: ' . $debug_api_response, true );
		//=== End of debug purposes ===

		return $response;
	}

}