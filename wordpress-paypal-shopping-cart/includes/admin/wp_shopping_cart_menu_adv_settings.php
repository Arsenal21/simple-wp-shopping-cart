<?php

function show_wp_cart_adv_settings_page() {

    require_once(WP_CART_PATH . 'includes/admin/wp_shopping_cart_admin_utils.php');

    if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access the settings page.' );
    }

    if ( isset( $_POST[ 'wpspc_adv_settings_update' ] ) ) {
	$nonce = $_REQUEST[ '_wpnonce' ];
	if ( ! wp_verify_nonce( $nonce, 'wpspc_adv_settings_update' ) ) {
	    wp_die( 'Error! Nonce Security Check Failed! Go back to email settings menu and save the settings again.' );
	}

	$enable_pp_smart_checkout	 = filter_input( INPUT_POST, 'wpspc_enable_pp_smart_checkout', FILTER_SANITIZE_NUMBER_INT );
	$live_client_id			 = filter_input( INPUT_POST, 'wpspc_pp_live_client_id', FILTER_SANITIZE_STRING );
	$test_client_id			 = filter_input( INPUT_POST, 'wpspc_pp_test_client_id', FILTER_SANITIZE_STRING );
	$live_secret			 = filter_input( INPUT_POST, 'wpspc_pp_live_secret', FILTER_SANITIZE_STRING );
	$test_secret			 = filter_input( INPUT_POST, 'wpspc_pp_test_secret', FILTER_SANITIZE_STRING );
	$disable_standard_checkout	 = filter_input( INPUT_POST, 'wpspc_disable_standard_checkout', FILTER_SANITIZE_NUMBER_INT );
	$btn_size			 = filter_input( INPUT_POST, 'wpspc_pp_smart_checkout_btn_size', FILTER_SANITIZE_STRING );
	$btn_color			 = filter_input( INPUT_POST, 'wpspc_pp_smart_checkout_btn_color', FILTER_SANITIZE_STRING );
	$btn_shape			 = filter_input( INPUT_POST, 'wpspc_pp_smart_checkout_btn_shape', FILTER_SANITIZE_STRING );
	$btn_layout			 = filter_input( INPUT_POST, 'wpspc_pp_smart_checkout_btn_layout', FILTER_SANITIZE_STRING );
	$pm_credit			 = filter_input( INPUT_POST, 'wpspc_pp_smart_checkout_payment_method_credit', FILTER_SANITIZE_STRING );
	$pm_elv				 = filter_input( INPUT_POST, 'wpspc_pp_smart_checkout_payment_method_elv', FILTER_SANITIZE_STRING );

	update_option( 'wpspc_enable_pp_smart_checkout', $enable_pp_smart_checkout );
	update_option( 'wpspc_pp_live_client_id', $live_client_id );
	update_option( 'wpspc_pp_live_secret', $live_secret );
	update_option( 'wpspc_pp_test_client_id', $test_client_id );
	update_option( 'wpspc_pp_test_secret', $test_secret );
	update_option( 'wpspc_disable_standard_checkout', $disable_standard_checkout );
	update_option( 'wpspc_pp_smart_checkout_btn_size', $btn_size );
	update_option( 'wpspc_pp_smart_checkout_btn_color', $btn_color );
	update_option( 'wpspc_pp_smart_checkout_btn_shape', $btn_shape );
	update_option( 'wpspc_pp_smart_checkout_btn_layout', $btn_layout );
	update_option( 'wpspc_pp_smart_checkout_payment_method_credit', $pm_credit );
	update_option( 'wpspc_pp_smart_checkout_payment_method_elv', $pm_elv );

	echo '<div id="message" class="updated fade"><p><strong>';
	echo 'Advanced Settings Updated!';
	echo '</strong></p></div>';
    }
    $wpspc_send_buyer_email = '';
    if ( get_option( 'wpspc_send_buyer_email' ) ) {
	$wpspc_send_buyer_email = 'checked="checked"';
    }
    $wpspc_buyer_from_email	 = get_option( 'wpspc_buyer_from_email' );
    $wpspc_buyer_email_subj	 = get_option( 'wpspc_buyer_email_subj' );
    $wpspc_buyer_email_body	 = get_option( 'wpspc_buyer_email_body' );
    $wpspc_send_seller_email = '';
    if ( get_option( 'wpspc_send_seller_email' ) ) {
	$wpspc_send_seller_email = 'checked="checked"';
    }
    $wpspc_notify_email_address = get_option( 'wpspc_notify_email_address' );
    if ( empty( $wpspc_notify_email_address ) ) {
	$wpspc_notify_email_address = get_bloginfo( 'admin_email' ); //default value
    }
    $wpspc_seller_email_subj = get_option( 'wpspc_seller_email_subj' );
    if ( empty( $wpspc_seller_email_subj ) ) {
	$wpspc_seller_email_subj = "Notification of product sale";
    }
    $wpspc_seller_email_body = get_option( 'wpspc_seller_email_body' );
    if ( empty( $wpspc_seller_email_body ) ) {
	$wpspc_seller_email_body = "Dear Seller\n" .
	"\nThis mail is to notify you of a product sale.\n" .
	"\n{product_details}" .
	"\n\nThe sale was made to {first_name} {last_name} ({payer_email})" .
	"\n\nThanks";
    }
    ?>

    <div class="wspsc_yellow_box">
        <p><?php _e( "For more information, updates, detailed documentation and video tutorial, please visit:", "wordpress-simple-paypal-shopping-cart" ); ?><br />
    	<a href="https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768" target="_blank"><?php _e( "WP Simple Cart Homepage", "wordpress-simple-paypal-shopping-cart" ); ?></a></p>
    </div>

    <form method="post" action="">
	<?php wp_nonce_field( 'wpspc_adv_settings_update' ); ?>
        <input type="hidden" name="info_update" id="info_update" value="true" />

        <div class="postbox">
    	<h3 class="hndle">
    	    <label for="title"><?php _e( "PayPal Smart Checkout Settings", "wordpress-simple-paypal-shopping-cart" ); ?></label>
    	</h3>
    	<div class="inside">

    	    <table class="form-table">

    		<tr valign="top">
    		    <th scope="row"><?php _e( "Enable PayPal Smart Checkout", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="checkbox" name="wpspc_enable_pp_smart_checkout" value="1"<?php echo get_option( 'wpspc_enable_pp_smart_checkout' ) ? ' checked' : ''; ?>/>
    			<span class="description"><?php _e( "Enable PayPal Smart Checkout.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Live Client ID", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_live_client_id" size="100" value="<?php echo get_option( 'wpspc_pp_live_client_id' ); ?>"/>
    			<span class="description"><?php _e( "Enter your live Client ID.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Live Secret", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_live_secret" size="100" value="<?php echo get_option( 'wpspc_pp_live_secret' ); ?>"/>
    			<span class="description"><?php _e( "Enter your live Secret.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Sandbox Client ID", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_test_client_id" size="100" value="<?php echo get_option( 'wpspc_pp_test_client_id' ); ?>"/>
    			<span class="description"><?php _e( "Enter your sandbox Client ID.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Sandbox Secret", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_test_secret" size="100" value="<?php echo get_option( 'wpspc_pp_test_secret' ); ?>"/>
    			<span class="description"><?php _e( "Enter your sandbox Secret.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>

    	    </table>

    	    <h4><?php _e( "Button Appearance Settings", "wordpress-simple-paypal-shopping-cart" ); ?></h4>
    	    <hr />

    	    <table class="form-table">
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Size", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td>
    			<select name="wpspc_pp_smart_checkout_btn_size">
				<?php
				$btn_size	 = get_option( 'wpspc_pp_smart_checkout_btn_size' );
				echo WPSPCAdminUtils::gen_options( array(
				    array( 'medium', __( "Medium", "wordpress-simple-paypal-shopping-cart" ) ),
				    array( 'large', __( "Large", "wordpress-simple-paypal-shopping-cart" ) ),
				    array( 'responsive', __( "Repsonsive", "wordpress-simple-paypal-shopping-cart" ) ),
				), $btn_size );
				?>
    			</select>
    			<span class="description"><?php _e( "Select button size.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Color", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td>
    			<select name="wpspc_pp_smart_checkout_btn_color">
				<?php
				$btn_color	 = get_option( 'wpspc_pp_smart_checkout_btn_color' );
				echo WPSPCAdminUtils::gen_options( array(
				    array( 'gold', __( "Gold", "wordpress-simple-paypal-shopping-cart" ) ),
				    array( 'blue', __( "Blue", "wordpress-simple-paypal-shopping-cart" ) ),
				    array( 'silver', __( "Silver", "wordpress-simple-paypal-shopping-cart" ) ),
				    array( 'black', __( "Black", "wordpress-simple-paypal-shopping-cart" ) ),
				), $btn_color );
				?>
    			</select>
    			<span class="description"><?php _e( "Select button color.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
		    <?php
		    $btn_layout	 = get_option( 'wpspc_pp_smart_checkout_btn_layout' );
		    $btn_shape	 = get_option( 'wpspc_pp_smart_checkout_btn_shape' );
		    ?>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Shape", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td>
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_shape" value="rect"<?php WPSPCAdminUtils::e_checked( $btn_shape, 'rect', true ); ?>> <?php _e( "Rectangular ", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_shape" value="pill"<?php WPSPCAdminUtils::e_checked( $btn_shape, 'pill' ); ?>> <?php _e( "Pill", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p class="description"><?php _e( "Select button shape.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Layout", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td>
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_layout" value="vertical"<?php WPSPCAdminUtils::e_checked( $btn_layout, 'vertical', true ); ?>> <?php _e( "Vertical", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_layout" value="horizontal"<?php WPSPCAdminUtils::e_checked( $btn_layout, 'horizontal' ); ?>> <?php _e( "Horizontal", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p class="description"><?php _e( "Select button layout.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
    		    </td>
    		</tr>
    	    </table>

    	    <h4><?php _e( "Additional Settings", "wordpress-simple-paypal-shopping-cart" ); ?></h4>
    	    <hr />
		<?php
		$pm_credit	 = get_option( 'wpspc_pp_smart_checkout_payment_method_credit' );
		$pm_elv		 = get_option( 'wpspc_pp_smart_checkout_payment_method_elv' );
		?>
    	    <table class="form-table">
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Payment Methods", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td>
    			<p><label><input type="checkbox" name="wpspc_pp_smart_checkout_payment_method_credit" value="1"<?php WPSPCAdminUtils::e_checked( $pm_credit ); ?>> <?php _e( "PayPal Credit", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p><label><input type="checkbox" name="wpspc_pp_smart_checkout_payment_method_elv" value="1"<?php WPSPCAdminUtils::e_checked( $pm_elv ); ?>> <?php _e( "ELV", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p class="description"><?php _e( "Select payment methods that could be used by customers. Note that payment with cards is always enabled.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Disable Standard PayPal Checkout", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="checkbox" name="wpspc_disable_standard_checkout" value="1"<?php echo get_option( 'wpspc_disable_standard_checkout' ) ? ' checked' : ''; ?>/>
    			<span class="description"><?php _e( "By default PayPal standard checkout is always enabled. If you only want to use the PayPal Smart Checkout instead then use this checkbox to disable the standard checkout option. This option will only have effect when Smart Checkout is enabled.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    	    </table>

    	</div>
        </div>

        <div class="submit">
    	<input type="submit" class="button-primary" name="wpspc_adv_settings_update" value="<?php echo (__( "Update Options &raquo;", "wordpress-simple-paypal-shopping-cart" )) ?>" />
        </div>
    </form>

    <?php
    wpspsc_settings_menu_footer();
}
