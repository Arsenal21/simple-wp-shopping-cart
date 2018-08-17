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

	$enable_pp_smart_checkout = filter_input( INPUT_POST, 'wpspc_enable_pp_smart_checkout', FILTER_SANITIZE_NUMBER_INT );

	update_option( 'wpspc_enable_pp_smart_checkout', $enable_pp_smart_checkout );

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
