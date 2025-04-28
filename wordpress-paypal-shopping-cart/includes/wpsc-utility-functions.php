<?php

function wpsc_contains_special_char($str) {
    //This function checks if the input string contains any of the special characters that are not allowed in the product name.
    // The set of unsupported special characters: [, ], <, >
    $special_chars = "[]<>";

    // Use strpbrk to check if any of the special characters are in the input string
    if( strpbrk($str, $special_chars) !== false ) {
        // One of the special characters is in the input string
        return true;
    }
    // None of the special characters is in the input string
    return false;
}

function wpsc_is_txn_already_processed( $order_id, $ipn_data ){
    $txn_id = $ipn_data['txn_id'];
    $transaction_id = get_post_meta( $order_id, 'wpsc_txn_id', true );
    if (! empty( $transaction_id )) {
        if ($transaction_id == $txn_id) { 
            //this transaction has been already processed once
            return true;
        }
    }
    return false;
}

/**
 * @deprecated This method has been deprecated. Use get_total_cart_qty() from WPSC_Cart instead.
 * @return int Returns the total number of items in the cart.
 */
function wpsc_get_total_cart_qty() {
    $wspsc_cart = WPSC_Cart::get_instance();
    $total_items = $wspsc_cart->get_total_cart_qty();
    return $total_items;
}

/**
 * @deprecated This method has been deprecated. Use get_total_cart_sub_total() from WPSC_Cart instead.
 * @return int Returns the total number of items in the cart.
 */
function wpsc_get_total_cart_sub_total() {
    $wspsc_cart = WPSC_Cart::get_instance();
    $total_sub_total = $wspsc_cart->get_total_cart_sub_total();
    return $total_sub_total;
}

function wpsc_clean_incomplete_old_cart_orders() {
    //Empty any incomplete old cart orders (that are more than 48 hours old)
    global $wpdb;
    $specific_time = date('Y-m-d H:i:s', strtotime('-48 hours'));
    $wpdb->query(
            $wpdb->prepare("DELETE FROM $wpdb->posts
                 WHERE post_type = %s
                 AND post_status = %s
                 AND post_date < %s
                ", WPSC_Cart::POST_TYPE, 'trash', $specific_time
            )
    );
}

function wpsc_get_countries(){
    return array (
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
}

function wpsc_get_country_name_by_country_code( $country_code ) {
    $countries = wpsc_get_countries();
    $country_code = isset( $country_code ) ? strtoupper( $country_code ) : '';
    $country = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
    return $country;
}

function wpsc_get_countries_opts( $selected = false ) {
    $countries = wpsc_get_countries();
    asort( $countries );

    $out       = '';
    $tpl       = '<option value="%s"%s>%s</option>';
    foreach ( $countries as $c_code => $c_name ) {
        $selected_str = '';
        if ( false !== $selected ) {
            if ( $c_code === $selected ) {
                $selected_str = ' selected';
            }
        }
        $out .= sprintf( $tpl, esc_attr( $c_code ), $selected_str, esc_html( $c_name ) );
    }
    return $out;
}

/**
 * Check whether the given string is a proper shipping region lookup string or not.
 *
 * @param string $str Shipping regions lookup string.
 * 
 * @return array|bool If valid, return the shipping regions array option, FALSE otherwise
 */
function check_shipping_region_str($str){
    // Check if customer have not selected any shipping region option.
    if (empty($str) || $str == '-1') {
        return false;
    }
    
    // Get the available shipping region options set in admin end.
    $available_region_options = get_option('wpsc_shipping_region_variations');
    
    $str_to_arr = explode(':', $str);

    foreach ($available_region_options as $region) {
        if ($str_to_arr[0] === strtolower($region['loc']) && isset($str_to_arr[1]) && $str_to_arr[1] == $region['type']) {
            // The shipping region string is valid, return the original array element.
            return $region;
        }
    }

    return false;
}

function wpsc_get_cart_cpt_id_by_cart_id( $cart_id ) {
	$query = new WP_Query(
		array(
			'post_type'      => WPSC_Cart::POST_TYPE,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'), // TODO: this might need a optimization.
			'meta_query'     => array(
				array(
					'key'   => 'wpsc_cart_id',
					'value' => $cart_id,
					'compare' => '='
				)
			)
		)
	);

	if ( ! empty( $query->posts ) ) {
		return intval( $query->posts[0] );
	}

	return 0; // Not found
}