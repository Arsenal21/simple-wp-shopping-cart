<?php

/*
  Plugin Name: WP Simple Shopping Cart
  Version: 5.0.6
  Plugin URI: https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768
  Author: Tips and Tricks HQ, Ruhul Amin, mra13
  Author URI: https://www.tipsandtricks-hq.com/
  Description: Simple WordPress Shopping Cart Plugin, very easy to use and great for selling products and services from your blog!
  Text Domain: wordpress-simple-paypal-shopping-cart
  Domain Path: /languages/
 */

//Slug - wpsc. Use this slug/prefix for all the functions and classes.

if ( ! defined( 'ABSPATH' ) ) { //Exit if accessed directly
	exit;
}

define( 'WP_CART_VERSION', '5.0.6' );
define( 'WP_CART_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'WP_CART_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_CART_URL', plugins_url( '', __FILE__ ) );
define( 'WP_CART_SITE_URL', site_url() );
define( 'WP_CART_LIVE_PAYPAL_URL', 'https://www.paypal.com/cgi-bin/webscr' );
define( 'WP_CART_SANDBOX_PAYPAL_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr' );
define( 'WP_CART_CURRENCY_SYMBOL', get_option( 'cart_currency_symbol' ) );
if ( ! defined( 'WP_CART_MANAGEMENT_PERMISSION' ) ) { //This will allow the user to define custom capability for this constant in wp-config file
	define( 'WP_CART_MANAGEMENT_PERMISSION', 'manage_options' );
}
define( 'WP_CART_MAIN_MENU_SLUG', 'wspsc-menu-main' );
define( 'WP_CART_LOG_FILENAME', 'wspsc-debug-log' );

//Loading language files
//Set up localisation. First loaded overrides strings present in later loaded file
$locale = apply_filters( 'plugin_locale', get_locale(), 'wordpress-simple-paypal-shopping-cart' );
load_textdomain( 'wordpress-simple-paypal-shopping-cart', WP_LANG_DIR . "/wordpress-simple-paypal-shopping-cart-$locale.mo" );
load_plugin_textdomain( 'wordpress-simple-paypal-shopping-cart', false, WP_CART_FOLDER . '/languages' );

include_once( WP_CART_PATH . 'wp_shopping_cart_shortcodes.php' );
include_once( WP_CART_PATH . 'includes/wpsc-shortcodes-related.php' );
include_once( WP_CART_PATH . 'includes/wpsc-debug-logging-functions.php' );
include_once( WP_CART_PATH . 'includes/wpsc-utility-functions.php' );
include_once( WP_CART_PATH . 'includes/wpsc-misc-functions.php' );
include_once( WP_CART_PATH . 'includes/classes/class-wpsc-persistent-msg.php' );
include_once( WP_CART_PATH . 'includes/classes/class-coupon.php' );
include_once( WP_CART_PATH . 'includes/class-wspsc-cart.php' );
include_once( WP_CART_PATH . 'includes/class-wspsc-cart-item.php' );
include_once( WP_CART_PATH . 'includes/wpsc-misc-checkout-ajax-handler.php' );
include_once( WP_CART_PATH . 'includes/wpsc-paypal-ppcp-checkout-form-related.php' );
include_once( WP_CART_PATH . 'includes/wpsc-post-payment-related.php' );
include_once( WP_CART_PATH . 'includes/wpsc-cart-functions.php' );
include_once( WP_CART_PATH . 'includes/wpsc-deprecated-functions.php' );
include_once( WP_CART_PATH . 'includes/admin/wp_shopping_cart_orders.php' );
include_once( WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_main.php' );
include_once( WP_CART_PATH . 'includes/admin/wp_shopping_cart_tinymce.php' );
require_once( WP_CART_PATH . 'includes/admin/wp_shopping_cart_admin_utils.php');
include_once( WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_discounts.php' );
include_once( WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_tools.php' );
include_once( WP_CART_PATH . 'includes/classes/class.wspsc_blocks.php' );
include_once( WP_CART_PATH . 'lib/paypal/class-tthq-paypal-main.php' );

wspsc_check_and_start_session();

function always_show_cart_handler( $atts ) {
	return print_wp_shopping_cart( $atts );
}

function show_wp_shopping_cart_handler( $atts ) {
	$wspsc_cart = WSPSC_Cart::get_instance();
	$output = "";
	if ( $wspsc_cart->cart_not_empty() ) {
		$output = print_wp_shopping_cart( $atts );
	}
	return $output;
}

// Reset cart option
if ( isset( $_REQUEST["reset_wp_cart"] ) && ! empty( $_REQUEST["reset_wp_cart"] ) ) {
	$wspsc_cart = WSPSC_Cart::get_instance();

	//resets cart and cart_id after payment is made.
	$wspsc_cart->reset_cart_after_txn();

	// Redirect to the same url without the 'reset_wp_cart' query arg, by if there is a cart in that page, the query doesn't create problem using the cart.
	$after_cart_reset_redirect_url = remove_query_arg("reset_wp_cart");
	wpsc_redirect_to_url( $after_cart_reset_redirect_url );
}

//Clear the cart if the customer landed on the thank you page (if this option is enabled)
if ( get_option( 'wp_shopping_cart_reset_after_redirection_to_return_page' ) ) {
	//TODO - remove this field altogether later. Cart will always be reset using query prameter on the thank you page.
	if ( get_option( 'cart_return_from_paypal_url' ) == cart_current_page_url() ) {
		$wspsc_cart = WSPSC_Cart::get_instance();
		$wspsc_cart->reset_cart_after_txn();
	}
}

function process_allowed_shipping_countries($countries_str){
	if (empty($countries_str)) {
		return array();
	}

	$countries_arr = explode(',', $countries_str);
	$processed_countries_arr = array();

	foreach ($countries_arr as $country) {
		array_push($processed_countries_arr, strtoupper(trim($country)));
	}

	return $processed_countries_arr;
}

/**
 * @deprecated: Reset the WPSC cart and associated session variables.
 * This function is deprecated and should no longer be used. Please use the 'WSPSC_Cart' class and its 'reset_cart()'
 * method to reset the cart and associated variables.
 */
function reset_wp_cart() {
	$wspsc_cart = WSPSC_Cart::get_instance();
	$wspsc_cart->reset_cart();
}

function wpspc_cart_actions_handler() {
	$wspsc_cart = WSPSC_Cart::get_instance();
	$wspsc_cart->clear_cart_action_msg();

	if ( isset( $_POST['addcart'] ) ) { 
		//Add to cart action

		//create new cart object when add to cart button is clicked the first time
		if ( ! $wspsc_cart->get_cart_id() ) {
			$wspsc_cart->create_cart();
		}

		//Some sites using caching need to be able to disable nonce on the add cart button. Otherwise 48 hour old cached pages will have stale nonce value and fail for valid users.
		if ( get_option( 'wspsc_disable_nonce_add_cart' ) ) {
			//This site has disabled the nonce check for add cart button.
			//Do not check nonce for this site since the site admin has indicated that he does not want to check nonce for add cart button.
		} else {
			//Check nonce
			$nonce = $_REQUEST['_wpnonce'];
			if ( ! wp_verify_nonce( $nonce, 'wspsc_addcart' ) ) {
				wp_die( 'Error! Nonce Security Check Failed!' );
			}
		}

		setcookie( "cart_in_use", "true", time() + 21600, "/", COOKIE_DOMAIN ); //Useful to not serve cached page when using with a caching plugin
		setcookie( "wp_cart_in_use", "1", time() + 21600, "/", COOKIE_DOMAIN ); //Exclusion rule for Batcache caching (used by some hosting like wordpress.com)
		if ( function_exists( 'wp_cache_serve_cache_file' ) ) { //WP Super cache workaround
			setcookie( "comment_author_", "wp_cart", time() + 21600, "/", COOKIE_DOMAIN );
		}

		//Sanitize post data
		$post_wspsc_product = isset( $_POST['wspsc_product'] ) ? stripslashes( sanitize_text_field( $_POST['wspsc_product'] ) ) : '';
		$post_item_number = isset( $_POST['item_number'] ) ? sanitize_text_field( $_POST['item_number'] ) : '';
		$post_cart_link = isset( $_POST['cartLink'] ) ? esc_url_raw( sanitize_text_field( urldecode( $_POST['cartLink'] ) ) ) : '';
		$post_stamp_pdf = isset( $_POST['stamp_pdf'] ) ? sanitize_text_field( $_POST['stamp_pdf'] ) : '';
		$post_encoded_file_val = isset( $_POST['file_url'] ) ? sanitize_text_field( $_POST['file_url'] ) : '';
		$post_thumbnail = isset( $_POST['thumbnail'] ) ? esc_url_raw( sanitize_text_field( $_POST['thumbnail'] ) ) : '';
		$digital_flag = isset( $_POST['digital'] ) ? esc_url_raw( sanitize_text_field( $_POST['digital'] ) ) : '';

		//$post_wspsc_tmp_name = isset( $_POST[ 'product_tmp' ] ) ? stripslashes( sanitize_text_field( $_POST[ 'product_tmp' ] ) ) : '';
		//The product name is encoded and decoded to avoid any special characters in the product name creating hashing issues
		$post_wspsc_tmp_name_two = html_entity_decode($_POST['product_tmp_two']);
		$post_wspsc_tmp_name_two = stripslashes( sanitize_text_field( $post_wspsc_tmp_name_two ) );

		//Sanitize and validate price
		if ( isset( $_POST['price'] ) ) {
			$price = sanitize_text_field( $_POST['price'] );
			$hash_once_p = sanitize_text_field( $_POST['hash_one'] );
			$p_key = get_option( 'wspsc_private_key_one' );
			$hash_one_cm = md5( $p_key . '|' . $price . '|' . $post_wspsc_tmp_name_two );

			if ( get_option( 'wspsc_disable_price_check_add_cart' ) ) {
				//This site has disabled the price check for add cart button.
				//Do not perform the price check for this site since the site admin has indicated that he does not want to do it on this site.
			} else {
				if ( $hash_once_p != $hash_one_cm ) { //Security check failed. Price field has been tampered. Fail validation.
					$error_msg = '<p>Error! The price field may have been tampered. Security check failed.</p>';
					$error_msg .= '<p>If this site uses any caching, empty the cache then try again.</p>';
					$error_msg .= "<p>If the issue persists go to the settings menu of the plugin and select/tick the 'Disable Price Check for Add to Cart' checkbox and save it.</p>";
					wp_die( $error_msg );
				}
			}

			$price = str_replace( WP_CART_CURRENCY_SYMBOL, "", $price ); //Remove any currency symbol from the price.
			//Check that the price field is numeric.
			if ( ! is_numeric( $price ) ) { //Price validation failed
				wp_die( 'Error! The price validation failed. The value must be numeric.' );
			}
			//At this stage the price amt has already been sanitized and validated.
		} else {
			wp_die( 'Error! Missing price value. The price must be set.' );
		}

		//Sanitize and validate shipping price
		if ( isset( $_POST['shipping'] ) ) {
			$shipping = sanitize_text_field( $_POST['shipping'] );
			$hash_two_val = sanitize_text_field( $_POST['hash_two'] );
			$p_key = get_option( 'wspsc_private_key_one' );
			$hash_two_cm = md5( $p_key . '|' . $shipping . '|' . $post_wspsc_tmp_name_two );

			if ( get_option( 'wspsc_disable_price_check_add_cart' ) ) {
				//This site has disabled the price check for add cart button.
				//Do not perform the price check for this site since the site admin has indicated that he does not want to do it on this site.
			} else {
				if ( $hash_two_val != $hash_two_cm ) { //Shipping validation failed
					wp_die( 'Error! The shipping price validation failed.' );
				}
			}

			$shipping = str_replace( WP_CART_CURRENCY_SYMBOL, "", $shipping ); //Remove any currency symbol from the price.
			//Check that the shipping price field is numeric.
			if ( ! is_numeric( $shipping ) ) { //Shipping price validation failed
				wp_die( 'Error! The shipping price validation failed. The value must be numeric.' );
			}
			//At this stage the shipping price amt has already been sanitized and validated.
		} else {
			wp_die( 'Error! Missing shipping price value. The price must be set.' );
		}

		$is_do_not_show_qty_in_cart_enabled = get_option('wp_shopping_cart_do_not_show_qty_in_cart') == 'checked="checked"' ? true : false;
		$count = 1;
		$products = array();
		if ( $wspsc_cart->get_items() ) {
			$products = $wspsc_cart->get_items();
			if ( is_array( $products ) ) {
				foreach ( $products as $key => $item ) {
					if ( $item->get_name() == $post_wspsc_product ) {
                        $count += $item->get_quantity();
                        if ($is_do_not_show_qty_in_cart_enabled){
	                        $msg = __( "This item is already in your cart", "wordpress-simple-paypal-shopping-cart" );
                            $cart_id = $wspsc_cart->get_cart_id();

                            $persistent_msg = WPSC_Persistent_Msg::get_instance();
                            $persistent_msg->set_cart_id($cart_id);

                            // This one is to show below add to cart button.
	                        $persistent_msg->set_msg( $item->get_name(), $msg );

                            // This one is to show in the cart.
	                        $wspsc_cart->set_cart_action_msg($persistent_msg->get_formatted_msg( $msg ));
                        } else {
						    $item->set_quantity( $item->get_quantity() + 1 );
                            unset( $products[ $key ] );
                            array_push( $products, $item );
                        }
					}
				}
			} else {
				$products = array();
			}
		}

		if ( $count == 1 ) {
			//This is the first quantity of this item.
			$wspsc_cart_item = new WSPSC_Cart_Item();
			$wspsc_cart_item->set_name( $post_wspsc_product );
			$wspsc_cart_item->set_price( $price );
			$wspsc_cart_item->set_price_orig( $price );
			$wspsc_cart_item->set_quantity( $count );
			$wspsc_cart_item->set_shipping( $shipping );
			$wspsc_cart_item->set_cart_link( $post_cart_link );
			$wspsc_cart_item->set_item_number( $post_item_number );
			$wspsc_cart_item->set_digital_flag( $digital_flag );
			if ( ! empty( $post_encoded_file_val ) ) {
				$wspsc_cart_item->set_file_url( $post_encoded_file_val );
			}
			if ( ! empty( $post_thumbnail ) ) {
				$wspsc_cart_item->set_thumbnail( $post_thumbnail );
			}
			$product['stamp_pdf'] = $post_stamp_pdf;
			$wspsc_cart_item->set_stamp_pdf( $post_stamp_pdf );
			array_push( $products, $wspsc_cart_item );
		}

		sort( $products );

		if ( $wspsc_cart->get_cart_id() ) {
			$wspsc_cart->add_items( $products );
		}


		//if cart is not yet created, save the returned products
		//so it can be saved when cart is created
		$products_discount = wpspsc_reapply_discount_coupon_if_needed(); //Re-apply coupon to the cart if necessary	
		if ( is_array( $products_discount ) ) {
			$products = $products_discount;
		}
		if ( ! $wspsc_cart->get_cart_id() ) {
			$wspsc_cart->create_cart();
			$wspsc_cart->add_items( $products );
		} else {
			//cart updating
			if ( $wspsc_cart->get_cart_id() ) {
				$wspsc_cart->add_items( $products );
			} else {
				echo "<p>" . ( __( "Error! Your session is out of sync. Please reset your session.", "wordpress-simple-paypal-shopping-cart" ) ) . "</p>";
			}
		}


		if ( get_option( 'wp_shopping_cart_auto_redirect_to_checkout_page' ) ) {
			$checkout_url = sanitize_text_field(get_option( 'cart_checkout_page_url' ));
			if ( empty( $checkout_url ) ) {
				echo "<br /><strong>" . ( __( "Shopping Cart Configuration Error! You must specify a value in the 'Checkout Page URL' field for the automatic redirection feature to work!", "wordpress-simple-paypal-shopping-cart" ) ) . "</strong><br />";
			} else {
				wpsc_redirect_to_url( $checkout_url );
				exit;
			}
		}
		//Redirect to the anchor if the anchor option is enabled.
		wpsc_redirect_if_using_anchor();
	} else if ( isset( $_POST['cquantity'] ) ) {
		//Quantity change action
		$nonce = $_REQUEST['_wpnonce'];
		//Check nonce
		if ( ! wp_verify_nonce( $nonce, 'wspsc_cquantity' ) ) {
			wp_die( 'Error! Nonce Security Check Failed!' );
		}

		$post_wspsc_product = isset( $_POST['wspsc_product'] ) ? stripslashes( sanitize_text_field( $_POST['wspsc_product'] ) ) : '';
		$post_quantity = isset( $_POST['quantity'] ) ? sanitize_text_field( $_POST['quantity'] ) : '';
		if ( ! is_numeric( $post_quantity ) ) {
			wp_die( 'Error! The quantity value must be numeric.' );
		}
		$products = $wspsc_cart->get_items();
		foreach ( $products as $key => $item ) {
			if ( ( stripslashes( $item->get_name() ) == $post_wspsc_product ) && $post_quantity ) {
				$item->set_quantity( $post_quantity );
				unset( $products[ $key ] );
				array_push( $products, $item );
			} else if ( ( $item->get_name() == $post_wspsc_product ) && ! $post_quantity ) {
				unset( $products[ $key ] );
			}
		}
		sort( $products );
		$wspsc_cart->add_items( $products );

		wpspsc_reapply_discount_coupon_if_needed(); //Re-apply coupon to the cart if necessary

		if ( $wspsc_cart->get_cart_id() ) {
			$wspsc_cart->add_items( $products );
		}
		//Redirect to the anchor if the anchor option is enabled.
		wpsc_redirect_if_using_anchor();
	} else if ( isset( $_POST['delcart'] ) ) {
		//Remove item action
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'wspsc_delcart' ) ) {
			wp_die( 'Error! Nonce security check failed for remove item from the cart action!' );
		}
		$post_wspsc_product = isset( $_POST['wspsc_product'] ) ? stripslashes( sanitize_text_field( $_POST['wspsc_product'] ) ) : '';
		$products = $wspsc_cart->get_items();

		//if user clears cart & refresh the page and click on submit form again
		// there comes a php warning since $products is false in this case
		if ( $products ) {
			foreach ( $products as $key => $item ) {
				if ( $item->get_name() == $post_wspsc_product )
					unset( $products[ $key ] );
			}
			$wspsc_cart->add_items( $products );
		}

		//update the products in database after apply coupon
		wpspsc_reapply_discount_coupon_if_needed(); //Re-apply coupon to the cart if necessary

		if ( ! $wspsc_cart->get_items() ) {
			$wspsc_cart->reset_cart();
		}
		//Redirect to the anchor if the anchor option is enabled.
		wpsc_redirect_if_using_anchor();
	} else if ( isset( $_POST['wpsc_empty_cart'])) {
		//Empty cart action
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'wpsc_empty_cart' ) ) {
			wp_die( 'Error! Nonce security check failed for empty cart action!' );
		}

		//Empty the cart
		$wspsc_cart->reset_cart();
		//Redirect to the anchor if the anchor option is enabled.
		wpsc_redirect_if_using_anchor();
	} else if ( isset( $_POST['wpspsc_coupon_code'] ) ) {
		//Apply coupon action
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'wspsc_coupon' ) ) {
			wp_die( 'Error! Nonce Security Check Failed!' );
		}
		$coupon_code = isset( $_POST['wpspsc_coupon_code'] ) ? sanitize_text_field( $_POST['wpspsc_coupon_code'] ) : '';
		//Apply discount and update cart products in database
		wpspsc_apply_cart_discount( $coupon_code );
		//Redirect to the anchor if the anchor option is enabled. This redirect needs to be handled using JS.
		wpsc_js_redirect_if_using_anchor();
	} else if ( isset( $_POST['wpsc_shipping_region_submit'] ) ) {
		//Shipping region selected action
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'wpsc_shipping_region' ) ) {
			wp_die( 'Error! Nonce Security Check Failed!' );
		}

		$selected_shipping_region_str = isset( $_POST['wpsc_shipping_region'] ) ? sanitize_text_field( stripslashes($_POST['wpsc_shipping_region'] )) : '';

		$wspsc_cart = WSPSC_Cart::get_instance();

		// Check to make sure selected shipping region option is not tempered.
		if (!check_shipping_region_str($selected_shipping_region_str)) {
			$wspsc_cart->set_selected_shipping_region('-1');
		}else{
			$wspsc_cart->set_selected_shipping_region($selected_shipping_region_str);
		}
	
		// Recalculate to update all the price for new regional shipping cost.
		$wspsc_cart->calculate_cart_totals_and_postage();

        // Save the cart.
        $wspsc_cart->save_cart_to_postmeta();

		wpsc_js_redirect_if_using_anchor();
	}
}

function wpsc_redirect_if_using_anchor() {
	if ( get_option( 'shopping_cart_anchor' ) ) {
		$current_url = wpsc_get_current_page_url();
		//Remove trailing slash if there is one.
		$current_url = rtrim($current_url,"/");
		$anchor_url =  $current_url . "#wpsc_cart_anchor";
		wpsc_redirect_to_url( $anchor_url );
	}
}

function wpsc_js_redirect_if_using_anchor() {
	if ( get_option( 'shopping_cart_anchor' ) ) {
		add_action( 'wp_footer', function () {
			$anchor_name = "#wpsc_cart_anchor";
			?>
			<script>
				document.addEventListener("DOMContentLoaded", function () {
					window.location.href = "<?php echo $anchor_name; ?>";
				})
			</script>
		<?php
		} );
	}
}

function wpsc_redirect_to_url( $url, $delay = '0', $exit = '1' ) {
	if ( empty( $url ) ) {
		echo "<br /><strong>" . __( "Error! The URL value is empty. Please specify a correct URL value to redirect to!", "wordpress-simple-paypal-shopping-cart" ) . "</strong>";
		exit;
	}

	$url = apply_filters( 'wpsc_redirect_to_url', $url );

	if ( ! headers_sent() ) {
		header( 'Location: ' . $url );
	} else {
		echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '" />';
	}
	if ( $exit == '1' ) {
		exit;
	}
}

function wpsc_get_current_page_url() {
	$pageURL = 'http';

	if ( isset( $_SERVER['SCRIPT_URI'] ) && ! empty( $_SERVER['SCRIPT_URI'] ) ) {
		return $_SERVER['SCRIPT_URI'];
	}

	if ( isset( $_SERVER["HTTPS"] ) && ( $_SERVER["HTTPS"] == "on" ) ) {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	if ( isset( $_SERVER["SERVER_PORT"] ) && ( $_SERVER["SERVER_PORT"] != "80" ) && ( $_SERVER["SERVER_PORT"] != "443" ) ) {
		$pageURL .= ltrim( $_SERVER["SERVER_NAME"], ".*" ) . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= ltrim( $_SERVER["SERVER_NAME"], ".*" ) . $_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

function wp_cart_add_custom_field() {
	$wspsc_cart = WSPSC_Cart::get_instance();
	$collection_obj = WPSPSC_Coupons_Collection::get_instance();

	$cart_id = $wspsc_cart->get_cart_id();
	if ( ! $cart_id ) {
		echo '<div class="wspsc_yellow_box">Error! cart ID is missing. cannot add custom field values.</div>';
		return;
	}
	$wspsc_cart->set_cart_custom_values( "" );

	//Create the custom field name value pairs.	
	$custom_field_val = "";
	$name = 'wp_cart_id';
	$value = $wspsc_cart->get_cart_id();
	$custom_field_val = wpc_append_values_to_custom_field( $name, $value );

	$clientip = $_SERVER['REMOTE_ADDR'];
	if ( ! empty( $clientip ) ) {
		$name = 'ip';
		$value = $clientip;
		$custom_field_val = wpc_append_values_to_custom_field( $name, $value );
	}

	if ( function_exists( 'wp_aff_platform_install' ) ) {
		$name = 'ap_id';
		$value = '';
		if ( isset( $_SESSION['ap_id'] ) ) {
			$value = $_SESSION['ap_id'];
		} else if ( isset( $_COOKIE['ap_id'] ) ) {
			$value = $_COOKIE['ap_id'];
		}
		if ( ! empty( $value ) ) {
			$custom_field_val = wpc_append_values_to_custom_field( $name, $value );
		}
	}

	if ( $collection_obj->get_applied_coupon_code( $wspsc_cart->get_cart_id() ) ) {
		$name = "coupon_code";
		$value = $collection_obj->get_applied_coupon_code( $wspsc_cart->get_cart_id() );
		$custom_field_val = wpc_append_values_to_custom_field( $name, $value );
	}

	//Trigger action hook that can be used to append more custom fields value that is saved to the session ($_SESSION[ 'wp_cart_custom_values' ])
	do_action( 'wspsc_cart_custom_field_appended' );

	$custom_field_val = apply_filters( 'wpspc_cart_custom_field_value', $custom_field_val );
	//Save the custom field values to the order post meta.
	update_post_meta( $cart_id, 'wpsc_cart_custom_values', $custom_field_val );

	$custom_field_val = urlencode( $custom_field_val ); //URL encode the custom field value so nothing gets lost when it is passed around.	
	$output = '<input type="hidden" name="custom" value="' . $custom_field_val . '" />';
	return $output;
}

function wp_cart_add_read_form_javascript() {
	$debug_marker = "<!-- WP Simple Shopping Cart plugin v" . WP_CART_VERSION . " - https://wordpress.org/plugins/wordpress-simple-paypal-shopping-cart/ -->";
	echo "\n" . $debug_marker . "\n";
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

/**
 * @deprecated This method has been DEPRECATED. Use cart_not_empty() from WSPSC_Cart instead.
 * @return int Returns the total number of items in the cart.
 */
function cart_not_empty() {
	$wspsc_cart = WSPSC_Cart::get_instance();
	return $wspsc_cart->cart_not_empty();
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
	global $wp;
	if ( is_object( $wp ) && isset( $wp->request ) ) {
		//Try to get the URL from WP
		$pageURL = home_url( add_query_arg( array(), $wp->request ) );
	} else {
		//Get URL using the other method.
		$pageURL = 'http';
		if ( ! isset( $_SERVER["HTTPS"] ) ) {
			$_SERVER["HTTPS"] = "";
		}
		if ( ! isset( $_SERVER["SERVER_PORT"] ) ) {
			$_SERVER["SERVER_PORT"] = "";
		}

		if ( $_SERVER["HTTPS"] == "on" ) {
			$pageURL .= "s";
		}
		$pageURL .= "://";
		if ( $_SERVER["SERVER_PORT"] != "80" ) {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
	}
	$pageURL = apply_filters( 'wspsc_cart_current_page_url', $pageURL );
	return $pageURL;
}

/**
 * @deprecated This method has been deprecated. Use simple_cart_total() from WSPSC_Cart instead.
 */
function simple_cart_total() {
	$wspsc_cart = WSPSC_Cart::get_instance();
	return $wspsc_cart->simple_cart_total();
}

function wp_paypal_shopping_cart_load_widgets() {
	$widget_options = array( 'classname' => 'wp_paypal_shopping_cart_widgets', 'description' => __( "WP Paypal Shopping Cart Widget" ) );
	wp_register_sidebar_widget( 'wp_paypal_shopping_cart_widgets', __( 'WP Paypal Shopping Cart' ), 'show_wp_simple_cart_widget', $widget_options );
}

function show_wp_simple_cart_widget( $args ) {
	// outputs the content of the widget
	extract( $args );

	$cart_title = get_option( 'wp_cart_title' );
	if ( empty( $cart_title ) ) {
		$cart_title = __( "Shopping Cart", "wordpress-simple-paypal-shopping-cart" );
	}

	echo $before_widget;
	echo $before_title . $cart_title . $after_title;
	echo print_wp_shopping_cart();
	echo $after_widget;
}

function wspsc_admin_side_enqueue_scripts() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'wspsc-discounts' ) { //simple paypal shopping cart discount page
		wp_enqueue_style( 'jquery-ui-style', WP_CART_URL . '/assets/jquery.ui.min.css', array(), WP_CART_VERSION );

		wp_register_script( 'wpspsc-admin', WP_CART_URL . '/lib/wpspsc_admin_side.js', array( 'jquery', 'jquery-ui-datepicker' ) );
		wp_enqueue_script( 'wpspsc-admin' );
	}
}

function wspsc_admin_side_styles() {
	wp_enqueue_style( 'wpsc-admin-style', WP_CART_URL . '/assets/wpsc-admin-styles.css', array(), WP_CART_VERSION );
}

function wspsc_front_side_enqueue_scripts() {
	//jQuery
	wp_enqueue_script( 'jquery' );
	
	//Front end styles
	wp_enqueue_style( 'wpsc-style', WP_CART_URL . '/assets/wpsc-front-end-styles.css', array(), WP_CART_VERSION );

	//Stripe checkout/library Related
	wp_register_script( "wspsc.stripe", "https://js.stripe.com/v3/", array( "jquery" ), WP_CART_VERSION );

	$publishable_key = get_option( 'wp_shopping_cart_enable_sandbox' ) ? get_option( 'wpspc_stripe_test_publishable_key' ) : get_option( 'wpspc_stripe_live_publishable_key' );
	$stripe_js_obj = "wspsc_stripe_js_obj";
	wp_add_inline_script( "wspsc.stripe", "var " . $stripe_js_obj . " = Stripe('" . esc_js( $publishable_key ) . "'); var wspsc_ajax_url='" . esc_js( admin_url( 'admin-ajax.php' ) ) . "';" );

	wp_register_script( "wspsc-checkout-stripe", WP_CART_URL . "/assets/js/wspsc-checkout-stripe.js", array( "jquery", "wspsc.stripe"), WP_CART_VERSION);

	//General scripts
	wp_register_script( "wspsc-checkout-cart-script", WP_CART_URL . "/assets/js/wspsc-cart-script.js", array('wp-i18n'), WP_CART_VERSION, true);
	$is_tnc_enabled = empty(get_option('wp_shopping_cart_enable_tnc')) ? 'false' : 'true' ;
	wp_add_inline_script("wspsc-checkout-cart-script", "const wspscIsTncEnabled = " . $is_tnc_enabled .";" , 'before');
	
	$is_shipping_region_enabled = empty(get_option('enable_shipping_by_region')) ? 'false' : 'true' ;
	wp_add_inline_script("wspsc-checkout-cart-script", "const wspscIsShippingRegionEnabled = " . $is_shipping_region_enabled .";" , 'before');
	
	if ($is_shipping_region_enabled) {
		$configured_shipping_region_options  = get_option('wpsc_shipping_region_variations', array() );
		$region_options  = array();
		foreach ($configured_shipping_region_options as $region) {
			$region_options[] = implode(':', array(strtolower($region['loc']), $region['type']));
		}
		wp_add_inline_script("wspsc-checkout-cart-script", "const wpscShippingRegionOptions = " . json_encode( $region_options ) .";" , 'before');
	}
}

function wpspc_plugin_install() {
	wpspc_run_activation();
}

register_activation_hook( __FILE__, 'wpspc_plugin_install' );

// Add the settings link
function wp_simple_cart_add_settings_link( $links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) ) {
		$settings_link = '<a href="admin.php?page=wspsc-menu-main">' . ( __( "Settings", "wordpress-simple-paypal-shopping-cart" ) ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}

add_filter( 'plugin_action_links', 'wp_simple_cart_add_settings_link', 10, 2 );

add_action( 'widgets_init', 'wp_paypal_shopping_cart_load_widgets' );

add_action( 'init', 'wp_cart_init_handler' );
add_action( 'admin_init', 'wp_cart_admin_init_handler' );

if ( ! is_admin() ) {
	add_filter( 'widget_text', 'do_shortcode' );
}

add_action( 'wp_head', 'wp_cart_add_read_form_javascript' );
add_action( 'wp_enqueue_scripts', 'wspsc_front_side_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'wspsc_admin_side_enqueue_scripts' );
add_action( 'admin_print_styles', 'wspsc_admin_side_styles' );
