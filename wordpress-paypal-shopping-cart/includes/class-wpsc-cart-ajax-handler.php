<?php

class WPSC_Cart_Ajax_Handler {

	public function __construct() {
		if (!empty( get_option( 'wpsc_enable_ajax_add_to_cart', false ) )){
			add_action( 'wp_ajax_wpsc_add_to_cart', array( $this, "handle_add_to_cart" ) );
			add_action( 'wp_ajax_nopriv_wpsc_add_to_cart', array( $this, "handle_add_to_cart" ) );

			add_action( 'wpsc_before_shopping_cart_render', array($this, 'load_ppcp_sdk_if_enabled'), 10, 2);
		}
	}

	public function load_ppcp_sdk_if_enabled($args, $wspsc_cart) {
		$ppcp_configs = \TTHQ\WPSC\Lib\PayPal\PayPal_PPCP_Config::get_instance();
		$paypal_ppcp_checkout_enabled = $ppcp_configs->get_value('ppcp_checkout_enable');

		if (empty($paypal_ppcp_checkout_enabled)){
			// PPCP is not enabled.
			return;
		}

		$live_client_id = $ppcp_configs->get_value('paypal-live-client-id');
		$sandbox_client_id = $ppcp_configs->get_value('paypal-sandbox-client-id');
		$sandbox_enabled = $ppcp_configs->get_value('enable-sandbox-testing');
		$is_live_mode = $sandbox_enabled ? 0 : 1;

		$disable_funding_card = $ppcp_configs->get_value('ppcp_disable_funding_card');
		$disable_funding_credit = $ppcp_configs->get_value('ppcp_disable_funding_credit');
		$disable_funding_venmo = $ppcp_configs->get_value('ppcp_disable_funding_venmo');
		$disable_funding = array();
		if( !empty($disable_funding_card)){
			$disable_funding[] = 'card';
		}
		if( !empty($disable_funding_credit)){
			$disable_funding[] = 'credit';
		}
		if( !empty($disable_funding_venmo)){
			$disable_funding[] = 'venmo';
		}

		$currency = isset($args['currency']) ? $args['currency'] : 'USD';
		$pp_js_button = \TTHQ\WPSC\Lib\PayPal\PayPal_JS_Button_Embed::get_instance();

		$settings_args = array(
			'is_live_mode' => $is_live_mode,
			'live_client_id' => $live_client_id,
			'sandbox_client_id' => $sandbox_client_id,
			'currency' => $currency,
			'disable-funding' => $disable_funding, /*array('card', 'credit', 'venmo'),*/
			'intent' => 'capture', /* It is used to set the "intent" parameter in the JS SDK */
			'is_subscription' => 0, /* It is used to set the "vault" parameter in the JS SDK */
		);

		$pp_js_button->set_settings_args( $settings_args );

		// $pp_js_button->load_paypal_sdk();

		add_action( 'wp_footer', array($pp_js_button, 'load_paypal_sdk') );
	}

	public function handle_add_to_cart() {
		//Some sites using caching need to be able to disable nonce on the add cart button. Otherwise 48 hour old cached pages will have stale nonce value and fail for valid users.
		if ( ! empty( get_option( 'wspsc_disable_nonce_add_cart' ) ) ) {
			//This site has disabled the nonce check for add cart button.
			//Do not check nonce for this site since the site admin has indicated that he does not want to check nonce for add cart button.
		} else {
			if ( ! check_ajax_referer( 'wspsc_addcart', false, false ) ) {
				wp_send_json_error( array(
					'message' => __( "Error! Nonce Security Check Failed!", "wordpress-simple-paypal-shopping-cart" ),
					'data'    => $_POST,
				) );
			}
		}

		try {
			$wpsc_cart = $this->process_add_to_cart();

			// Get new cart output that needs in the front-end.
			$cart_shortcode_output = array();
			if (isset($_POST['getCart']) && !empty($_POST['getCart'])){
				$cartCount = intval(sanitize_text_field($_POST['getCart']));
				for ($i = 0; $i < $cartCount; $i++){
					$cart_shortcode_output[] = print_wp_shopping_cart();
				}
			}

			$compact_cart_shortcode_output = isset($_POST['getCompactCart']) && !empty($_POST['getCompactCart']) ? wpsc_compact_cart_handler(array()) : '';
			$compact_cart2_shortcode_output = isset($_POST['getCompactCart2']) && !empty($_POST['getCompactCart2']) ? wpsc_compact_cart2_handler(array()) : '';

			wp_send_json_success( array(
				'message' => __( "Added to cart!", "wordpress-simple-paypal-shopping-cart" ),
				'cart_shortcode_output' => $cart_shortcode_output,
				'compact_cart_shortcode_output' => $compact_cart_shortcode_output,
				'compact_cart2_shortcode_output' => $compact_cart2_shortcode_output,
			) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage(),
			) );
		}
	}

	//Add to cart action
	public function process_add_to_cart() {
		$wpsc_cart = WPSC_Cart::get_instance();
		$wpsc_cart->clear_cart_action_msg();

		//create new cart object when add to cart button is clicked the first time
		if ( ! $wpsc_cart->get_cart_id() ) {
			$wpsc_cart->create_cart();
		}

		setcookie( "cart_in_use", "true", time() + 21600, "/", COOKIE_DOMAIN ); //Useful to not serve cached page when using with a caching plugin
		setcookie( "wp_cart_in_use", "1", time() + 21600, "/", COOKIE_DOMAIN ); //Exclusion rule for Batcache caching (used by some hosting like wordpress.com)
		if ( function_exists( 'wp_cache_serve_cache_file' ) ) { //WP Super cache workaround
			setcookie( "comment_author_", "wp_cart", time() + 21600, "/", COOKIE_DOMAIN );
		}

		//Sanitize post data
		$post_wspsc_product = isset( $_POST['wspsc_product'] ) ? stripslashes( sanitize_text_field( $_POST['wspsc_product'] ) ) : '';
		$post_item_number   = isset( $_POST['item_number'] ) ? sanitize_text_field( $_POST['item_number'] ) : '';
		$post_cart_link     = isset( $_POST['cartLink'] ) ? esc_url_raw( sanitize_text_field( urldecode( $_POST['cartLink'] ) ) ) : '';
		$post_stamp_pdf     = isset( $_POST['stamp_pdf'] ) ? sanitize_text_field( $_POST['stamp_pdf'] ) : '';

		$post_thumbnail = isset( $_POST['thumbnail'] ) ? esc_url_raw( sanitize_text_field( $_POST['thumbnail'] ) ) : '';
		$digital_flag   = isset( $_POST['digital'] ) ? esc_url_raw( sanitize_text_field( $_POST['digital'] ) ) : '';

		//Get the product key for the dynamic product.
		$wpsc_dynamic_products = WPSC_Dynamic_Products::get_instance();
		$posted_price          = isset( $_POST['price'] ) ? sanitize_text_field( $_POST['price'] ) : '';

		$applied_variation1 = isset( $_POST['variation1'] ) ? sanitize_text_field( $_POST['variation1'] ) : '';
		$applied_variation2 = isset( $_POST['variation2'] ) ? sanitize_text_field( $_POST['variation2'] ) : '';
		$applied_variation3 = isset( $_POST['variation3'] ) ? sanitize_text_field( $_POST['variation3'] ) : '';

		$post_wspsc_tmp_name = isset( $_POST['product_tmp'] ) ? stripslashes( sanitize_text_field( $_POST['product_tmp'] ) ) : '';
		//The product name is encoded and decoded to avoid any special characters in the product name creating hashing issues

		// Generate the key using 'product_tmp' post data instead of 'wspsc_product' post data, because the 'wspsc_product' gets changed for variation products.
		$wpsc_product_key = $wpsc_dynamic_products::generate_product_key( $post_wspsc_tmp_name, $posted_price );

		//Get the file url for the dynamic product (if any)
		$post_file_url = $wpsc_dynamic_products->get_data_by_param( $wpsc_product_key, 'file_url' );

		//Sanitize and validate price
		if ( isset( $_POST['price'] ) ) {
			$price = sanitize_text_field( $_POST['price'] );

			if ( get_option( 'wspsc_disable_price_check_add_cart' ) ) {
				//This site has disabled the price check for add cart button.
				//Do not perform the price check for this site since the site admin has indicated that he does not want to do it on this site.
			} else {
				$price_from_db = $wpsc_dynamic_products->get_data_by_param( $wpsc_product_key, 'price' );
				if ( $price != $price_from_db ) {
					//Security check failed. Price field may have been tampered. Fail the validation.
					$error_msg = __( "Error! The price field may have been tampered. Security check failed.", "wordpress-simple-paypal-shopping-cart" );
					$error_msg .= ' ' . __( "If this site uses any caching, empty the cache then try again.", "wordpress-simple-paypal-shopping-cart" );
					$error_msg .= ' ' . __( "If the issue persists go to the settings menu of the plugin and select/tick the 'Disable Price Check for Add to Cart' checkbox and save it.", "wordpress-simple-paypal-shopping-cart" );
					// wp_die( $error_msg );
					throw new \Exception( $error_msg );
				}
			}

			$price = str_replace( WP_CART_CURRENCY_SYMBOL, "", $price ); //Remove any currency symbol from the price.
			//Check that the price field is numeric.
			if ( ! is_numeric( $price ) ) { //Price validation failed
				throw new \Exception( __( "Error! The price validation failed. The value must be numeric.", "wordpress-simple-paypal-shopping-cart" ) );
			}

			$variation_price = 0;
			if ( ! empty( $applied_variation1 ) ) {
				$variation_price += $wpsc_dynamic_products->get_variation_price( $wpsc_product_key, 'var1', $applied_variation1 );
			}
			if ( ! empty( $applied_variation2 ) ) {
				$variation_price += $wpsc_dynamic_products->get_variation_price( $wpsc_product_key, 'var2', $applied_variation2 );
			}
			if ( ! empty( $applied_variation3 ) ) {
				$variation_price += $wpsc_dynamic_products->get_variation_price( $wpsc_product_key, 'var3', $applied_variation3 );
			}

			$price += $variation_price;

			if ( floatval( $price ) < 0 ) {
				throw new \Exception( __( 'Error! Product price amount cannot be negative.', "wordpress-simple-paypal-shopping-cart" ) );
			}

			//At this stage the price amt has already been sanitized and validated.
		} else {
			throw new \Exception( __( 'Error! Missing price value. The price must be set.', "wordpress-simple-paypal-shopping-cart" ) );
		}

		//Sanitize and validate shipping price
		if ( isset( $_POST['shipping'] ) ) {
			$shipping = sanitize_text_field( $_POST['shipping'] );

			if ( get_option( 'wspsc_disable_price_check_add_cart' ) ) {
				//This site has disabled the price check for add cart button.
				//Do not perform the price check for this site since the site admin has indicated that he does not want to do it on this site.
			} else {
				$shipping_from_db = $wpsc_dynamic_products->get_data_by_param( $wpsc_product_key, 'shipping' );
				if ( $shipping != $shipping_from_db ) { //Shipping validation failed
					throw new \Exception( __( 'Error! The shipping price validation failed.', "wordpress-simple-paypal-shopping-cart" ) );
				}
			}

			$shipping = str_replace( WP_CART_CURRENCY_SYMBOL, "", $shipping ); //Remove any currency symbol from the price.
			//Check that the shipping price field is numeric.
			if ( ! is_numeric( $shipping ) ) { //Shipping price validation failed
				throw new \Exception( __( 'Error! The shipping price validation failed. The value must be numeric.', "wordpress-simple-paypal-shopping-cart" ) );
			}
			//At this stage the shipping price amt has already been sanitized and validated.
		} else {
			throw new \Exception( __( 'Error! Missing shipping price value. The price must be set.', "wordpress-simple-paypal-shopping-cart" ) );
		}

		$is_do_not_show_qty_in_cart_enabled = get_option( 'wp_shopping_cart_do_not_show_qty_in_cart' ) == 'checked="checked"' ? true : false;
		$count                              = 1;
		$products                           = array();
		if ( $wpsc_cart->get_items() ) {
			$products = $wpsc_cart->get_items();
			if ( is_array( $products ) ) {
				foreach ( $products as $key => $item ) {
					if ( $item->get_name() == $post_wspsc_product ) {
						$count += $item->get_quantity();
						if ( $is_do_not_show_qty_in_cart_enabled ) {
							$msg     = __( "This item is already in your cart", "wordpress-simple-paypal-shopping-cart" );
							$cart_id = $wpsc_cart->get_cart_id();

							$persistent_msg = WPSC_Persistent_Msg::get_instance();
							$persistent_msg->set_cart_id( $cart_id );

							// This one is to show below add to cart button.
							$persistent_msg->set_msg( $item->get_name(), $msg );

							// This one is to show in the cart.
							$wpsc_cart->set_cart_action_msg( $persistent_msg->get_formatted_msg( $msg ) );
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
			$wspsc_cart_item = new WPSC_Cart_Item();
			$wspsc_cart_item->set_name( $post_wspsc_product );
			$wspsc_cart_item->set_price( $price );
			$wspsc_cart_item->set_price_orig( $price );
			$wspsc_cart_item->set_quantity( $count );
			$wspsc_cart_item->set_shipping( $shipping );
			$wspsc_cart_item->set_cart_link( $post_cart_link );
			$wspsc_cart_item->set_item_number( $post_item_number );
			$wspsc_cart_item->set_digital_flag( $digital_flag );
			if ( ! empty( $post_file_url ) ) {
				$wspsc_cart_item->set_file_url( $post_file_url );
			}
			if ( ! empty( $post_thumbnail ) ) {
				$wspsc_cart_item->set_thumbnail( $post_thumbnail );
			}
			$product['stamp_pdf'] = $post_stamp_pdf;
			$wspsc_cart_item->set_stamp_pdf( $post_stamp_pdf );
			array_push( $products, $wspsc_cart_item );
		}

		sort( $products );

		if ( $wpsc_cart->get_cart_id() ) {
			$wpsc_cart->add_items( $products );
		}


		//if cart is not yet created, save the returned products
		//so it can be saved when cart is created
		$products_discount = wpsc_reapply_discount_coupon_if_needed(); //Re-apply coupon to the cart if necessary
		if ( is_array( $products_discount ) ) {
			$products = $products_discount;
		}

		if ( ! $wpsc_cart->get_cart_id() ) {
			$wpsc_cart->create_cart();
			$wpsc_cart->add_items( $products );
		} else {
			//cart updating
			if ( $wpsc_cart->get_cart_id() ) {
				$wpsc_cart->add_items( $products );
			} else {
				throw new \Exception( __( "Error! Your session is out of sync. Please reset your session.", "wordpress-simple-paypal-shopping-cart" ) );
			}
		}

		return $wpsc_cart;
	}

}

new WPSC_Cart_Ajax_Handler();
