<?php

namespace TTHQ\WPSC\Lib\PayPal;

use TTHQ\WPSC\Lib\PayPal\Onboarding\PayPal_PPCP_Onboarding_Serverside;

//Includes
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-config.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-request-api.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-request-api-injector.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-js-button-embed.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-subsc-billing-plan.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-webhook.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-webhook-event-handler.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-onapprove-ipn-handler.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-utility-functions.php' );//Misc project specific utility functions.
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-utility-ipn-related.php' );//Misc IPN related utility functions.
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-cache.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-bearer.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-button-ajax-handler.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-acdc-related.php' );

//Onboarding related includes
include_once( WP_CART_PATH . 'lib/paypal/onboarding-related/class-tthq-paypal-onboarding.php' );//PPCP Onboarding related functions.
include_once( WP_CART_PATH . 'lib/paypal/onboarding-related/class-tthq-paypal-onboarding-serverside.php' );//PPCP Onboarding serverside helper.

/**
 * The Main class to handle the new PayPal library related tasks. 
 * It initializes when this file is inlcuded.
 */
class PayPal_Main {

	public static $api_base_url_production = 'https://api-m.paypal.com';	
	public static $api_base_url_sandbox = 'https://api-m.sandbox.paypal.com';
	public static $signup_url_production = 'https://www.paypal.com/bizsignup/partner/entry';	
	public static $signup_url_sandbox = 'https://www.sandbox.paypal.com/bizsignup/partner/entry';	
	public static $partner_id_production = '3FWGC6LFTMTUG';//Same as the partner's merchant id of the live account.
	public static $partner_id_sandbox = '47CBLN36AR4Q4';// Same as the merchant id of the platform app sandbox account.
	public static $partner_client_id_production = 'AWo6ovbrHzKZ3hHFJ7APISP4MDTjes-rJPrIgyFyKmbH-i8iaWQpmmaV5hyR21m-I6f_APG6n2rkZbmR'; //Platform app's client id.
	public static $partner_client_id_sandbox = 'AeO65uHbDsjjFBdx3DO6wffuH2wIHHRDNiF5jmNgXOC8o3rRKkmCJnpmuGzvURwqpyIv-CUYH9cwiuhX';

	public static $pp_api_connection_settings_menu_page = '';//This is saved in the constructor (as a config item)
	public static $paypal_webhook_event_query_arg = '';
	
    public function __construct( $conf ) {

		// Do plugin specific config
		$config = PayPal_PPCP_Config::get_instance();
		$config->set_plugin_shortname($conf['plugin_shortname']);
		$config->set_log_text_method($conf['log_text_method']);
		$config->set_log_array_method($conf['log_array_method']);
		$config->set_ppcp_settings_key($conf['ppcp_settings_key']);

		$config->load_settings_from_db();

		//Plugin specific (whatever the sandbox toggle option is used in the plugin, we will use that.
		if ( get_option( $conf['enable_sandbox_settings_key'] ) ) {
			//The sandbox mode is enabled in the main plugin settings. Set it in our ppcp settings as well.
			$config->set_value('enable-sandbox-testing', 'checked="checked"');
		} else {
			$config->set_value('enable-sandbox-testing', '');
		}

		//Set some default values for the button appearance (only if they are not set already).
		$button_height = $config->get_value('ppcp_btn_height');
		if ( empty( $button_height ) ) {
			$config->set_value('ppcp_btn_height', 'medium');
		}
		$button_color = $config->get_value('ppcp_btn_color');
		if ( empty( $button_color ) ) {
			$config->set_value('ppcp_btn_color', 'blue');
		}
		$disable_funding_credit = $config->get_value('ppcp_disable_funding_credit');
		if ( $disable_funding_credit === '' ) {
			$config->set_value('ppcp_disable_funding_credit', '1');
		}

		//Save the config object.
		$config->save();

		//Set the menu page for the API connection settings.
		self::$pp_api_connection_settings_menu_page = $conf['api_connection_settings_page'];
		self::$paypal_webhook_event_query_arg = PayPal_Utility_Functions::auto_prefix('paypal_webhook_event', '_');
		
		if ( isset( $_GET['action'] ) && $_GET['action'] == self::$paypal_webhook_event_query_arg && isset( $_GET['mode'] )) {
			//Register action (to handle webhook) only on our webhook notification URL.
			new PayPal_Webhook_Event_Handler();
		}


		//Initialize the PayPal Ajax Create and Capture Order Class so it can handle the ajax request(s).
		new PayPal_Button_Ajax_Hander();

		//Initialize the PayPal OnApprove IPN Handler so it can handle the 'onApprove' ajax request(s).
		new PayPal_OnApprove_IPN_Handler();

		//Initialize the PayPal ACDC related class so it can handle the ajax request(s).
		new PayPal_ACDC_Related();

		//Initialize the PayPal onboarding serverside class so it can handle the 'onboardedCallback' ajax request.
		new PayPal_PPCP_Onboarding_Serverside();	
		
    }

}

/**
 * TODO: This is a plugin specific configurations,
 * 
 * FIXME: This need to rework or remove if needed.
 */
new PayPal_Main(
    array(
        'plugin_shortname' => 'wpsc',
        'api_connection_settings_page' => 'admin.php?page=wspsc-menu-main&action=ppcp-settings&subtab=api-connection', // REPACE: Need to change this across different plugins.
        'log_text_method' => 'wpsc_log_payment_debug',
        'log_array_method' => 'wpsc_log_debug_array',
        'ppcp_settings_key' => 'wpsc_paypal_ppcp_settings',
		'enable_sandbox_settings_key' => 'wp_shopping_cart_enable_sandbox',
    )
);