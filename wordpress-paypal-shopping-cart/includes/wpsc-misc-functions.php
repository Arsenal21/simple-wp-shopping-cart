<?php

/* This function gets called when init is executed */
function wp_cart_init_handler()
{
    $orders_menu_permission = apply_filters('wspsc_orders_menu_permission', 'manage_options'); // TODO: Old hook. Need to remove this.
    $orders_menu_permission = apply_filters('wpsc_orders_menu_permission', 'manage_options');

    //Add any common init hook handing code
    if( is_admin() && current_user_can($orders_menu_permission)) //Init hook handing code for wp-admin
    {
        wpsc_create_orders_page();
    }
    else//Init hook handling code for front end
    {
        wpsc_cart_actions_handler();
        add_filter('ngg_render_template','wp_cart_ngg_template_handler',10,2);
        if(isset($_REQUEST['simple_cart_ipn']))
        {
            include_once( WP_CART_PATH . 'paypal.php');
            wpc_handle_paypal_ipn();
            exit;
        }
        else if(isset($_REQUEST["simple_cart_stripe_ipn"]))
        {
            include_once( WP_CART_PATH . 'stripe.php');
            wpc_handle_stripe_ipn();
            exit;
        }
    }
    if (is_admin()) {
        add_action('admin_init', 'wp_cart_add_tinymce_button');
        
        wpsc_check_and_handle_csv_export();

	//TODO - can be removed at a later version.
	if (isset($_GET['page']) && $_GET['page'] == 'wordpress-paypal-shopping-cart') {
	    //let's redirect old settings page to new
	    wp_redirect(get_admin_url() . 'admin.php?page=wspsc-menu-main', 301);
	    exit;
	}

    }
}

/*
* This function gets called when admin_init hook is executed
*/
function wp_cart_admin_init_handler() {
    wpsc_add_meta_boxes();

    //Handle feedback in the admin area.
	include_once WP_CART_PATH . 'includes/admin/wp_shopping_cart_admin_user_feedback.php';
	$user_feedback = new WPSC_Admin_User_Feedback();
	$user_feedback->init();

    // View log file if requested.
    $action = isset( $_GET['wspsc-action'] ) ? sanitize_text_field( stripslashes ( $_GET['wspsc-action'] ) ) : '';
	if ( ! empty( $action ) && $action === 'view_log' ) {
        check_admin_referer( 'wspsc_view_log_nonce' );
        wpsc_read_log_file();
	}
}

function wpsc_number_format_price($price)
{
    $formatted_num = number_format($price,2,'.','');
    return $formatted_num;
}

function wpsc_strip_char_from_price_amount($price_amount)
{
    if(!is_numeric($price_amount)){
        $price_amount = preg_replace("/[^0-9\.]/", "",$price_amount);
    }
    return $price_amount;
}

function wpc_append_values_to_custom_field($name,$value)
{
    $wspsc_cart = WPSC_Cart::get_instance();
    $custom_field_val = $wspsc_cart->get_cart_custom_values(); 

    $new_val = $name.'='.$value;
    if (empty($custom_field_val)){
        $custom_field_val = $new_val;
    }
    else{
        $custom_field_val = $custom_field_val.'&'.$new_val;
    }
    
    $wspsc_cart->set_cart_custom_values($custom_field_val);
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

function wp_cart_ngg_template_handler($arg1,$arg2)
{
    if($arg2=="gallery-wp-cart"){
        $template_name = "gallery-wp-cart";
        $gallery_template = WP_CART_PATH. "/lib/$template_name.php";
        return $gallery_template;
    }
    return $arg1;
}

/**
 * @deprecated: Adds new cart record in the database.
 *
 * This function is deprecated and should no longer be used. Please use the 'WPSC_Cart' class and its 'create_cart()'
 * method to create a new.
 */
function wpsc_insert_new_record()
{
    //First time adding to the cart
    $wspsc_cart = WPSC_Cart::get_instance();
    $wspsc_cart->create_cart();
}

/**
 * @deprecated: Update the cart items record in the database using the 'wpsc_cart_items' post meta.
 *
 * This function is deprecated and should no longer be used. Please use the 'WPSC_Cart' class and its 'add_items()'
 * method to add items to the cart and update the cart items record in the database.
 */
function wpsc_update_cart_items_record()
{
    $wspsc_cart = WPSC_Cart::get_instance();
    $items = $wspsc_cart->get_items();
    $wspsc_cart->add_items($items);
}

function wpsc_apply_dynamic_tags_on_email($text, $ipn_data, $args)
{
    $order_id = $args['order_id'];
    $purchase_amount = get_post_meta( $order_id, 'wpsc_total_amount', true );
    $purchase_date = date("Y-m-d");
    $tags = array("{first_name}","{last_name}","{product_details}","{payer_email}","{transaction_id}","{purchase_amt}","{purchase_date}","{coupon_code}","{address}","{phone}","{order_id}");
    $vals = array($ipn_data['first_name'], $ipn_data['last_name'], $args['product_details'], $args['payer_email'], $ipn_data['txn_id'], $purchase_amount, $purchase_date, (isset($args['coupon_code']) ? $args['coupon_code'] : '') , $args['address'], (isset($ipn_data['contact_phone']) ? $ipn_data['contact_phone'] : ''), $order_id);

    $body = stripslashes(str_replace($tags, $vals, $text));
    return $body;
}

function wpsc_run_activation()
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

    $wspsc_default_tnc_text = __('I accept the <a href="https://example.com/terms-and-conditions/" target="_blank">Terms and Conditions</a>', "wordpress-simple-paypal-shopping-cart");
    add_option('wp_shopping_cart_tnc_text', $wspsc_default_tnc_text);

	// Set plugin activation time.
	if ( empty( get_option( 'wspsc_plugin_activated_time' ) ) ) {
		add_option( 'wspsc_plugin_activated_time', time() );
	}

    $default_allowed_shipping_countries = "US, GB, CA, AU";
    add_option('wpsc_stripe_allowed_shipping_countries', $default_allowed_shipping_countries);

	wpsc_check_and_create_thank_you_page();
}

/**
 * Check if a Thank-you page exists. Create new one if not.
 */
function wpsc_check_and_create_thank_you_page() {
    $thank_you_page_url = get_option('cart_return_from_paypal_url');
    if (!empty($thank_you_page_url)){
        // User already using a page as a thank-you page, don't create another one.
        return;
    }

    // Create new thank you page.
	$post_id = wp_insert_post( array(
		'post_title'     => 'Thank You',
		'post_name'      => 'Thank-You-Order-Summary',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_content'   => '<!-- wp:shortcode -->[wpsc_thank_you]<!-- /wp:shortcode -->',
		'post_status'    => 'publish',
		'post_type'      => 'page',
	) );

	// Save the Thank you page URL in settings.
	update_option( 'cart_return_from_paypal_url', get_permalink($post_id) );
}

function wpsc_settings_menu_documentation_msg()
{
    ?>
    <div class="wpsc-grey-box">
        <p>
            <?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "wordpress-simple-paypal-shopping-cart"); ?>
            <?php echo ' '; ?>
            <a href="https://www.tipsandtricks-hq.com/ecommerce/wp-shopping-cart" target="_blank"><?php _e("WP Simple Cart Documentation", "wordpress-simple-paypal-shopping-cart"); ?></a>
        </p>
    </div>
    <?php
}

function wpsc_settings_menu_footer()
{
    ?>
    <div class="wpsc-yellow-box">
    <p>
        <?php _e("Need a shopping cart plugin with a lot of features and support? Check out our ", "wordpress-simple-paypal-shopping-cart"); ?>
        <a href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank"><?php _e("WP eStore Plugin", "wordpress-simple-paypal-shopping-cart"); ?></a>
    </p>
    <p>
        <?php _e('You can also try our free', "wordpress-simple-paypal-shopping-cart") ?> <a href="https://wordpress.org/plugins/wp-express-checkout/" target="_blank">WP Express Checkout</a> <?php _e('or', "wordpress-simple-paypal-shopping-cart") ?> <a href="https://wordpress.org/plugins/stripe-payments/" target="_blank">Accept Stripe Payments</a> <?php _e('plugins to sell your products.', "wordpress-simple-paypal-shopping-cart") ?>
    </p>
    </div>
    <?php
}

function wpsc_settings_output_sandbox_mode_msg(){
    if( get_option ('wp_shopping_cart_enable_sandbox') ) {
        // Sandbox mode is enabled.
        echo '<p class="wpsc-blue-box">' . __('Note: the sandbox/test mode is enabled. You can turn off the sandbox mode (from the general settings menu) when you want to do live transactions.', 'wordpress-simple-paypal-shopping-cart') . '</p>';
    }    
}

/**
 * Converts price amount to cents.
 * 
 * It uses round and then number_format to ensure that there is always 2 decimal places (even if the trailing zero is dropped by the round function).
 * This is to avoid issues with Stripe's zero decimal currencies.
 *
 * @param int|float $amount The price amount to convert.
 * 
 * @return int Price in cents
 */
function wpsc_amount_in_cents($amount) {
    $amountFormatted = round( $amount, 2 );
	$amountFormatted = number_format( $amountFormatted, 2 );

    $centsAmount = $amountFormatted;

    //if amount is not decimal. multiply by 100
    if (strpos($amountFormatted, '.') === false) {
        $amountUnformatted = str_replace(['.', ','], '', $amountFormatted);
        $centsAmount = intval($amountUnformatted) * 100;
    }
    else{
        //if amount is decimal, remove the period and comma.
        $amountUnformatted = str_replace(['.', ','], '', $amountFormatted);    
        $centsAmount = intval($amountUnformatted);    
    }
    return $centsAmount; 
}


function wpsc_is_zero_cents_currency($payment_currency){
    $zero_cents_currencies= array( 'JPY', 'MGA', 'VND', 'KRW' ) ;
    return in_array( $payment_currency, $zero_cents_currencies ) ;
}

function wpsc_load_stripe_lib() {
    //this function loads Stripe PHP SDK and ensures only once instance is loaded
    if ( ! class_exists( '\Stripe\Stripe' ) ) {
        require_once WP_CART_PATH . 'lib/stripe-gateway/init.php';        
    }
}