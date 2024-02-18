<?php

use TTHQ\WPSC\Lib\PayPal\PayPal_PPCP_Config;

function print_wp_shopping_cart( $args = array() ) {
	$wspsc_cart = WSPSC_Cart::get_instance();
	$wspsc_cart->calculate_cart_totals_and_postage();

	//Get the on page cart div ID. This will increment the count so we start from 1.
	$on_page_cart_div_id = $wspsc_cart->get_next_on_page_cart_div_id();
	//Get the current count of on page cart divs (used in various places of HTML and JS code).
	$carts_cnt = $wspsc_cart->get_on_page_carts_div_count();

	$output = '';

	//Check and handle the cart empty case
	if ( ! $wspsc_cart->cart_not_empty() ) {
		$empty_cart_text = get_option( 'wp_cart_empty_text' );
		if ( ! empty( $empty_cart_text ) ) {
			$output .= '<div class="wp_cart_empty_cart_section">';
			if ( preg_match( '/http/', $empty_cart_text ) ) {
				$output .= '<img src="' . $empty_cart_text . '" alt="' . $empty_cart_text . '" class="wp_cart_empty_cart_image" />';
			} else {
				$output .= __( $empty_cart_text, 'wordpress-simple-paypal-shopping-cart' );
			}
			$output .= '</div>';
		}
		$cart_products_page_url = get_option( 'cart_products_page_url' );
		if ( ! empty( $cart_products_page_url ) ) {
			$output .= '<div class="wp_cart_visit_shop_link"><a rel="nofollow" href="' . esc_url( $cart_products_page_url ) . '">' . ( __( 'Visit The Shop', 'wordpress-simple-paypal-shopping-cart' ) ) . '</a></div>';
		}
		return $output;
	}

	//Get the default currency and other settings.
	$email = get_bloginfo( 'admin_email' );
	$defaultCurrency = get_option( 'cart_payment_currency' );
	$defaultSymbol = get_option( 'cart_currency_symbol' );
	$defaultEmail = get_option( 'cart_paypal_email' );
		if ( ! empty( $defaultCurrency ) ) {
		$paypal_currency = $defaultCurrency;
	} else {
		$paypal_currency = __( 'USD', 'wordpress-simple-paypal-shopping-cart' );
	}
	if ( ! empty( $defaultSymbol ) ) {
		$paypal_symbol = $defaultSymbol;
	} else {
		$paypal_symbol = __( '$', 'wordpress-simple-paypal-shopping-cart' );
	}

	if ( ! empty( $defaultEmail ) ) {
		$email = $defaultEmail;
	}

	$decimal = '.';
	$urls = '';

	$return = get_option( 'cart_return_from_paypal_url' );
	if ( empty( $return ) ) {
		$return = WP_CART_SITE_URL . '/';
	}
	$return_url = add_query_arg( 'reset_wp_cart', '1', $return );

	$urls .= '<input type="hidden" name="return" value="' . $return_url . '" />';

	$cancel = get_option( 'cart_cancel_from_paypal_url' );
	if ( isset( $cancel ) && ! empty( $cancel ) ) {
		$urls .= '<input type="hidden" name="cancel_return" value="' . $cancel . '" />';
	}

	$notify = WP_CART_SITE_URL . '/?simple_cart_ipn=1';
	$notify = apply_filters( 'wspsc_paypal_ipn_notify_url', $notify );
	$urls .= '<input type="hidden" name="notify_url" value="' . $notify . '" />';

	$title = get_option( 'wp_cart_title' );

	//Start outputting the main cart div and contents.
	$output .= '<div id="'.$on_page_cart_div_id.'" class="shopping_cart">';
	$output .= '<a name="wpsc_cart_anchor"></a>';
	if ( ! get_option( 'wp_shopping_cart_image_hide' ) ) {
		$cart_icon_img_src = WP_CART_URL . '/images/shopping_cart_icon.png';
		$cart_icon_img_src = apply_filters( 'wspsc_cart_icon_image_src', $cart_icon_img_src );
		$output .= "<img src='" . $cart_icon_img_src . "' class='wspsc_cart_header_image' value='" . ( __( 'Cart', 'wordpress-simple-paypal-shopping-cart' ) ) . "' alt='" . ( __( 'Cart', 'wordpress-simple-paypal-shopping-cart' ) ) . "' />";
	}
	if ( ! empty( $title ) ) {
		$output .= '<h2 class="wpsc_cart_title">';
		$output .= $title;
		$output .= '</h2>';
	}

	$output .= '<span id="pinfo" style="display: none; font-weight: bold; color: red;">' . ( __( 'Hit enter to submit new Quantity.', 'wordpress-simple-paypal-shopping-cart' ) ) . '</span>';
	$output .= '<table style="width: 100%;">';

	$count = 1;
	$total = 0;
	$form = '';
	if ( $wspsc_cart->get_items() ) {
		$output .= '
        <tr class="wspsc_cart_item_row">
        <th class="wspsc_cart_item_name_th">' . ( __( 'Item Name', 'wordpress-simple-paypal-shopping-cart' ) ) . '</th><th class="wspsc_cart_qty_th">' . ( __( 'Quantity', 'wordpress-simple-paypal-shopping-cart' ) ) . '</th><th class="wspsc_cart_price_th">' . ( __( 'Price', 'wordpress-simple-paypal-shopping-cart' ) ) . '</th><th class="wspsc_remove_item_th"></th>
        </tr>';

		$total = $wspsc_cart->get_total_cart_sub_total();
		$postage_cost = $wspsc_cart->get_postage_cost();

		$cart_free_shipping_threshold = get_option( 'cart_free_shipping_threshold' );
		if ( ! empty( $cart_free_shipping_threshold ) && $total > $cart_free_shipping_threshold ) {
			$postage_cost = 0;
		}

		$item_tpl = "{name: '%s', quantity: '%d', price: '%s', currency: '" . $paypal_currency . "'}";
		$items_list = '';

		foreach ( $wspsc_cart->get_items() as $item ) {
			//Let's form JS array of items for Smart Checkout
			$number_formatted_item_price = wpspsc_number_format_price( $item->get_price() );
			$items_list .= sprintf( $item_tpl, esc_js( $item->get_name() ), esc_js( $item->get_quantity() ), esc_js( $number_formatted_item_price ) ) . ',';

			$output .= '<tr class="wspsc_cart_item_thumb"><td class="wspsc_cart_item_name_td" style="overflow: hidden;">';
			$output .= '<div class="wp_cart_item_info">';
			if ( isset( $args['show_thumbnail'] ) && ! empty( $item->get_thumbnail() ) ) {
				$output .= '<span class="wp_cart_item_thumbnail"><img src="' . esc_url( $item->get_thumbnail() ) . '" class="wp_cart_thumb_image" alt="' . esc_attr( $item->get_name() ) . '" ></span>';
			}
			$item_info = apply_filters( 'wspsc_cart_item_name', '<a href="' . esc_url( $item->get_cart_link() ) . '">' . esc_attr( $item->get_name() ) . '</a>', $item );
			$output .= '<span class="wp_cart_item_name">' . $item_info . '</span>';
			$output .= '<span class="wp_cart_clear_float"></span>';
			$output .= '</div>';
			$output .= '</td>';

			$uniqid = uniqid();

			$output .= "<td class='wspsc_cart_qty_td' style='text-align: center'><form method=\"post\"  action=\"\" name='pcquantity_" . $uniqid . "' style='display: inline'>" . wp_nonce_field( 'wspsc_cquantity', '_wpnonce', true, false ) . '
                <input type="hidden" name="wspsc_product" value="' . htmlspecialchars( $item->get_name() ) . "\" />
	        <input type='hidden' name='cquantity' value='1' /><input type='number' class='wspsc_cart_item_qty' name='quantity' value='" . esc_attr( $item->get_quantity() ) . "' min='0' step='1' size='3' onchange='document.pcquantity_" . $uniqid . ".submit();' onkeypress='document.getElementById(\"pinfo\").style.display = \"\";' /></form></td>
	        <td style='text-align: center'>" . print_payment_currency( ( $item->get_price() * $item->get_quantity() ), $paypal_symbol, $decimal ) . "</td>
	        <td class='wspsc_remove_item_td'><form method=\"post\" action=\"\" class=\"wp_cart_remove_item_form\">" . wp_nonce_field( 'wspsc_delcart', '_wpnonce', true, false ) . '
	        <input type="hidden" name="wspsc_product" value="' . esc_attr( $item->get_name() ) . "\" />
	        <input type='hidden' name='delcart' value='1' />
	        <input type='image' src='" . WP_CART_URL . "/images/Shoppingcart_delete.png' value='" . ( __( 'Remove', 'wordpress-simple-paypal-shopping-cart' ) ) . "' title='" . ( __( 'Remove', 'wordpress-simple-paypal-shopping-cart' ) ) . "' /></form></td></tr>
	        ";

			$form .= "
	            <input type=\"hidden\" name=\"item_name_$count\" value=\"" . esc_attr( $item->get_name() ) . "\" />
	            <input type=\"hidden\" name=\"amount_$count\" value='" . wpspsc_number_format_price( $item->get_price() ) . "' />
	            <input type=\"hidden\" name=\"quantity_$count\" value=\"" . esc_attr( $item->get_quantity() ) . "\" />
	            <input type='hidden' name='item_number_$count' value='" . esc_attr( $item->get_item_number() ) . "' />
	        ";
			$count++;
		}
		$items_list = rtrim( $items_list, ',' );
		if ( ! get_option( 'wp_shopping_cart_use_profile_shipping' ) ) {
			//Not using profile based shipping
			$postage_cost = wpspsc_number_format_price( $postage_cost );
			$form .= "<input type=\"hidden\" name=\"shipping_1\" value='" . esc_attr( $postage_cost ) . "' />"; //You can also use "handling_cart" variable to use shipping and handling here
		}

		//Tackle the "no_shipping" parameter
		if ( get_option( 'wp_shopping_cart_collect_address' ) ) { //force address collection
			$form .= '<input type="hidden" name="no_shipping" value="2" />';
		} else {
			//Not using the force address collection feature
			if ( $postage_cost == 0 ) {
				//No shipping amount present in the cart. Set flag for "no shipping address collection".
				$form .= '<input type="hidden" name="no_shipping" value="1" />';
			}
		}
	}

	$count--;

	if ( $count ) {

		wp_enqueue_script( "wspsc-checkout-cart-script" );

		if ( $postage_cost != 0 ) {
			$output .= "
                <tr class='wspsc_cart_subtotal'><td colspan='2' style='font-weight: bold; text-align: right;'>" . ( __( 'Subtotal', 'wordpress-simple-paypal-shopping-cart' ) ) . ": </td><td style='text-align: center'>" . print_payment_currency( $total, $paypal_symbol, $decimal ) . "</td><td></td></tr>
                <tr class='wspsc_cart_shipping'><td colspan='2' style='font-weight: bold; text-align: right;'>" . ( __( 'Shipping', 'wordpress-simple-paypal-shopping-cart' ) ) . ": </td><td style='text-align: center'>" . print_payment_currency( $postage_cost, $paypal_symbol, $decimal ) . '</td><td></td></tr>';
		}

		$output .= "<tr class='wspsc_cart_total'><td colspan='2' style='font-weight: bold; text-align: right;'>" . ( __( 'Total', 'wordpress-simple-paypal-shopping-cart' ) ) . ": </td><td style='text-align: center'>" . print_payment_currency( ( $total + $postage_cost ), $paypal_symbol, $decimal ) . '</td><td></td></tr>';

		$wpspsc_cart_action_msg = $wspsc_cart->get_cart_action_msg();
		if ( $wpspsc_cart_action_msg ) {
			$output .= '<tr class="wspsc_cart_action_msg"><td colspan="4"><span class="wpspsc_cart_action_msg">' . $wpspsc_cart_action_msg . '</span></td></tr>';
		}

		if ( get_option( 'wpspsc_enable_coupon' ) == '1' ) {
			$output .= '<tr class="wspsc_cart_coupon_row"><td colspan="4">
                <div class="wpspsc_coupon_section">
                <span class="wpspsc_coupon_label">' . ( __( 'Enter Coupon Code', 'wordpress-simple-paypal-shopping-cart' ) ) . '</span>
                <form  method="post" action="" >' . wp_nonce_field( 'wspsc_coupon', '_wpnonce', true, false ) . '
                <input type="text" name="wpspsc_coupon_code" value="" size="10" />
                <span class="wpspsc_coupon_apply_button"><input type="submit" name="wpspsc_apply_coupon" class="wpspsc_apply_coupon" value="' . ( __( 'Apply', 'wordpress-simple-paypal-shopping-cart' ) ) . '" /></span>
                </form>
                </div>
                </td></tr>';
		}

		$paypal_checkout_url = WP_CART_LIVE_PAYPAL_URL;
		if ( get_option( 'wp_shopping_cart_enable_sandbox' ) ) {
			$paypal_checkout_url = WP_CART_SANDBOX_PAYPAL_URL;
		}

		$form_target_code = '';
		if ( get_option( 'wspsc_open_pp_checkout_in_new_tab' ) ) {
			$form_target_code = 'target="_blank"';
		}

		$output = apply_filters( 'wpspsc_before_checkout_form', $output );

		$output .= "<tr class='wpspsc_checkout_form'><td colspan='4'>";

		$is_shipping_by_region_enabled = get_option('enable_shipping_by_region');
		
		if ( $is_shipping_by_region_enabled ) {
			$selected_shipping_region_variant = $wspsc_cart->get_selected_shipping_region();
			$output .= wspsc_generate_shipping_region_section($carts_cnt, $selected_shipping_region_variant);
		}

		// Check if terms and conditions are enabled or not.
		$is_tnc_enabled = get_option( 'wp_shopping_cart_enable_tnc' ) != '';
		if ( $is_tnc_enabled ) {
			$output .= wspsc_generate_tnc_section( $carts_cnt );
		}
		$output .= '<form action="' . $paypal_checkout_url . '" method="post" ' . $form_target_code . ' class="wspsc_checkout_form_standard">';
		$output .= $form;
		$style = get_option( 'wpspc_disable_standard_checkout' ) ? 'display:none !important" data-wspsc-hidden="1' : '';
		if ( $count ) {
			$checkout_button_img_src = WP_CART_URL . '/images/' . ( __( 'paypal_checkout_EN.png', 'wordpress-simple-paypal-shopping-cart' ) );
			$output .= '<input type="image" src="' . apply_filters( 'wspsc_cart_checkout_button_image_src', $checkout_button_img_src ) . '" name="submit" class="wp_cart_checkout_button wp_cart_checkout_button_' . $carts_cnt . '" style="' . $style . '" alt="' . ( __( "Make payments with PayPal - it\'s fast, free and secure!", 'wordpress-simple-paypal-shopping-cart' ) ) . '" />';
		}

		$output .= $urls . '
            <input type="hidden" name="business" value="' . $email . '" />
            <input type="hidden" name="currency_code" value="' . $paypal_currency . '" />
            <input type="hidden" name="cmd" value="_cart" />
            <input type="hidden" name="upload" value="1" />
            <input type="hidden" name="rm" value="2" />
            <input type="hidden" name="charset" value="utf-8" />
            <input type="hidden" name="bn" value="TipsandTricks_SP" />';

		$page_style_name = get_option( 'wp_cart_paypal_co_page_style' );
		if ( ! empty( $page_style_name ) ) {
			$output .= '<input type="hidden" name="image_url" value="' . $page_style_name . '" />';
		}
		$output .= wp_cart_add_custom_field();

		$extra_pp_fields = apply_filters( 'wspsc_cart_extra_paypal_fields', '' ); //Can be used to add extra PayPal hidden input fields for the cart checkout
		$output .= $extra_pp_fields;

		$output .= '</form>';
		//END of standard PayPal checkout form

		//The args array that we will use to pass any data to the functions that render the checkout forms. 
		//The cart object can be retrieved from the global scope (if additional details from cart is needed).
		$args = array( 
			'carts_cnt' => $carts_cnt, 
			'total' => $total, 
			'postage_cost' => $postage_cost, 
			'currency' => $paypal_currency, 
			'return_url' => $return_url, 
			'items_list' => $items_list, 
			'is_tnc_enabled' => $is_tnc_enabled 
		);
		
		//--- Start PayPal (New API) Checkout ---
		$paypal_ppcp_configs = PayPal_PPCP_Config::get_instance();
		$paypal_ppcp_checkout_enabled = $paypal_ppcp_configs->get_value('ppcp_checkout_enable');	
		if( !empty($paypal_ppcp_checkout_enabled) ){
			//PayPal (New API) option is enabled.
			$ppcp_checkout_output = wpsc_render_paypal_ppcp_checkout_form($args);
			$output .= $ppcp_checkout_output;
		}
		//End of PayPal (New API) Checkout.

		//--- Start PayPal Smart Checkout ---
		//Smart checkout option is only displayed if PayPal (New API) option is disabled. 
		if ( empty($paypal_ppcp_checkout_enabled) && get_option( 'wpspc_enable_pp_smart_checkout' ) ) {
			//Show PayPal Smart Payment Button

			//adding form in smart checkout button, so simple cart collect customer input adon works
			$output .= '<form action="" method="POST" class="wpspc_pp_smart_checkout_form">';

			//Some number formatting (before it is used in JS code.
			$formatted_total = wpspsc_number_format_price( $total );
			$formatted_postage_cost = wpspsc_number_format_price( $postage_cost );
			$totalpluspostage = ( $total + $postage_cost );
			$formatted_totalpluspostage = wpspsc_number_format_price( $totalpluspostage );

			//check mode and if client ID is set for it
			$client_id = get_option( 'wp_shopping_cart_enable_sandbox' ) ? get_option( 'wpspc_pp_test_client_id' ) : get_option( 'wpspc_pp_live_client_id' );
			if ( empty( $client_id ) ) {
				//client ID is not set
				$output .= '<div style="color: red;">' . sprintf( __( 'PayPal Smart Checkout error: %s client ID is not set. Please set it on the PayPal Smart Checkout Settings tab.', 'wordpress-simple-paypal-shopping-cart' ), get_option( 'wp_shopping_cart_enable_sandbox' ) ? 'Sandbox' : 'Live' ) . '</div>';
			} else {
				//checkout script should be inserted only once, otherwise it would produce JS error
				//Load the JS SDK on footer so it only loads once per page (if the cart is present)
				add_action( 'wp_footer', 'wspsc_load_paypal_smart_checkout_js' );

				$btn_layout = get_option( 'wpspc_pp_smart_checkout_btn_layout' );
				$btn_layout = empty( $btn_layout ) ? 'vertical' : $btn_layout;
				$btn_size = get_option( 'wpspc_pp_smart_checkout_btn_size' );
				$btn_size = empty( $btn_size ) ? 'medium' : $btn_size;
				$btn_shape = get_option( 'wpspc_pp_smart_checkout_btn_shape' );
				$btn_shape = empty( $btn_shape ) ? 'rect' : $btn_shape;
				$btn_color = get_option( 'wpspc_pp_smart_checkout_btn_color' );
				$btn_color = empty( $btn_color ) ? 'gold' : $btn_color;

				$pm_str = '';

				$pm_credit = get_option( 'wpspc_pp_smart_checkout_payment_method_credit' );
				$pm_str .= empty( $pm_credit ) ? '' : ', paypal.FUNDING.CREDIT';
				$pm_elv = get_option( 'wpspc_pp_smart_checkout_payment_method_elv' );
				$pm_str .= empty( $pm_elv ) ? '' : ', paypal.FUNDING.ELV';

				ob_start();
				?>

				<div class="wp-cart-paypal-button-container-<?php echo $carts_cnt; ?>"></div>
				<input type="submit" class="wpspc_pp_smart_checkout_form_<?php echo $carts_cnt; ?> wp_cart_checkout_button"
					style="display:none" />
				</form>

				<script type="text/javascript">
					// Get terms and condition settings.
					var wpspscTncEnabled = <?php echo $is_tnc_enabled ? 'true' : 'false' ?>;
					var wpscShippingRegionEnabled = <?php echo $is_shipping_by_region_enabled ? 'true' : 'false' ?>;

					document.addEventListener('wspsc_paypal_smart_checkout_sdk_loaded', function () {

						//disable form submission, as it is smart checkout
						jQuery(".wpspc_pp_smart_checkout_form").submit(false);

						//Anything that goes here will only be executed after the PayPal SDK is loaded.
						console.log('PayPal Smart Checkout SDK loaded.');

						var wpspsc_cci_do_submit = true;

						paypal.Button.render({
							env: '<?php echo get_option( 'wp_shopping_cart_enable_sandbox' ) ? 'sandbox' : 'production'; ?>',
							style: {
								layout: '<?php echo esc_js( $btn_layout ); ?>',
								size: '<?php echo esc_js( $btn_size ); ?>',
								shape: '<?php echo esc_js( $btn_shape ); ?>',
								color: '<?php echo esc_js( $btn_color ); ?>'
							},
							funding: {
								allowed: [paypal.FUNDING.CARD<?php echo $pm_str; ?>],
								disallowed: []
							},
							client: {
								sandbox: '<?php echo get_option( 'wpspc_pp_test_client_id' ); ?>',
								production: '<?php echo get_option( 'wpspc_pp_live_client_id' ); ?>'
							},
							validate: function (actions) {
								//			    wpspsc_pp_actions = actions;
								//			    wpspsc_pp_actions.disable();

								//validate only runs when buttons render first time
								jQuery(document).ready(function ($) {

									actions.enable();

									/**
									 * The codes below will run only if the customer input addon is installed and there are fields added.
									 */

									// checks if there is any required input field with empty value.
									if (jQuery('.wpspsc_cci_input').length > 0 && has_empty_required_input(<?php echo $carts_cnt; ?>)) {
										actions.disable();
									}

									// Disable paypal smart checkout form submission if terms and condition validation error.
									const currentSmartPaymentForm = '.wpspc_pp_smart_checkout_form_<?php echo $carts_cnt; ?>';
									if (!wspsc_validateTnc(currentSmartPaymentForm, false)) {
										actions.disable();
									}
									if (!wspsc_validateShippingRegion(currentSmartPaymentForm, false)) {
										actions.disable();
									}

									// listen to change in inputs and check if any empty required input fields.
									jQuery('.wpspsc_cci_input, .wp_shopping_cart_tnc_input').on('change', function () {
										let isAnyValidationError = false;
										if (has_empty_required_input(<?php echo $carts_cnt; ?>)) {
											isAnyValidationError = true;
										}
										
										// Also check if terms and condition has checked.
										if (wpspscTncEnabled) {
											if (!wspsc_validateTnc(currentSmartPaymentForm, false)) {
												isAnyValidationError = true;
											}
										}

										// Also check if shipping by region has selected.	
										if (wpscShippingRegionEnabled){
											if (!wspsc_validateShippingRegion(currentSmartPaymentForm, false)) {
												isAnyValidationError = true;
											}
										}

										if (isAnyValidationError) {
											// There is a validation error, don't proceed to checkout.
											actions.disable();
										} else {
											actions.enable();
										}
									});
								});
							},
							onClick: function () {
								wpspsc_cci_do_submit = false;
								var res = jQuery('.wpspc_pp_smart_checkout_form_<?php echo $carts_cnt; ?>').triggerHandler('click');
								// if (typeof res === "undefined" || res) {
								//				    wpspsc_pp_actions.enable();
								// } else {
								//				    wpspsc_pp_actions.disable();
								// }
								wpspsc_cci_do_submit = true;

								const currentSmartPaymentForm = '.wpspc_pp_smart_checkout_form_<?php echo $carts_cnt; ?>';
								// Check if shipping region is enabled and append error message if validation fails.
								if (wpscShippingRegionEnabled) {
									const shippingRegionContainer = wspsc_getClosestElement(currentSmartPaymentForm, wpscShippingRegionContainerSelector)
									wspsc_handleShippingRegionErrorMsg(shippingRegionContainer);
								}

								// Check if terms and condition is enabled and append error message if not checked.
								if (wpspscTncEnabled) {
									const tncContainer = wspsc_getClosestElement(currentSmartPaymentForm, wspscTncContainerSelector)
									wspsc_handleTncErrorMsg(tncContainer);
								}

							},
							payment: function (data, actions) {
								return actions.payment.create({
									payment: {
										transactions: [{
											amount: {
												total: '<?php echo $formatted_totalpluspostage; ?>', currency: '<?php echo $paypal_currency; ?>',
												details: { subtotal: '<?php echo $formatted_total; ?>', shipping: '<?php echo $formatted_postage_cost; ?>' }
											},
											item_list: {
												items: [<?php echo $items_list; ?>]
											}
										}]
									},
									meta: { partner_attribution_id: 'TipsandTricks_SP' }
								});
							},
							onError: function (error) {
								console.log(error);
								alert('<?php echo esc_js( __( 'Error occured during PayPal Smart Checkout process.', 'wordpress-simple-paypal-shopping-cart' ) ); ?>\n\n' + error);
							},
							onAuthorize: function (data, actions) {
								jQuery("[class^='wp-cart-paypal-button-container']").hide();
								jQuery('.wp_cart_checkout_button').hide();
								jQuery('.wpspsc-spinner-cont').css('display', 'inline-block');
								return actions.payment.execute().then(function (data) {
									jQuery.post('<?php echo get_admin_url(); ?>admin-ajax.php',
										{ 'action': 'wpspsc_process_pp_smart_checkout', 'wpspsc_payment_data': data })
										.done(function (result) {
											if (result.success) {
												window.location.href = '<?php echo esc_js( $return_url ); ?>';
											} else {
												console.log(result);
												alert(result.errMsg)
												jQuery("[class^='wp-cart-paypal-button-container']").show();
												if (jQuery('.wp_cart_checkout_button').data('wspsc-hidden') !== "1") {
													jQuery('.wp_cart_checkout_button').show();
												}
												jQuery('.wp_cart_checkout_button').show();
												jQuery('.wpspsc-spinner-cont').hide();
											}
										})
										.fail(function (result) {
											console.log(result);
											jQuery("[class^='wp-cart-paypal-button-container']").show();
											if (jQuery('.wp_cart_checkout_button').data('wspsc-hidden') !== "1") {
												jQuery('.wp_cart_checkout_button').show();
											}
											jQuery('.wpspsc-spinner-cont').hide();
											alert('<?php echo esc_js( __( 'HTTP error occured during payment process:', 'wordpress-simple-paypal-shopping-cart' ) ); ?>' + ' ' + result.status + ' ' + result.statusText);
										});
								});
							}
						}, '.wp-cart-paypal-button-container-<?php echo $carts_cnt; ?>');

					});

					/**
					 * Checks if any input element has required attribute with empty value
					 * @param cart_no Target cart no.
					 * @returns {boolean}
					 */
					function has_empty_required_input(cart_no) {
						let has_any = false;
						let target_input = '.wpspsc_cci_input';
						let target_form = jQuery('.wpspc_pp_smart_checkout_form_' + cart_no).closest('.shopping_cart');

						jQuery(target_form).find(target_input).each(function () {
							if (jQuery(this).prop("required") && !jQuery(this).val().trim()) {
								has_any = true;
							}
						});

						return has_any;
					}
				</script>
				<style>
					@keyframes wpspsc-spinner {
						to {
							transform: rotate(360deg);
						}
					}

					.wpspsc-spinner {
						margin: 0 auto;
						text-indent: -9999px;
						vertical-align: middle;
						box-sizing: border-box;
						position: relative;
						width: 60px;
						height: 60px;
						border-radius: 50%;
						border: 5px solid #ccc;
						border-top-color: #0070ba;
						animation: wpspsc-spinner .6s linear infinite;
					}

					.wpspsc-spinner-cont {
						width: 100%;
						text-align: center;
						margin-top: 10px;
						display: none;
					}
				</style>
				<div class="wpspsc-spinner-cont">
					<div class="wpspsc-spinner"></div>
				</div>
				<?php
				$output .= ob_get_clean();
			}
		}

		//---  Start of Stripe checkout --- 
		if ( get_option( 'wpspc_enable_stripe_checkout' ) ) {
			$wspsc_Cart = WSPSC_Cart::get_instance();

			wp_enqueue_script( "wspsc.stripe" );
			wp_enqueue_script( "wspsc-checkout-stripe" );

			$output .= '<form class="wspsc-stripe-payment-form" >';
			$stripe_checkout_button_img_src = WP_CART_URL . '/images/' . ( __( 'stripe_checkout_EN.gif', 'wordpress-simple-paypal-shopping-cart' ) );

			if ( get_option( 'wpspc_stripe_button_image_url' ) ) {
				$stripe_checkout_button_img_src = get_option( 'wpspc_stripe_button_image_url' );
			}
			$output .= '<input class="wspsc_stripe_btn wp_cart_checkout_button"  value="wspsc_stripe_checkout" type="image" src="' . apply_filters( 'wspsc_cart_stripe_checkout_button_image_src', $stripe_checkout_button_img_src ) . '" name="submit" class="wp_cart_checkout_button wp_cart_checkout_button_' . $carts_cnt . '" alt="' . ( __( "Make payments with Stripe - it\'s fast, free and secure!", 'wordpress-simple-paypal-shopping-cart' ) ) . '" />';

			$output .= wp_cart_add_custom_field();
			$extra_stripe_fields = apply_filters( 'wspsc_cart_extra_stripe_fields', '' ); //Can be used to add extra PayPal hidden input fields for the cart checkout
			$output .= $extra_stripe_fields;
			$output .= '<div class="wpspsc-spinner-cont" id="wpspsc_spinner_' . esc_js( $wspsc_Cart->get_cart_id() ) . '">
						<div class="wpspsc-spinner"></div>
					</div>';
			$output .= '</form>';
		}
		$output .= '</td></input>';
	}
	$output .= '</table></div>';
	$output = apply_filters( 'wpspsc_after_cart_output', $output );
	return $output;
}

/**
 * Loads the checkout.js script from PayPal that is used for PayPal Smart Checkout. 
 * Then it triggers the wspsc_paypal_smart_checkout_sdk_loaded event.
 */
function wspsc_load_paypal_smart_checkout_js() {
	$script_url = 'https://www.paypalobjects.com/api/checkout.js';
	?>
	<script type="text/javascript">
		var script = document.createElement('script');
		script.type = 'text/javascript';
		script.async = true;
		script.src = '<?php echo esc_url_raw( $script_url ); ?>';
		script.onload = function () {
			document.dispatchEvent(new Event('wspsc_paypal_smart_checkout_sdk_loaded'));
		};
		document.getElementsByTagName('head')[0].appendChild(script);
	</script>
	<?php
}

/**
 * Generate the rendering code for term and conditions if enabled.
 * 
 * @param int $carts_cnt The cart no.
 * 		
 * @return string HTML output.
 */
function wspsc_generate_tnc_section( $carts_cnt ) {
	$html = '';

	$wspsc_default_tnc_text = __( 'I accept the <a href="https://example.com/terms-and-conditions/" target="_blank">Terms and Conditions</a>', "wordpress-simple-paypal-shopping-cart" );
	$wspsc_tnc_text = ! empty( get_option( 'wp_shopping_cart_tnc_text' ) ) ? wp_kses_post( get_option( 'wp_shopping_cart_tnc_text' ) ) : $wspsc_default_tnc_text;

	$html .= '<div class="wp-shopping-cart-tnc-container pure-u-1" style="margin-top: 10px;">';
	$html .= '<p>';
	$html .= '<label for="wp_shopping_cart_tnc_input_' . $carts_cnt . '" class="pure-checkbox">';
	$html .= '<input class="wp_shopping_cart_tnc_input" id="wp_shopping_cart_tnc_input_' . $carts_cnt . '" type="checkbox" value="1" style="margin-right: 8px">';
	$html .= $wspsc_tnc_text;
	$html .= '</label>';
	$html .= '<br />';
	$html .= '<span class="wp-shopping-cart-tnc-error" style="color: #cc0000; font-size: smaller;" role="alert"></span>';
	$html .= '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Generate the rendering code for shipping region if enabled.
 * 
 * @param int $carts_cnt The cart no.
 * @param string $selected_option The selected option as string.
 * 
 * @return string HTML output.
 */
function wspsc_generate_shipping_region_section($carts_cnt, $selected_option) {
	$wpsc_shipping_variations_settings_arr  = get_option('wpsc_shipping_region_variations');

	$html = '';
	
	$html .= '<div class="wpsc-shipping-region-container" style="margin-bottom: 8px">';
	$html .= '<form method="post" action="" class="wpsc-shipping-region-form" id="wpsc-shipping-region-form-'.$carts_cnt.'">';
	$html .= '<div><label class="wpsc-shipping-region-label" for="wpsc-shipping-region-input-'.$carts_cnt.'">'. __( 'Select Shipping Region', 'wordpress-simple-paypal-shopping-cart' ). '</label></div>';
	$html .= '<select class="wpsc-shipping-region-input" id="wpsc-shipping-region-input-'.$carts_cnt.'" name="wpsc_shipping_region">';
	$html .= '<option value="-1">'.__( 'Select a Region', 'wordpress-simple-paypal-shopping-cart' ).'</option>';
	$html .= wpsc_get_shipping_region_opts($wpsc_shipping_variations_settings_arr, $selected_option);
	$html .= '</select>';
	$html .= '<span class="wpsc_select_region_button">';
	$html .= '<input type="submit" name="wpsc_shipping_region_submit" class="wpsc_shipping_region_submit" value="'.__( 'Apply', 'wordpress-simple-paypal-shopping-cart' ).'" />';
	$html .= '</span>';
	$html .= '<div class="wpsc-shipping-region-error" style="color: #cc0000; font-size: smaller; margin-top: 6px" role="alert"></div>';
	$html .= wp_nonce_field( 'wpsc_shipping_region', '_wpnonce', true, false );
	$html .= '</form>';
	$html .= '</div>';

	return $html;
}

/**
 * Generates options for shipping region select input in the shopping cart.
 *
 * @param array $region_options Collection of available options configured in admin side.
 * @param boolean|string $selected Selected option as string if there is any.
 * @return string HTML option elements as string.
 */
function wpsc_get_shipping_region_opts( $region_options, $selected = false ) {
	$countries = wpsc_get_countries();
	$out       = '';
	$tpl = '<option value="%s" %s> %s </option>';
	foreach ($region_options as $key => $region) {
		$selected_str = '';
		$value = implode(':', array(strtolower($region['loc']), $region['type']));
		if ($selected) {
			$selected_str = $value === $selected ? 'selected' : '';
		}
		$text = '';

		if ($region['type'] == '0') {
			$text .= $countries[$region['loc']];
		}else{
			$text .= $region['loc'];
		}

		$out .= sprintf( $tpl, $value, $selected_str, $text );
	}

    return $out;
}