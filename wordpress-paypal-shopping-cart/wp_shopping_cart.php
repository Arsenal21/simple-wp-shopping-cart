<?php

/*
  Plugin Name: WP Simple Paypal Shopping cart
  Version: 4.4.7
  Plugin URI: https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768
  Author: Tips and Tricks HQ, Ruhul Amin, mra13
  Author URI: https://www.tipsandtricks-hq.com/
  Description: Simple WordPress Shopping Cart Plugin, very easy to use and great for selling products and services from your blog!
  Text Domain: wordpress-simple-paypal-shopping-cart
  Domain Path: /languages/
 */

//Slug - wspsc

if ( ! defined( 'ABSPATH' ) ) {//Exit if accessed directly
    exit;
}

define( 'WP_CART_VERSION', '4.4.7' );
define( 'WP_CART_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'WP_CART_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_CART_URL', plugins_url( '', __FILE__ ) );
define( 'WP_CART_SITE_URL', site_url() );
define( 'WP_CART_LIVE_PAYPAL_URL', 'https://www.paypal.com/cgi-bin/webscr' );
define( 'WP_CART_SANDBOX_PAYPAL_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr' );
define( 'WP_CART_CURRENCY_SYMBOL', get_option( 'cart_currency_symbol' ) );
if ( ! defined( 'WP_CART_MANAGEMENT_PERMISSION' ) ) {//This will allow the user to define custom capability for this constant in wp-config file
    define( 'WP_CART_MANAGEMENT_PERMISSION', 'manage_options' );
}
define( 'WP_CART_MAIN_MENU_SLUG', 'wspsc-menu-main' );

//Loading language files
//Set up localisation. First loaded overrides strings present in later loaded file
$locale = apply_filters( 'plugin_locale', get_locale(), 'wordpress-simple-paypal-shopping-cart' );
load_textdomain( 'wordpress-simple-paypal-shopping-cart', WP_LANG_DIR . "/wordpress-simple-paypal-shopping-cart-$locale.mo" );
load_plugin_textdomain( 'wordpress-simple-paypal-shopping-cart', false, WP_CART_FOLDER . '/languages' );

//PHP session
if ( ! is_admin() || wp_doing_ajax() ) {
    //Only use session for front-end and ajax.
    if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
	if ( session_status() == PHP_SESSION_NONE ) {
	    session_start();
	}
    } else {
	if ( session_id() == '' ) {
	    session_start();
	}
    }
}

include_once('wp_shopping_cart_utility_functions.php');
include_once('wp_shopping_cart_shortcodes.php');
include_once('wp_shopping_cart_misc_functions.php');
include_once('wp_shopping_cart_orders.php');
include_once('class-coupon.php');
include_once('includes/wspsc-cart-functions.php');
include_once('includes/admin/wp_shopping_cart_menu_main.php');
include_once('includes/admin/wp_shopping_cart_tinymce.php');

function always_show_cart_handler( $atts ) {
    return print_wp_shopping_cart( $atts );
}

function show_wp_shopping_cart_handler( $atts ) {
    $output = "";
    if ( cart_not_empty() ) {
	$output = print_wp_shopping_cart( $atts );
    }
    return $output;
}

function shopping_cart_show( $content ) {
    if ( strpos( $content, "<!--show-wp-shopping-cart-->" ) !== FALSE ) {
	if ( cart_not_empty() ) {
	    $content	 = preg_replace( '/<p>\s*<!--(.*)-->\s*<\/p>/i', "<!--$1-->", $content );
	    $matchingText	 = '<!--show-wp-shopping-cart-->';
	    $replacementText = print_wp_shopping_cart();
	    $content	 = str_replace( $matchingText, $replacementText, $content );
	}
    }
    return $content;
}

// Reset cart option
if ( isset( $_REQUEST[ "reset_wp_cart" ] ) && ! empty( $_REQUEST[ "reset_wp_cart" ] ) ) {
    reset_wp_cart();
}

//Clear the cart if the customer landed on the thank you page (if this option is enabled)
if ( get_option( 'wp_shopping_cart_reset_after_redirection_to_return_page' ) ) {
    //TODO - remove this field altogether later. Cart will always be reset using query prameter on the thank you page.
    if ( get_option( 'cart_return_from_paypal_url' ) == cart_current_page_url() ) {
	reset_wp_cart();
    }
}

if ( wp_doing_ajax() ) {
    add_action( 'wp_ajax_wpspsc_process_pp_smart_checkout', 'wpspsc_process_pp_smart_checkout' );
    add_action( 'wp_ajax_nopriv_wpspsc_process_pp_smart_checkout', 'wpspsc_process_pp_smart_checkout' );
}

function wpspsc_process_pp_smart_checkout() {
    if ( isset( $_POST[ 'wpspsc_payment_data' ] ) ) {
	$data = $_POST[ 'wpspsc_payment_data' ];
    }
    if ( empty( $data ) ) {
	wp_send_json( array( 'success' => false, 'errMsg' => __( 'Empty payment data received.', "wordpress-simple-paypal-shopping-cart" ) ) );
    }

    include_once('paypal.php');

    $ipn_handler_instance = new paypal_ipn_handler();

    $ipn_data_success = $ipn_handler_instance->create_ipn_from_smart_checkout( $data );

    if ( $ipn_data_success !== true ) {
	//error occured during IPN array creation
	wp_send_json( array( 'success' => false, 'errMsg' => $ipn_data_success ) );
    }

    $debug_enabled	 = false;
    $debug		 = get_option( 'wp_shopping_cart_enable_debug' );
    if ( $debug ) {
	$debug_enabled = true;
    }

    if ( $debug_enabled ) {
	$ipn_handler_instance->ipn_log = true;
    }

    $res = $ipn_handler_instance->validate_ipn_smart_checkout();

    if ( $res !== true ) {
	wp_send_json( array( 'success' => false, 'errMsg' => $res ) );
    }

    $res = $ipn_handler_instance->validate_and_dispatch_product();

    if ( $res === true ) {
	wp_send_json( array( 'success' => true ) );
    } else {
	wp_send_json( array( 'success' => false, 'errMsg' => __( 'Error occured during payment processing. Check debug log for additional details.', "wordpress-simple-paypal-shopping-cart" ) ) );
    }
}

function reset_wp_cart() {
    if ( ! isset( $_SESSION[ 'simpleCart' ] ) ) {
	return;
    }
    $products = $_SESSION[ 'simpleCart' ];
    if ( ! is_array( $products ) ) {
	return;
    }
    foreach ( $products as $key => $item ) {
	unset( $products[ $key ] );
    }
    $_SESSION[ 'simpleCart' ] = $products;
    unset( $_SESSION[ 'simple_cart_id' ] );
    unset( $_SESSION[ 'wpspsc_cart_action_msg' ] );
    unset( $_SESSION[ 'wpspsc_discount_applied_once' ] );
    unset( $_SESSION[ 'wpspsc_applied_coupon_code' ] );
}

function wpspc_cart_actions_handler() {
    unset( $_SESSION[ 'wpspsc_cart_action_msg' ] );

    if ( isset( $_POST[ 'addcart' ] ) ) {//Add to cart action
	//Some sites using caching need to be able to disable nonce on the add cart button. Otherwise 48 hour old cached pages will have stale nonce value and fail for valid users.
	if ( get_option( 'wspsc_disable_nonce_add_cart' ) ) {
	    //This site has disabled the nonce check for add cart button.
	    //Do not check nonce for this site since the site admin has indicated that he does not want to check nonce for add cart button.
	} else {
	    //Check nonce
	    $nonce = $_REQUEST[ '_wpnonce' ];
	    if ( ! wp_verify_nonce( $nonce, 'wspsc_addcart' ) ) {
		wp_die( 'Error! Nonce Security Check Failed!' );
	    }
	}

	setcookie( "cart_in_use", "true", time() + 21600, "/", COOKIE_DOMAIN );  //useful to not serve cached page when using with a caching plugin
	if ( function_exists( 'wp_cache_serve_cache_file' ) ) {//WP Super cache workaround
	    setcookie( "comment_author_", "wp_cart", time() + 21600, "/", COOKIE_DOMAIN );
	}

	//Sanitize post data
	$post_wspsc_product	 = isset( $_POST[ 'wspsc_product' ] ) ? stripslashes( sanitize_text_field( $_POST[ 'wspsc_product' ] ) ) : '';
	$post_item_number	 = isset( $_POST[ 'item_number' ] ) ? sanitize_text_field( $_POST[ 'item_number' ] ) : '';
	$post_cart_link		 = isset( $_POST[ 'cartLink' ] ) ? esc_url_raw( sanitize_text_field( urldecode( $_POST[ 'cartLink' ] ) ) ) : '';
	$post_stamp_pdf		 = isset( $_POST[ 'stamp_pdf' ] ) ? sanitize_text_field( $_POST[ 'stamp_pdf' ] ) : '';
	$post_encoded_file_val	 = isset( $_POST[ 'file_url' ] ) ? sanitize_text_field( $_POST[ 'file_url' ] ) : '';
	$post_thumbnail		 = isset( $_POST[ 'thumbnail' ] ) ? esc_url_raw( sanitize_text_field( $_POST[ 'thumbnail' ] ) ) : '';
	//Sanitize and validate price
	if ( isset( $_POST[ 'price' ] ) ) {
	    $price		 = sanitize_text_field( $_POST[ 'price' ] );
	    $hash_once_p	 = sanitize_text_field( $_POST[ 'hash_one' ] );
	    $p_key		 = get_option( 'wspsc_private_key_one' );
	    $hash_one_cm	 = md5( $p_key . '|' . $price );
	    if ( $hash_once_p != $hash_one_cm ) {//Security check failed. Price field has been tampered. Fail validation.
		wp_die( 'Error! The price field may have been tampered. Security check failed.' );
	    }
	    $price = str_replace( WP_CART_CURRENCY_SYMBOL, "", $price ); //Remove any currency symbol from the price.
	    //Check that the price field is numeric.
	    if ( ! is_numeric( $price ) ) {//Price validation failed
		wp_die( 'Error! The price validation failed. The value must be numeric.' );
	    }
	    //At this stage the price amt has already been sanitized and validated.
	} else {
	    wp_die( 'Error! Missing price value. The price must be set.' );
	}

	//Sanitize and validate shipping price
	if ( isset( $_POST[ 'shipping' ] ) ) {
	    $shipping	 = sanitize_text_field( $_POST[ 'shipping' ] );
	    $hash_two_val	 = sanitize_text_field( $_POST[ 'hash_two' ] );
	    $p_key		 = get_option( 'wspsc_private_key_one' );
	    $hash_two_cm	 = md5( $p_key . '|' . $shipping );
	    if ( $hash_two_val != $hash_two_cm ) {//Shipping validation failed
		wp_die( 'Error! The shipping price validation failed.' );
	    }

	    $shipping = str_replace( WP_CART_CURRENCY_SYMBOL, "", $shipping ); //Remove any currency symbol from the price.
	    //Check that the shipping price field is numeric.
	    if ( ! is_numeric( $shipping ) ) {//Shipping price validation failed
		wp_die( 'Error! The shipping price validation failed. The value must be numeric.' );
	    }
	    //At this stage the shipping price amt has already been sanitized and validated.            
	} else {
	    wp_die( 'Error! Missing shipping price value. The price must be set.' );
	}


	$count		 = 1;
	$products	 = array();
	if ( isset( $_SESSION[ 'simpleCart' ] ) ) {
	    $products = $_SESSION[ 'simpleCart' ];
	    if ( is_array( $products ) ) {
		foreach ( $products as $key => $item ) {
		    if ( $item[ 'name' ] == $post_wspsc_product ) {
			$count += $item[ 'quantity' ];
			$item[ 'quantity' ] ++;
			unset( $products[ $key ] );
			array_push( $products, $item );
		    }
		}
	    } else {
		$products = array();
	    }
	}

	if ( $count == 1 ) {
	    //This is the first quantity of this item.

	    $product = array( 'name' => $post_wspsc_product, 'price' => $price, 'price_orig' => $price, 'quantity' => $count, 'shipping' => $shipping, 'cartLink' => $post_cart_link, 'item_number' => $post_item_number );
	    if ( ! empty( $post_encoded_file_val ) ) {
		$product[ 'file_url' ] = $post_encoded_file_val;
	    }
	    if ( ! empty( $post_thumbnail ) ) {
		$product[ 'thumbnail' ] = $post_thumbnail;
	    }
	    $product[ 'stamp_pdf' ] = $post_stamp_pdf;

	    array_push( $products, $product );
	}

	sort( $products );
	$_SESSION[ 'simpleCart' ] = $products;

	wpspsc_reapply_discount_coupon_if_needed(); //Re-apply coupon to the cart if necessary

	if ( ! isset( $_SESSION[ 'simple_cart_id' ] ) && empty( $_SESSION[ 'simple_cart_id' ] ) ) {
	    wpspc_insert_new_record();
	} else {
	    //cart updating
	    if ( isset( $_SESSION[ 'simple_cart_id' ] ) && ! empty( $_SESSION[ 'simple_cart_id' ] ) ) {
		wpspc_update_cart_items_record();
	    } else {
		echo "<p>" . (__( "Error! Your session is out of sync. Please reset your session.", "wordpress-simple-paypal-shopping-cart" )) . "</p>";
	    }
	}


	if ( get_option( 'wp_shopping_cart_auto_redirect_to_checkout_page' ) ) {
	    $checkout_url = get_option( 'cart_checkout_page_url' );
	    if ( empty( $checkout_url ) ) {
		echo "<br /><strong>" . (__( "Shopping Cart Configuration Error! You must specify a value in the 'Checkout Page URL' field for the automatic redirection feature to work!", "wordpress-simple-paypal-shopping-cart" )) . "</strong><br />";
	    } else {
		$redirection_parameter = 'Location: ' . $checkout_url;
		header( $redirection_parameter );
		exit;
	    }
	}
    } else if ( isset( $_POST[ 'cquantity' ] ) ) {
	$nonce = $_REQUEST[ '_wpnonce' ];
	if ( ! wp_verify_nonce( $nonce, 'wspsc_cquantity' ) ) {
	    wp_die( 'Error! Nonce Security Check Failed!' );
	}
	$post_wspsc_product	 = isset( $_POST[ 'wspsc_product' ] ) ? stripslashes( sanitize_text_field( $_POST[ 'wspsc_product' ] ) ) : '';
	$post_quantity		 = isset( $_POST[ 'quantity' ] ) ? sanitize_text_field( $_POST[ 'quantity' ] ) : '';
	if ( ! is_numeric( $post_quantity ) ) {
	    wp_die( 'Error! The quantity value must be numeric.' );
	}
	$products = $_SESSION[ 'simpleCart' ];
	foreach ( $products as $key => $item ) {
	    if ( (stripslashes( $item[ 'name' ] ) == $post_wspsc_product) && $post_quantity ) {
		$item[ 'quantity' ] = $post_quantity;
		unset( $products[ $key ] );
		array_push( $products, $item );
	    } else if ( ($item[ 'name' ] == $post_wspsc_product) && ! $post_quantity ) {
		unset( $products[ $key ] );
	    }
	}
	sort( $products );
	$_SESSION[ 'simpleCart' ] = $products;

	wpspsc_reapply_discount_coupon_if_needed(); //Re-apply coupon to the cart if necessary

	if ( isset( $_SESSION[ 'simple_cart_id' ] ) && ! empty( $_SESSION[ 'simple_cart_id' ] ) ) {
	    wpspc_update_cart_items_record();
	}
    } else if ( isset( $_POST[ 'delcart' ] ) ) {
	$nonce = $_REQUEST[ '_wpnonce' ];
	if ( ! wp_verify_nonce( $nonce, 'wspsc_delcart' ) ) {
	    wp_die( 'Error! Nonce Security Check Failed!' );
	}
	$post_wspsc_product	 = isset( $_POST[ 'wspsc_product' ] ) ? stripslashes( sanitize_text_field( $_POST[ 'wspsc_product' ] ) ) : '';
	$products		 = $_SESSION[ 'simpleCart' ];
	foreach ( $products as $key => $item ) {
	    if ( $item[ 'name' ] == $post_wspsc_product )
		unset( $products[ $key ] );
	}
	$_SESSION[ 'simpleCart' ] = $products;

	wpspsc_reapply_discount_coupon_if_needed(); //Re-apply coupon to the cart if necessary

	if ( isset( $_SESSION[ 'simple_cart_id' ] ) && ! empty( $_SESSION[ 'simple_cart_id' ] ) ) {
	    wpspc_update_cart_items_record();
	}
	if ( count( $_SESSION[ 'simpleCart' ] ) < 1 ) {
	    reset_wp_cart();
	}
    } else if ( isset( $_POST[ 'wpspsc_coupon_code' ] ) ) {
	$nonce = $_REQUEST[ '_wpnonce' ];
	if ( ! wp_verify_nonce( $nonce, 'wspsc_coupon' ) ) {
	    wp_die( 'Error! Nonce Security Check Failed!' );
	}
	$coupon_code = isset( $_POST[ 'wpspsc_coupon_code' ] ) ? sanitize_text_field( $_POST[ 'wpspsc_coupon_code' ] ) : '';
	wpspsc_apply_cart_discount( $coupon_code );
	if ( isset( $_SESSION[ 'simple_cart_id' ] ) && ! empty( $_SESSION[ 'simple_cart_id' ] ) ) {
	    wpspc_update_cart_items_record();
	}
    }
}

function wp_cart_add_custom_field() {
    $_SESSION[ 'wp_cart_custom_values' ]	 = "";
    $custom_field_val			 = "";
    $name					 = 'wp_cart_id';
    $value					 = $_SESSION[ 'simple_cart_id' ];
    $custom_field_val			 = wpc_append_values_to_custom_field( $name, $value );

    $clientip = $_SERVER[ 'REMOTE_ADDR' ];
    if ( ! empty( $clientip ) ) {
	$name			 = 'ip';
	$value			 = $clientip;
	$custom_field_val	 = wpc_append_values_to_custom_field( $name, $value );
    }

    if ( function_exists( 'wp_aff_platform_install' ) ) {
	$name	 = 'ap_id';
	$value	 = '';
	if ( isset( $_SESSION[ 'ap_id' ] ) ) {
	    $value = $_SESSION[ 'ap_id' ];
	} else if ( isset( $_COOKIE[ 'ap_id' ] ) ) {
	    $value = $_COOKIE[ 'ap_id' ];
	}
	if ( ! empty( $value ) ) {
	    $custom_field_val = wpc_append_values_to_custom_field( $name, $value );
	}
    }

    if ( isset( $_SESSION[ 'wpspsc_applied_coupon_code' ] ) ) {
	$name			 = "coupon_code";
	$value			 = $_SESSION[ 'wpspsc_applied_coupon_code' ];
	$custom_field_val	 = wpc_append_values_to_custom_field( $name, $value );
    }

    $custom_field_val	 = apply_filters( 'wpspc_cart_custom_field_value', $custom_field_val );
    $custom_field_val	 = urlencode( $custom_field_val ); //URL encode the custom field value so nothing gets lost when it is passed around.
    $output			 = '<input type="hidden" name="custom" value="' . $custom_field_val . '" />';
    return $output;
}

function print_wp_cart_button_new( $content ) {
    $addcart = get_option( 'addToCartButtonName' );
    if ( ! $addcart || ($addcart == '') )
	$addcart = __( "Add to Cart", "wordpress-simple-paypal-shopping-cart" );

    $pattern = '#\[wp_cart:.+:price:.+:end]#';
    preg_match_all( $pattern, $content, $matches );

    foreach ( $matches[ 0 ] as $match ) {
	$var_output	 = '';
	$pos		 = strpos( $match, ":var1" );
	if ( $pos ) {
	    $match_tmp	 = $match;
	    // Variation control is used
	    $pos2		 = strpos( $match, ":var2" );
	    if ( $pos2 ) {
		$pattern	 = '#var2\[.*]:#';
		preg_match_all( $pattern, $match_tmp, $matches3 );
		$match3		 = $matches3[ 0 ][ 0 ];
		$match_tmp	 = str_replace( $match3, '', $match_tmp );

		$pattern = 'var2[';
		$m3	 = str_replace( $pattern, '', $match3 );
		$pattern = ']:';
		$m3	 = str_replace( $pattern, '', $m3 );
		$pieces3 = explode( '|', $m3 );

		$variation2_name = $pieces3[ 0 ];
		$var_output	 .= $variation2_name . " : ";
		$var_output	 .= '<select name="variation2" onchange="ReadForm (this.form, false);">';
		for ( $i = 1; $i < sizeof( $pieces3 ); $i ++ ) {
		    $var_output .= '<option value="' . $pieces3[ $i ] . '">' . $pieces3[ $i ] . '</option>';
		}
		$var_output .= '</select><br />';
	    }

	    $pattern = '#var1\[.*]:#';
	    preg_match_all( $pattern, $match_tmp, $matches2 );
	    $match2	 = $matches2[ 0 ][ 0 ];

	    $match_tmp = str_replace( $match2, '', $match_tmp );

	    $pattern = 'var1[';
	    $m2	 = str_replace( $pattern, '', $match2 );
	    $pattern = ']:';
	    $m2	 = str_replace( $pattern, '', $m2 );
	    $pieces2 = explode( '|', $m2 );

	    $variation_name	 = $pieces2[ 0 ];
	    $var_output	 .= $variation_name . " : ";
	    $var_output	 .= '<select name="variation1" onchange="ReadForm (this.form, false);">';
	    for ( $i = 1; $i < sizeof( $pieces2 ); $i ++ ) {
		$var_output .= '<option value="' . $pieces2[ $i ] . '">' . $pieces2[ $i ] . '</option>';
	    }
	    $var_output .= '</select><br />';
	}

	$pattern = '[wp_cart:';
	$m	 = str_replace( $pattern, '', $match );

	$pattern = 'price:';
	$m	 = str_replace( $pattern, '', $m );
	$pattern = 'shipping:';
	$m	 = str_replace( $pattern, '', $m );
	$pattern = ':end]';
	$m	 = str_replace( $pattern, '', $m );

	$pieces = explode( ':', $m );

	$replacement	 = '<div class="wp_cart_button_wrapper">';
	$replacement	 .= '<form method="post" class="wp-cart-button-form" action="" style="display:inline" onsubmit="return ReadForm(this, true);" ' . apply_filters( "wspsc_add_cart_button_form_attr", "" ) . '>';
	$replacement	 .= wp_nonce_field( 'wspsc_addcart', '_wpnonce', true, false ); //nonce value

	if ( ! empty( $var_output ) ) {
	    $replacement .= $var_output;
	}

	if ( preg_match( "/http/", $addcart ) ) {
	    //Use the image as the add to cart button
	    $replacement .= '<input type="image" src="' . $addcart . '" class="wp_cart_button" alt="' . (__( "Add to Cart", "wordpress-simple-paypal-shopping-cart" )) . '"/>';
	} else {
	    //Plain text add to cart button
	    $replacement .= '<input type="submit" class="wspsc_add_cart_submit" name="wspsc_add_cart_submit" value="' . $addcart . '" />';
	}

	$replacement	 .= '<input type="hidden" name="wspsc_product" value="' . $pieces[ '0' ] . '" /><input type="hidden" name="price" value="' . $pieces[ '1' ] . '" />';
	$replacement	 .= '<input type="hidden" name="product_tmp" value="' . $pieces[ '0' ] . '" />';
	if ( sizeof( $pieces ) > 2 ) {
	    //We likely have shipping
	    if ( ! is_numeric( $pieces[ '2' ] ) ) {//Shipping parameter has non-numeric value. Discard it and set it to 0.
		$pieces[ '2' ] = 0;
	    }
	    $replacement .= '<input type="hidden" name="shipping" value="' . $pieces[ '2' ] . '" />';
	} else {
	    //Set shipping to 0 by default (when no shipping is specified in the shortcode)
	    $pieces[ '2' ]	 = 0;
	    $replacement	 .= '<input type="hidden" name="shipping" value="' . $pieces[ '2' ] . '" />';
	}

	$p_key = get_option( 'wspsc_private_key_one' );
	if ( empty( $p_key ) ) {
	    $p_key = uniqid( '', true );
	    update_option( 'wspsc_private_key_one', $p_key );
	}
	$hash_one	 = md5( $p_key . '|' . $pieces[ '1' ] ); //Price hash
	$replacement	 .= '<input type="hidden" name="hash_one" value="' . $hash_one . '" />';

	$hash_two	 = md5( $p_key . '|' . $pieces[ '2' ] ); //Shipping hash
	$replacement	 .= '<input type="hidden" name="hash_two" value="' . $hash_two . '" />';

	$replacement	 .= '<input type="hidden" name="cartLink" value="' . esc_url( cart_current_page_url() ) . '" />';
	$replacement	 .= '<input type="hidden" name="addcart" value="1" /></form>';
	$replacement	 .= '</div>';
	$content	 = str_replace( $match, $replacement, $content );
    }
    return $content;
}

function wp_cart_add_read_form_javascript() {
    $debug_marker = "<!-- WP Simple Shopping Cart plugin v" . WP_CART_VERSION . " - https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768/ -->";
    echo "\n${debug_marker}\n";
    echo '
	<script type="text/javascript">
	<!--
	//
	function ReadForm (obj1, tst) 
	{ 
	    // Read the user form
	    var i,j,pos;
	    val_total="";val_combo="";		
	
	    for (i=0; i<obj1.length; i++) 
	    {     
	        // run entire form
	        obj = obj1.elements[i];           // a form element
	
	        if (obj.type == "select-one") 
	        {   // just selects
	            if (obj.name == "quantity" ||
	                obj.name == "amount") continue;
		        pos = obj.selectedIndex;        // which option selected
		        val = obj.options[pos].value;   // selected value
		        val_combo = val_combo + " (" + val + ")";
	        }
	    }
		// Now summarize everything we have processed above
		val_total = obj1.product_tmp.value + val_combo;
		obj1.wspsc_product.value = val_total;
	}
	//-->
	</script>';
}

function print_wp_cart_button_for_product( $name, $price, $shipping = 0, $var1 = '', $var2 = '', $var3 = '', $atts = array() ) {
    $addcart = get_option( 'addToCartButtonName' );
    if ( ! $addcart || ($addcart == '') ) {
	$addcart = __( "Add to Cart", "wordpress-simple-paypal-shopping-cart" );
    }

    $var_output = "";
    if ( ! empty( $var1 ) ) {
	$var1_pieces	 = explode( '|', $var1 );
	$variation1_name = $var1_pieces[ 0 ];
	$var_output	 .= '<span class="wp_cart_variation_name">' . $variation1_name . ' : </span>';
	$var_output	 .= '<select name="variation1" class="wp_cart_variation1_select" onchange="ReadForm (this.form, false);">';
	for ( $i = 1; $i < sizeof( $var1_pieces ); $i ++ ) {
	    $var_output .= '<option value="' . $var1_pieces[ $i ] . '">' . $var1_pieces[ $i ] . '</option>';
	}
	$var_output .= '</select><br />';
    }
    if ( ! empty( $var2 ) ) {
	$var2_pieces	 = explode( '|', $var2 );
	$variation2_name = $var2_pieces[ 0 ];
	$var_output	 .= '<span class="wp_cart_variation_name">' . $variation2_name . ' : </span>';
	$var_output	 .= '<select name="variation2" class="wp_cart_variation2_select" onchange="ReadForm (this.form, false);">';
	for ( $i = 1; $i < sizeof( $var2_pieces ); $i ++ ) {
	    $var_output .= '<option value="' . $var2_pieces[ $i ] . '">' . $var2_pieces[ $i ] . '</option>';
	}
	$var_output .= '</select><br />';
    }
    if ( ! empty( $var3 ) ) {
	$var3_pieces	 = explode( '|', $var3 );
	$variation3_name = $var3_pieces[ 0 ];
	$var_output	 .= '<span class="wp_cart_variation_name">' . $variation3_name . ' : </span>';
	$var_output	 .= '<select name="variation3" class="wp_cart_variation3_select" onchange="ReadForm (this.form, false);">';
	for ( $i = 1; $i < sizeof( $var3_pieces ); $i ++ ) {
	    $var_output .= '<option value="' . $var3_pieces[ $i ] . '">' . $var3_pieces[ $i ] . '</option>';
	}
	$var_output .= '</select><br />';
    }

    $replacement	 = '<div class="wp_cart_button_wrapper">';
    $replacement	 .= '<form method="post" class="wp-cart-button-form" action="" style="display:inline" onsubmit="return ReadForm(this, true);" ' . apply_filters( "wspsc_add_cart_button_form_attr", "" ) . '>';
    $replacement	 .= wp_nonce_field( 'wspsc_addcart', '_wpnonce', true, false );
    if ( ! empty( $var_output ) ) {//Show variation
	$replacement .= '<div class="wp_cart_variation_section">' . $var_output . '</div>';
    }

    if ( isset( $atts[ 'button_image' ] ) && ! empty( $atts[ 'button_image' ] ) ) {
	//Use the custom button image specified in the shortcode
	$replacement .= '<input type="image" src="' . $atts[ 'button_image' ] . '" class="wp_cart_button" alt="' . (__( "Add to Cart", "wordpress-simple-paypal-shopping-cart" )) . '"/>';
    } else if ( isset( $atts[ 'button_text' ] ) && ! empty( $atts[ 'button_text' ] ) ) {
	//Use the custom button text specified in the shortcode        
	$replacement .= '<input type="submit" class="wspsc_add_cart_submit" name="wspsc_add_cart_submit" value="' . apply_filters( 'wspsc_add_cart_submit_button_value', $atts[ 'button_text' ], $price ) . '" />';
    } else {
	//Use the button text or image value from the settings
	if ( preg_match( "/http:/", $addcart ) || preg_match( "/https:/", $addcart ) ) {
	    //Use the image as the add to cart button
	    $replacement .= '<input type="image" src="' . $addcart . '" class="wp_cart_button" alt="' . (__( "Add to Cart", "wordpress-simple-paypal-shopping-cart" )) . '"/>';
	} else {
	    //Use plain text add to cart button
	    $replacement .= '<input type="submit" class="wspsc_add_cart_submit" name="wspsc_add_cart_submit" value="' . apply_filters( 'wspsc_add_cart_submit_button_value', $addcart, $price ) . '" />';
	}
    }

    $replacement	 .= '<input type="hidden" name="wspsc_product" value="' . $name . '" />';
    $replacement	 .= '<input type="hidden" name="price" value="' . $price . '" />';
    $replacement	 .= '<input type="hidden" name="shipping" value="' . $shipping . '" />';
    $replacement	 .= '<input type="hidden" name="addcart" value="1" />';
    $replacement	 .= '<input type="hidden" name="cartLink" value="' . esc_url( cart_current_page_url() ) . '" />';
    $replacement	 .= '<input type="hidden" name="product_tmp" value="' . $name . '" />';
    isset( $atts[ 'item_number' ] ) ? $item_num	 = $atts[ 'item_number' ] : $item_num	 = '';
    $replacement	 .= '<input type="hidden" name="item_number" value="' . $item_num . '" />';

    if ( isset( $atts[ 'file_url' ] ) ) {
	$file_url	 = $atts[ 'file_url' ];
	$file_url	 = base64_encode( $file_url );
	$replacement	 .= '<input type="hidden" name="file_url" value="' . $file_url . '" />';
    }
    if ( isset( $atts[ 'thumbnail' ] ) ) {
	$replacement .= '<input type="hidden" name="thumbnail" value="' . $atts[ 'thumbnail' ] . '" />';
    }
    if ( isset( $atts[ 'stamp_pdf' ] ) ) {
	$replacement .= '<input type="hidden" name="stamp_pdf" value="' . $atts[ 'stamp_pdf' ] . '" />';
    }

    $p_key = get_option( 'wspsc_private_key_one' );
    if ( empty( $p_key ) ) {
	$p_key = uniqid( '', true );
	update_option( 'wspsc_private_key_one', $p_key );
    }
    $hash_one	 = md5( $p_key . '|' . $price );
    $replacement	 .= '<input type="hidden" name="hash_one" value="' . $hash_one . '" />';

    $hash_two	 = md5( $p_key . '|' . $shipping );
    $replacement	 .= '<input type="hidden" name="hash_two" value="' . $hash_two . '" />';

    $replacement	 .= '</form>';
    $replacement	 .= '</div>';
    return $replacement;
}

function cart_not_empty() {
    $count = 0;
    if ( isset( $_SESSION[ 'simpleCart' ] ) && is_array( $_SESSION[ 'simpleCart' ] ) ) {
	foreach ( $_SESSION[ 'simpleCart' ] as $item )
	    $count ++;
	return $count;
    } else
	return 0;
}

function print_payment_currency( $price, $symbol, $decimal = '.' ) {
    $formatted_price = '';
    $formatted_price = apply_filters( 'wspsc_print_formatted_price', $formatted_price, $price, $symbol );
    if ( ! empty( $formatted_price ) ) {
	return $formatted_price;
    }
    $formatted_price = $symbol . number_format( $price, 2, $decimal, ',' );
    return $formatted_price;
}

function cart_current_page_url() {
    $pageURL = 'http';
    if ( ! isset( $_SERVER[ "HTTPS" ] ) ) {
	$_SERVER[ "HTTPS" ] = "";
    }
    if ( ! isset( $_SERVER[ "SERVER_PORT" ] ) ) {
	$_SERVER[ "SERVER_PORT" ] = "";
    }

    if ( $_SERVER[ "HTTPS" ] == "on" ) {
	$pageURL .= "s";
    }
    $pageURL .= "://";
    if ( $_SERVER[ "SERVER_PORT" ] != "80" ) {
	$pageURL .= $_SERVER[ "SERVER_NAME" ] . ":" . $_SERVER[ "SERVER_PORT" ] . $_SERVER[ "REQUEST_URI" ];
    } else {
	$pageURL .= $_SERVER[ "SERVER_NAME" ] . $_SERVER[ "REQUEST_URI" ];
    }
    return $pageURL;
}

function simple_cart_total() {
    $grand_total = 0;
    foreach ( (array) $_SESSION[ 'simpleCart' ] as $item ) {
	$total			 += $item[ 'price' ] * $item[ 'quantity' ];
	$item_total_shipping	 += $item[ 'shipping' ] * $item[ 'quantity' ];
    }
    $grand_total = $total + $item_total_shipping;
    return wpspsc_number_format_price( $grand_total );
}

function wp_paypal_shopping_cart_load_widgets() {
    register_widget( 'WP_PayPal_Cart_Widget' );
}

class WP_PayPal_Cart_Widget extends WP_Widget {

    function __construct() {
	parent::__construct( 'wp_paypal_shopping_cart_widgets', 'WP Paypal Shopping Cart', array( 'description' => 'WP Paypal Shopping Cart Widget' ) );
    }

    function form( $instance ) {
	// outputs the options form on admin
    }

    function update( $new_instance, $old_instance ) {
	// processes widget options to be saved
    }

    function widget( $args, $instance ) {
	// outputs the content of the widget
	extract( $args );

	$cart_title	 = get_option( 'wp_cart_title' );
	if ( empty( $cart_title ) )
	    $cart_title	 = __( "Shopping Cart", "wordpress-simple-paypal-shopping-cart" );

	echo $before_widget;
	echo $before_title . $cart_title . $after_title;
	echo print_wp_shopping_cart();
	echo $after_widget;
    }

}

function wspsc_admin_side_enqueue_scripts() {
    if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'wspsc-discounts' ) { //simple paypal shopping cart discount page
	wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css' );
	wp_register_script( 'wpspsc-admin', WP_CART_URL . '/lib/wpspsc_admin_side.js', array( 'jquery', 'jquery-ui-datepicker' ) );
	wp_enqueue_script( 'wpspsc-admin' );
    }
}

function wspsc_admin_side_styles() {
    wp_enqueue_style( 'wspsc-admin-style', WP_CART_URL . '/assets/wspsc-admin-styles.css', array(), WP_CART_VERSION );
}

function wspsc_front_side_enqueue_scripts() {
    wp_enqueue_style( 'wspsc-style', WP_CART_URL . '/wp_shopping_cart_style.css', array(), WP_CART_VERSION );
}

function wpspc_plugin_install() {
    wpspc_run_activation();
}

register_activation_hook( __FILE__, 'wpspc_plugin_install' );

// Add the settings link
function wp_simple_cart_add_settings_link( $links, $file ) {
    if ( $file == plugin_basename( __FILE__ ) ) {
	$settings_link = '<a href="admin.php?page=wspsc-menu-main">' . (__( "Settings", "wordpress-simple-paypal-shopping-cart" )) . '</a>';
	array_unshift( $links, $settings_link );
    }
    return $links;
}

add_filter( 'plugin_action_links', 'wp_simple_cart_add_settings_link', 10, 2 );

add_action( 'widgets_init', 'wp_paypal_shopping_cart_load_widgets' );

add_action( 'init', 'wp_cart_init_handler' );
add_action( 'admin_init', 'wp_cart_admin_init_handler' );

add_filter( 'the_content', 'print_wp_cart_button_new', 11 );
add_filter( 'the_content', 'shopping_cart_show' );

if ( ! is_admin() ) {
    add_filter( 'widget_text', 'do_shortcode' );
}

add_action( 'wp_head', 'wp_cart_add_read_form_javascript' );
add_action( 'wp_enqueue_scripts', 'wspsc_front_side_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'wspsc_admin_side_enqueue_scripts' );
add_action( 'admin_print_styles', 'wspsc_admin_side_styles' );
