<?php

function show_wp_cart_manual_checkout_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to access the settings page.' );
	}

	if ( isset( $_POST['wpsc_manual_checkout_settings_update'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'wpsc_manual_checkout_settings_update' ) ) {
			wp_die( 'Error! Nonce Security Check Failed! Go back to stripe settings menu and save the settings again.' );
		}

		$enable_manual_checkout = filter_input( INPUT_POST, 'wpsc_enable_manual_checkout', FILTER_SANITIZE_NUMBER_INT );
		$manual_checkout_form_instruction = isset($_POST['wpsc_manual_checkout_form_instruction']) ? wp_kses_post($_POST['wpsc_manual_checkout_form_instruction']) : '';
		$manual_checkout_btn_text = isset($_POST['wpsc_manual_checkout_btn_text']) && !empty(trim($_POST['wpsc_manual_checkout_btn_text'])) ? sanitize_text_field($_POST['wpsc_manual_checkout_btn_text']) : __("Proceed to Manual Checkout", "wordpress-simple-paypal-shopping-cart");

        $send_buyer_payment_instruction_email = filter_input( INPUT_POST, 'wpsc_send_buyer_payment_instruction_email', FILTER_SANITIZE_NUMBER_INT );
		$buyer_payment_instruction_email_subject = isset($_POST['wpsc_buyer_payment_instruction_email_subject']) ? stripslashes(sanitize_text_field($_POST['wpsc_buyer_payment_instruction_email_subject'])) : '';
		$buyer_payment_instruction_email_body = isset($_POST['wpsc_buyer_payment_instruction_email_body']) ? stripslashes(wp_kses_post($_POST['wpsc_buyer_payment_instruction_email_body'])) : '';

		$send_manual_checkout_notification_email_to_seller = filter_input( INPUT_POST, 'wpsc_send_seller_manual_checkout_notification_email', FILTER_SANITIZE_NUMBER_INT );
		$seller_manual_checkout_notification_email_address = isset($_POST['wpsc_seller_manual_checkout_notification_email_address']) ? sanitize_email($_POST['wpsc_seller_manual_checkout_notification_email_address']) : '';
		$seller_manual_checkout_notification_email_subject = isset($_POST['wpsc_seller_manual_checkout_notification_email_subject']) ? stripslashes(sanitize_text_field($_POST['wpsc_seller_manual_checkout_notification_email_subject'])) : '';
		$seller_manual_checkout_notification_email_body = isset($_POST['wpsc_seller_manual_checkout_notification_email_body']) ? stripslashes(wp_kses_post($_POST['wpsc_seller_manual_checkout_notification_email_body'])) : '';

		update_option( 'wpsc_enable_manual_checkout', $enable_manual_checkout );
		update_option( 'wpsc_manual_checkout_form_instruction', $manual_checkout_form_instruction );
		update_option( 'wpsc_manual_checkout_btn_text', $manual_checkout_btn_text );

		update_option( 'wpsc_send_buyer_payment_instruction_email', $send_buyer_payment_instruction_email );
		update_option( 'wpsc_buyer_payment_instruction_email_subject', $buyer_payment_instruction_email_subject );
		update_option( 'wpsc_buyer_payment_instruction_email_body', $buyer_payment_instruction_email_body );

		update_option( 'wpsc_send_seller_manual_checkout_notification_email', $send_manual_checkout_notification_email_to_seller );
		update_option( 'wpsc_seller_manual_checkout_notification_email_address', $seller_manual_checkout_notification_email_address );
		update_option( 'wpsc_seller_manual_checkout_notification_email_subject', $seller_manual_checkout_notification_email_subject );
		update_option( 'wpsc_seller_manual_checkout_notification_email_body', $seller_manual_checkout_notification_email_body );

		echo '<div id="message" class="updated fade"><p>' . __("Manual Checkout Settings Updated!", "wordpress-simple-paypal-shopping-cart") . '</p></div>';
	}

	$enable_manual_checkout = get_option( 'wpsc_enable_manual_checkout' ) ? 'checked="checked"' : '';
	$manual_checkout_form_instruction = get_option( 'wpsc_manual_checkout_form_instruction' , '');
	$manual_checkout_btn_text = get_option( 'wpsc_manual_checkout_btn_text' , '');

	$send_buyer_payment_instruction_email = get_option( 'wpsc_send_buyer_payment_instruction_email' ) ? 'checked="checked"' : '';
    $buyer_payment_instruction_email_subject = get_option( 'wpsc_buyer_payment_instruction_email_subject' , '');
    if (empty($buyer_payment_instruction_email_subject)){
	    $buyer_payment_instruction_email_subject = "Payment Instructions for Your Order";
    }

    $buyer_payment_instruction_email_body = get_option( 'wpsc_buyer_payment_instruction_email_body' , '');
    if (empty($buyer_payment_instruction_email_body)){
	    $buyer_payment_instruction_email_body = "Dear {first_name}\n".
	                                      "\nThank you for your purchase. Please follow the instructions below to complete your payment.\n".
	                                      "\nKindly transfer the amount of {purchase_amt} to the following bank account:".
                                          "\nAccount Number: XXXX-XXXX-XXXX-XXXX\n".
                                          "\nOnce the payment is made, please let me know.\n".
	                                      "\nThanks";
    }

	$send_manual_checkout_notification_email_to_seller = get_option( 'wpsc_send_seller_manual_checkout_notification_email' ) ? 'checked="checked"' : '';
	$seller_manual_checkout_notification_email_address = get_option( 'wpsc_seller_manual_checkout_notification_email_address', '');
	$seller_manual_checkout_notification_email_subject = get_option( 'wpsc_seller_manual_checkout_notification_email_subject' , '');
	if (empty($seller_manual_checkout_notification_email_subject)){
		$seller_manual_checkout_notification_email_subject = "New Manual Checkout Sale Notification";
	}

	$seller_manual_checkout_notification_email_body = get_option( 'wpsc_seller_manual_checkout_notification_email_body' , '');
	if (empty($seller_manual_checkout_notification_email_body)){
		$seller_manual_checkout_notification_email_body = "Dear Seller\n".
		                                  "\nA new sale has been completed via manual checkout.".
		                                  "\nTransaction ID: {transaction_id}\n".
                                          "\nPlease review the order details in your dashboard.\n".
		                                  "\nThanks"; // TODO: Need to fix this text
	}

	$wpsc_email_content_type = get_option('wpsc_email_content_type');

	//Show the documentation message
	wpsc_settings_menu_documentation_msg();
	?>

    <form method="post" action="">
		<?php wp_nonce_field( 'wpsc_manual_checkout_settings_update' ); ?>
        <input type="hidden" name="info_update" id="info_update" value="true"/>

        <div class="postbox">
            <h3 class="hndle"><?php _e( "Manual Checkout Settings", "wordpress-simple-paypal-shopping-cart" ); ?></h3>
            <div class="inside">
                <p>
		            <?php _e("For instructions on enabling Manual Checkout, please refer to ", "wordpress-simple-paypal-shopping-cart") ?>
                    <?php echo '<a href="https://www.tipsandtricks-hq.com/ecommerce/simple-shopping-cart-enabling-manual-offline-checkout" target="_blank">' . __("this documentation", "wordpress-simple-paypal-shopping-cart") . '</a>.'; ?>
                </p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( "Enable Manual Checkout", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <input type="checkbox" name="wpsc_enable_manual_checkout" value="1" <?php esc_attr_e($enable_manual_checkout); ?> />
                            <p class="description">
                                <?php
									_e( "Select this option to enable manual or offline checkout in the cart.", "wordpress-simple-paypal-shopping-cart" );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( "Manual Checkout Button Text", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <input type="text" name="wpsc_manual_checkout_btn_text" value="<?php esc_attr_e($manual_checkout_btn_text); ?>" size="50" />
                            <p class="description">
                                <?php
									_e( "Customize the manual checkout button text in the cart. The default text is 'Proceed to Manual Checkout'.", "wordpress-simple-paypal-shopping-cart" );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( "Manual Checkout Instructions on Checkout Form", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <?php
//                            add_filter( 'wp_default_editor', 'wpsc_set_default_email_body_editor' );
                            wp_editor(
	                            html_entity_decode( $manual_checkout_form_instruction ),
	                            'wpsc_manual_checkout_form_instruction',
	                            array(
		                            'textarea_name' => "wpsc_manual_checkout_form_instruction",
		                            'teeny'         => true,
		                            'media_buttons' => true,
		                            'textarea_rows' => 8,
		                            'quicktags' => false,
	                            )
                            );
//                            remove_filter( 'wp_default_editor', 'wpsc_set_default_email_body_editor' );
                            ?>

                            <p class="description">
                                <?php _e( "Add manual checkout instructions here to display them above the form.", "wordpress-simple-paypal-shopping-cart" );?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( "Send Manual Checkout Payment Instructions to Buyer via Email", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <input type="checkbox"
                                   name="wpsc_send_buyer_payment_instruction_email"
                                   value="1" <?php esc_attr_e($send_buyer_payment_instruction_email); ?>
                            />
                            <p class="description"><?php _e( "If enabled, the plugin will send an email to the buyer after completing a manual checkout.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( "Payment Instruction Email Subject", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <input type="text"
                                   name="wpsc_buyer_payment_instruction_email_subject"
                                   value="<?php echo esc_attr( $buyer_payment_instruction_email_subject ); ?>"
                                   size="50"
                            />
                            <br/>
                            <p class="description"><?php _e( "This is the subject line for the email sent to the buyer.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e("Payment Instruction Email Body", "wordpress-simple-paypal-shopping-cart");?></th>
                        <td>
                        <?php if ($wpsc_email_content_type == 'html') {
                            add_filter( 'wp_default_editor', 'wpsc_set_default_email_body_editor' );
                            wp_editor(
                                html_entity_decode( $buyer_payment_instruction_email_body ),
                                'wpsc_buyer_payment_instruction_email_body',
                                array(
                                    'textarea_name' => "wpsc_buyer_payment_instruction_email_body",
                                    'teeny'         => true,
                                )
                            );
                            remove_filter( 'wp_default_editor', 'wpsc_set_default_email_body_editor' );
                        } else { ?>
                            <textarea name="wpsc_buyer_payment_instruction_email_body" cols="90" rows="7"><?php echo esc_textarea( $buyer_payment_instruction_email_body ); ?></textarea>
                        <?php }
                            echo wp_kses_post(WPSC_Email_Handler::get_email_merge_tags_hints());
                        ?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( "Send Manual Checkout Notification to Seller via Email", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <input type="checkbox"
                                   name="wpsc_send_seller_manual_checkout_notification_email"
                                   value="1" <?php esc_attr_e($send_manual_checkout_notification_email_to_seller); ?>
                            />
                            <p class="description"><?php _e( "If checked, the plugin will send an email to the seller after a manual checkout.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( "Manual Checkout Notification Email Address", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <input type="text"
                                   name="wpsc_seller_manual_checkout_notification_email_address"
                                   value="<?php esc_attr_e( $seller_manual_checkout_notification_email_address ); ?>"
                                   size="50"
                            />
                            <br/>
                            <p class="description"><?php _e( "The email address for receiving manual checkout notifications. If left empty, the email address from the 'Email Settings' menu will be used.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( "Notification Email Subject", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td>
                            <input type="text"
                                   name="wpsc_seller_manual_checkout_notification_email_subject"
                                   value="<?php esc_attr_e( $seller_manual_checkout_notification_email_subject ); ?>"
                                   size="50"
                            />
                            <br/>
                            <p class="description"><?php _e( "This is the subject of the email that will be sent to the seller.", "wordpress-simple-paypal-shopping-cart" ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e("Notification Email Body", "wordpress-simple-paypal-shopping-cart");?></th>
                        <td>
			                <?php if ($wpsc_email_content_type == 'html') {
				                add_filter( 'wp_default_editor', 'wpsc_set_default_email_body_editor' );
				                wp_editor(
					                html_entity_decode( $seller_manual_checkout_notification_email_body ),
					                'wpsc_seller_manual_checkout_notification_email_body',
					                array(
						                'textarea_name' => "wpsc_seller_manual_checkout_notification_email_body",
						                'teeny'         => true,
					                )
				                );
				                remove_filter( 'wp_default_editor', 'wpsc_set_default_email_body_editor' );
			                } else { ?>
                                <textarea name="wpsc_seller_manual_checkout_notification_email_body" cols="90" rows="7"><?php echo esc_textarea( $seller_manual_checkout_notification_email_body ); ?></textarea>
			                <?php }
			                echo wp_kses_post(WPSC_Email_Handler::get_email_merge_tags_hints());
			                ?>
                        </td>
                    </tr>
                </table>

                <div class="submit">
                    <input type="submit"
                           class="button-primary"
                           name="wpsc_manual_checkout_settings_update"
                           value="<?php _e( "Save Changes", "wordpress-simple-paypal-shopping-cart"); ?>"
                    />
                </div>

            </div>
        </div>
    </form>

	<?php
	wpsc_settings_menu_footer();
}

function wpsc_set_default_email_body_editor( $r ) {
	$r = 'html';
	return $r;
}
