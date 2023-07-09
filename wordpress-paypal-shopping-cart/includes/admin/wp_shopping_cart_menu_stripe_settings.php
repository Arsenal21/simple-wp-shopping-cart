<?php

function show_wp_cart_stripe_settings_page()
{

    require_once(WP_CART_PATH . 'includes/admin/wp_shopping_cart_admin_utils.php');

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access the settings page.');
    }

    if (isset($_POST['wpspc_stripe_settings_update'])) {
        $nonce = $_REQUEST['_wpnonce'];
        if (!wp_verify_nonce($nonce, 'wpspc_stripe_settings_update')) {
            wp_die('Error! Nonce Security Check Failed! Go back to stripe settings menu and save the settings again.');
        }

        $enable_stipe_checkout     = filter_input(INPUT_POST, 'wpspc_enable_stripe_checkout', FILTER_SANITIZE_NUMBER_INT);

        $live_publishable_key             = sanitize_text_field($_POST['wpspc_stripe_live_publishable_key']);
        $test_publishable_key             = sanitize_text_field($_POST['wpspc_stripe_test_publishable_key']);
        $live_secret_key             = sanitize_text_field($_POST['wpspc_stripe_live_secret_key']);
        $test_secret_key             = sanitize_text_field($_POST['wpspc_stripe_test_secret_key']);

        $wpspc_stripe_button_image_url             = sanitize_text_field($_POST['wpspc_stripe_button_image_url']);


        update_option('wpspc_enable_stripe_checkout', $enable_stipe_checkout);
        update_option('wpspc_stripe_live_publishable_key', $live_publishable_key);
        update_option('wpspc_stripe_live_secret_key', $live_secret_key);
        update_option('wpspc_stripe_test_publishable_key', $test_publishable_key);
        update_option('wpspc_stripe_test_secret_key', $test_secret_key);
        update_option('wpspc_stripe_collect_address', (isset($_POST['wpspc_stripe_collect_address']) && $_POST['wpspc_stripe_collect_address']!='') ? 'checked="checked"':'' );

        update_option('wpspc_stripe_button_image_url', $wpspc_stripe_button_image_url);

        echo '<div id="message" class="updated fade"><p><strong>';
        echo 'Stripe Settings Updated!';
        echo '</strong></p></div>';
    }
    if (get_option('wpspc_stripe_collect_address'))
        $wpspc_stripe_collect_address = 'checked="checked"';
    else
        $wpspc_stripe_collect_address = '';
?>

    <div class="wspsc_yellow_box">
        <p><?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "wordpress-simple-paypal-shopping-cart"); ?><br />
            <a href="https://www.tipsandtricks-hq.com/ecommerce/wp-shopping-cart" target="_blank"><?php _e("WP Simple Cart Homepage", "wordpress-simple-paypal-shopping-cart"); ?></a>
        </p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('wpspc_stripe_settings_update'); ?>
        <input type="hidden" name="info_update" id="info_update" value="true" />

        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e("Stripe Checkout Settings", "wordpress-simple-paypal-shopping-cart"); ?></label>
            </h3>
            <div class="inside">

                <table class="form-table">

                    <tr valign="top">
                        <th scope="row"><?php _e("Enable Stripe Checkout", "wordpress-simple-paypal-shopping-cart"); ?></th>
                        <td><input type="checkbox" name="wpspc_enable_stripe_checkout" value="1" <?php echo get_option('wpspc_enable_stripe_checkout') ? ' checked' : ''; ?> />
                            <span class="description">
                                <?php 
                                _e("To learn how to enable Stripe, please refer to ", "wordpress-simple-paypal-shopping-cart");
                                echo '<a href="https://www.tipsandtricks-hq.com/ecommerce/simple-shopping-cart-enabling-stripe-checkout" target="_blank">' . __("the documentation", "wordpress-simple-paypal-shopping-cart") . '</a>.'; 
                                ?>
                            </span>

                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e("Live Publishable Key", "wordpress-simple-paypal-shopping-cart"); ?></th>
                        <td><input type="text" name="wpspc_stripe_live_publishable_key" size="100" value="<?php echo esc_attr(get_option('wpspc_stripe_live_publishable_key')); ?>" />
                            <span class="description"><?php _e("Enter your live Publishable Key.", "wordpress-simple-paypal-shopping-cart"); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e("Live Secret Key", "wordpress-simple-paypal-shopping-cart"); ?></th>
                        <td><input type="text" name="wpspc_stripe_live_secret_key" size="100" value="<?php echo esc_attr(get_option('wpspc_stripe_live_secret_key')); ?>" />
                            <span class="description"><?php _e("Enter your live Secret Key.", "wordpress-simple-paypal-shopping-cart"); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e("Test Publishable Key", "wordpress-simple-paypal-shopping-cart"); ?></th>
                        <td><input type="text" name="wpspc_stripe_test_publishable_key" size="100" value="<?php echo esc_attr(get_option('wpspc_stripe_test_publishable_key')); ?>" />
                            <span class="description"><?php _e("Enter your test Publishable Key.", "wordpress-simple-paypal-shopping-cart"); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e("Test Secret Key", "wordpress-simple-paypal-shopping-cart"); ?></th>
                        <td><input type="text" name="wpspc_stripe_test_secret_key" size="100" value="<?php echo esc_attr(get_option('wpspc_stripe_test_secret_key')); ?>" />
                            <span class="description"><?php _e("Enter your test Secret Key.", "wordpress-simple-paypal-shopping-cart"); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e("Collect Address on Stripe Checkout Page", "wordpress-simple-paypal-shopping-cart");?></th>
                        <td><input type="checkbox" name="wpspc_stripe_collect_address" value="1" <?php echo $wpspc_stripe_collect_address;?> />
                        <span class="description"><?php _e("If this option is checked, customers will be required to enter their address on Stripe during the checkout process.", "wordpress-simple-paypal-shopping-cart")?></span></td>
                    </tr>

                </table>

                <h4><?php _e("Button Appearance Settings", "wordpress-simple-paypal-shopping-cart"); ?></h4>
                <hr />

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e("Checkout Button Image URL", "wordpress-simple-paypal-shopping-cart"); ?></th>
                        <td><input type="text" name="wpspc_stripe_button_image_url" size="100" value="<?php echo esc_attr(get_option('wpspc_stripe_button_image_url')); ?>" />
                            <span class="description"><?php _e("If you want to customize the look of the button using an image then enter the URL of the image.", "wordpress-simple-paypal-shopping-cart"); ?></span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="submit">
            <input type="submit" class="button-primary" name="wpspc_stripe_settings_update" value="<?php echo (__("Update Options &raquo;", "wordpress-simple-paypal-shopping-cart")) ?>" />
        </div>
    </form>

<?php
    wpspsc_settings_menu_footer();
}
