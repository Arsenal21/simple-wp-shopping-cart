<?php

function wpsc_render_manual_checkout_form() {
	wp_enqueue_script( "wpsc-checkout-manual" );

	$wpsc_cart = WPSC_Cart::get_instance();
	$cart_id = $wpsc_cart->get_cart_id();
	$is_all_cart_items_digital = $wpsc_cart->all_cart_items_digital();

	$manual_checkout_hide_country_field = get_option( 'wpsc_manual_checkout_hide_country_field' , false);

	$output = '';

	$output .= '<div class="wpsc-manual-checkout-section">';
	$output .= '<div class="wpsc-manual-payment-form-wrap">';
	$output .= '<form class="wpsc-manual-payment-form" style="display: none">';
	$manual_checkout_form_instruction = get_option( 'wpsc_manual_checkout_form_instruction' , '');
	if (!empty($manual_checkout_form_instruction)){
		$output .= '<div class="wpsc-manual-payment-form-instructions">' . wp_kses_post($manual_checkout_form_instruction) . '</div>';
	}

	$output .= '<div class="wpsc-manual-payment-form-fields">';

	$output .= '<div class="wpsc-manual-payment-form-basic-fields">';

	$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-fname-field">';
	$output .= '<label class="wpsc-manual-payment-form-label">'. __('First Name','wordpress-simple-paypal-shopping-cart') . '<br>';
	$output .= '<input type="text" class="wpsc-manual-payment-form-fname" name="wpsc_manual_payment_form_fname" value="" />';
	$output .= '</label>';
	$output .= '</div>';

	$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-lname-field">';
	$output .= '<label class="wpsc-manual-payment-form-label">'. __('Last Name','wordpress-simple-paypal-shopping-cart') . '<br>';
	$output .= '<input type="text" class="wpsc-manual-payment-form-lname" name="wpsc_manual_payment_form_lname" value="" />';
	$output .= '</label>';
	$output .= '</div>';

	$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-email-field">';
	$output .= '<label class="wpsc-manual-payment-form-label">'. __('Email','wordpress-simple-paypal-shopping-cart') . '<br>';
	$output .= '<input type="text" class="wpsc-manual-payment-form-email" name="wpsc_manual_payment_form_email" value="" />';
	$output .= '</label>';
	$output .= '</div>';

	$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-phone-field">';
	$output .= '<label class="wpsc-manual-payment-form-label">'. __('Phone','wordpress-simple-paypal-shopping-cart') . '<br>';
	$output .= '<input type="text" class="wpsc-manual-payment-form-phone" name="wpsc_manual_payment_form_phone" value="" />';
	$output .= '</label>';
	$output .= '</div>';
	$output .= '</div>';

	// end of 'wpsc-manual-payment-form-basic-fields'

	if ( !$is_all_cart_items_digital ) {
	    $output .= '<div class="wpsc-manual-payment-address-section-label">'. __('Shipping Address', 'wordpress-simple-paypal-shopping-cart') .'</div>';

	    $output .= '<div class="wpsc-manual-payment-form-address-fields">';

		$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-street-address-field">';
		$output .= '<label class="wpsc-manual-payment-form-label">' . __( 'Street Address', 'wordpress-simple-paypal-shopping-cart' ) . '<br>';
		$output .= '<input type="text" class="wpsc-manual-payment-form-street" name="wpsc_manual_payment_form_street" value="" />';
		$output .= '</label>';
		$output .= '</div>';

		$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-city-field">';
		$output .= '<label class="wpsc-manual-payment-form-label">' . __( 'City', 'wordpress-simple-paypal-shopping-cart' ) . '<br>';
		$output .= '<input type="text" class="wpsc-manual-payment-form-city" name="wpsc_manual_payment_form_city" value="" />';
		$output .= '</label>';
		$output .= '</div>';

		if (empty($manual_checkout_hide_country_field)){
		$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-country-field">';
		$output .= '<label class="wpsc-manual-payment-form-label">' . __( 'Country', 'wordpress-simple-paypal-shopping-cart' ) . '<br>';
		$output .= '<select class="wpsc-manual-payment-form-country">';
		$output .= '<option value="">' . __( 'Select one', 'wordpress-simple-paypal-shopping-cart' ) . '</option>';
		$output .=  wpsc_get_countries_opts();
		$output .= '</select>';
		$output .= '</label>';
		$output .= '</div>';
		}

		$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-state-field">';
		$output .= '<label class="wpsc-manual-payment-form-label">' . __( 'State', 'wordpress-simple-paypal-shopping-cart' ) . '<br>';
		$output .= '<input type="text" class="wpsc-manual-payment-form-state" name="wpsc_manual_payment_form_state" value="" />';
		$output .= '</label>';
		$output .= '</div>';

		$output .= '<div class="wpsc-manual-payment-form-field wpsc-manual-payment-postal-code-field">';
		$output .= '<label class="wpsc-manual-payment-form-label">' . __( 'Postal Code', 'wordpress-simple-paypal-shopping-cart' ) . '<br>';
		$output .= '<input type="text" class="wpsc-manual-payment-form-postal-code" name="wpsc_manual_payment_form_postal_code" value="" />';
		$output .= '</label>';
		$output .= '</div>';

		$output .= '</div>'; // end of 'wpsc-manual-payment-address-form-fields'
	}

	$output .= '</div>'; // end of 'wpsc-manual-payment-form-fields'

	$output .= wp_nonce_field('wpsc_manual_payment_form_nonce_action', 'wpsc_manual_payment_form_nonce', true, false);
	$output .= '<input type="hidden" name="wpsc_manual_payment_form_card_id" value="'.esc_attr($cart_id).'" />';
	
	$output .= apply_filters('wpsc_before_manual_checkout_form_submit', '', $wpsc_cart);

	$output .= '<p class="submit wpsc-manual-payment-form-submit-section">';
	$output .= '<input type="submit" class="wpsc-manual-payment-form-submit" value="'. __('Place Order','wordpress-simple-paypal-shopping-cart') .'" />';
	$output .= '<input type="reset" class="wpsc-manual-payment-form-cancel" value="'. __('Cancel','wordpress-simple-paypal-shopping-cart') .'" />';
	$output .= '</p>';

	$output .= '<div class="wpsc-manual-payment-form-loader"></div>';

	$output .= '</form>';

	$output .= '</div>'; // end of 'wpsc-manual-payment-form-wrap'

	$output .= '<button type="button" class="wpsc-manual-payment-proceed-to-checkout-btn" >'. get_option( 'wpsc_manual_checkout_btn_text' , '') .'</button>';

	$output .= '</div>'; // end of 'wpsc-manual-checkout-section'

	return $output;
}