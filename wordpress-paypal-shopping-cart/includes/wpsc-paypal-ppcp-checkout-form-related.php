<?php

use TTHQ\WPSC\Lib\PayPal\PayPal_PPCP_Config;

function wpsc_render_paypal_ppcp_checkout_form( $output, $args ){
    //FIXME - implement this function

    /*****************************************
     * Settings and checkout button specific variables
     *****************************************/
    $ppcp_configs = PayPal_PPCP_Config::get_instance();
    $live_client_id = $ppcp_configs->get_value('paypal-live-client-id');
    $sandbox_client_id = $ppcp_configs->get_value('paypal-sandbox-client-id');
    $sandbox_enabled = $ppcp_configs->get_value('enable-sandbox-testing');
    $is_live_mode = $sandbox_enabled ? 0 : 1;

	$currency = isset($args['currency']) ? $args['currency'] : 'USD';

	$disable_funding_card = $ppcp_configs->get_value('ppcp_disable_funding_card');
    $disable_funding_credit = $ppcp_configs->get_value('ppcp_disable_funding_credit');
    $disable_funding_venmo = $ppcp_configs->get_value('ppcp_disable_funding_venmo');
    $disable_funding = array();
    if( !empty($disable_funding_card)){
        $disable_funding[] = 'card';
    }
    if( !empty($disable_funding_credit)){
        $disable_funding[] = 'credit';
    }
    if( !empty($disable_funding_venmo)){
        $disable_funding[] = 'venmo';
    }

	// $btn_type = get_post_meta($button_id, 'pp_buy_now_new_btn_type', true);
    // $btn_shape = get_post_meta($button_id, 'pp_buy_now_new_btn_shape', true);
    // $btn_layout = get_post_meta($button_id, 'pp_buy_now_new_btn_layout', true);
    // $btn_color = get_post_meta($button_id, 'pp_buy_now_new_btn_color', true);

    // $btn_width = get_post_meta($button_id, 'pp_buy_now_new_btn_width', true);
    // $btn_height = get_post_meta($button_id, 'pp_buy_now_new_btn_height', true);
    // $btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
    // $btn_height = isset( $btn_sizes[ $btn_height ] ) ? $btn_sizes[ $btn_height ] : 35;

    // $return_url = get_post_meta($button_id, 'return_url', true);
    // $txn_success_message = __('Transaction completed successfully!', 'simple-membership');


    /**********************
     * PayPal SDK related settings
     **********************/
    //Configure the paypal SDK settings
    $settings_args = array(
        'is_live_mode' => $is_live_mode,
        'live_client_id' => $live_client_id,
        'sandbox_client_id' => $sandbox_client_id,
        'currency' => $currency,
        'disable-funding' => $disable_funding, /*array('card', 'credit', 'venmo'),*/
        'intent' => 'capture', /* It is used to set the "intent" parameter in the JS SDK */
        'is_subscription' => 0, /* It is used to set the "vault" parameter in the JS SDK */
    );


    return $output;
}