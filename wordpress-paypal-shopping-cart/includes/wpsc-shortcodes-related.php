<?php 

function print_wp_cart_button_for_product( $name, $price, $shipping = 0, $var1 = '', $var2 = '', $var3 = '', $atts = array() ) {
	$addcart = get_option( 'addToCartButtonName' );
	if ( ! $addcart || ( $addcart == '' ) ) {
		$addcart = __( "Add to Cart", "wordpress-simple-paypal-shopping-cart" );
	}

	//The product name is encoded and decoded to avoid any special characters in the product name creating hashing issues.
	$product_tmp_two = htmlentities( $name );
	$name_before_htmlentity = $name;

	$var_output = "";
	if ( ! empty( $var1 ) ) {
		$var1 = sanitize_text_field($var1);
		$var1_pieces = explode( '|', $var1 );
		$variation1_name = $var1_pieces[0];
		$var_output .= '<span class="wp_cart_variation_name">' . $variation1_name . ' : </span>';
		$var_output .= '<select name="variation1" class="wp_cart_variation1_select" onchange="ReadForm (this.form, false);">';
		for ( $i = 1; $i < sizeof( $var1_pieces ); $i++ ) {
			$var_output .= '<option value="' . esc_attr($var1_pieces[ $i ]) . '">' . esc_attr($var1_pieces[ $i ]) . '</option>';
		}
		$var_output .= '</select><br />';
	}
	if ( ! empty( $var2 ) ) {
		$var2 = sanitize_text_field($var2);
		$var2_pieces = explode( '|', $var2 );
		$variation2_name = $var2_pieces[0];
		$var_output .= '<span class="wp_cart_variation_name">' . $variation2_name . ' : </span>';
		$var_output .= '<select name="variation2" class="wp_cart_variation2_select" onchange="ReadForm (this.form, false);">';
		for ( $i = 1; $i < sizeof( $var2_pieces ); $i++ ) {
			$var_output .= '<option value="' . esc_attr($var2_pieces[ $i ]) . '">' . esc_attr($var2_pieces[ $i ]) . '</option>';
		}
		$var_output .= '</select><br />';
	}
	if ( ! empty( $var3 ) ) {
		$var3 = sanitize_text_field($var3);
		$var3_pieces = explode( '|', $var3 );
		$variation3_name = $var3_pieces[0];
		$var_output .= '<span class="wp_cart_variation_name">' . $variation3_name . ' : </span>';
		$var_output .= '<select name="variation3" class="wp_cart_variation3_select" onchange="ReadForm (this.form, false);">';
		for ( $i = 1; $i < sizeof( $var3_pieces ); $i++ ) {
			$var_output .= '<option value="' . esc_attr($var3_pieces[ $i ]) . '">' . esc_attr($var3_pieces[ $i ]) . '</option>';
		}
		$var_output .= '</select><br />';
	}

	$replacement = '<div class="wp_cart_button_wrapper">';

	$add_cart_button_form_attr = "";
	$add_cart_button_form_attr = apply_filters( "wspsc_add_cart_button_form_attr", $add_cart_button_form_attr ); // TODO: Old hook. Need to remove this.
	$add_cart_button_form_attr = apply_filters( "wpsc_add_cart_button_form_attr", $add_cart_button_form_attr );

	$replacement .= '<form method="post" class="wp-cart-button-form" action="" style="display:inline" onsubmit="return ReadForm(this, true);" ' . $add_cart_button_form_attr . '>';
	$replacement .= wp_nonce_field( 'wspsc_addcart', '_wpnonce', true, false );
	if ( ! empty( $var_output ) ) { //Show variation
		$replacement .= '<div class="wp_cart_variation_section">' . $var_output . '</div>';
	}

	if ( isset( $atts['button_image'] ) && ! empty( $atts['button_image'] ) ) {
		//Use the custom button image specified in the shortcode
		$replacement .= '<input type="image" src="' . esc_url_raw( $atts['button_image'] ) . '" class="wp_cart_button" alt="' . ( __( "Add to Cart", "wordpress-simple-paypal-shopping-cart" ) ) . '"/>';
	} else if ( isset( $atts['button_text'] ) && ! empty( $atts['button_text'] ) ) {
		//Use the custom button text specified in the shortcode
		$wpsc_add_cart_submit_button_value = esc_attr( $atts['button_text'] );
		$wpsc_add_cart_submit_button_value = apply_filters( 'wspsc_add_cart_submit_button_value', $wpsc_add_cart_submit_button_value, $price ); // TODO: Old hook. Need to remove this.
		$wpsc_add_cart_submit_button_value = apply_filters( 'wpsc_add_cart_submit_button_value', $wpsc_add_cart_submit_button_value, $price );

		$replacement .= '<input type="submit" class="wspsc_add_cart_submit" name="wspsc_add_cart_submit" value="' . $wpsc_add_cart_submit_button_value . '" />';
	} else {
		//Use the button text or image value from the settings
		if ( preg_match( "/http:/", $addcart ) || preg_match( "/https:/", $addcart ) ) {
			//Use the image as the add to cart button
			$replacement .= '<input type="image" src="' . esc_url_raw( $addcart ) . '" class="wp_cart_button" alt="' . ( __( "Add to Cart", "wordpress-simple-paypal-shopping-cart" ) ) . '"/>';
		} else {
			//Use plain text add to cart button
			$wpsc_add_cart_submit_button_value = esc_attr( $addcart );
			$wpsc_add_cart_submit_button_value = apply_filters( 'wspsc_add_cart_submit_button_value', $wpsc_add_cart_submit_button_value, $price ); // TODO: Old hook. Need to remove this.
			$wpsc_add_cart_submit_button_value = apply_filters( 'wpsc_add_cart_submit_button_value', $wpsc_add_cart_submit_button_value, $price );

			$replacement .= '<input type="submit" class="wspsc_add_cart_submit" name="wspsc_add_cart_submit" value="' . $wpsc_add_cart_submit_button_value . '" />';
		}
	}

	$replacement .= '<input type="hidden" name="wspsc_product" value="' . esc_attr( $name ) . '" />';
	$replacement .= '<input type="hidden" name="price" value="' . esc_attr( $price ) . '" />';
	$replacement .= '<input type="hidden" name="shipping" value="' . esc_attr( $shipping ) . '" />';
	$replacement .= '<input type="hidden" name="addcart" value="1" />';
	$replacement .= '<input type="hidden" name="cartLink" value="' . esc_url( cart_current_page_url() ) . '" />';
	$replacement .= '<input type="hidden" name="product_tmp" value="' . esc_attr( $name ) . '" />';
	$replacement .= '<input type="hidden" name="product_tmp_two" value="' . esc_attr( $product_tmp_two ) . '" />';
	isset( $atts['item_number'] ) ? $item_num = $atts['item_number'] : $item_num = '';
	$replacement .= '<input type="hidden" name="item_number" value="' . esc_attr($item_num) . '" />';

	if ( isset( $atts['thumbnail'] ) ) {
		$replacement .= '<input type="hidden" name="thumbnail" value="' . esc_url($atts['thumbnail']) . '" />';
	}
	if ( isset( $atts['stamp_pdf'] ) ) {
		$replacement .= '<input type="hidden" name="stamp_pdf" value="' . esc_url( $atts['stamp_pdf'] ) . '" />';
	}
	if ( isset( $atts['digital'] ) ) {
		$replacement .= '<input type="hidden" name="digital" value="' . esc_attr( $atts['digital'] ). '" />';
	}

	$p_key = get_option( 'wspsc_private_key_one' );
	if ( empty( $p_key ) ) {
		$p_key = uniqid( '', true );
		update_option( 'wspsc_private_key_one', $p_key );
	}

	$replacement .= '</form>';

	// Prepare product data to save in dynamic products.
	$dynamic_product_data = array(
		'name' => $name,
		'price' => $price,
		'shipping' => $shipping,
	);
	if ( isset( $atts['file_url'] ) ) {
		$dynamic_product_data['file_url'] = esc_url_raw($atts['file_url']);
	}

	$product_key = WPSC_Dynamic_Products::generate_product_key($name, $price);
	WPSC_Dynamic_Products::get_instance()->save($product_key, $dynamic_product_data);

	$cart_id = WPSC_Cart::get_instance()->get_cart_id();
	if (!empty($cart_id)){
		$persistent_msg = WPSC_Persistent_Msg::get_instance();
		$persistent_msg->set_cart_id($cart_id);

		// Append the saved persistent message here if there is any.
		$replacement .= $persistent_msg->get_msg($product_tmp_two);
	}

	$replacement .= '</div>';
	return $replacement;
}
