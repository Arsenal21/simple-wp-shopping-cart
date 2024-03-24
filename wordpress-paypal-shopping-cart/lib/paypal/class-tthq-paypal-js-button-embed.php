<?php

namespace TTHQ\WPSC\Lib\PayPal;

class PayPal_JS_Button_Embed {
	protected static $instance;
    protected static $on_page_payment_buttons = array();
	/**
	* REPLACE: plugin prefix across different plugins.
	*/
    public $button_id_prefix = 'wpsc_paypal_button_';
	public $settings_args = array();
	public $settings_args_subscription = array();

	function __construct() {

	}

	/*
	 * This needs to be a Singleton class. To make sure that for the full page, the JS loaded events in the footer are triggering one time only.
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/*
	Set the settings args that will be used to generate the PayPal JS SDK arguments.
	 */
	public function set_settings_args( $settings_args ) {
		//Example settings args array
		/*
		$settings_args = array(
			'is_live_mode' => 0,
			'live_client_id' => 'THE LIVE CLIENT ID',
			'sandbox_client_id' => 'THE SANDBOX CLIENT ID',
			'currency' => 'USD',
			'disable-funding' => '', //array('card', 'credit', 'venmo')
			'intent' => 'capture',
			'is_subscription' => 0,
		);
		*/
		$this->settings_args = $settings_args;
	}

	/*
	Set the settings args that will be used to generate the PayPal JS SDK arguments for Subscription buttons.
	 */
	public function set_settings_args_for_subscriptions( $settings_args_sub ) {
		$this->settings_args_subscription = $settings_args_sub;
	}

	public function get_next_button_id() {
		$next_button_id = $this->button_id_prefix . count(self::$on_page_payment_buttons);
		self::$on_page_payment_buttons[] = $next_button_id;
		return $next_button_id;
	}

	public function get_button_id_prefix() {
		return $this->button_id_prefix;
	}

	/*
	 * Generate the arguments for the PayPal JS SDK. It will be used to load the SDK script.
	 */
	public function generate_paypal_js_sdk_args( $args = array()){

		//Reference - https://developer.paypal.com/sdk/js/configuration/
		$sdk_args = array();
		$sdk_args['client-id'] = $args['is_live_mode'] ? $args['live_client_id'] : $args['sandbox_client_id'];
		if( empty($sdk_args['client-id']) ){
			//Client ID is required. Add a log entry for this.
			$env_mode_name = $args['is_live_mode'] ? 'Live' : 'Sandbox';
			PayPal_Utility_Functions::log( 'PayPal client ID is missing in the settings for environment mode: ' . $env_mode_name, false );
		}

		$sdk_args['intent'] = isset($args['intent']) ? $args['intent'] : 'capture';
		$sdk_args['currency'] = $args['currency'];

		if ( isset( $args['is_subscription'] ) && ! empty( $args['is_subscription'] ) ) {
			//Enable vault for subscription payments.
			$sdk_args['vault'] = 'true';
		}

		// Enable Venmo by default (could be disabled by 'disable-funding' option).
		$sdk_args['enable-funding']  = 'venmo';//We can add more funding options here (exmaple: venmo, paylater)
		// Required for Venmo in sandbox.
		if ( ! $args['is_live_mode'] ) {
			$sdk_args['buyer-country']  = 'US';
		}

		//Check disable funding options.
		$disabled_funding = isset( $args['disable-funding'] ) ? $args['disable-funding'] : '';
		if ( is_array( $disabled_funding ) && ! empty( $disabled_funding ) ) {
			// Convert array to comma separated string.
			$disable_funding_arg = '';
			foreach ( $disabled_funding as $funding ) {
				$disable_funding_arg .= $funding . ',';
			}
			$disable_funding_arg = rtrim( $disable_funding_arg, ',' );//Remove the last comma and any white space.
			$sdk_args['disable-funding'] = $disable_funding_arg;
		}

		/**
		 * Trigger filter hook so the PayPal SDK arguments can be modified.
		 * * Remember to use plugin shortname as prefix as tag when hooking to this hook.
		 * * For example 'generate_paypal_js_sdk_args' is actually 'wpsc_generate_paypal_js_sdk_args' for simple cart plugin.
		 */ 
		$sdk_args = apply_filters( PayPal_Utility_Functions::hook('generate_paypal_js_sdk_args'), $sdk_args );
		return $sdk_args;
	}

	/**
	 * Load the PayPal JS SDK Script in the footer. This one loads the SDK with standard parameters (useful for one-time payments).
	 * 
	 * It will be called from the button's shortcode (using a hook) if at least one button is present on the page.
	 * The button's JS code needs to be executed after the SDK is loaded. Check for '<prefix>_paypal_sdk_loaded' event.
	 */
	public function load_paypal_sdk() {
		$args = $this->settings_args;
		$sdk_args = $this->generate_paypal_js_sdk_args($args);

		$script_url = add_query_arg( $sdk_args, 'https://www.paypal.com/sdk/js' );
		?>
		<script type="text/javascript">
			wpsc_onDocumentReady(function(){
				var script = document.createElement( 'script' );
				script.type = 'text/javascript';
				script.setAttribute( 'data-partner-attribution-id', 'TipsandTricks_SP_PPCP' );
				script.async = true;
				script.src = '<?php echo esc_url_raw( $script_url ); ?>';	
				script.onload = function () {
					document.dispatchEvent(new Event('wpsc_paypal_sdk_loaded'));//REPLACE: plugin prefix across different plugins.
				};
				document.getElementsByTagName( 'head' )[0].appendChild( script );
			})

			function wpsc_onDocumentReady(callback) {
            	// If the document is already loaded, execute the callback immediately
				if (document.readyState !== 'loading') {
					callback();
				} else {
					// Otherwise, wait for the DOMContentLoaded event
					document.addEventListener('DOMContentLoaded', callback);
				}
			}
		</script>
		<?php
	}

	/**
	 * Load the PayPal JS SDK Script for Subscription buttons in the footer. Loads the SDK with parameters useful for subscription buttons.
	 * 
	 * It will be called from the button's shortcode (using a hook) if at least one button is present on the page.
	 * The button's JS code needs to be executed after the SDK is loaded. Check for '<prefix>_paypal_sdk_subscriptions_loaded' event.
	 */
	public function load_paypal_sdk_for_subscriptions() {
		$args = $this->settings_args_subscription;
		$sdk_args = $this->generate_paypal_js_sdk_args($args);

		$script_url = add_query_arg( $sdk_args, 'https://www.paypal.com/sdk/js' );
		?>
		<script type="text/javascript">
			var script_sub = document.createElement( 'script' );
			script_sub.type = 'text/javascript';
			script_sub.setAttribute( 'data-partner-attribution-id', 'TipsandTricks_SP_PPCP' );
			/**
			* REPLACE: plugin prefix across different plugins.
			*/
			script_sub.setAttribute( 'data-namespace', 'wpsc_paypal_subscriptions' );//Use a different namespace for the subscription buttons.
			script_sub.async = true;
			script_sub.src = '<?php echo esc_url_raw( $script_url ); ?>';
			script_sub.onload = function() {
				document.dispatchEvent(new Event('wpsc_paypal_sdk_subscriptions_loaded'));//REPLACE: plugin prefix across different plugins.
			};
			document.getElementsByTagName( 'head' )[0].appendChild( script_sub );
		</script>
		<?php
	}

	/**
	 * Generate the PayPal JS SDK Script.
	 * 
	 * It can be called to get the SDK script that can be used right where you want to output it.
	 */
	public function generate_paypal_sdk_script_output() {
		$sdk_args = $this->generate_paypal_js_sdk_args();
		$script_url = add_query_arg( $sdk_args, 'https://www.paypal.com/sdk/js' );

		$output = '<script src="' . esc_url_raw( $script_url ) . '" data-partner-attribution-id="TipsandTricks_SP_PPCP"></script>';
		return $output;
	}

}