<?php 

/**
 * @deprecated: This function is deprecated and should no longer be used.
 * Please use the shortcode.
 */
function shopping_cart_show_deprecated( $content ) {
	$wspsc_cart = WPSC_Cart::get_instance();
	if ( strpos( $content, "<!--show-wp-shopping-cart-->" ) !== FALSE ) {
		if ( $wspsc_cart->cart_not_empty() ) {
			$content = preg_replace( '/<p>\s*<!--(.*)-->\s*<\/p>/i', "<!--$1-->", $content );
			$matchingText = '<!--show-wp-shopping-cart-->';
			$replacementText = print_wp_shopping_cart();
			$content = str_replace( $matchingText, $replacementText, $content );
		}
	}
	return $content;
}

/**
 * @deprecated: This function is deprecated and should no longer be used.
 * Please use the shortcode.
 */
function print_wp_cart_button_deprecated( $content ) {
	$addcart = get_option( 'addToCartButtonName' );
	if ( ! $addcart || ( $addcart == '' ) )
		$addcart = __( "Add to Cart", "wordpress-simple-paypal-shopping-cart" );

	$pattern = '#\[wp_cart:.+:price:.+:end]#';
	preg_match_all( $pattern, $content, $matches );

	foreach ( $matches[0] as $match ) {
		$var_output = '';
		$pos = strpos( $match, ":var1" );
		if ( $pos ) {
			$match_tmp = $match;
			// Variation control is used
			$pos2 = strpos( $match, ":var2" );
			if ( $pos2 ) {
				$pattern = '#var2\[.*]:#';
				preg_match_all( $pattern, $match_tmp, $matches3 );
				$match3 = $matches3[0][0];
				$match_tmp = str_replace( $match3, '', $match_tmp );

				$pattern = 'var2[';
				$m3 = str_replace( $pattern, '', $match3 );
				$pattern = ']:';
				$m3 = str_replace( $pattern, '', $m3 );
				$pieces3 = explode( '|', $m3 );

				$variation2_name = $pieces3[0];
				$var_output .= $variation2_name . " : ";
				$var_output .= '<select name="variation2" onchange="ReadForm (this.form, false);">';
				for ( $i = 1; $i < sizeof( $pieces3 ); $i++ ) {
					$var_output .= '<option value="' . $pieces3[ $i ] . '">' . $pieces3[ $i ] . '</option>';
				}
				$var_output .= '</select><br />';
			}

			$pattern = '#var1\[.*]:#';
			preg_match_all( $pattern, $match_tmp, $matches2 );
			$match2 = $matches2[0][0];

			$match_tmp = str_replace( $match2, '', $match_tmp );

			$pattern = 'var1[';
			$m2 = str_replace( $pattern, '', $match2 );
			$pattern = ']:';
			$m2 = str_replace( $pattern, '', $m2 );
			$pieces2 = explode( '|', $m2 );

			$variation_name = $pieces2[0];
			$var_output .= $variation_name . " : ";
			$var_output .= '<select name="variation1" onchange="ReadForm (this.form, false);">';
			for ( $i = 1; $i < sizeof( $pieces2 ); $i++ ) {
				$var_output .= '<option value="' . $pieces2[ $i ] . '">' . $pieces2[ $i ] . '</option>';
			}
			$var_output .= '</select><br />';
		}

		$pattern = '[wp_cart:';
		$m = str_replace( $pattern, '', $match );

		$pattern = 'price:';
		$m = str_replace( $pattern, '', $m );
		$pattern = 'shipping:';
		$m = str_replace( $pattern, '', $m );
		$pattern = ':end]';
		$m = str_replace( $pattern, '', $m );

		$pieces = explode( ':', $m );

		$replacement = '<div class="wp_cart_button_wrapper">';

		$wpsc_add_cart_button_form_attr = '';
		$wpsc_add_cart_button_form_attr = apply_filters( "wspsc_add_cart_button_form_attr", $wpsc_add_cart_button_form_attr ); // TODO: Old hook. Need to remove this.
		$wpsc_add_cart_button_form_attr = apply_filters( "wpsc_add_cart_button_form_attr", $wpsc_add_cart_button_form_attr );

		$replacement .= '<form method="post" class="wp-cart-button-form" action="" style="display:inline" onsubmit="return ReadForm(this, true);" ' . $wpsc_add_cart_button_form_attr . '>';
		$replacement .= wp_nonce_field( 'wspsc_addcart', '_wpnonce', true, false ); //nonce value

		if ( ! empty( $var_output ) ) {
			$replacement .= $var_output;
		}

		if ( preg_match( "/http/", $addcart ) ) {
			//Use the image as the add to cart button
			$replacement .= '<input type="image" src="' . $addcart . '" class="wp_cart_button" alt="' . ( __( "Add to Cart", "wordpress-simple-paypal-shopping-cart" ) ) . '"/>';
		} else {
			//Plain text add to cart button
			$replacement .= '<input type="submit" class="wspsc_add_cart_submit" name="wspsc_add_cart_submit" value="' . esc_attr( $addcart ) . '" />';
		}

		$replacement .= '<input type="hidden" name="wspsc_product" value="' . esc_attr( $pieces['0'] ) . '" /><input type="hidden" name="price" value="' . esc_attr( $pieces['1'] ) . '" />';
		$replacement .= '<input type="hidden" name="product_tmp" value="' . esc_attr( $pieces['0'] ) . '" />';
		//encode the product name to avoid any special characters in the product name creating hashing issues
		$product_tmp_two = htmlentities( $pieces['0'] );
		$replacement .= '<input type="hidden" name="product_tmp_two" value="' . esc_attr( $product_tmp_two ) . '" />';

		if ( sizeof( $pieces ) > 2 ) {
			//We likely have shipping
			if ( ! is_numeric( $pieces['2'] ) ) { //Shipping parameter has non-numeric value. Discard it and set it to 0.
				$pieces['2'] = 0;
			}
			$replacement .= '<input type="hidden" name="shipping" value="' . esc_attr( $pieces['2'] ) . '" />';
		} else {
			//Set shipping to 0 by default (when no shipping is specified in the shortcode)
			$pieces['2'] = 0;
			$replacement .= '<input type="hidden" name="shipping" value="' . esc_attr( $pieces['2'] ) . '" />';
		}

		$p_key = get_option( 'wspsc_private_key_one' );
		if ( empty( $p_key ) ) {
			$p_key = uniqid( '', true );
			update_option( 'wspsc_private_key_one', $p_key );
		}
		$hash_one = md5( $p_key . '|' . $pieces['1'] . '|' . $product_tmp_two ); //Price hash
		$replacement .= '<input type="hidden" name="hash_one" value="' . $hash_one . '" />';

		$hash_two = md5( $p_key . '|' . $pieces['2'] . '|' . $product_tmp_two ); //Shipping hash
		$replacement .= '<input type="hidden" name="hash_two" value="' . $hash_two . '" />';

		$replacement .= '<input type="hidden" name="cartLink" value="' . esc_url( cart_current_page_url() ) . '" />';
		$replacement .= '<input type="hidden" name="addcart" value="1" /></form>';
		$replacement .= '</div>';
		$content = str_replace( $match, $replacement, $content );
	}
	return $content;
}

add_filter( 'the_content', 'print_wp_cart_button_deprecated', 11 );//Deprecated. Use the shortcode instead.
add_filter( 'the_content', 'shopping_cart_show_deprecated' );//Deprecated. Use the shortcode instead.

