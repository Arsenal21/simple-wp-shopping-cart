<?php

function show_wp_cart_email_settings_page()
{
    if(!current_user_can('manage_options')){
        wp_die('You do not have permission to access the settings page.');
    }
    
    if (isset($_POST['wpspc_email_settings_update']))
    {
        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'wpspc_email_settings_update')){
                wp_die('Error! Nonce Security Check Failed! Go back to email settings menu and save the settings again.');
        }
        update_option('wpspc_send_buyer_email', (isset($_POST['wpspc_send_buyer_email']) && $_POST['wpspc_send_buyer_email']!='') ? 'checked="checked"':'' );        
        update_option('wpspc_buyer_from_email', stripslashes((string)$_POST["wpspc_buyer_from_email"]));
        update_option('wpspc_buyer_email_subj', stripslashes((string)$_POST["wpspc_buyer_email_subj"]));
        update_option('wpspc_buyer_email_body', stripslashes((string)$_POST["wpspc_buyer_email_body"]));;
        
        update_option('wpspc_send_seller_email', (isset($_POST['wpspc_send_seller_email']) && $_POST['wpspc_send_seller_email']!='') ? 'checked="checked"':'' );        
        update_option('wpspc_notify_email_address', stripslashes((string)$_POST["wpspc_notify_email_address"]));
        update_option('wpspc_seller_email_subj', stripslashes((string)$_POST["wpspc_seller_email_subj"]));
        update_option('wpspc_seller_email_body', stripslashes((string)$_POST["wpspc_seller_email_body"]));;
        
        echo '<div id="message" class="updated fade"><p><strong>';
        echo 'Email Settings Updated!';
        echo '</strong></p></div>';
    }
    $wpspc_send_buyer_email = '';
    if (get_option('wpspc_send_buyer_email')){
        $wpspc_send_buyer_email = 'checked="checked"';
    }
    $wpspc_buyer_from_email = get_option('wpspc_buyer_from_email');    
    $wpspc_buyer_email_subj = get_option('wpspc_buyer_email_subj');    
    $wpspc_buyer_email_body = get_option('wpspc_buyer_email_body');
    $wpspc_send_seller_email = '';
    if (get_option('wpspc_send_seller_email')){
        $wpspc_send_seller_email = 'checked="checked"';
    }
    $wpspc_notify_email_address = get_option('wpspc_notify_email_address'); 
    if(empty($wpspc_notify_email_address)){
        $wpspc_notify_email_address = get_bloginfo('admin_email'); //default value
    }
    $wpspc_seller_email_subj = get_option('wpspc_seller_email_subj');  
    if(empty($wpspc_seller_email_subj)){
        $wpspc_seller_email_subj = "Notification of product sale";
    }
    $wpspc_seller_email_body = get_option('wpspc_seller_email_body');
    if(empty($wpspc_seller_email_body)){
        $wpspc_seller_email_body = "Dear Seller\n".
        "\nThis mail is to notify you of a product sale.\n".
        "\n{product_details}".      
        "\n\nThe sale was made to {first_name} {last_name} ({payer_email})".
        "\n\nThanks";
    }
    ?>
    
    <div class="wspsc_yellow_box">
    <p><?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "wordpress-simple-paypal-shopping-cart"); ?><br />
    <a href="https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768" target="_blank"><?php _e("WP Simple Cart Homepage", "wordpress-simple-paypal-shopping-cart"); ?></a></p>
    </div>
    
    <form method="post" action="">
    <?php wp_nonce_field('wpspc_email_settings_update'); ?>
    <input type="hidden" name="info_update" id="info_update" value="true" />
    
    <div class="postbox">
    <h3 class="hndle"><label for="title"><?php _e("Purchase Confirmation Email Settings", "wordpress-simple-paypal-shopping-cart");?></label></h3>
    <div class="inside">

    <p><i><?php _e("The following options affect the emails that gets sent to your buyers after a purchase.", "wordpress-simple-paypal-shopping-cart");?></i></p>

    <table class="form-table">

    <tr valign="top">
    <th scope="row"><?php _e("Send Emails to Buyer After Purchase", "wordpress-simple-paypal-shopping-cart");?></th>
    <td><input type="checkbox" name="wpspc_send_buyer_email" value="1" <?php echo $wpspc_send_buyer_email; ?> /><span class="description"><?php _e("If checked the plugin will send an email to the buyer with the sale details. If digital goods are purchased then the email will contain the download links for the purchased products.", "wordpress-simple-paypal-shopping-cart");?></a></span></td>
    </tr>
    
    <tr valign="top">
    <th scope="row"><?php _e("From Email Address", "wordpress-simple-paypal-shopping-cart");?></th>
    <td><input type="text" name="wpspc_buyer_from_email" value="<?php echo esc_attr($wpspc_buyer_from_email); ?>" size="50" />
    <br /><p class="description"><?php _e("Example: Your Name &lt;sales@your-domain.com&gt; This is the email address that will be used to send the email to the buyer. This name and email address will appear in the from field of the email.", "wordpress-simple-paypal-shopping-cart");?></p></td>
    </tr>

    <tr valign="top">
    <th scope="row"><?php _e("Buyer Email Subject", "wordpress-simple-paypal-shopping-cart");?></th>
    <td><input type="text" name="wpspc_buyer_email_subj" value="<?php echo esc_attr($wpspc_buyer_email_subj); ?>" size="50" />
    <br /><p class="description"><?php _e("This is the subject of the email that will be sent to the buyer.", "wordpress-simple-paypal-shopping-cart");?></p></td>
    </tr>

    <tr valign="top">
    <th scope="row"><?php _e("Buyer Email Body", "wordpress-simple-paypal-shopping-cart");?></th>
    <td>
    <textarea name="wpspc_buyer_email_body" cols="90" rows="7"><?php echo esc_textarea($wpspc_buyer_email_body); ?></textarea>
    <br /><p class="description"><?php _e("This is the body of the email that will be sent to the buyer. Do not change the text within the braces {}. You can use the following email tags in this email body field:", "wordpress-simple-paypal-shopping-cart");?>
    <br />{first_name} – <?php _e("First name of the buyer", "wordpress-simple-paypal-shopping-cart");?>
    <br />{last_name} – <?php _e("Last name of the buyer", "wordpress-simple-paypal-shopping-cart");?>
    <br />{payer_email} – <?php _e("Email Address of the buyer", "wordpress-simple-paypal-shopping-cart");?>
    <br />{address} – <?php _e("Address of the buyer", "wordpress-simple-paypal-shopping-cart");?>     
    <br />{product_details} – <?php _e("The item details of the purchased product (this will include the download link for digital items).", "wordpress-simple-paypal-shopping-cart");?>   
    <br />{transaction_id} – <?php _e("The unique transaction ID of the purchase", "wordpress-simple-paypal-shopping-cart");?> 
    <br />{order_id} – <?php _e("The order ID reference of this transaction in the cart orders menu", "wordpress-simple-paypal-shopping-cart");?> 
    <br />{purchase_amt} – <?php _e("The amount paid for the current transaction", "wordpress-simple-paypal-shopping-cart");?>
    <br />{purchase_date} – <?php _e("The date of the purchase", "wordpress-simple-paypal-shopping-cart");?>
    <br />{coupon_code} – <?php _e("Coupon code applied to the purchase", "wordpress-simple-paypal-shopping-cart");?>
    </p></td>
    </tr>
    
    <tr valign="top">
    <th scope="row"><?php _e("Send Emails to Seller After Purchase", "wordpress-simple-paypal-shopping-cart");?></th>
    <td><input type="checkbox" name="wpspc_send_seller_email" value="1" <?php echo $wpspc_send_seller_email; ?> /><span class="description"><?php _e("If checked the plugin will send an email to the seller with the sale details", "wordpress-simple-paypal-shopping-cart");?></a></span></td>
    </tr>
    
    <tr valign="top">
    <th scope="row"><?php _e("Notification Email Address*", "wordpress-simple-paypal-shopping-cart");?></th>
    <td><input type="text" name="wpspc_notify_email_address" value="<?php echo esc_attr($wpspc_notify_email_address); ?>" size="50" />
    <br /><p class="description"><?php _e("This is the email address where the seller will be notified of product sales. You can put multiple email addresses separated by comma (,) in the above field to send the notification to multiple email addresses.", "wordpress-simple-paypal-shopping-cart");?></p></td>
    </tr>

    <tr valign="top">
    <th scope="row"><?php _e("Seller Email Subject*", "wordpress-simple-paypal-shopping-cart");?></th>
    <td><input type="text" name="wpspc_seller_email_subj" value="<?php echo esc_attr($wpspc_seller_email_subj); ?>" size="50" />
    <br /><p class="description"><?php _e("This is the subject of the email that will be sent to the seller for record.", "wordpress-simple-paypal-shopping-cart");?></p></td>
    </tr>

    <tr valign="top">
    <th scope="row"><?php _e("Seller Email Body*", "wordpress-simple-paypal-shopping-cart");?></th>
    <td>
    <textarea name="wpspc_seller_email_body" cols="90" rows="7"><?php echo esc_textarea($wpspc_seller_email_body); ?></textarea>
    <br /><p class="description"><?php _e("This is the body of the email that will be sent to the seller for record. Do not change the text within the braces {}. You can use the following email tags in this email body field:", "wordpress-simple-paypal-shopping-cart");?>
    <br />{first_name} – <?php _e("First name of the buyer", "wordpress-simple-paypal-shopping-cart");?>
    <br />{last_name} – <?php _e("Last name of the buyer", "wordpress-simple-paypal-shopping-cart");?>
    <br />{payer_email} – <?php _e("Email Address of the buyer", "wordpress-simple-paypal-shopping-cart");?>
    <br />{address} – <?php _e("Address of the buyer", "wordpress-simple-paypal-shopping-cart");?>    
    <br />{product_details} – <?php _e("The item details of the purchased product (this will include the download link for digital items).", "wordpress-simple-paypal-shopping-cart");?>
    <br />{transaction_id} – <?php _e("The unique transaction ID of the purchase", "wordpress-simple-paypal-shopping-cart");?>
    <br />{order_id} – <?php _e("The order ID reference of this transaction in the cart orders menu", "wordpress-simple-paypal-shopping-cart");?>
    <br />{purchase_amt} – <?php _e("The amount paid for the current transaction", "wordpress-simple-paypal-shopping-cart");?>
    <br />{purchase_date} – <?php _e("The date of the purchase", "wordpress-simple-paypal-shopping-cart");?>
    <br />{coupon_code} – <?php _e("Coupon code applied to the purchase", "wordpress-simple-paypal-shopping-cart");?>
    </p></td>
    </tr>

    </table>    

    </div></div>
        
    <div class="submit">
        <input type="submit" class="button-primary" name="wpspc_email_settings_update" value="<?php echo (__("Update Options &raquo;", "wordpress-simple-paypal-shopping-cart")) ?>" />
    </div>
    </form>
    
    <?php
    wpspsc_settings_menu_footer();
}
