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
    $txn_success_extra_msg = __('Feel free to add more items to your shopping cart for another checkout.', 'wordpress-simple-paypal-shopping-cart');

    $is_tnc_enabled = get_option( 'wp_shopping_cart_enable_tnc' ) != '';
    $is_shipping_by_region_enabled = get_option('enable_shipping_by_region');
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
    $wspsc_cart = WPSC_Cart::get_instance();
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
        var wpscShippingRegionEnabled = <?php echo $is_shipping_by_region_enabled ? 'true' : 'false' ?>;

        document.addEventListener( "wpsc_paypal_sdk_loaded", function() { 
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
                actions.enable();

                /**
                 * The codes below will run only if the customer input addon is installed and there are fields added.
                 */

                // Checks if there is any required input field with empty value.                        
                if (document.querySelectorAll('.wpspsc_cci_input').length > 0 && has_empty_required_input(<?php echo $carts_cnt; ?>)) {
                    actions.disable();
                }
                                    
                // Disable paypal smart checkout form submission if terms and condition validation error.
                const currentPPCPButtonWrapper = '#wpsc_paypal_button_<?php echo $carts_cnt; ?>';
                if (!wspsc_validateTnc(currentPPCPButtonWrapper, false)) {
                    actions.disable();
                }
                if (!wspsc_validateShippingRegion(currentPPCPButtonWrapper, false)) {
                    actions.disable();
                }

                // Listen for changes to the required fields.
                document.querySelectorAll('.wpspsc_cci_input, .wp_shopping_cart_tnc_input').forEach( function(element) {
                    element.addEventListener('change', function () {
                        let isAnyValidationError = false;

                        if (has_empty_required_input(<?php echo $carts_cnt; ?>)) {
                            isAnyValidationError = true;
                        }

                        // Check if terms and condition has checked.
                        if (wpspscTncEnabled) {
                            if (!wspsc_validateTnc(currentPPCPButtonWrapper, false)) {
                                isAnyValidationError = true;
                            }
                        }

                        // Check if shipping by region has selected.
                        if (wpscShippingRegionEnabled){
                            if (!wspsc_validateShippingRegion(currentPPCPButtonWrapper, false)) {
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
                })
            }

            /**
             * OnClick is called when the button is clicked
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
             */
            function onClickHandler(){
                const currentPPCPButtonWrapper = '#wpsc_paypal_button_<?php echo $carts_cnt; ?>';
                // Emitting custom event for addons.
                document.dispatchEvent(new CustomEvent('wpsc_ppcp_checkout_button_clicked', { 
                    detail: {
                        cartNo: <?php echo $carts_cnt; ?>,
                    }
                }));
                
                // Check if shipping region is enabled and append error message if validation fails.
                if (wpscShippingRegionEnabled) {
                    const shippingRegionContainer = wspsc_getClosestElement(currentPPCPButtonWrapper, wpscShippingRegionContainerSelector)
                    wspsc_handleShippingRegionErrorMsg(shippingRegionContainer);
                }
                // Check if terms and condition is enabled and append error message if not checked.
                if (wpspscTncEnabled) {
                    const tncContainer = wspsc_getClosestElement(currentPPCPButtonWrapper, wspscTncContainerSelector)
                    wspsc_handleTncErrorMsg(tncContainer);
                }

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
                        console.error('Error occurred during create-order call to PayPal. ' + error_message);
                        throw new Error(error_message);
                    }
                } catch (error) {
                    console.error(error);
                    alert('Could not initiate PayPal Checkout...\n\n' + JSON.stringify(error));
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
                const pp_button_container = document.getElementById('<?php echo esc_js($on_page_embed_button_id); ?>');
                const pp_button_spinner_container = wspsc_getClosestElement(pp_button_container, '.wpsc-pp-button-spinner-container', '.shopping_cart');
                pp_button_container.style.display = 'none'; //Hide the buttons
                pp_button_spinner_container.style.display = 'inline-block'; //Show the spinner.

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
                        let return_url = new URL("<?php echo esc_url_raw($return_url); ?>");
                        return_url.searchParams.set('cart_id',  pp_bn_data.cart_id);
                        return_url.searchParams.set('_wpnonce',  '<?php echo wp_create_nonce('wpsc_thank_you_nonce_action'); ?>');
                        if( return_url ){
                            //redirect to the Thank you page URL.
                            console.log('Redirecting to the Thank you page URL: ' + return_url);
                            window.location.href = return_url;
                            return;
                        } else {
                            //No return URL is set. Just show a success message.
                            console.log('No return URL is set in the settings. Showing a success message.');

                            //We are going to show the success message in the shopping_cart's container.
                            txn_success_msg = '<?php echo esc_attr($txn_success_message).' '.esc_attr($txn_success_extra_msg); ?>';
                            // Select all elements with the class 'shopping_cart'
                            var shoppingCartDivs = document.querySelectorAll('.shopping_cart');

                            // Loop through the NodeList and update each element
                            shoppingCartDivs.forEach(function(div, index) {
                                div.innerHTML = '<div id="wpsc-cart-txn-success-msg-' + index + '" class="wpsc-cart-txn-success-msg">' + txn_success_msg + '</div>';
                            });

                            //Note: We need to use on_page_cart_div_ids for compact carts. 
                            //Then we will be able to use the on_page_cart_div_ids array to get the cart div ids of the page (including the compact carts)

                            // Scroll to the success message container of the cart we are interacting with.
                            const interacted_cart_element = document.getElementById('wpsc_shopping_cart_' + <?php echo esc_attr($carts_cnt); ?>);
                            if (interacted_cart_element) {
                                interacted_cart_element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                            return;
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
                        //alert('Unexpected error occurred with the transaction. Enable debug logging to get more details.\n\n' + JSON.stringify(error_detail));
                    }

                    //Return the button and the spinner back to their orignal display state.
                    pp_button_container.style.display = 'block'; // Show the buttons
                    pp_button_spinner_container.style.display = 'none'; // Hide the spinner

                } catch (error) {
                    console.error(error);
                    alert('PayPal returned an error! Transaction could not be processed. Enable the debug logging feature to get more details...\n\n' + JSON.stringify(error));
                }
            }

            /**
             * If an error prevents buyer checkout, alert the user that an error has occurred with the buttons using this callback.
             * 
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onerror
             */
            function onErrorHandler(err) {
                console.error('An error prevented the user from checking out with PayPal. ' + JSON.stringify(err));
                alert( '<?php echo esc_js(__("Error occurred during PayPal checkout process.", "wordpress-simple-paypal-shopping-cart")); ?>\n\n' + JSON.stringify(err) );
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

        /**
         * Checks if any input element has required attribute with empty value
         * @param cart_no Target cart no.
         * @returns {boolean} TRUE if empty required field found, FALSE otherwise.
         */
        function has_empty_required_input(cart_no) {
            let has_any = false;
            const target_input = '.wpspsc_cci_input';
            const currentPPCPButtonWrapper = '#wpsc_paypal_button_'+cart_no;
            const target_form = wspsc_getClosestElement(currentPPCPButtonWrapper, 'table', '.shopping_cart');
            const cciInputElements = target_form.querySelectorAll(target_input);
            cciInputElements.forEach(function (inputElement) {
                if (inputElement.required && !inputElement.value.trim()) {
                    // Empty required field found!
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