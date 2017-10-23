<?php

function print_wp_shopping_cart($args = array()) {
    $output = "";
    if (!cart_not_empty()) {
        $empty_cart_text = get_option('wp_cart_empty_text');
        if (!empty($empty_cart_text)) {
            $output .= '<div class="wp_cart_empty_cart_section">';
            if (preg_match("/http/", $empty_cart_text)) {
                $output .= '<img src="' . $empty_cart_text . '" alt="' . $empty_cart_text . '" class="wp_cart_empty_cart_image" />';
            } else {
                $output .= __($empty_cart_text, "wordpress-simple-paypal-shopping-cart");
            }
            $output .= '</div>';
        }
        $cart_products_page_url = get_option('cart_products_page_url');
        if (!empty($cart_products_page_url)) {
            $output .= '<div class="wp_cart_visit_shop_link"><a rel="nofollow" href="' . esc_url($cart_products_page_url) . '">' . (__("Visit The Shop", "wordpress-simple-paypal-shopping-cart")) . '</a></div>';
        }
        return $output;
    }
    $email = get_bloginfo('admin_email');
    $use_affiliate_platform = get_option('wp_use_aff_platform');
    $defaultCurrency = get_option('cart_payment_currency');
    $defaultSymbol = get_option('cart_currency_symbol');
    $defaultEmail = get_option('cart_paypal_email');
    if (!empty($defaultCurrency))
        $paypal_currency = $defaultCurrency;
    else
        $paypal_currency = __("USD", "wordpress-simple-paypal-shopping-cart");
    if (!empty($defaultSymbol))
        $paypal_symbol = $defaultSymbol;
    else
        $paypal_symbol = __("$", "wordpress-simple-paypal-shopping-cart");

    if (!empty($defaultEmail))
        $email = $defaultEmail;

    $decimal = '.';
    $urls = '';

    $return = get_option('cart_return_from_paypal_url');
    if (empty($return)) {
        $return = WP_CART_SITE_URL . '/';
    }
    $return_url = add_query_arg('reset_wp_cart', '1', $return);  

    $urls .= '<input type="hidden" name="return" value="' . $return_url . '" />';
    
    $cancel = get_option('cart_cancel_from_paypal_url');
    if(isset($cancel) && !empty($cancel)){
        $urls .= '<input type="hidden" name="cancel_return" value="' . $cancel . '" />';
    }

    $notify = WP_CART_SITE_URL . '/?simple_cart_ipn=1';
    $notify = apply_filters('wspsc_paypal_ipn_notify_url', $notify);
    $urls .= '<input type="hidden" name="notify_url" value="' . $notify . '" />';

    $title = get_option('wp_cart_title');
    //if (empty($title)) $title = __("Your Shopping Cart", "wordpress-simple-paypal-shopping-cart");

    global $plugin_dir_name;
    $output .= '<div class="shopping_cart">';
    if (!get_option('wp_shopping_cart_image_hide')) {
        $cart_icon_img_src = WP_CART_URL . "/images/shopping_cart_icon.png";
        $cart_icon_img_src = apply_filters('wspsc_cart_icon_image_src', $cart_icon_img_src);
        $output .= "<img src='" . $cart_icon_img_src . "' class='wspsc_cart_header_image' value='" . (__("Cart", "wordpress-simple-paypal-shopping-cart")) . "' alt='" . (__("Cart", "wordpress-simple-paypal-shopping-cart")) . "' />";
    }
    if (!empty($title)) {
        $output .= '<h2>';
        $output .= $title;
        $output .= '</h2>';
    }

    $output .= '<span id="pinfo" style="display: none; font-weight: bold; color: red;">' . (__("Hit enter to submit new Quantity.", "wordpress-simple-paypal-shopping-cart")) . '</span>';
    $output .= '<table style="width: 100%;">';

    $count = 1;
    $total_items = 0;
    $total = 0;
    $form = '';
    if ($_SESSION['simpleCart'] && is_array($_SESSION['simpleCart'])) {
        $output .= '
        <tr class="wspsc_cart_item_row">
        <th class="wspsc_cart_item_name_th">' . (__("Item Name", "wordpress-simple-paypal-shopping-cart")) . '</th><th class="wspsc_cart_qty_th">' . (__("Quantity", "wordpress-simple-paypal-shopping-cart")) . '</th><th class="wspsc_cart_price_th">' . (__("Price", "wordpress-simple-paypal-shopping-cart")) . '</th><th></th>
        </tr>';
        $item_total_shipping = 0;
        $postage_cost = 0;
        foreach ($_SESSION['simpleCart'] as $item) {
            $total += $item['price'] * $item['quantity'];
            $item_total_shipping += $item['shipping'] * $item['quantity'];
            $total_items += $item['quantity'];
        }
        if (!empty($item_total_shipping)) {
            $baseShipping = get_option('cart_base_shipping_cost');
            $postage_cost = $item_total_shipping + $baseShipping;
        }

        $cart_free_shipping_threshold = get_option('cart_free_shipping_threshold');
        if (!empty($cart_free_shipping_threshold) && $total > $cart_free_shipping_threshold) {
            $postage_cost = 0;
        }

        foreach ($_SESSION['simpleCart'] as $item) {
            
            $output .= '<tr class="wspsc_cart_item_thumb"><td class="wspsc_cart_item_name_td" style="overflow: hidden;">';
            $output .= '<div class="wp_cart_item_info">';
            if(isset($args['show_thumbnail'])){
                $output .= '<span class="wp_cart_item_thumbnail"><img src="'.esc_url($item['thumbnail']).'" class="wp_cart_thumb_image" alt="'.esc_attr($item['name']).'" ></span>';
            }
            $item_info = apply_filters('wspsc_cart_item_name', '<a href="'.esc_url($item['cartLink']).'">'.esc_attr($item['name']).'</a>', $item);
            $output .= '<span class="wp_cart_item_name">'.$item_info.'</span>';
            $output .= '<span class="wp_cart_clear_float"></span>';
            $output .= '</div>';
            $output .= '</td>';
            
            $output .= "<td class='wspsc_cart_qty_td' style='text-align: center'><form method=\"post\"  action=\"\" name='pcquantity' style='display: inline'>".wp_nonce_field('wspsc_cquantity', '_wpnonce', true, false)."
                <input type=\"hidden\" name=\"wspsc_product\" value=\"" . htmlspecialchars($item['name']) . "\" />
	        <input type='hidden' name='cquantity' value='1' /><input type='text' class='wspsc_cart_item_qty' name='quantity' value='" . esc_attr($item['quantity']) . "' size='1' onchange='document.pcquantity.submit();' onkeypress='document.getElementById(\"pinfo\").style.display = \"\";' /></form></td>
	        <td style='text-align: center'>" . print_payment_currency(($item['price'] * $item['quantity']), $paypal_symbol, $decimal) . "</td>
	        <td><form method=\"post\" action=\"\" class=\"wp_cart_remove_item_form\">".wp_nonce_field('wspsc_delcart', '_wpnonce', true, false)."
	        <input type=\"hidden\" name=\"wspsc_product\" value=\"" . esc_attr($item['name']) . "\" />
	        <input type='hidden' name='delcart' value='1' />
	        <input type='image' src='" . WP_CART_URL . "/images/Shoppingcart_delete.png' value='" . (__("Remove", "wordpress-simple-paypal-shopping-cart")) . "' title='" . (__("Remove", "wordpress-simple-paypal-shopping-cart")) . "' /></form></td></tr>
	        ";

            $form .= "
	            <input type=\"hidden\" name=\"item_name_$count\" value=\"" . esc_attr($item['name']) . "\" />
	            <input type=\"hidden\" name=\"amount_$count\" value='" . wpspsc_number_format_price($item['price']) . "' />
	            <input type=\"hidden\" name=\"quantity_$count\" value=\"" . esc_attr($item['quantity']) . "\" />
	            <input type='hidden' name='item_number_$count' value='" . esc_attr($item['item_number']) . "' />
	        ";
            $count++;
        }
        if (!get_option('wp_shopping_cart_use_profile_shipping')) {
            //Not using profile based shipping
            $postage_cost = wpspsc_number_format_price($postage_cost);
            $form .= "<input type=\"hidden\" name=\"shipping_1\" value='" . esc_attr($postage_cost) . "' />"; //You can also use "handling_cart" variable to use shipping and handling here 
        }
        
        //Tackle the "no_shipping" parameter
        if (get_option('wp_shopping_cart_collect_address')) {//force address collection
            $form .= '<input type="hidden" name="no_shipping" value="2" />';
        } else {
            //Not using the force address collection feature
            if($postage_cost == 0){
                //No shipping amount present in the cart. Set flag for "no shipping address collection".
                $form .= '<input type="hidden" name="no_shipping" value="1" />';
            }
        }
    }

    $count--;

    if ($count) {
        if ($postage_cost != 0) {
            $output .= "
                <tr class='wspsc_cart_subtotal'><td colspan='2' style='font-weight: bold; text-align: right;'>" . (__("Subtotal", "wordpress-simple-paypal-shopping-cart")) . ": </td><td style='text-align: center'>" . print_payment_currency($total, $paypal_symbol, $decimal) . "</td><td></td></tr>
                <tr class='wspsc_cart_shipping'><td colspan='2' style='font-weight: bold; text-align: right;'>" . (__("Shipping", "wordpress-simple-paypal-shopping-cart")) . ": </td><td style='text-align: center'>" . print_payment_currency($postage_cost, $paypal_symbol, $decimal) . "</td><td></td></tr>";
        }

        $output .= "<tr class='wspsc_cart_total'><td colspan='2' style='font-weight: bold; text-align: right;'>" . (__("Total", "wordpress-simple-paypal-shopping-cart")) . ": </td><td style='text-align: center'>" . print_payment_currency(($total + $postage_cost), $paypal_symbol, $decimal) . "</td><td></td></tr>";

        if (isset($_SESSION['wpspsc_cart_action_msg']) && !empty($_SESSION['wpspsc_cart_action_msg'])) {
            $output .= '<tr class="wspsc_cart_action_msg"><td colspan="4"><span class="wpspsc_cart_action_msg">' . $_SESSION['wpspsc_cart_action_msg'] . '</span></td></tr>';
        }

        if (get_option('wpspsc_enable_coupon') == '1') {
            $output .= '<tr class="wspsc_cart_coupon_row"><td colspan="4">
                <div class="wpspsc_coupon_section">
                <span class="wpspsc_coupon_label">' . (__("Enter Coupon Code", "wordpress-simple-paypal-shopping-cart")) . '</span>
                <form  method="post" action="" >'.wp_nonce_field('wspsc_coupon', '_wpnonce', true, false).'
                <input type="text" name="wpspsc_coupon_code" value="" size="10" />
                <span class="wpspsc_coupon_apply_button"><input type="submit" name="wpspsc_apply_coupon" class="wpspsc_apply_coupon" value="' . (__("Apply", "wordpress-simple-paypal-shopping-cart")) . '" /></span>
                </form>
                </div>
                </td></tr>';
        }

        $paypal_checkout_url = WP_CART_LIVE_PAYPAL_URL;
        if (get_option('wp_shopping_cart_enable_sandbox')) {
            $paypal_checkout_url = WP_CART_SANDBOX_PAYPAL_URL;
        }

        $form_target_code = '';
        if (get_option('wspsc_open_pp_checkout_in_new_tab')) {
            $form_target_code = 'target="_blank"';
        }

        $output .= "<tr class='wpspsc_checkout_form'><td colspan='4'>";
        $output .= '<form action="' . $paypal_checkout_url . '" method="post" ' . $form_target_code . '>';
        $output .= $form;
        if ($count){
            $checkout_button_img_src = WP_CART_URL . '/images/' . (__("paypal_checkout_EN.png", "wordpress-simple-paypal-shopping-cart"));
            $output .= '<input type="image" src="' . apply_filters('wspsc_cart_checkout_button_image_src', $checkout_button_img_src) . '" name="submit" class="wp_cart_checkout_button" alt="' . (__("Make payments with PayPal - it\'s fast, free and secure!", "wordpress-simple-paypal-shopping-cart")) . '" />';
        }
        
        $output .= $urls . '
            <input type="hidden" name="business" value="' . $email . '" />
            <input type="hidden" name="currency_code" value="' . $paypal_currency . '" />
            <input type="hidden" name="cmd" value="_cart" />
            <input type="hidden" name="upload" value="1" />
            <input type="hidden" name="rm" value="2" />
            <input type="hidden" name="charset" value="utf-8" />
            <input type="hidden" name="bn" value="TipsandTricks_SP" />';
        $wp_cart_note_to_seller_text = get_option('wp_cart_note_to_seller_text');
        if (!empty($wp_cart_note_to_seller_text)) {
            $output .= '<input type="hidden" name="no_note" value="0" /><input type="hidden" name="cn" value="' . $wp_cart_note_to_seller_text . '" />';
        }
        $page_style_name = get_option('wp_cart_paypal_co_page_style');
        if (!empty($page_style_name)) {
            $output .= '<input type="hidden" name="image_url" value="' . $page_style_name . '" />';
        }
        $output .= wp_cart_add_custom_field();
        
        $extra_pp_fields = apply_filters('wspsc_cart_extra_paypal_fields', '');//Can be used to add extra PayPal hidden input fields for the cart checkout
        $output .= $extra_pp_fields;
                
        $output .= '</form>';
        $output .= '</td></tr>';
    }
    $output .= "</table></div>";
    return $output;
}
