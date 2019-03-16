<?php

/* This function gets called when init is executed */
function wp_cart_init_handler()
{
    $orders_menu_permission = apply_filters('wspsc_orders_menu_permission', 'manage_options');
    //Add any common init hook handing code
    if( is_admin() && current_user_can($orders_menu_permission)) //Init hook handing code for wp-admin
    {
        wpspc_create_orders_page();
    }
    else//Init hook handling code for front end
    {
        wpspc_cart_actions_handler();
        add_filter('ngg_render_template','wp_cart_ngg_template_handler',10,2);
        if(isset($_REQUEST['simple_cart_ipn']))
        {
            include_once('paypal.php');
            wpc_handle_paypal_ipn();
            exit;
        }
    }
    if (is_admin()) {
        add_action('admin_init', 'wp_cart_add_tinymce_button');
	
	//TODO - can be removed at a later version.
	if (isset($_GET['page']) && $_GET['page'] == 'wordpress-paypal-shopping-cart') {
	    //let's redirect old settings page to new
	    wp_redirect(get_admin_url() . 'admin.php?page=wspsc-menu-main', 301);
	    exit;
	}

    }
}

function wp_cart_admin_init_handler()
{
    wpspc_add_meta_boxes();
}

function wpspsc_number_format_price($price)
{
    $formatted_num = number_format($price,2,'.','');
    return $formatted_num;
}

function wspsc_strip_char_from_price_amount($price_amount)
{
    if(!is_numeric($price_amount)){
        $price_amount = preg_replace("/[^0-9\.]/", "",$price_amount);
    }
    return $price_amount;
}

function wpc_append_values_to_custom_field($name,$value)
{
    $custom_field_val = $_SESSION['wp_cart_custom_values'];
    $new_val = $name.'='.$value;
    if (empty($custom_field_val)){
        $custom_field_val = $new_val;
    }
    else{
        $custom_field_val = $custom_field_val.'&'.$new_val;
    }
    $_SESSION['wp_cart_custom_values'] = $custom_field_val;
    return $custom_field_val;
}

function wp_cart_get_custom_var_array($custom_val_string)
{
    $delimiter = "&";
    $customvariables = array();
    $namevaluecombos = explode($delimiter, $custom_val_string);
    foreach ($namevaluecombos as $keyval_unparsed)
    {
            $equalsignposition = strpos($keyval_unparsed, '=');
            if ($equalsignposition === false)
            {
                $customvariables[$keyval_unparsed] = '';
                continue;
            }
            $key = substr($keyval_unparsed, 0, $equalsignposition);
            $value = substr($keyval_unparsed, $equalsignposition + 1);
            $customvariables[$key] = $value;
    }
    return $customvariables;
}

function wspsc_reset_logfile()
{
    $log_reset = true;
    $logfile = dirname(__FILE__).'/ipn_handle_debug.txt';
    $text = '['.date('m/d/Y g:i A').'] - SUCCESS : Log file reset';
    $text .= "\n------------------------------------------------------------------\n\n";
    $fp = fopen($logfile, 'w');
    if($fp != FALSE) {
            @fwrite($fp, $text);
            @fclose($fp);
    }
    else{
            $log_reset = false;	
    }
    return $log_reset;
}

function wp_cart_ngg_template_handler($arg1,$arg2)
{
    if($arg2=="gallery-wp-cart"){
        $template_name = "gallery-wp-cart";
        $gallery_template = WP_CART_PATH. "/lib/$template_name.php";
        return $gallery_template;
    }
    return $arg1;
}

function wpspc_insert_new_record()
{
    //First time adding to the cart
    //$cart_id = uniqid();
    //$_SESSION['simple_cart_id'] = $cart_id;
    $wpsc_order = array(
    'post_title'    => 'WPSC Cart Order',
    'post_type'     => 'wpsc_cart_orders',
    'post_content'  => '',
    'post_status'   => 'trash',
    );
    // Insert the post into the database
    $post_id  = wp_insert_post($wpsc_order);
    if($post_id){
        //echo "post id: ".$post_id;
        $_SESSION['simple_cart_id'] = $post_id;
        $updated_wpsc_order = array(
            'ID'             => $post_id,
            'post_title'    => $post_id,
            'post_type'     => 'wpsc_cart_orders',
        );
        wp_update_post($updated_wpsc_order);
        $status = "In Progress";
        update_post_meta($post_id, 'wpsc_order_status', $status);
        if(isset($_SESSION['simpleCart']) && !empty($_SESSION['simpleCart']))
        {
            update_post_meta( $post_id, 'wpsc_cart_items', $_SESSION['simpleCart']);
        }
    }
}

function wpspc_update_cart_items_record()
{
    if(isset($_SESSION['simpleCart']) && !empty($_SESSION['simpleCart']))
    {
        $post_id = $_SESSION['simple_cart_id'];
        update_post_meta( $post_id, 'wpsc_cart_items', $_SESSION['simpleCart']);
    }
}

function wpspc_apply_dynamic_tags_on_email($text, $ipn_data, $args)
{
    $order_id = $args['order_id'];
    $purchase_amount = get_post_meta( $order_id, 'wpsc_total_amount', true );
    $purchase_date = date("Y-m-d");
    $tags = array("{first_name}","{last_name}","{product_details}","{payer_email}","{transaction_id}","{purchase_amt}","{purchase_date}","{coupon_code}","{address}","{phone}","{order_id}");
    $vals = array($ipn_data['first_name'], $ipn_data['last_name'], $args['product_details'], $args['payer_email'], $ipn_data['txn_id'], $purchase_amount, $purchase_date, (isset($args['coupon_code']) ? $args['coupon_code'] : '') , $args['address'], (isset($ipn_data['contact_phone']) ? $ipn_data['contact_phone'] : ''), $order_id);

    $body = stripslashes(str_replace($tags, $vals, $text));
    return $body;
}

function wpspc_run_activation()
{
    //General options
    add_option('wp_cart_title', __("Your Shopping Cart", "wordpress-simple-paypal-shopping-cart"));
    add_option('wp_cart_empty_text', __("Your cart is empty", "wordpress-simple-paypal-shopping-cart"));
    add_option('cart_return_from_paypal_url', get_bloginfo('wpurl'));

    //Add Confirmation Email Settings
    add_option("wpspc_send_buyer_email", 1); 
    $from_email_address = get_bloginfo('name')." <sales@your-domain.com>";
    add_option('wpspc_buyer_from_email', $from_email_address);
    $buyer_email_subj = "Thank you for the purchase";
    add_option('wpspc_buyer_email_subj', $buyer_email_subj);
    $email_body = "Dear {first_name} {last_name}"."\n";
    $email_body .= "\nThank you for your purchase! You ordered the following item(s):\n";
    $email_body .= "\n{product_details}";
    add_option('wpspc_buyer_email_body', $email_body);
    
    $notify_email_address = get_bloginfo('admin_email');
    add_option('wpspc_notify_email_address', $notify_email_address);
    $seller_email_subj = "Notification of product sale";
    add_option('wpspc_seller_email_subj', $seller_email_subj);
    $seller_email_body = "Dear Seller\n";
    $seller_email_body .= "\nThis mail is to notify you of a product sale.\n";
    $seller_email_body .= "\n{product_details}";        
    $seller_email_body .= "\n\nThe sale was made to {first_name} {last_name} ({payer_email})";
    $seller_email_body .= "\n\nThanks";
    add_option('wpspc_seller_email_body', $seller_email_body);
    
    //Generate and save a private key for this site
    $unique_id = uniqid('', true);
    add_option('wspsc_private_key_one',$unique_id);
}

function wpspsc_settings_menu_footer()
{
    ?>
    <div class="wspsc_yellow_box">
    <p><?php _e("Need a shopping cart plugin with a lot of features and good support? Check out our ", "wordpress-simple-paypal-shopping-cart"); ?>
    <a href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank"><?php _e("WP eStore Plugin", "wordpress-simple-paypal-shopping-cart"); ?></a></p>
    </div>
    <?php
}