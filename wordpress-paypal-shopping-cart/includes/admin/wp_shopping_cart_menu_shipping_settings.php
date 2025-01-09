<?php

function show_wp_cart_shipping_settings_page()
{
    if(!current_user_can('manage_options')){
        wp_die('You do not have permission to access the settings page.');
    }

    if (isset($_POST['wpspc_shipping_settings_update']))
    {
        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'wpspc_shipping_settings_update')){
                wp_die('Error! Nonce Security Check Failed! Go back to email settings menu and save the settings again.');
        }

        $enable_shipping_by_region_value = (isset($_POST['enable_shipping_by_region']) && !empty(sanitize_text_field($_POST['enable_shipping_by_region']))) ? 'checked="checked"':'' ;
        update_option('cart_base_shipping_cost', sanitize_text_field($_POST["cart_base_shipping_cost"]));
        update_option('cart_free_shipping_threshold', sanitize_text_field($_POST["cart_free_shipping_threshold"]));
        update_option('enable_shipping_by_region', $enable_shipping_by_region_value );

        $wpsc_shipping_region_variations_base = isset($_POST['wpsc_shipping_region_variations_base']) ? array_map('sanitize_text_field', $_POST['wpsc_shipping_region_variations_base']) : array();
        $wpsc_shipping_region_variations_loc = isset($_POST['wpsc_shipping_region_variations_loc']) ? array_map('sanitize_text_field', $_POST['wpsc_shipping_region_variations_loc']) : array();
        $wpsc_shipping_region_variations_amt = isset($_POST['wpsc_shipping_region_variations_amt']) ? array_map('sanitize_text_field', $_POST['wpsc_shipping_region_variations_amt']) : array();
        
        /**
         * Check if all the three inputs related to shipping region variation option is not empty, only then update the option.
         * This is to prevent updating the option with empty array and erase all configured variation option when shipping by region is disabled.
         * But this also raise the problem of deleting the last item, which is solved by the later code block.
         */
        if ( ! empty( $wpsc_shipping_region_variations_base ) && ! empty( $wpsc_shipping_region_variations_loc ) && ! empty( $wpsc_shipping_region_variations_amt ) ) {
            $wpsc_shipping_variations_arr = array();
            foreach ( $wpsc_shipping_region_variations_base as $i => $type ) {
                $loc_str = isset($wpsc_shipping_region_variations_loc[ $i ]) ? sanitize_text_field(stripslashes($wpsc_shipping_region_variations_loc[ $i ])) : '';
                $tax = isset($wpsc_shipping_region_variations_amt[ $i ]) ? floatval( $wpsc_shipping_region_variations_amt[ $i ] ) : 0;
                $wpsc_shipping_variations_arr[] = array(
                    'type'   => $type,
                    'loc'    => $loc_str,
                    'amount' => $tax,
                );
            }
            update_option('wpsc_shipping_region_variations', $wpsc_shipping_variations_arr );
        }

        /**
         * Check if the last item needs to be deleted or not.
         * The hidden input 'wpsc_shipping_region_variations_delete_last' will have the value of '1', set by javascript if the delete button of the last variation item is clicked.
         */
        $wpsc_shipping_region_variations_delete_last = isset($_POST['wpsc_shipping_region_variations_delete_last']) && !empty(sanitize_text_field($_POST['wpsc_shipping_region_variations_delete_last']));
        if ($wpsc_shipping_region_variations_delete_last) {
            update_option('wpsc_shipping_region_variations', array() );
        }

        echo '<div id="message" class="notice notice-success"><p><strong>';
        echo 'Shipping Settings Updated!';
        echo '</strong></p></div>';
    }

    $baseShipping = get_option('cart_base_shipping_cost');
    if (empty($baseShipping)) $baseShipping = 0;

    $cart_free_shipping_threshold = get_option('cart_free_shipping_threshold');

    if (get_option('enable_shipping_by_region')){
        $enable_shipping_by_region = 'checked="checked"';
    }
    else{
        $enable_shipping_by_region = '';
    }

    $wpsc_shipping_variations_arr  = get_option('wpsc_shipping_region_variations');

    //Show the documentation message
    wpsc_settings_menu_documentation_msg();
    ?>
    <style>
        #wpsc-shipping-region-variations-add-btn{
            vertical-align: baseline;
        }

        #wpsc-shipping-region-variations-add-btn span{
            vertical-align: middle;
        }
        
        #wpsc-shipping-region-variations-tbl{
            margin-bottom: 16px;
        }

        #wpsc-shipping-region-variations-tbl thead th{
            text-align: center;
            padding: 0;
        }

        #wpsc-shipping-region-variations-tbl tbody td{
            padding: 8px 0px;
        }

        .wpsc-shipping-region-variations-del-btn{
            vertical-align: baseline;
            padding: 0 5px !important;
        }

        .wpsc-shipping-region-variations-del-btn .dashicons{
            font-size: 18px;
        }

        .wpsc-shipping-region-variations-del-btn span{
            vertical-align: middle;
        }

        .wpsc-shipping-region-variations-input{
            max-width: unset !important;
            width: 100%;
        }
    </style>
    <script>
        jQuery($).ready(function(){
            let wpscShippingVarData = <?php echo json_encode(	array(
				'cOpts'             => wpsc_get_countries_opts(),
				'disabledForSub'    => empty($enable_shipping_by_region),
				'text'               => array(
					'delConfirm'    => __( 'Are you sure you want to delete this variation?', "wordpress-simple-paypal-shopping-cart" ),
					'delButton'     => __( 'Delete variation', "wordpress-simple-paypal-shopping-cart" ),
					'country'       => __( 'Country', "wordpress-simple-paypal-shopping-cart" ),
					'state'         => __( 'State', "wordpress-simple-paypal-shopping-cart" ),
					'city'          => __( 'City', "wordpress-simple-paypal-shopping-cart" ),
				),
			)) ?>;
            jQuery('#wpsc-shipping-region-variations-add-btn').click(function (e) {
                e.preventDefault();
                const variationRowTpl = `<tr>
                            <td>
                                <select class="wpsc-shipping-region-variations-base wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_base[]">
                                    <option value="0">${wpscShippingVarData.text.country}</option>
                                    <option value="1">${wpscShippingVarData.text.state}</option>
                                    <option value="2">${wpscShippingVarData.text.city}</option>
                                </select>
                            </td>
                            <td>
                                <div class="wpsc-shipping-region-variations-cont-type-0">
                                    <select class="wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_loc[]">${wpscShippingVarData.cOpts}</select>
                                </div>
                                <div class="wpsc-shipping-region-variations-cont-type-1" style="display:none;">
                                    <input class="wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_loc[]" type="text" disabled value="">
                                </div>
                                <div class="wpsc-shipping-region-variations-cont-type-2" style="display:none;">
                                    <input class="wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_loc[]" type="text" disabled value="">
                                </div>
                            </td>
                            <td>
                                <input type="number" class="wpsc-shipping-region-variations-input" step="any" min="0" name="wpsc_shipping_region_variations_amt[]" value="0">
                            </td>
                            <td>
                                <button type="button" class="button wpsc-shipping-region-variations-del-btn wpsc-shipping-region-variations-del-btn-small">
                                    <span class="dashicons dashicons-trash" title="${wpscShippingVarData.text.delButton}"></span>
                                </button>
                            </td>
                        </tr>`;

                const variationRowTplHidden = jQuery(variationRowTpl).css('display', 'none');
                jQuery('#wpsc-shipping-region-variations-tbl').find('tbody').append(variationRowTplHidden);
                jQuery('#wpsc-shipping-region-variations-tbl').show();
                variationRowTplHidden.fadeIn(200);
            });

            jQuery('#wpsc-shipping-region-variations-tbl').on('click', 'button.wpsc-shipping-region-variations-del-btn', function (e) {
                e.preventDefault();
                if (confirm(wpscShippingVarData.text.delConfirm)) {
                    jQuery(this).closest('tr').fadeOut(300, function () { jQuery(this).remove(); });
                                    
                    // Check if the variation table gets empty. If so, hide the table.
                    const tableBody = jQuery('#wpsc-shipping-region-variations-tbl tbody tr');
                    if(tableBody.length < 2){
                        jQuery('#wpsc-shipping-region-variations-tbl').fadeOut(300);
                        jQuery('#wpsc_shipping_region_variations_delete_last').val('1');
                    }
                }
            });

            jQuery('#wpsc-shipping-region-variations-tbl').on('change', 'select.wpsc-shipping-region-variations-base', function (e) {
                var selBase = jQuery(this).val();
                jQuery(this).closest('tr').find('div').hide();
                jQuery(this).closest('tr').find('div').find('input,select').prop('disabled', true);
                jQuery(this).closest('tr').find('.wpsc-shipping-region-variations-cont-type-' + selBase).show();
                jQuery(this).closest('tr').find('.wpsc-shipping-region-variations-cont-type-' + selBase).find('input,select').prop('disabled', false);
            });
        })
    </script>
    <form method="post" action="">
        <?php wp_nonce_field('wpspc_shipping_settings_update'); ?>
        <input type="hidden" name="info_update" id="info_update" value="true" />

        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e("Shipping Settings", "wordpress-simple-paypal-shopping-cart");?></label></h3>
            <div class="inside">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e("Base Shipping Cost", "wordpress-simple-paypal-shopping-cart");?></th>
                        <td>
                            <input type="text" name="cart_base_shipping_cost" value="<?php esc_attr_e($baseShipping)?>" size="5" /> <br /> <?php _e("This is the base shipping cost that will be added to the total of individual products shipping cost. Put 0 if you do not want to charge shipping cost or use base shipping cost.", "wordpress-simple-paypal-shopping-cart") ?>
                            <a href="https://www.tipsandtricks-hq.com/ecommerce/wordpress-shopping-cart-how-the-shipping-cost-calculation-works-297" target="_blank"><?php _e("Learn More on Shipping Calculation", "wordpress-simple-paypal-shopping-cart")?></a>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e("Free Shipping for Orders Over", "wordpress-simple-paypal-shopping-cart")?></th>
                        <td>
                            <input type="text" name="cart_free_shipping_threshold" value="<?php esc_attr_e($cart_free_shipping_threshold)?>" size="5" /> 
                            <br />
                            <?php _e("When a customer orders more than this amount he/she will get free shipping. Leave empty if you do not want to use it.", "wordpress-simple-paypal-shopping-cart")?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e("Regional Shipping Settings", "wordpress-simple-paypal-shopping-cart");?></label></h3>
            <div class="inside">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e("Enable Shipping by Region", "wordpress-simple-paypal-shopping-cart")?></th>
                        <td><input type="checkbox" name="enable_shipping_by_region" value="1" <?php echo $enable_shipping_by_region ?> />
                        <br />
                        <p class="description"><?php _e('Select this option to enable region based shipping cost additions.', 'wordpress-simple-paypal-shopping-cart') ?></p>
                        <p class="description"><?php _e('You can define shipping regions and allocate extra shipping costs for each. Customers will choose their region from a list, and the relevant additional charge will be included in the total shipping cost.', 'wordpress-simple-paypal-shopping-cart') ?></p>
                        </td>
                    </tr>

                    <?php if (!empty($enable_shipping_by_region)) {?>
                    <tr valign="top">
                        <th scope="row"><?php _e("Shipping Regions", "wordpress-simple-paypal-shopping-cart")?></th>
                        <td>
                            <div>
                                <table class="" id="wpsc-shipping-region-variations-tbl"<?php echo empty( $wpsc_shipping_variations_arr ) ? 'style="display:none;"' : ''; ?>>
                                    <thead>
                                        <tr id="wpsc-shipping-region-variations-tbl-header-row">
                                            <th style="width: 20%;"><?php esc_html_e( 'Type', 'wordpress-simple-paypal-shopping-cart' ); ?></th>
                                            <th style="width: 50%;"><?php esc_html_e( 'Location', 'wordpress-simple-paypal-shopping-cart' ); ?></th>
                                            <th style="width: 20%;"><?php esc_html_e( 'Shipping Cost', 'wordpress-simple-paypal-shopping-cart' ); ?></th>
                                            <th style="width: 10%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        foreach ( $wpsc_shipping_variations_arr as $v ) {
                                            $c_code = '0' === $v['type'] ? $v['loc'] : '';
                                        ?>
                                        <tr>
                                            <td>
                                                <select class="wpsc-shipping-region-variations-base wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_base[]">
                                                    <option value="0" <?php echo '0' === $v['type'] ? 'selected' : '' ?>><?php esc_html_e( 'Country', 'stripe-payments' ) ?></option>
                                                    <option value="1" <?php echo '1' === $v['type'] ? 'selected' : '' ?>><?php esc_html_e( 'State', 'stripe-payments' ) ?></option>
                                                    <option value="2" <?php echo '2' === $v['type'] ? 'selected' : '' ?>><?php esc_html_e( 'City', 'stripe-payments' ) ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <!-- Country type location field (type = 0) -->
                                                <div class="wpsc-shipping-region-variations-cont-type-0" style="<?php echo $v['type'] === '0' ? '' : 'display:none' ?>">
                                                    <select class="wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_loc[]" <?php echo '0' === $v['type'] ? '' : 'disabled' ?>>
                                                        <?php echo wpsc_get_countries_opts( $c_code ) ?>
                                                    </select>
                                                </div>
                                                <!-- State type location field (type = 1) -->
                                                <div class="wpsc-shipping-region-variations-cont-type-1" style="<?php echo '1' === $v['type'] ? '' : 'display:none' ?>">
                                                    <input class="wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_loc[]" type="text" <?php echo $v['type'] === '1' ? '' : 'disabled' ?> value="<?php echo $v['type'] === '1' ? esc_attr($v['loc']) : '' ?>">
                                                </div>
                                                <!-- City type location field (type = 2) -->
                                                <div class="wpsc-shipping-region-variations-cont-type-2" style="<?php echo '2' === $v['type'] ? '' : 'display:none' ?>">
                                                    <input class="wpsc-shipping-region-variations-input" name="wpsc_shipping_region_variations_loc[]" type="text" <?php echo $v['type'] === '2' ? '' : 'disabled' ?> value="<?php echo $v['type'] === '2' ? esc_attr($v['loc']) : '' ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="wpsc-shipping-region-variations-input" step="any" min="0" name="wpsc_shipping_region_variations_amt[]" value="<?php esc_attr_e($v['amount']) ?>">
                                            </td>
                                            <td>
                                                <button type="button" class="button wpsc-shipping-region-variations-del-btn wpsc-shipping-region-variations-del-btn-small">
                                                    <span class="dashicons dashicons-trash" title="<?php _e( 'Delete variation', 'stripe-payments' ) ?>"></span>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <p>
                                <button type="button" id="wpsc-shipping-region-variations-add-btn" class="button">
                                    <span class="dashicons dashicons-plus"></span> <?php _e( 'Add Shipping Variation', 'wordpress-simple-paypal-shopping-cart' ); ?>
                                </button>
                            </p>
                            <p class="description"><?php _e('Use this to configure shipping additions on a per-region basis.', 'wordpress-simple-paypal-shopping-cart') ?></p>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        
        <input type="hidden" id="wpsc_shipping_region_variations_delete_last" name="wpsc_shipping_region_variations_delete_last" value="0">

        <div class="submit">
            <input type="submit" class="button-primary" name="wpspc_shipping_settings_update" value="<?php echo (__("Update Options &raquo;", "wordpress-simple-paypal-shopping-cart")) ?>" />
        </div>
    </form>

    <?php
    wpsc_settings_menu_footer();
}
