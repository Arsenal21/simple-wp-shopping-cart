<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function show_wp_cart_tax_settings_page()
{
    if(!current_user_can('manage_options')){
        wp_die('You do not have permission to access the settings page.');
    }

    if (isset($_POST['wpsc_tax_settings_update']))
    {
        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'wpsc_tax_settings_update')){
            wp_die('Error! Nonce Security Check Failed! Go back to tax settings menu and save the settings again.');
        }

        $enable_tax_by_region_value = (isset($_POST['enable_tax_by_region']) && !empty(sanitize_text_field($_POST['enable_tax_by_region']))) ? 'checked="checked"': '';
        update_option('wpsc_tax_percentage', abs(floatval(sanitize_text_field($_POST["tax_percentage"]))));
        update_option('wpsc_enable_tax_by_region', $enable_tax_by_region_value );

        $wpsc_tax_region_variations_base = isset($_POST['wpsc_tax_region_variations_base']) ? array_map('sanitize_text_field', $_POST['wpsc_tax_region_variations_base']) : array();
        $wpsc_tax_region_variations_loc = isset($_POST['wpsc_tax_region_variations_loc']) ? array_map('sanitize_text_field', $_POST['wpsc_tax_region_variations_loc']) : array();
        $wpsc_tax_region_variations_amt = isset($_POST['wpsc_tax_region_variations_amt']) ? array_map('sanitize_text_field', $_POST['wpsc_tax_region_variations_amt']) : array();
        
        /**
         * Check if all the three inputs related to tax region variation option is not empty, only then update the option.
         * This is to prevent updating the option with empty array and erase all configured variation option when tax by region is disabled.
         * But this also raise the problem of deleting the last item, which is solved by the later code block.
         */
        if ( ! empty( $wpsc_tax_region_variations_base ) && ! empty( $wpsc_tax_region_variations_loc ) && ! empty( $wpsc_tax_region_variations_amt ) ) {
            $wpsc_tax_variations_arr = array();
            foreach ( $wpsc_tax_region_variations_base as $i => $type ) {
                $loc_str = isset($wpsc_tax_region_variations_loc[ $i ]) ? sanitize_text_field(stripslashes($wpsc_tax_region_variations_loc[ $i ])) : '';
                $tax = isset($wpsc_tax_region_variations_amt[ $i ]) ? floatval( $wpsc_tax_region_variations_amt[ $i ] ) : 0;
                $wpsc_tax_variations_arr[] = array(
                    'type'   => $type,
                    'loc'    => $loc_str,
                    'amount' => $tax,
                );
            }
            update_option('wpsc_tax_region_variations', $wpsc_tax_variations_arr );
        }

        /**
         * Check if the last item needs to be deleted or not.
         * The hidden input 'wpsc_tax_region_variations_delete_last' will have the value of '1', set by javascript if the delete button of the last variation item is clicked.
         */
        $wpsc_tax_region_variations_delete_last = isset($_POST['wpsc_tax_region_variations_delete_last']) && !empty(sanitize_text_field($_POST['wpsc_tax_region_variations_delete_last']));
        if ($wpsc_tax_region_variations_delete_last) {
            update_option('wpsc_tax_region_variations', array() );
        }

        echo '<div id="message" class="notice notice-success"><p><strong>';
        echo 'Tax Settings Updated!';
        echo '</strong></p></div>';
    }

    $tax_percentage = get_option('wpsc_tax_percentage');
    if (empty($tax_percentage)) $tax_percentage = 0;

    if (get_option('wpsc_enable_tax_by_region')){
        $enable_tax_by_region = 'checked="checked"';
    }
    else{
        $enable_tax_by_region = '';
    }

    $wpsc_tax_variations_arr  = get_option('wpsc_tax_region_variations', array());

    //Show the documentation message
    wpsc_settings_menu_documentation_msg();
    ?>
    <style>
        #wpsc-tax-region-variations-add-btn{
            vertical-align: baseline;
        }

        #wpsc-tax-region-variations-add-btn span{
            vertical-align: middle;
            line-height: normal;
        }
        
        #wpsc-tax-region-variations-tbl{
            margin-bottom: 16px;
        }

        #wpsc-tax-region-variations-tbl thead th{
            text-align: center;
            padding: 0;
        }

        #wpsc-tax-region-variations-tbl tbody td{
            padding: 8px 0px;
        }

        .wpsc-tax-region-variations-del-btn{
            vertical-align: baseline;
            padding: 0 5px !important;
        }

        .wpsc-tax-region-variations-del-btn .dashicons{
            font-size: 18px;
            vertical-align: middle !important;
            line-height: 1 !important;
        }

        .wpsc-tax-region-variations-input{
            max-width: unset !important;
            width: 100%;
        }
    </style>
    <script>
        jQuery($).ready(function(){
            let wpscTaxVarData = <?php echo json_encode(	array(
				'cOpts'             => wpsc_get_countries_opts(),
				'disabledForSub'    => empty($enable_tax_by_region),
				'text'               => array(
					'delConfirm'    => __( 'Are you sure you want to delete this variation?', "wordpress-simple-paypal-shopping-cart" ),
					'delButton'     => __( 'Delete variation', "wordpress-simple-paypal-shopping-cart" ),
					'country'       => __( 'Country', "wordpress-simple-paypal-shopping-cart" ),
					'state'         => __( 'State', "wordpress-simple-paypal-shopping-cart" ),
					'city'          => __( 'City', "wordpress-simple-paypal-shopping-cart" ),
				),
			)) ?>;
            jQuery('#wpsc-tax-region-variations-add-btn').click(function (e) {
                e.preventDefault();
                const variationRowTpl = `<tr>
                            <td>
                                <select class="wpsc-tax-region-variations-base wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_base[]">
                                    <option value="0">${wpscTaxVarData.text.country}</option>
                                    <option value="1">${wpscTaxVarData.text.state}</option>
                                    <option value="2">${wpscTaxVarData.text.city}</option>
                                </select>
                            </td>
                            <td>
                                <div class="wpsc-tax-region-variations-cont-type-0">
                                    <select class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_loc[]">${wpscTaxVarData.cOpts}</select>
                                </div>
                                <div class="wpsc-tax-region-variations-cont-type-1" style="display:none;">
                                    <input class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_loc[]" type="text" disabled value="">
                                </div>
                                <div class="wpsc-tax-region-variations-cont-type-2" style="display:none;">
                                    <input class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_loc[]" type="text" disabled value="">
                                </div>
                            </td>
                            <td>
                                <input type="number" min="0" max="100" step="any" class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_amt[]" value="0">
                            </td>
                            <td>
                                <button type="button" class="button wpsc-tax-region-variations-del-btn wpsc-tax-region-variations-del-btn-small">
                                    <span class="dashicons dashicons-trash" title="${wpscTaxVarData.text.delButton}"></span>
                                </button>
                            </td>
                        </tr>`;

                const variationRowTplHidden = jQuery(variationRowTpl).css('display', 'none');
                jQuery('#wpsc-tax-region-variations-tbl').find('tbody').append(variationRowTplHidden);
                jQuery('#wpsc-tax-region-variations-tbl').show();
                variationRowTplHidden.fadeIn(200);
            });

            jQuery('#wpsc-tax-region-variations-tbl').on('click', 'button.wpsc-tax-region-variations-del-btn', function (e) {
                e.preventDefault();
                if (confirm(wpscTaxVarData.text.delConfirm)) {
                    jQuery(this).closest('tr').fadeOut(300, function () { jQuery(this).remove(); });
                                    
                    // Check if the variation table gets empty. If so, hide the table.
                    const tableBody = jQuery('#wpsc-tax-region-variations-tbl tbody tr');
                    if(tableBody.length < 2){
                        jQuery('#wpsc-tax-region-variations-tbl').fadeOut(300);
                        jQuery('#wpsc_tax_region_variations_delete_last').val('1');
                    }
                }
            });

            jQuery('#wpsc-tax-region-variations-tbl').on('change', 'select.wpsc-tax-region-variations-base', function (e) {
                var selBase = jQuery(this).val();
                jQuery(this).closest('tr').find('div').hide();
                jQuery(this).closest('tr').find('div').find('input,select').prop('disabled', true);
                jQuery(this).closest('tr').find('.wpsc-tax-region-variations-cont-type-' + selBase).show();
                jQuery(this).closest('tr').find('.wpsc-tax-region-variations-cont-type-' + selBase).find('input,select').prop('disabled', false);
            });
        })
    </script>
    <form method="post" action="">
        <?php wp_nonce_field('wpsc_tax_settings_update'); ?>
        <input type="hidden" name="info_update" id="info_update" value="true" />

        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php esc_html_e("Tax Settings", "wordpress-simple-paypal-shopping-cart");?></label></h3>
            <div class="inside">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e("Tax Percentage", "wordpress-simple-paypal-shopping-cart");?></th>
                        <td>
                            <input type="number" min="0" max="100" step="any" name="tax_percentage" value="<?php echo esc_attr($tax_percentage)?>" size="5" />
                            <p class="description">
                                <?php esc_html_e("Enter the tax percentage to apply to the total cost of individual products. Set to 0 to disable tax.", "wordpress-simple-paypal-shopping-cart") ?>
                                <a href="https://www.tipsandtricks-hq.com/ecommerce/configuring-taxes-in-simple-shopping-cart-5401" target="_blank"><?php esc_html_e("View the Tax Documentation", "wordpress-simple-paypal-shopping-cart"); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php esc_html_e("Regional Tax Settings", "wordpress-simple-paypal-shopping-cart");?></label></h3>
            <div class="inside">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e("Enable Tax by Region", "wordpress-simple-paypal-shopping-cart")?></th>
                        <td>
                            <input type="checkbox" name="enable_tax_by_region" value="1" <?php checked( ! empty( $enable_tax_by_region ), true ) ?> />
                            <p class="description"><?php esc_html_e('Select this option to enable region based tax.', 'wordpress-simple-paypal-shopping-cart') ?></p>
                            <p class="description"><?php esc_html_e('You can define tax regions and allocate tax percentage for each. Customers will choose their region from a list, and the relevant tax percentage will be applied to the product cost.', 'wordpress-simple-paypal-shopping-cart') ?></p>
                        </td>
                    </tr>

                    <?php if (!empty($enable_tax_by_region)) {?>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e("Tax Regions", "wordpress-simple-paypal-shopping-cart")?></th>
                        <td>
                            <div>
                                <table class="" id="wpsc-tax-region-variations-tbl"<?php echo empty( $wpsc_tax_variations_arr ) ? 'style="display:none;"' : ''; ?>>
                                    <thead>
                                        <tr id="wpsc-tax-region-variations-tbl-header-row">
                                            <th style="width: 20%;"><?php esc_html_e( 'Type', 'wordpress-simple-paypal-shopping-cart' ); ?></th>
                                            <th style="width: 50%;"><?php esc_html_e( 'Location', 'wordpress-simple-paypal-shopping-cart' ); ?></th>
                                            <th style="width: 20%;"><?php esc_html_e( 'Tax %', 'wordpress-simple-paypal-shopping-cart' ); ?></th>
                                            <th style="width: 10%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        foreach ( $wpsc_tax_variations_arr as $v ) {
                                            $c_code = '0' === $v['type'] ? $v['loc'] : '';
                                        ?>
                                        <tr>
                                            <td>
                                                <select class="wpsc-tax-region-variations-base wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_base[]">
                                                    <option value="0" <?php echo '0' === $v['type'] ? 'selected' : '' ?>><?php esc_html_e( 'Country', 'wordpress-simple-paypal-shopping-cart' ) ?></option>
                                                    <option value="1" <?php echo '1' === $v['type'] ? 'selected' : '' ?>><?php esc_html_e( 'State', 'wordpress-simple-paypal-shopping-cart' ) ?></option>
                                                    <option value="2" <?php echo '2' === $v['type'] ? 'selected' : '' ?>><?php esc_html_e( 'City', 'wordpress-simple-paypal-shopping-cart' ) ?></option>
                                                </select>
                                            </td>
                                            <td>
                                                <!-- Country type location field (type = 0) -->
                                                <div class="wpsc-tax-region-variations-cont-type-0" style="<?php echo $v['type'] === '0' ? '' : 'display:none' ?>">
                                                    <select class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_loc[]" <?php echo '0' === $v['type'] ? '' : 'disabled' ?>>
                                                        <?php echo wp_kses( wpsc_get_countries_opts( $c_code ), WPSC_Utility_Kses::wp_kses_select_option_tags() ) ?>
                                                    </select>
                                                </div>
                                                <!-- State type location field (type = 1) -->
                                                <div class="wpsc-tax-region-variations-cont-type-1" style="<?php echo '1' === $v['type'] ? '' : 'display:none' ?>">
                                                    <input class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_loc[]" type="text" <?php echo $v['type'] === '1' ? '' : 'disabled' ?> value="<?php echo $v['type'] === '1' ? esc_attr($v['loc']) : '' ?>">
                                                </div>
                                                <!-- City type location field (type = 2) -->
                                                <div class="wpsc-tax-region-variations-cont-type-2" style="<?php echo '2' === $v['type'] ? '' : 'display:none' ?>">
                                                    <input class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_loc[]" type="text" <?php echo $v['type'] === '2' ? '' : 'disabled' ?> value="<?php echo $v['type'] === '2' ? esc_attr($v['loc']) : '' ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" min="0" max="100" step="any" class="wpsc-tax-region-variations-input" name="wpsc_tax_region_variations_amt[]" value="<?php echo esc_attr($v['amount']) ?>">
                                            </td>
                                            <td>
                                                <button type="button" class="button wpsc-tax-region-variations-del-btn wpsc-tax-region-variations-del-btn-small">
                                                    <span class="dashicons dashicons-trash" title="<?php esc_html_e( 'Delete variation', 'wordpress-simple-paypal-shopping-cart' ) ?>"></span>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <p>
                                <button type="button" id="wpsc-tax-region-variations-add-btn" class="button">
                                    <span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Tax Variation', 'wordpress-simple-paypal-shopping-cart' ); ?>
                                </button>
                            </p>
                            <p class="description"><?php esc_html_e('Use this to configure tax variations on a per-region basis.', 'wordpress-simple-paypal-shopping-cart') ?></p>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        
        <input type="hidden" id="wpsc_tax_region_variations_delete_last" name="wpsc_tax_region_variations_delete_last" value="0">

        <div class="submit">
            <input type="submit" class="button-primary" name="wpsc_tax_settings_update" value="<?php echo (esc_html__("Update Options &raquo;", "wordpress-simple-paypal-shopping-cart")) ?>" />
        </div>
    </form>

    <?php
    wpsc_settings_menu_footer();
}
