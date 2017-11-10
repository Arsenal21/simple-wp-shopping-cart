<?php
/*
 * This page handles the orders menu page in the admin dashboard
 */

add_action('save_post', 'wpspc_cart_save_orders', 10, 2);

function wpspc_create_orders_page() {
    register_post_type('wpsc_cart_orders', array(
        'labels' => array(
            'name' => __("Cart Orders", "wordpress-simple-paypal-shopping-cart"),
            'singular_name' => __("Cart Order", "wordpress-simple-paypal-shopping-cart"),
            'add_new' => __("Add New", "wordpress-simple-paypal-shopping-cart"),
            'add_new_item' => __("Add New Order", "wordpress-simple-paypal-shopping-cart"),
            'edit' => __("Edit", "wordpress-simple-paypal-shopping-cart"),
            'edit_item' => __("Edit Order", "wordpress-simple-paypal-shopping-cart"),
            'new_item' => __("New Order", "wordpress-simple-paypal-shopping-cart"),
            'view' => __("View", "wordpress-simple-paypal-shopping-cart"),
            'view_item' => __("View Order", "wordpress-simple-paypal-shopping-cart"),
            'search_items' => __("Search Order", "wordpress-simple-paypal-shopping-cart"),
            'not_found' => __("No order found", "wordpress-simple-paypal-shopping-cart"),
            'not_found_in_trash' => __("No order found in Trash", "wordpress-simple-paypal-shopping-cart"),
            'parent' => __("Parent Order", "wordpress-simple-paypal-shopping-cart")
        ),
        'public' => true,
        'menu_position' => 90,
        'supports' => false,
        'taxonomies' => array(''),
        'menu_icon' => 'dashicons-cart',
        'has_archive' => true
            )
    );
}

function wpspc_add_meta_boxes() {
    add_meta_box('order_review_meta_box', __("Order Review", "wordpress-simple-paypal-shopping-cart"), 'wpspc_order_review_meta_box', 'wpsc_cart_orders', 'normal', 'high'
    );
}

function wpspc_order_review_meta_box($wpsc_cart_orders) {
    $order_id = $wpsc_cart_orders->ID;
    $first_name = get_post_meta($wpsc_cart_orders->ID, 'wpsc_first_name', true);
    $last_name = get_post_meta($wpsc_cart_orders->ID, 'wpsc_last_name', true);
    $email = get_post_meta($wpsc_cart_orders->ID, 'wpsc_email_address', true);
    $txn_id = get_post_meta($wpsc_cart_orders->ID, 'wpsc_txn_id', true);
    $ip_address = get_post_meta($wpsc_cart_orders->ID, 'wpsc_ipaddress', true);
    $total_amount = get_post_meta($wpsc_cart_orders->ID, 'wpsc_total_amount', true);
    $shipping_amount = get_post_meta($wpsc_cart_orders->ID, 'wpsc_shipping_amount', true);
    $address = get_post_meta($wpsc_cart_orders->ID, 'wpsc_address', true);
    $phone = get_post_meta($wpsc_cart_orders->ID, 'wpspsc_phone', true);
    $email_sent_value = get_post_meta($wpsc_cart_orders->ID, 'wpsc_buyer_email_sent', true);

    $email_sent_field_msg = "No";
    if (!empty($email_sent_value)) {
        $email_sent_field_msg = "Yes. " . $email_sent_value;
    }

    $items_ordered = get_post_meta($wpsc_cart_orders->ID, 'wpspsc_items_ordered', true);
    $applied_coupon = get_post_meta($wpsc_cart_orders->ID, 'wpsc_applied_coupon', true);
    ?>
    <table>
        <p><?php
            _e("Order ID: #", "wordpress-simple-paypal-shopping-cart");
            echo esc_attr($order_id);
            ?></p>
        <?php if ($txn_id) { ?>
            <p><?php
                _e("Transaction ID: #", "wordpress-simple-paypal-shopping-cart");
                echo esc_attr($txn_id);
                ?></p>
        <?php } ?>
        <tr>
            <td><?php _e("First Name", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="40" name="wpsc_first_name" value="<?php echo esc_attr($first_name); ?>" /></td>
        </tr>
        <tr>
            <td><?php _e("Last Name", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="40" name="wpsc_last_name" value="<?php echo esc_attr($last_name); ?>" /></td>
        </tr>
        <tr>
            <td><?php _e("Email Address", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="40" name="wpsc_email_address" value="<?php echo esc_attr($email); ?>" /></td>
        </tr>
        <tr>
            <td><?php _e("IP Address", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="40" name="wpsc_ipaddress" value="<?php echo esc_attr($ip_address); ?>" /></td>
        </tr>
        <tr>
            <td><?php _e("Total", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="20" name="wpsc_total_amount" value="<?php echo esc_attr($total_amount); ?>" /></td>
        </tr>
        <tr>
            <td><?php _e("Shipping", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="20" name="wpsc_shipping_amount" value="<?php echo esc_attr($shipping_amount); ?>" /></td>
        </tr>
        <tr>
            <td><?php _e("Address", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td>
                <textarea name="wpsc_address" cols="83" rows="2"><?php echo esc_attr($address); ?></textarea>
                <p class="description">
                    <?php _e("An address value will only be present if the order has physical item(s) with shipping amount. ", "wordpress-simple-paypal-shopping-cart"); ?>
                    <?php _e("If you want to collect address for all orders then enable the 'Must Collect Shipping Address on PayPal' option from the plugin settings.", "wordpress-simple-paypal-shopping-cart"); ?>
                </p>
            </td>
        </tr>
        <tr>
            <td><?php _e("Phone", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td>
                <input type="text" size="40" name="wpspsc_phone" value="<?php echo esc_attr($phone); ?>" />
                <p class="description">
                    <?php _e("A phone number will only be present if the customer entered one during the checkout.", "wordpress-simple-paypal-shopping-cart"); ?>
                </p>
            </td>
        </tr>
        <tr>
            <td><?php _e("Buyer Email Sent?", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="80" name="wpsc_buyer_email_sent" value="<?php echo esc_attr($email_sent_field_msg); ?>" readonly /></td>
        </tr>  
        <tr>
            <td><?php _e("Item(s) Ordered:", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><textarea name="wpspsc_items_ordered" cols="83" rows="5"><?php echo esc_attr($items_ordered); ?></textarea></td>
        </tr>
        <tr>
            <td><?php _e("Applied Coupon Code:", "wordpress-simple-paypal-shopping-cart"); ?></td>
            <td><input type="text" size="20" name="wpsc_applied_coupon" value="<?php echo esc_attr($applied_coupon); ?>" readonly /></td>
        </tr>
        <?php
        do_action('wpspsc_edit_order_pre_table_end', $order_id);
        ?>
    </table>
    <?php
}

/*
 * Save the order data from the edit order interface.
 * This function is hooked to save_post action. so it only gets executed for a logged in wp user
 */

function wpspc_cart_save_orders($order_id, $wpsc_cart_orders) {
    // Check post type for movie reviews
    if ($wpsc_cart_orders->post_type == 'wpsc_cart_orders') {
        // Store data in post meta table if present in post data
        if (isset($_POST['wpsc_first_name']) && $_POST['wpsc_first_name'] != '') {
            $first_name = sanitize_text_field($_POST['wpsc_first_name']);
            update_post_meta($order_id, 'wpsc_first_name', $first_name);
        }
        if (isset($_POST['wpsc_last_name']) && $_POST['wpsc_last_name'] != '') {
            $last_name = sanitize_text_field($_POST['wpsc_last_name']);
            update_post_meta($order_id, 'wpsc_last_name', $last_name);
        }
        if (isset($_POST['wpsc_email_address']) && $_POST['wpsc_email_address'] != '') {
            $email_address = sanitize_email($_POST['wpsc_email_address']);
            update_post_meta($order_id, 'wpsc_email_address', $email_address);
        }
        if (isset($_POST['wpsc_ipaddress']) && $_POST['wpsc_ipaddress'] != '') {
            $ipaddress = sanitize_text_field($_POST['wpsc_ipaddress']);
            update_post_meta($order_id, 'wpsc_ipaddress', $ipaddress);
        }
        if (isset($_POST['wpsc_total_amount']) && $_POST['wpsc_total_amount'] != '') {
            $total_amount = sanitize_text_field($_POST['wpsc_total_amount']);
            if (!is_numeric($total_amount)) {
                wp_die('Error! Total amount must be a numeric number.');
            }
            update_post_meta($order_id, 'wpsc_total_amount', $total_amount);
        }
        if (isset($_POST['wpsc_shipping_amount']) && $_POST['wpsc_shipping_amount'] != '') {
            $shipping_amount = sanitize_text_field($_POST['wpsc_shipping_amount']);
            if (!is_numeric($shipping_amount)) {
                wp_die('Error! Shipping amount must be a numeric number.');
            }
            update_post_meta($order_id, 'wpsc_shipping_amount', $shipping_amount);
        }
        if (isset($_POST['wpsc_address']) && $_POST['wpsc_address'] != '') {
            $address = sanitize_text_field($_POST['wpsc_address']);
            update_post_meta($order_id, 'wpsc_address', $address);
        }
        if (isset($_POST['wpspsc_phone']) && $_POST['wpspsc_phone'] != '') {
            $phone = sanitize_text_field($_POST['wpspsc_phone']);
            update_post_meta($order_id, 'wpspsc_phone', $phone);
        }
        if (isset($_POST['wpspsc_items_ordered']) && $_POST['wpspsc_items_ordered'] != '') {
            $items_ordered = stripslashes(esc_textarea($_POST['wpspsc_items_ordered']));
            update_post_meta($order_id, 'wpspsc_items_ordered', $items_ordered);
        }
    }
}

add_filter('manage_edit-wpsc_cart_orders_columns', 'wpspc_orders_display_columns');

function wpspc_orders_display_columns($columns) {
    //unset( $columns['title'] );
    unset($columns['comments']);
    unset($columns['date']);
    $columns['title'] = __("Order ID", "wordpress-simple-paypal-shopping-cart");
    $columns['wpsc_first_name'] = __("First Name", "wordpress-simple-paypal-shopping-cart");
    $columns['wpsc_last_name'] = __("Last Name", "wordpress-simple-paypal-shopping-cart");
    $columns['wpsc_email_address'] = __("Email", "wordpress-simple-paypal-shopping-cart");
    $columns['wpsc_total_amount'] = __("Total", "wordpress-simple-paypal-shopping-cart");
    $columns['wpsc_order_status'] = __("Status", "wordpress-simple-paypal-shopping-cart");
    $columns['date'] = __("Date", "wordpress-simple-paypal-shopping-cart");
    return $columns;
}

//add_action( 'manage_posts_custom_column', 'wpsc_populate_order_columns' , 10, 2);
add_action('manage_wpsc_cart_orders_posts_custom_column', 'wpspc_populate_order_columns', 10, 2);

function wpspc_populate_order_columns($column, $post_id) {
    if ('wpsc_first_name' == $column) {
        $first_name = get_post_meta($post_id, 'wpsc_first_name', true);
        echo esc_attr($first_name);
    } else if ('wpsc_last_name' == $column) {
        $last_name = get_post_meta($post_id, 'wpsc_last_name', true);
        echo esc_attr($last_name);
    } else if ('wpsc_email_address' == $column) {
        $email = get_post_meta($post_id, 'wpsc_email_address', true);
        echo esc_attr($email);
    } else if ('wpsc_total_amount' == $column) {
        $total_amount = get_post_meta($post_id, 'wpsc_total_amount', true);
        echo esc_attr($total_amount);
    } else if ('wpsc_order_status' == $column) {
        $status = get_post_meta($post_id, 'wpsc_order_status', true);
        echo esc_attr($status);
    }
}

add_filter('post_type_link', "wpspsc_customize_order_link", 10, 2);

function wpspsc_customize_order_link($permalink, $post) {
    if ($post->post_type == 'wpsc_cart_orders') { //The post type is cart orders
        $permalink = get_admin_url() . 'post.php?post=' . $post->ID . '&action=edit';
    }
    return $permalink;
}

add_filter('posts_join', 'wp_cart_search_join');

function wp_cart_search_join($join) {
    // this function joins postmeta table to the search results in order for us to be able to search post meta values as well
    global $pagenow, $wpdb;
    if (is_admin() && $pagenow == 'edit.php' && (isset($_GET['post_type']) && $_GET['post_type'] == 'wpsc_cart_orders') && (isset($_GET['s']) && $_GET['s'] != '')) {
        $join .= 'LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
    }
    return $join;
}

add_filter('posts_where', 'wp_cart_search_where');

function wp_cart_search_where($where) {
    global $pagenow, $wpdb;
    if (is_admin() && $pagenow == 'edit.php' && (isset($_GET['post_type']) && $_GET['post_type'] == 'wpsc_cart_orders') && (isset($_GET['s']) && $_GET['s'] != '')) {
        $where = preg_replace(
                "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/", "(" . $wpdb->postmeta . ".meta_key=\"wpsc_first_name\" AND " . $wpdb->postmeta . ".meta_value LIKE $1)"
                . " OR (" . $wpdb->postmeta . ".meta_key=\"wpsc_last_name\" AND " . $wpdb->postmeta . ".meta_value LIKE $1)"
                . " OR (" . $wpdb->postmeta . ".meta_key=\"wpsc_email_address\" AND " . $wpdb->postmeta . ".meta_value LIKE $1)"
                , $where);
    }
    return $where;
}

add_filter('posts_distinct', 'wp_cart_search_distinct');

function wp_cart_search_distinct($where) {
    // this function removes duplicates in search results
    global $pagenow;

    if (is_admin() && $pagenow == 'edit.php' && (isset($_GET['post_type']) && $_GET['post_type'] == 'wpsc_cart_orders') && (isset($_GET['s']) && $_GET['s'] != '')) {
        return "DISTINCT";
    }
    return $where;
}

add_filter('title_save_pre', 'wp_cart_save_title');

function wp_cart_save_title($post_title) {
    //this function replaces title with post_ID in wpsc_cart_orders to avoid WP from assigning "Auto Draft" title to the post
    if (isset($_POST['post_type']) && $_POST['post_type'] == 'wpsc_cart_orders') {
        $post_title = $_POST['post_ID'];
    }
    return $post_title;
}
