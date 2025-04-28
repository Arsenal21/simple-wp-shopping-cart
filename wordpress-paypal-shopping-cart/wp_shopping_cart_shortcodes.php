<?php

function wpsc_register_shortcodes(){
    if( !is_admin() ){
        //Register the shortcodes on the front-end only.
        add_shortcode('wpsc_show_shopping_cart', 'wpsc_show_wp_shopping_cart_handler' );
        add_shortcode('show_wp_shopping_cart', 'wpsc_show_wp_shopping_cart_handler' );

        add_shortcode('wpsc_always_show_shopping_cart', 'wpsc_always_show_cart_handler' );
        add_shortcode('always_show_wp_shopping_cart', 'wpsc_always_show_cart_handler' );

        add_shortcode('wpsc_add_to_cart_button', 'wpsc_cart_button_handler' );
        add_shortcode('wp_cart_button', 'wpsc_cart_button_handler' );

        add_shortcode('wpsc_display_product', 'wpsc_cart_display_product_handler' );
        add_shortcode('wp_cart_display_product', 'wpsc_cart_display_product_handler' );

        add_shortcode('wpsc_compact_cart', 'wpsc_compact_cart_handler');
        add_shortcode('wp_compact_cart', 'wpsc_compact_cart_handler');

        add_shortcode('wpsc_compact_cart2', 'wpsc_compact_cart2_handler');
        add_shortcode('wp_compact_cart2', 'wpsc_compact_cart2_handler');

        add_shortcode('wpsc_thank_you', 'wpsc_thank_you_sc_handler');

        //Do shortcode in text widgets.
        add_filter( 'widget_text', 'do_shortcode' );
    }
}

function wpsc_cart_button_handler($atts){
	extract(shortcode_atts(array(
		'name' => '',
        'item_number' =>'',
		'price' => '',
		'shipping' => '0',
		'var1' => '',
		'var2' => '',
		'var3' => '',
        'thumbnail' => '',
        'button_text' => '',
        'button_image' => '',
        'file_url' => '',
        'digital' => '',
        'stamp_pdf' => '',
	), $atts));

    // Check if the name is empty
	if(empty($name)){
        return '<div style="color:red;">'.(__("Error! You must specify a product name in the shortcode.", "wordpress-simple-paypal-shopping-cart")).'</div>';
	}

    // The 'name' parameter value coming from the block inserter is already htmlentitied. So we need to decode it (otherwise it may cause issues in the hashing process).
	$name = html_entity_decode($name); 
	if( wpsc_contains_special_char($name) ){
		return '<div style="color:red;">'.(__("Error! Special characters like [, ], <, > are not supported in the product name.", "wordpress-simple-paypal-shopping-cart")).'</div>';
	}

    // Check if the price is empty
	if(empty($price)){
            return '<div style="color:red;">'.(__("Error! You must specify a price for your product in the shortcode.", "wordpress-simple-paypal-shopping-cart")).'</div>';
	}
    $price = wpsc_strip_char_from_price_amount($price);
    $shipping = wpsc_strip_char_from_price_amount($shipping);

	return print_wp_cart_button_for_product($name, $price, $shipping, $var1, $var2, $var3, $atts);
}

function wpsc_cart_display_product_handler($atts)
{
    extract(shortcode_atts(array(
        'name' => '',
        'item_number' =>'',
        'price' => '',
        'shipping' => '0',
        'var1' => '',
        'var2' => '',
        'var3' => '',
        'thumbnail' => '',
        'thumb_target' => '',
        'thumb_alt' => '',
        'description' => '',
        'button_text' => '',
        'button_image' => '',
        'file_url' => '',
        'digital' => '',
        'stamp_pdf' => '',
    ), $atts));

    // Check if the name is empty
    if(empty($name)){
        return '<div style="color:red;">'.(__("Error! You must specify a product name in the shortcode.", "wordpress-simple-paypal-shopping-cart")).'</div>';
    }

	// The 'name' parameter value coming from the block inserter is already htmlentitied. So we need to decode it (otherwise it may cause issues in the hashing process).
	$name = html_entity_decode($name);
	if( wpsc_contains_special_char($name) ){
		return '<div style="color:red;">'.(__("Error! Special characters like [, ], <, > are not supported in the product name.", "wordpress-simple-paypal-shopping-cart")).'</div>';
	}

    // Check if the price is empty
    if(empty($price)){
        return '<div style="color:red;">'.(__("Error! You must specify a price for your product in the shortcode.", "wordpress-simple-paypal-shopping-cart")).'</div>';
    }
    if(empty($thumbnail)){
        return '<div style="color:red;">'.(__("Error! You must specify a thumbnail image for your product in the shortcode.", "wordpress-simple-paypal-shopping-cart")).'</div>';
    }
    if(empty($thumb_alt)){
        //Use the product name as alt if the thumb_alt is not defined.
        $thumb_alt = $name;
    }

    $price = wpsc_strip_char_from_price_amount($price);
    $shipping = wpsc_strip_char_from_price_amount($shipping);
    $thumbnail_code = '<img src="'.esc_url_raw($thumbnail).'" alt="'.esc_attr( $thumb_alt ).'">';
    if(!empty($thumb_target) && preg_match("/http/", $thumb_target)){
        $thumbnail_code = '<a href="'.esc_url_raw($thumb_target).'"><img src="'.esc_url_raw($thumbnail).'" alt="'.esc_attr( $thumb_alt ).'"></a>';
    }

    $thumbnail_code = apply_filters('wspsc_product_box_thumbnail_code', $thumbnail_code, $atts);// TODO: Old hook. Need to remove this.
    $thumbnail_code = apply_filters('wpsc_product_box_thumbnail_code', $thumbnail_code, $atts);

    $currency_symbol = WP_CART_CURRENCY_SYMBOL;
    $formatted_price = print_payment_currency($price, $currency_symbol);
    $button_code = print_wp_cart_button_for_product($name, $price, $shipping, $var1, $var2, $var3, $atts);

	ob_start();
	?>
    <div class="wp_cart_product_display_box_wrapper">
	    <div class="wp_cart_product_display_box">
	        <div class="wp_cart_product_thumbnail">
	            <?php echo $thumbnail_code; ?>
	        </div>
	        <div class="wp_cart_product_display_bottom">
	            <div class="wp_cart_product_name">
	                <?php echo $name ?>
	            </div>
	            <div class="wp_cart_product_description">
		            <?php echo $description ?>
	            </div>
                <div class="wp_cart_product_price">
	                <?php echo $formatted_price ?>
	            </div>
                <div class="wp_cart_product_button">
	                <?php echo $button_code ?>
                </div>
            </div>
	    </div>
    </div>
	<?php
	$display_code = ob_get_clean();

    return $display_code;
}

function wpsc_compact_cart_handler($args)
{
    $wspsc_cart = WPSC_Cart::get_instance();
    $num_items = $wspsc_cart->get_total_cart_qty();
    $curSymbol = WP_CART_CURRENCY_SYMBOL;
    $checkout_url = get_option('cart_checkout_page_url');

    $output = "";
    $output .= '<div class="wpsps_compact_cart wpsps-cart-wrapper">';
    $output .= '<div class="wpsps_compact_cart_container">';
    if($num_items>0){
            $cart_total = $wspsc_cart->get_total_cart_sub_total();
            //Shows "Item" for 1.  Shows "Items" for 0 or more than 1.
            $item_message = ($num_items == 1) ? __("Item", "wordpress-simple-paypal-shopping-cart") : __("Items", "wordpress-simple-paypal-shopping-cart");
            $output .= $num_items . " " . $item_message;
            $output .= '<span class="wpsps_compact_cart_price"> '. print_payment_currency($cart_total,$curSymbol).'</span>';
            if(!empty($checkout_url)){
                $output .= '<a class="wpsps_compact_cart_co_btn" href="'.esc_url_raw($checkout_url).'">'.__("View Cart", "wordpress-simple-paypal-shopping-cart").'</a>';
            }
    }
    else{
            $cart_total = 0;
            $output .= __("Cart is empty", "wordpress-simple-paypal-shopping-cart");
            $output .= '<span class="wpsps_compact_cart_price"> '. print_payment_currency($cart_total,$curSymbol).'</span>';
    }
    $output .= '</div>';
    $output .= '</div>';
    return $output;
}

function wpsc_compact_cart2_handler($args)
{
    $wspsc_cart = WPSC_Cart::get_instance();
    $num_items = $wspsc_cart->get_total_cart_qty();
    $checkout_url = get_option('cart_checkout_page_url');

    $output = "";
    $output .= '<div class="wspsc_compact_cart2 wpsps-cart-wrapper">';
    $output .= '<div class="wspsc_compact_cart2_container">';

    $output .= '<div class="wspsc_compact_cart2_inside">';
    //Shows "Item" for 1.  Shows "Items" for 0 or more than 1.
    $item_message = ($num_items == 1) ? __("Item", "wordpress-simple-paypal-shopping-cart") : __("Items", "wordpress-simple-paypal-shopping-cart");

    if(!empty($checkout_url)){
        $output .= '<a class="wspsc_compact_cart2_view_cart_link" href="'.esc_url_raw($checkout_url).'">'.$num_items . " " . $item_message . '</a>';
    }else{
        $output .= $num_items . " " . $item_message;
    }
    $output .= '</div>';//end of .wspsc_compact_cart2_inside

    $output .= '</div>';//end of .wspsc_compact_cart2_container
    $output .= '</div>';
    return $output;
}

function wpsc_thank_you_sc_handler( $atts ) {
	$error_message = '';

    $thank_you_page_common_msg = '<p>' . __( 'This page displays the transaction result and the order summary after a customer completes a payment.', 'wordpress-simple-paypal-shopping-cart' ) . '</p>';
    $thank_you_page_common_msg .= '<p>' . __( 'When redirected here after a payment, customers will see their order details dynamically.', 'wordpress-simple-paypal-shopping-cart' ) . '</p>';
	if ( ! isset( $_GET['cart_id'] ) || empty( $_GET['cart_id'] ) ) {
		$error_message .= $thank_you_page_common_msg . '<p>' . __( 'Error! Cart ID value is missing in the URL.', 'wordpress-simple-paypal-shopping-cart' ) . '</p>';
		return $error_message;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wpsc_thank_you_nonce_action' ) ) {
		$error_message .= $thank_you_page_common_msg . '<p>' . __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wordpress-simple-paypal-shopping-cart' ) . '</p>';
		return $error_message;
	}

    $cart_id = $_GET['cart_id']; // here the $_GET['order_id'] is actually cart_id. So get the cpt ID form the cart_id.
	$order_id = wpsc_get_cart_cpt_id_by_cart_id($cart_id);
    if(empty($order_id)){
	    $error_message .= $thank_you_page_common_msg . '<p>' . __( 'Error! failed to retrieve order data.', 'wordpress-simple-paypal-shopping-cart' ) . '</p>';
	    return $error_message;
    }

	require_once( WP_CART_PATH . '/includes/classes/class.wpsc-thank-you.php' );

    ob_start();
	WPSC_Thank_You::wpsc_ty_output_order_summary( $order_id );
    return ob_get_clean();
}
