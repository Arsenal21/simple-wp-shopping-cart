<?php

use TTHQ\WPSC\Lib\PayPal\PayPal_PPCP_Config;
use TTHQ\WPSC\Lib\PayPal\PayPal_JS_Button_Embed;

function wpsc_render_paypal_ppcp_checkout_form( $args ){
    //FIXME - implement this function

    /***********************************************
     * Settings and checkout button specific variables
     ***********************************************/
    $ppcp_configs = PayPal_PPCP_Config::get_instance();
    $live_client_id = $ppcp_configs->get_value('paypal-live-client-id');
    $sandbox_client_id = $ppcp_configs->get_value('paypal-sandbox-client-id');
    $sandbox_enabled = $ppcp_configs->get_value('enable-sandbox-testing');
    $is_live_mode = $sandbox_enabled ? 0 : 1;

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

	$btn_type = !empty($ppcp_configs->get_value('ppcp_btn_type')) ? $ppcp_configs->get_value('ppcp_btn_type') : 'checkout';
    $btn_shape = !empty($ppcp_configs->get_value('ppcp_btn_shape')) ? $ppcp_configs->get_value('ppcp_btn_shape') : 'rect';
    $btn_layout = !empty($ppcp_configs->get_value('ppcp_btn_layout')) ? $ppcp_configs->get_value('ppcp_btn_layout') : 'vertical';
    $btn_color = !empty($ppcp_configs->get_value('ppcp_btn_color')) ? $ppcp_configs->get_value('ppcp_btn_color') : 'blue';

    $btn_width = !empty($ppcp_configs->get_value('ppcp_btn_width')) ? $ppcp_configs->get_value('ppcp_btn_width') : 250;
    $btn_height = $ppcp_configs->get_value('ppcp_btn_height');
    $btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
    $btn_height = isset( $btn_sizes[ $btn_height ] ) ? $btn_sizes[ $btn_height ] : 35;

    $currency = isset($args['currency']) ? $args['currency'] : 'USD';
    $return_url = get_option('cart_return_from_paypal_url');
    $txn_success_message = __('Transaction completed successfully!', 'wordpress-simple-paypal-shopping-cart');

    $is_tnc_enabled = get_option( 'wp_shopping_cart_enable_tnc' ) != '';

    /****************************
     * PayPal SDK related settings
     ****************************/
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

    //Initialize and set the settings args that will be used to load the JS SDK.
    $pp_js_button = PayPal_JS_Button_Embed::get_instance();
    $pp_js_button->set_settings_args( $settings_args );

    //Load the JS SDK on footer (so it only loads once per page)
    add_action( 'wp_footer', array($pp_js_button, 'load_paypal_sdk') );

    /************************************************
     * Checkout button's HTML and JS code related data
     ************************************************/

    //The on page embed button id is used to identify the button on the page. Useful when there are multiple buttons on the same page.
    $carts_cnt = isset($args['carts_cnt']) ? $args['carts_cnt'] : 0;
    //We will use the $carts_cnt variable here (instead of the standard get_next_button_id() function) so it can have the same count like the other checkout methods in the cart.
    $on_page_embed_button_id = $pp_js_button->get_button_id_prefix() . $carts_cnt;
    //Create nonce for this button.
    $wp_nonce = wp_create_nonce($on_page_embed_button_id);

    //Cart specific data
    $wspsc_cart = WSPSC_Cart::get_instance();
    $cart_id = $wspsc_cart->get_cart_id();

    //Some number formatting (before it is used in JS code.
    $wspsc_cart->calculate_cart_totals_and_postage();
    $formatted_sub_total = $wspsc_cart->get_sub_total_formatted();
    $formatted_postage_cost = $wspsc_cart->get_postage_cost_formatted();
    $formatted_grand_total = $wspsc_cart->get_grand_total_formatted();
    $payment_amount = $formatted_grand_total;

    //Just need to call this function so it can generate and save the custom values in the cart order meta (using cart_id)
    $custom_field_input_str = wp_cart_add_custom_field();
    
    //Start the ppcp button's HTML output
    $ppcp_output = '';
    ob_start();
    ?>
    <div class="wpsc-ppcp-button-wrapper">

    <!-- PayPal button container where the button will be rendered -->
    <div id="<?php echo esc_attr($on_page_embed_button_id); ?>" style="width: <?php echo esc_attr($btn_width); ?>px;"></div>
    
    <!-- Any additional hidden input fields (if needed) -->

    <script type="text/javascript">
        var wpspscTncEnabled = <?php echo $is_tnc_enabled ? 'true' : 'false' ?>;

    jQuery( function( $ ) {
        $( document ).on( "wpsc_paypal_sdk_loaded", function() { 
            //Anything that goes here will only be executed after the PayPal SDK is loaded.
            console.log('PayPal JS SDK is loaded.');

            /**
             * See documentation: https://developer.paypal.com/sdk/js/reference/
             */
            paypal.Buttons({
                /**
                 * Optional styling for buttons.
                 * 
                 * See documentation: https://developer.paypal.com/sdk/js/reference/#link-style
                 */
                style: {
                    color: '<?php echo esc_js($btn_color); ?>',
                    shape: '<?php echo esc_js($btn_shape); ?>',
                    height: <?php echo esc_js($btn_height); ?>,
                    label: '<?php echo esc_js($btn_type); ?>',
                    layout: '<?php echo esc_js($btn_layout); ?>',
                },

                // Triggers when the button first renders.
                onInit: onInitHandler,

                // Triggers when the button is clicked.
                onClick: onClickHandler,

                // Setup the transaction.
                createOrder: createOrderHandler,
    
                // Handle the onApprove event.
                onApprove: onApproveHandler,
    
                // Handle unrecoverable errors.
                onError: onErrorHandler,

                // Handles onCancel event.
                onCancel: onCancelHandler,

            })
            .render('#<?php echo esc_js($on_page_embed_button_id); ?>')
            .catch((err) => {
                console.error('PayPal Buttons failed to render');
            });

            /**
             * OnInit is called when the button first renders.
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
             */
            function onInitHandler(data, actions)  {
                jQuery(document).ready(function ($) {
                    actions.enable();

                    /**
                     * The codes below will run only if the customer input addon is installed and there are fields added.
                     */

                    // Checks if there is any required input field with empty value.                        
                    if (jQuery('.wpspsc_cci_input').length > 0 && has_empty_required_input(<?php echo $carts_cnt; ?>)) {
                        actions.disable();
                    }
                                        
                    // Disable paypal smart checkout form submission if terms and condition validation error.
                    const currentPPCPButtonWrapper = '#wpsc_paypal_button_<?php echo $carts_cnt; ?>';
                    if (!wspsc_validateTnc(currentPPCPButtonWrapper, false)) {
                        actions.disable();
                    }

                    // Listen for changes to the required fields.
                    jQuery('.wpspsc_cci_input, .wp_shopping_cart_tnc_input').on('change', function () {
                        if (has_empty_required_input(<?php echo $carts_cnt; ?>)) {
                            actions.disable();
                            return;
                        }

                        // Also check if terms and condition has checked.
                        if (wpspscTncEnabled) {
                            if (wspsc_validateTnc(currentPPCPButtonWrapper, false)) {
                                actions.enable();
                            } else {
                                actions.disable();
                            }
                        } else {
                            actions.enable();
                        }
                    });
                });
            }

            /**
             * OnClick is called when the button is clicked
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
             */
            function onClickHandler(){
                const currentPPCPButtonWrapper = '#wpsc_paypal_button_<?php echo $carts_cnt; ?>';
                if (wpspscTncEnabled) {
                    const tncContainer = wspsc_getClosestElement(currentPPCPButtonWrapper, wspscTncContainerSelector)
                    wspsc_handleTncErrorMsg(tncContainer);
                }

                /**
                 * CCI addon related. Previously for old paypal api, it used to listen to the 'wp_cart_checkout_button' css class which is not available in this new ppcp api.
                 * See code: simple-cart-collect-customer-input/wp-shopping-cart-cci-form_handler.class.php:59
                 * 
                 * Show alert message and focus unfilled require inputs if any.
                 */
                jQuery(currentPPCPButtonWrapper).parents('table').find('input.wpspsc_cci_input').each(function () {
                    if (jQuery(this).prop('required') && jQuery(this).val() === '') {
                        alert("Please fill in " + jQuery(this).siblings('div.wpspsc_cci_input_' + jQuery(this).attr('data-wpspsc-cci-id') + '_label').html());
                        jQuery(this).focus();
                    }
                });

            }

            /**
             * This is called when the buyer clicks the PayPal button, which launches the PayPal Checkout 
             * window where the buyer logs in and approves the transaction on the paypal.com website.
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-createorder
             */
            async function createOrderHandler() {
                // Create the order in PayPal using the PayPal API.
                // https://developer.paypal.com/docs/checkout/standard/integrate/
                // The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.                    
                console.log('Setting up the AJAX request for create-order call.');
                let pp_bn_data = {};
                pp_bn_data.cart_id = '<?php echo esc_js($cart_id); ?>';
                pp_bn_data.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
                //Ajax action: <prefix>_wpsc_pp_create_order 
                let post_data = 'action=wpsc_pp_create_order&data=' + JSON.stringify(pp_bn_data) + '&_wpnonce=<?php echo $wp_nonce; ?>';
                try {
                    // Using fetch for AJAX request. This is supported in all modern browsers.
                    const response = await fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                        method: "post",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: post_data
                    });

                    const response_data = await response.json();

                    if (response_data.order_id) {
                        console.log('Create-order API call to PayPal completed successfully.');
                        //If we need to see the order details, uncomment the following line.
                        //const order_data = response_data.order_data;
                        //console.log('Order data: ' + JSON.stringify(order_data));
                        return response_data.order_id;
                    } else {
                        const error_message = JSON.stringify(response_data);
                        throw new Error(error_message);
                    }
                } catch (error) {
                    console.error(error);
                    alert('Could not initiate PayPal Checkout...\n\n' + error);
                }
            }

            /**
             * Captures the funds from the transaction and shows a message to the buyer to let them know the 
             * transaction is successful. The method is called after the buyer approves the transaction on paypal.com.
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onapprove
             */
            async function onApproveHandler(data, actions) {
                console.log('Successfully created a transaction.');

                //Show the spinner while we process this transaction.
                var pp_button_container = jQuery('#<?php echo esc_js($on_page_embed_button_id); ?>');
                var pp_button_spinner_conainer = pp_button_container.siblings('.wpsc-pp-button-spinner-container');
                pp_button_container.hide();//Hide the buttons
                pp_button_spinner_conainer.css('display', 'inline-block');//Show the spinner.

                // Capture the order in PayPal using the PayPal API.
                // https://developer.paypal.com/docs/checkout/standard/integrate/
                // The server-side capture-order API is used. Then the Capture-ID is returned.
                console.log('Setting up the AJAX request for capture-order call.');
                let pp_bn_data = {};
                pp_bn_data.order_id = data.orderID;
                pp_bn_data.cart_id = '<?php echo esc_js($cart_id); ?>';
                pp_bn_data.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
                //Add custom_field data. It is important to encode the custom_field data so it doesn't mess up the data with & character.
                //const custom_data = document.getElementById('<?php echo esc_attr($on_page_embed_button_id."-custom-field"); ?>').value;
                //pp_bn_data.custom_field = encodeURIComponent(custom_data);

                //Ajax action: <prefix>_pp_capture_order
                let post_data = 'action=wpsc_pp_capture_order&data=' + JSON.stringify(pp_bn_data) + '&_wpnonce=<?php echo $wp_nonce; ?>';
                try {
                    const response = await fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                        method: "post",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: post_data
                    });

                    const response_data = await response.json();
                    const txn_data = response_data.txn_data;
                    const error_detail = txn_data?.details?.[0];
                    const error_msg = response_data.error_msg;//Our custom error message.
                    // Three cases to handle:
                    // (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                    // (2) Other non-recoverable errors -> Show a failure message
                    // (3) Successful transaction -> Show confirmation or thank you message

                    if (response_data.capture_id) {
                        // Successful transaction -> Show confirmation or thank you message
                        console.log('Capture-order API call to PayPal completed successfully.');

                        //Redirect to the Thank you page URL if it is set.
                        return_url = '<?php echo esc_url_raw($return_url); ?>';
                        if( return_url ){
                            //redirect to the Thank you page URL.
                            console.log('Redirecting to the Thank you page URL: ' + return_url);
                            window.location.href = return_url;
                            return;
                        } else {
                            //No return URL is set. Just show a success message.
                            txn_success_msg = '<?php echo esc_attr($txn_success_message); ?>';
                            alert(txn_success_msg);
                        }

                    } else if (error_detail?.issue === "INSTRUMENT_DECLINED") {
                        // Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                        console.log('Recoverable INSTRUMENT_DECLINED error. Calling actions.restart()');
                        return actions.restart();
                    } else if ( error_msg && error_msg.trim() !== '' ) {
                        //Our custom error message from the server.
                        console.error('Error occurred during PayPal checkout process.');
                        console.error( error_msg );
                        alert( error_msg );
                    } else {
                        // Other non-recoverable errors -> Show a failure message
                        console.error('Non-recoverable error occurred during PayPal checkout process.');
                        console.error( error_detail );
                        //alert('Error occurred with the transaction. Enable debug logging to get more details.\n\n' + JSON.stringify(error_detail));
                    }

                    //Return the button and the spinner back to their orignal display state.
                    pp_button_container.show();//Show the buttons
                    pp_button_spinner_conainer.hide();//Hide the spinner.

                } catch (error) {
                    console.error(error);
                    alert('Sorry, your transaction could not be processed...\n\n' + error);
                }
            }

            /**
             * If an error prevents buyer checkout, alert the user that an error has occurred with the buttons using this callback.
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onerror
             */
            function onErrorHandler(err) {
                console.error('An error prevented the user from checking out with PayPal. ' + JSON.stringify(err));
                alert( '<?php echo esc_js(__("Error occurred during PayPal checkout process.", "simple-membership")); ?>\n\n' + JSON.stringify(err) );
            }
            
            /**
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oncancel
             */
            function onCancelHandler (data) {
                console.log('Checkout operation cancelled by the customer.');
                //Return to the parent page which the button does by default.
            }

        });
    });

        /**
         * Checks if any input element has required attribute with empty value
         * @param cart_no Target cart no.
         * @returns {boolean}
         */
        function has_empty_required_input(cart_no) {
            let has_any = false;
            let target_input = '.wpspsc_cci_input';
            let target_form = jQuery('#wpsc_paypal_button_' + cart_no).closest('.shopping_cart');

            jQuery(target_form).find(target_input).each(function () {
                if (jQuery(this).prop("required") && !jQuery(this).val().trim()) {
                    has_any = true;
                }
            });

            return has_any;
        }
    </script>
    <style>
        @keyframes wpsc-pp-button-spinner {
            to {transform: rotate(360deg);}
        }
        .wpsc-pp-button-spinner {
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
            animation: wpsc-pp-button-spinner .6s linear infinite;
        }
        .wpsc-pp-button-spinner-container {
            width: 100%;
            text-align: center;
            margin-top:10px;
            display: none;
        }
    </style>
    <div class="wpsc-pp-button-spinner-container">
        <div class="wpsc-pp-button-spinner"></div>
    </div>
    </div><!-- end of button-wrapper -->
    <?php
    //Get the output from the buffer and clean the buffer.
    $ppcp_output = ob_get_clean();

    //The caller function will echo or append this output.
    return $ppcp_output;
}