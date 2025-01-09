<?php

function show_wp_cart_adv_settings_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access the settings page.' );
    }

    if ( isset( $_POST[ 'wpspc_adv_settings_update' ] ) ) {
	$nonce = $_REQUEST[ '_wpnonce' ];
	if ( ! wp_verify_nonce( $nonce, 'wpspc_adv_settings_update' ) ) {
	    wp_die( 'Error! Nonce Security Check Failed! Go back to email settings menu and save the settings again.' );
	}

	$enable_pp_smart_checkout	 = filter_input( INPUT_POST, 'wpspc_enable_pp_smart_checkout', FILTER_SANITIZE_NUMBER_INT );
	$live_client_id			 = sanitize_text_field( $_POST['wpspc_pp_live_client_id']);
	$test_client_id			 = sanitize_text_field( $_POST['wpspc_pp_test_client_id']);
	$live_secret			 = sanitize_text_field( $_POST['wpspc_pp_live_secret']);
	$test_secret			 = sanitize_text_field( $_POST['wpspc_pp_test_secret']);	
	$btn_size			 = sanitize_text_field( $_POST['wpspc_pp_smart_checkout_btn_size']);
	$btn_color			 = sanitize_text_field( $_POST['wpspc_pp_smart_checkout_btn_color']);
	$btn_shape			 = sanitize_text_field( $_POST['wpspc_pp_smart_checkout_btn_shape']);
	$btn_layout			 = sanitize_text_field( $_POST['wpspc_pp_smart_checkout_btn_layout']);
	$pm_credit			 = sanitize_text_field( $_POST['wpspc_pp_smart_checkout_payment_method_credit']);
	$pm_elv= isset($_POST['wpspc_pp_smart_checkout_payment_method_elv']) ? sanitize_text_field( $_POST['wpspc_pp_smart_checkout_payment_method_elv']) : '';

	update_option( 'wpspc_enable_pp_smart_checkout', $enable_pp_smart_checkout );
	update_option( 'wpspc_pp_live_client_id', $live_client_id );
	update_option( 'wpspc_pp_live_secret', $live_secret );
	update_option( 'wpspc_pp_test_client_id', $test_client_id );
	update_option( 'wpspc_pp_test_secret', $test_secret );
	update_option( 'wpspc_pp_smart_checkout_btn_size', $btn_size );
	update_option( 'wpspc_pp_smart_checkout_btn_color', $btn_color );
	update_option( 'wpspc_pp_smart_checkout_btn_shape', $btn_shape );
	update_option( 'wpspc_pp_smart_checkout_btn_layout', $btn_layout );
	update_option( 'wpspc_pp_smart_checkout_payment_method_credit', $pm_credit );
	update_option( 'wpspc_pp_smart_checkout_payment_method_elv', $pm_elv );

	echo '<div id="message" class="updated fade"><p><strong>';
	echo 'PayPal Smart Checkout Settings Updated!';
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

    //Show the documentation message
    wpsc_settings_menu_documentation_msg();
    ?>

    <form method="post" action="">
	<?php wp_nonce_field( 'wpspc_adv_settings_update' ); ?>
        <input type="hidden" name="info_update" id="info_update" value="true" />

        <div class="postbox">
    	<h3 class="hndle">
    	    <label for="title"><?php _e( "PayPal Smart Checkout Settings", "wordpress-simple-paypal-shopping-cart" ); ?></label>
    	</h3>
    	<div class="inside">

			<div class="wpsc-yellow-box">
			<p>
				<?php _e("<strong>Note:</strong> PayPal has deprecated the Smart Checkout API and replaced it with the PayPal Commerce Platform. Configure this new PayPal API from the", "wordpress-simple-paypal-shopping-cart"); ?>
				<?php echo ' '; ?>
				<a href="admin.php?page=wspsc-menu-main&action=ppcp-settings" target="_blank"><?php _e("PayPal PPCP Settings", "wordpress-simple-paypal-shopping-cart"); ?></a>
				<?php echo ' ' . __("tab of our plugin.", "wordpress-simple-paypal-shopping-cart"); ?>
			</p>
			</div>
			
    	    <table class="form-table">

    		<tr valign="top">
    		    <th scope="row"><?php _e( "Enable PayPal Smart Checkout", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="checkbox" name="wpspc_enable_pp_smart_checkout" value="1"<?php echo get_option( 'wpspc_enable_pp_smart_checkout' ) ? ' checked' : ''; ?>/>
    			<span class="description">
                            <?php _e( "Enable PayPal Smart Checkout.", "wordpress-simple-paypal-shopping-cart" ); ?>
                            <?php echo '<a href="https://www.tipsandtricks-hq.com/ecommerce/enabling-smart-button-checkout-setup-and-configuration-4568" target="_blank">' . __( "View Documentation", "wordpress-simple-paypal-shopping-cart" ) . '</a>.'; ?>
                        </span>

    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Live Client ID", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_live_client_id" size="100" value="<?php echo esc_attr( get_option( 'wpspc_pp_live_client_id' ) ); ?>"/>
    			<span class="description"><?php _e( "Enter your live Client ID.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Live Secret", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_live_secret" size="100" value="<?php echo esc_attr( get_option( 'wpspc_pp_live_secret' ) ); ?>"/>
    			<span class="description"><?php _e( "Enter your live Secret.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Sandbox Client ID", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_test_client_id" size="100" value="<?php echo esc_attr( get_option( 'wpspc_pp_test_client_id' ) ); ?>"/>
    			<span class="description"><?php _e( "Enter your sandbox Client ID.", "wordpress-simple-paypal-shopping-cart" ); ?></span>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Sandbox Secret", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td><input type="text" name="wpspc_pp_test_secret" size="100" value="<?php echo esc_attr( get_option( 'wpspc_pp_test_secret' )); ?>"/>
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
				echo WPSC_Admin_Utils::gen_options( array(
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
				echo WPSC_Admin_Utils::gen_options( array(
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
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_shape" value="rect"<?php WPSC_Admin_Utils::e_checked( $btn_shape, 'rect', true ); ?>> <?php _e( "Rectangular ", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_shape" value="pill"<?php WPSC_Admin_Utils::e_checked( $btn_shape, 'pill' ); ?>> <?php _e( "Pill", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p class="description"><?php _e( "Select button shape.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
    		    </td>
    		</tr>
    		<tr valign="top">
    		    <th scope="row"><?php _e( "Layout", "wordpress-simple-paypal-shopping-cart" ); ?></th>
    		    <td>
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_layout" value="vertical"<?php WPSC_Admin_Utils::e_checked( $btn_layout, 'vertical', true ); ?>> <?php _e( "Vertical", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p><label><input type="radio" name="wpspc_pp_smart_checkout_btn_layout" value="horizontal"<?php WPSC_Admin_Utils::e_checked( $btn_layout, 'horizontal' ); ?>> <?php _e( "Horizontal", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
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
    			<p><label><input type="checkbox" name="wpspc_pp_smart_checkout_payment_method_credit" value="1"<?php WPSC_Admin_Utils::e_checked( $pm_credit ); ?>> <?php _e( "PayPal Credit", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p><label><input type="checkbox" name="wpspc_pp_smart_checkout_payment_method_elv" value="1"<?php WPSC_Admin_Utils::e_checked( $pm_elv ); ?>> <?php _e( "ELV", "wordpress-simple-paypal-shopping-cart" ); ?></label></p>
    			<p class="description"><?php _e( "Select payment methods that could be used by customers. Note that payment with cards is always enabled.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
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
    wpsc_settings_menu_footer();
}
