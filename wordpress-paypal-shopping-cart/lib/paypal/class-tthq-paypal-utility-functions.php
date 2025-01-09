<?php

namespace TTHQ\WPSC\Lib\PayPal;

class PayPal_Utility_Functions{
    
    public static function get_api_environment_mode_from_settings(){
        $settings = PayPal_PPCP_Config::get_instance();
        $sandbox_enabled = $settings->get_value( 'enable-sandbox-testing' );//The value will be checked="checked" or empty string.
        if( !empty( $sandbox_enabled ) ){
            $environment_mode = 'sandbox';
        }else{
            $environment_mode = 'production';
        }
        return $environment_mode;
    }

	public static function get_api_base_url_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return PayPal_Main::$api_base_url_production;
		} else {
			return PayPal_Main::$api_base_url_sandbox;
		}
	}

	public static function get_signup_url_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return PayPal_Main::$signup_url_production;
		} else {
			return PayPal_Main::$signup_url_sandbox;
		}
	}    
	public static function get_partner_id_by_environment_mode( $environment_mode = 'production' ) {
		if ($environment_mode == 'production') {
			return PayPal_Main::$partner_id_production;
		} else {
			return PayPal_Main::$partner_id_sandbox;
		}
	}

    public static function get_partner_client_id_by_environment_mode( $environment_mode = 'production' ) {
        if ($environment_mode == 'production') {
            return PayPal_Main::$partner_client_id_production;
        } else {
            return PayPal_Main::$partner_client_id_sandbox;
        }
    }

    /**
     * Gets the seller merchant ID (payer ID) by environment mode. 
     * Used in the PayPal API calls (for setting PayPal-Auth-Assertion header value)
     */
    public static function get_seller_merchant_id_by_environment_mode( $environment_mode = 'production' ) {
        $settings = PayPal_PPCP_Config::get_instance();
        $seller_merchant_id = '';

        if ($environment_mode == 'production') {
            $seller_merchant_id = $settings->get_value('paypal-live-seller-merchant-id');
        } else {
            $seller_merchant_id = $settings->get_value('paypal-sandbox-seller-merchant-id');
        }
        return $seller_merchant_id;
    }

    public static function get_seller_client_id_by_environment_mode( $environment_mode = 'production' ) {
        $settings = PayPal_PPCP_Config::get_instance();
        $seller_client_id = '';

        if ($environment_mode == 'production') {
            $seller_client_id = $settings->get_value('paypal-live-client-id');
        } else {
            $seller_client_id = $settings->get_value('paypal-sandbox-client-id');
        }
        return $seller_client_id;
    }

    public static function create_product_params_from_button( $button_id ){
        $button_name = get_the_title( $button_id );
        $product_params = array(
            'name' => $button_name,
            'type' => 'DIGITAL',
        );
        return $product_params;
    }

    public static function create_subscription_args_from_button( $button_id ){
        $subsc_args = array(
            'currency' => get_post_meta($button_id, 'payment_currency', true),
            'sub_trial_price' => get_post_meta($button_id, 'trial_billing_amount', true),            
            'sub_trial_period' => get_post_meta($button_id, 'trial_billing_cycle', true),
            'sub_trial_period_type' => get_post_meta($button_id, 'trial_billing_cycle_term', true),
            'sub_recur_price' => get_post_meta($button_id, 'recurring_billing_amount', true),            
            'sub_recur_period' => get_post_meta($button_id, 'recurring_billing_cycle', true),
            'sub_recur_period_type' => get_post_meta($button_id, 'recurring_billing_cycle_term', true),
            'sub_recur_count' => get_post_meta($button_id, 'recurring_billing_cycle_count', true),
            'sub_recur_reattemp' => get_post_meta($button_id, 'recurring_billing_reattempt', true),
        );
        return $subsc_args;
    }

    /**
     * Checks if the plan details (core subscription plan values) in new form submission have changed for the given button ID.
     */
    public static function has_plan_details_changed_for_button( $button_id ){
		$plan_details_changed = false;
        $core_plan_fields = array(
            'payment_currency' => trim(sanitize_text_field($_REQUEST['payment_currency'])),
            'recurring_billing_amount' => trim(sanitize_text_field($_REQUEST['recurring_billing_amount'])),
            'recurring_billing_cycle' => trim(sanitize_text_field($_REQUEST['recurring_billing_cycle'])),
            'recurring_billing_cycle_term' => trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_term'])),
            'recurring_billing_cycle_count' => trim(sanitize_text_field($_REQUEST['recurring_billing_cycle_count'])),
            'trial_billing_amount' => trim(sanitize_text_field($_REQUEST['trial_billing_amount'])),
            'trial_billing_cycle' => trim(sanitize_text_field($_REQUEST['trial_billing_cycle'])),
            'trial_billing_cycle_term' => trim(sanitize_text_field($_REQUEST['trial_billing_cycle_term'])),
        );
		foreach ( $core_plan_fields as $meta_name => $value ) {
            $old_value = get_post_meta( $button_id, $meta_name, true );
            if ( $old_value !== $value ) {
                $plan_details_changed = true;
            }
		}
		return $plan_details_changed;
    }

    /**
     * Force creates a new billing plan for the button (the paypal account connection or the mode may have changed)
     */
    public static function create_billing_plan_fresh_new( $button_id ){
        //Reset any plan ID that may be saved for this button. 
        //We need to create completely new plan (using the current PayPal account and mode)
        update_post_meta($button_id, 'pp_subscription_plan_id', '');
        update_post_meta($button_id, 'pp_subscription_plan_mode', '');

        $ret = array();
        $ret = self::create_billing_plan_for_button( $button_id );
        return $ret;
    }

    /**
     * Checks if a billling plan exists for the given button ID. If not, it creates a new billing plan in PayPal. 
     * Returns the billing plan ID in an array.
     * @param mixed $button_id
     * @return array
     */
    public static function create_billing_plan_for_button( $button_id ){
        $output = "";
		$ret = array();
        $plan_id = get_post_meta($button_id, 'pp_subscription_plan_id', true);
        if ( empty ( $plan_id )){
            //Billing plan doesn't exist. Need to create a new billing plan in PayPal.
            $product_params = self::create_product_params_from_button( $button_id );         
            $subsc_args = self::create_subscription_args_from_button( $button_id );

            //Setup the PayPal API Injector class. This class is used to do certain premade API queries.
            $pp_api_injector = new PayPal_Request_API_Injector();
			$paypal_req_api = $pp_api_injector->get_paypal_req_api();
            $paypal_mode = $paypal_req_api->get_api_environment_mode();
            // Debugging
            // echo '<pre>';
            // var_dump($paypal_req_api);
            // echo '</pre>';

            $plan_id = $pp_api_injector->create_product_and_billing_plan($product_params, $subsc_args);
            if ( $plan_id !== false ) {
                //Plan created successfully. Save the plan ID for future reference.
                update_post_meta($button_id, 'pp_subscription_plan_id', $plan_id);
                update_post_meta($button_id, 'pp_subscription_plan_mode', $paypal_mode);

                $ret['success'] = true;
                $ret['plan_id'] = $plan_id;
				$ret['output'] = $output;
				return $ret;
            } else {
                //Plan creation failed. Show an error message.
                $last_error = $paypal_req_api->get_last_error();
                $error_message = isset($last_error['error_message']) ? $last_error['error_message'] : '';

                $output .= '<div class="paypal-ppcp-api-error-msg">';
                $output .= '<p>Error! Failed to create a subscription billing plan in your PayPal account. The following error message was returned from the PayPal API.</p>';
                $output .= '<p>Error Message: ' . esc_attr($error_message) . '</p>';
                $output .= '</div>';

                $ret['success'] = false;
                $ret['plan_id'] = '';
                $ret['error_message'] = $error_message;
                $ret['output'] = $output;
                return $ret;
            }
        }
        $ret['success'] = true;
        $ret['plan_id'] = $plan_id;
        $ret['output'] = $output;
		return $ret;
    }

    public static function check_billing_plan_exists( $plan_id ){
        //Setup the PayPal API Injector class. This class is used to do certain premade API queries.
        $pp_api_injector = new PayPal_Request_API_Injector();

        //Use the "Show plan details" API call to verify that the plan exists for the given account and mode.
        // https://developer.paypal.com/docs/api/subscriptions/v1/#plans_get
        $plan_details = $pp_api_injector->get_paypal_billing_plan_details( $plan_id );
        if( $plan_details !== false ){
            //Plan exists. Return true.
            return true;
        }
        
        $paypal_req_api = $pp_api_injector->get_paypal_req_api();
        $paypal_mode = $paypal_req_api->get_api_environment_mode();

        self::log( "Billing plan with ID: ". $plan_id . " does not exist in PayPal. The check was done in mode: ".$paypal_mode.". Maybe the plan was originally created in a different environment mode or account.", true );
		return false;
    }

    /**
     * Checks if a webhook already exists for this site for BOTH sandbox and production modes. If one doesn't exist, create one.
     */
	public static function check_and_create_webhooks_for_this_site() {
		$pp_webhook = new PayPal_Webhook();
		$pp_webhook->check_and_create_webhooks_for_both_modes();    
	}

    public static function check_current_mode_and_set_notice_if_webhook_not_set() {
        //Check if the current mode is sandbox or production. Then check if a webhook is set for that mode. 
        //If not, show a notice to the admin user by using the admin_notice hook.

        //TODO - need to finilaize this.
        //update_option( "<prefix>_show_webhook_notice_{$mode}", 'no' === $ret[ $mode ]['status'] );
        //Check the following code for example:
        //add_action( 'admin_notices', array( $this, 'show_webhooks_admin_notice' ) );
    }

    public static function log($data, $success = true, $end = false){
		// Check if debug logging method is valid.
		if (is_callable(PayPal_PPCP_Config::$log_text_method)) {
            call_user_func(PayPal_PPCP_Config::$log_text_method, $data, $success, $end);
            return;
		}
        
        wp_die("Invalid callable provided for simple debug logging.");
	}

    public static function log_array($data, $success = true, $end = false){
        // Check if debug logging method is valid.
		if (is_callable(PayPal_PPCP_Config::$log_array_method)) {
            call_user_func(PayPal_PPCP_Config::$log_array_method, $data, $success, $end);
            return;
		}
        
        wp_die("Invalid callable provided for array debug logging.");

	}

    /**
     * Process the hook names according to the plugin with appropriate prefixes.
     * 
     * * The idea of making this function to make this paypal library plugin independent.
     *
     * @param string $hook The basename name of the hook without wp_ajax or wp_ajax_nopriv etc.
     * @param boolean $is_ajax If its a ajax hook, use prefix: 'wp_ajax'
     * @param boolean $is_nopriv If its a ajax hook and a nopriv type hook, use prefix: 'wp_ajax_nopriv'
     * 
     * @return string The processed hook name.
     */
    public static function hook($hook, $is_ajax = false, $is_nopriv = false){
        $plugin_shortname = strtolower(PayPal_PPCP_Config::$plugin_shortname);

        $output = array();

        // Check whether its a ajax hook
        if ($is_ajax) {
            $output[] = 'wp_ajax';
            // Check whether is a nopriv type hook
            if ($is_nopriv) {
                $output[] = 'nopriv';
            }
        }

        $output[] = $plugin_shortname;
        $output[] = $hook;

        return implode("_", $output);
    }
    
    /**
     * Process a string with appropriate prefix and separator.
     * 
     * * The idea of making this function to make this paypal library plugin independent.
     *
     * @param string $str String to add prefix.
     * @param string $separator Separator to add between $prefix and $str.
     * @param string $prefix Prefix string.
     * 
     * @return string Processed string.
     */
    public static function auto_prefix($str, $separator = '', $prefix = ''){
        // Use plugin short name as prefix by default.
        $prefix = empty($prefix) ? strtolower(PayPal_PPCP_Config::$plugin_shortname) : $prefix;
        
        $output = $prefix;
        
        // Check if specific separator provided.
        if (!empty($separator)) {
            $output .= $separator;
        }
        
        $output .= $str;

        return $output;
    }

    /**
     * Retrieves an option value based on an option name.
     * 
     * * Wrapper of get_option() by WP. This wrapper function additionally prefixes option name with plugin shortname.
     * 
     * @param string $option Name of the option to retrieve. Expected to not be SQL-escaped.
     * @param mixed $default Optional. Default value to return if the option does not exist.
     * @param string $separator Optional. Separator to add after option name prefix. Default '_'.
     * 
     * @return mixed Value set for the option. A value of any type may be returned, including array, boolean, float, integer, null, object, and string.
     */
    public static function get_option($option, $default = \false, $separator = '_'){
        $option = self::auto_prefix($option, $separator);
        return get_option($option, $default);
    }

    /**
     * Updates the value of an option that was already added.
     * 
     * * Wrapper of update_option() by WP. This wrapper function additionally prefixes option name with plugin shortname.
     * 
     * @param string $option Name of the option to update. Expected to not be SQL-escaped.
     * @param mixed $value Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
     * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options, $autoload can only be updated using update_option() if $value is also changed. Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options, the default value is 'yes'. Default null.
     * @param string $separator Optional. Separator to add after option name prefix. Default '_'.
     * 
     * @return bool — True if the value was updated, false otherwise.
     */
    public static function update_option($option, $value, $autoload = \null, $separator = '_'){
        $option = self::auto_prefix($option, $separator);
        return update_option($option, $value, $autoload);
    }

    /**
     * Removes option by name. Prevents removal of protected WordPress options.
     * 
     * * Wrapper of delete_option() by WP. This wrapper function additionally prefixes option name with plugin shortname.
     * 
     * @param string $option Name of the option to delete. Expected to not be SQL-escaped.
     * @param string $separator Optional. Separator to add after option name prefix. Default '_'.
     * 
     * @return bool — True if the option was deleted, false otherwise.
     */
    public static function delete_option($option,  $separator = '_'){
        $option = self::auto_prefix($option, $separator);
        return delete_option($option);
    }

    public static function get_country_name_by_country_code( $country_code ) {
		$countries = array (
			'AW' => 'Aruba',
			'AF' => 'Afghanistan',
			'AO' => 'Angola',
			'AL' => 'Albania',
			'AD' => 'Andorra',
			'AE' => 'United Arab Emirates',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AS' => 'American Samoa',
			'AG' => 'Antigua and Barbuda',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BI' => 'Burundi',
			'BE' => 'Belgium',
			'BJ' => 'Benin',
			'BF' => 'Burkina Faso',
			'BD' => 'Bangladesh',
			'BG' => 'Bulgaria',
			'BH' => 'Bahrain',
			'BS' => 'Bahamas, The',
			'BA' => 'Bosnia and Herzegovina',
			'BY' => 'Belarus',
			'BZ' => 'Belize',
			'BM' => 'Bermuda',
			'BO' => 'Bolivia',
			'BR' => 'Brazil',
			'BB' => 'Barbados',
			'BN' => 'Brunei Darussalam',
			'BT' => 'Bhutan',
			'BW' => 'Botswana',
			'CF' => 'Central African Republic',
			'CA' => 'Canada',
			'CH' => 'Switzerland',
			'JG' => 'Channel Islands',
			'CL' => 'Chile',
			'CN' => 'China',
			'CI' => 'Cote d\'Ivoire',
			'CM' => 'Cameroon',
			'CD' => 'Congo, Dem. Rep.',
			'CG' => 'Congo, Rep.',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CV' => 'Cabo Verde',
			'CR' => 'Costa Rica',
			'CU' => 'Cuba',
			'CW' => 'Curacao',
			'KY' => 'Cayman Islands',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DE' => 'Germany',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DK' => 'Denmark',
			'DO' => 'Dominican Republic',
			'DZ' => 'Algeria',
			'EC' => 'Ecuador',
			'EG' => 'Egypt, Arab Rep.',
			'ER' => 'Eritrea',
			'ES' => 'Spain',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FI' => 'Finland',
			'FJ' => 'Fiji',
			'FR' => 'France',
			'FO' => 'Faroe Islands',
			'FM' => 'Micronesia, Fed. Sts.',
			'GA' => 'Gabon',
			'GB' => 'United Kingdom',
			'GE' => 'Georgia',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GN' => 'Guinea',
			'GM' => 'Gambia, The',
			'GW' => 'Guinea-Bissau',
			'GQ' => 'Equatorial Guinea',
			'GR' => 'Greece',
			'GD' => 'Grenada',
			'GL' => 'Greenland',
			'GT' => 'Guatemala',
			'GU' => 'Guam',
			'GY' => 'Guyana',
			'HK' => 'Hong Kong SAR, China',
			'HN' => 'Honduras',
			'HR' => 'Croatia',
			'HT' => 'Haiti',
			'HU' => 'Hungary',
			'ID' => 'Indonesia',
			'IM' => 'Isle of Man',
			'IN' => 'India',
			'IE' => 'Ireland',
			'IR' => 'Iran, Islamic Rep.',
			'IQ' => 'Iraq',
			'IS' => 'Iceland',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JO' => 'Jordan',
			'JP' => 'Japan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KG' => 'Kyrgyz Republic',
			'KH' => 'Cambodia',
			'KI' => 'Kiribati',
			'KN' => 'St. Kitts and Nevis',
			'KR' => 'Korea, Rep.',
			'KW' => 'Kuwait',
			'LA' => 'Lao PDR',
			'LB' => 'Lebanon',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LC' => 'St. Lucia',
			'LI' => 'Liechtenstein',
			'LK' => 'Sri Lanka',
			'LS' => 'Lesotho',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'LV' => 'Latvia',
			'MO' => 'Macao SAR, China',
			'MF' => 'St. Martin (French part)',
			'MA' => 'Morocco',
			'MC' => 'Monaco',
			'MD' => 'Moldova',
			'MG' => 'Madagascar',
			'MV' => 'Maldives',
			'MX' => 'Mexico',
			'MH' => 'Marshall Islands',
			'MK' => 'Macedonia, FYR',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MM' => 'Myanmar',
			'ME' => 'Montenegro',
			'MN' => 'Mongolia',
			'MP' => 'Northern Mariana Islands',
			'MZ' => 'Mozambique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'NA' => 'Namibia',
			'NC' => 'New Caledonia',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NI' => 'Nicaragua',
			'NL' => 'Netherlands',
			'NO' => 'Norway',
			'NP' => 'Nepal',
			'NR' => 'Nauru',
			'NZ' => 'New Zealand',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PA' => 'Panama',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PW' => 'Palau',
			'PG' => 'Papua New Guinea',
			'PL' => 'Poland',
			'PR' => 'Puerto Rico',
			'KP' => 'Korea, Dem. People’s Rep.',
			'PT' => 'Portugal',
			'PY' => 'Paraguay',
			'PS' => 'West Bank and Gaza',
			'PF' => 'French Polynesia',
			'QA' => 'Qatar',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'SA' => 'Saudi Arabia',
			'SD' => 'Sudan',
			'SN' => 'Senegal',
			'SG' => 'Singapore',
			'SB' => 'Solomon Islands',
			'SL' => 'Sierra Leone',
			'SV' => 'El Salvador',
			'SM' => 'San Marino',
			'SO' => 'Somalia',
			'RS' => 'Serbia',
			'SS' => 'South Sudan',
			'ST' => 'Sao Tome and Principe',
			'SR' => 'Suriname',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'SE' => 'Sweden',
			'SZ' => 'Swaziland',
			'SX' => 'Sint Maarten (Dutch part)',
			'SC' => 'Seychelles',
			'SY' => 'Syrian Arab Republic',
			'TC' => 'Turks and Caicos Islands',
			'TD' => 'Chad',
			'TG' => 'Togo',
			'TH' => 'Thailand',
			'TJ' => 'Tajikistan',
			'TM' => 'Turkmenistan',
			'TL' => 'Timor-Leste',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TV' => 'Tuvalu',
			'TW' => 'Taiwan, China',
			'TZ' => 'Tanzania',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'UY' => 'Uruguay',
			'US' => 'United States',
			'UZ' => 'Uzbekistan',
			'VC' => 'St. Vincent and the Grenadines',
			'VE' => 'Venezuela, RB',
			'VG' => 'British Virgin Islands',
			'VI' => 'Virgin Islands (U.S.)',
			'VN' => 'Vietnam',
			'VU' => 'Vanuatu',
			'WS' => 'Samoa',
			'XK' => 'Kosovo',
			'YE' => 'Yemen, Rep.',
			'ZA' => 'South Africa',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);

		$country_code = isset( $country_code ) ? strtoupper( $country_code ) : '';
		$country = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
		return $country;
	}

    public static function parse_custom_var( $custom ) {
		$delimiter = '&';
		$custom_variables = array();

		$name_value_combos = explode( $delimiter, $custom );
		foreach ( $name_value_combos as $key_val_unparsed ) {
			$equal_sign_position = strpos( $key_val_unparsed, '=' );
			if ( $equal_sign_position === false ) {
				$custom_variables[ $key_val_unparsed ] = '';
				continue;
			}
			$key                     = substr( $key_val_unparsed, 0, $equal_sign_position );
			$value                   = substr( $key_val_unparsed, $equal_sign_position + 1 );
			$custom_variables[ $key ] = $value;
		}

		return $custom_variables;
	}

	/**
	 * Creates an array of objecgts from the cart_items. This is useful for passing as the purchase units items
	 * @param array $cart_items
	 */
	public static function create_purchase_units_items_list( $cart_items ){
		if ( !is_array($cart_items) ) {
			//Cart is empty
			return '';
		}
	
		$currency = !empty(get_option( 'cart_payment_currency' )) ? get_option( 'cart_payment_currency' ) : 'USD';

		//Create the purchase unit items list.
		$purchase_unit_items_list = array();
		foreach ( $cart_items as $item ) {
			//Category is optional. 
			//If the 'digital' flag is set, then the category is 'DIGITAL_GOODS'. Otherwise, it is 'PHYSICAL_GOODS'.
			$category = 'PHYSICAL_GOODS';
			if( $item->is_digital_item() ){
				$category = 'DIGITAL_GOODS';
			}

			//Create an item object. It is very important to use the correct format.
			//Even if one comma is missed, it will not work.
			$pu_item = [
				"name" => $item->get_name(),
				"quantity" => $item->get_quantity(),
				"unit_amount" => [
					"value" => wpsc_number_format_price($item->get_price()),
					"currency_code" => $currency,
				],
				"category" => $category,
			];
			//Add the item object to the list to create an array of objects.
			$purchase_unit_items_list[] = $pu_item;
		}

		//Debugging purposes.		
		//PayPal_Utility_Functions::log_array( $purchase_unit_items_list, true );

		return $purchase_unit_items_list;
	}

}