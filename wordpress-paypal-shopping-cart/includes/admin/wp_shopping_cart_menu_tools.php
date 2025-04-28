<?php

function wpsc_show_tools_menu_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this settings page.');
    }

    echo '<div class="wrap">';
    echo '<h1>' . (__("Simple Shopping Cart Tools", "wordpress-simple-paypal-shopping-cart")) . '</h1>';
    
    echo '<div id="poststuff"><div id="post-body">';
    
    //Show the documentation message
    wpsc_settings_menu_documentation_msg();
    ?>

    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php _e("Export Cart Orders Data", "wordpress-simple-paypal-shopping-cart"); ?></label></h3>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('wspsc_tools_export_orders_data'); ?>

                <p><?php _e("You can use this option to export all the orders data to a CSV/Excel file.", "wordpress-simple-paypal-shopping-cart"); ?></p>
                <div class="submit">
                    <input type="submit" name="wspsc_export_orders_data" class="button-primary" value="<?php echo (__("Export Data", "wordpress-simple-paypal-shopping-cart")) ?>" />
                </div>

            </form>
        </div>
    </div>
    <?php
    
    wpsc_settings_menu_footer();
    
    echo '</div></div>';//End of poststuff and post-body
    echo '</div>';//End of wrap
    
}

function wpsc_check_and_handle_csv_export(){
    if (isset($_POST['wspsc_export_orders_data'])) {
        //CSV export form submitted. handle the CSV export.
        
        $nonce = $_REQUEST['_wpnonce'];
        if (!wp_verify_nonce($nonce, 'wspsc_tools_export_orders_data')) {
            wp_die('Error! Nonce Security Check Failed! Go back to Tools menu and try again.');
        }

        //Export and stream the file to the browser.
        wpsc_export_orders_data_to_csv();
    }
}

function wpsc_export_orders_data_to_csv(){
    // No point in creating the export file on the file-system. We'll stream
    // it straight to the browser. Much nicer.

    // Open the output stream
    $fh = fopen('php://output', 'w');
    
    $header_names = array("Order ID", "Transaction ID", "Date", "First Name", "Last Name", "Email", "IP Address", "Total", "Shipping", "Coupon Code", "Address", "Items Orders");

    $header_names = apply_filters( 'wpspc_export_csv_header', $header_names ); // TODO: Old hook. Need to remove this.
    $header_names = apply_filters( 'wpsc_export_csv_header', $header_names );

    // Start output buffering (to capture stream contents)
    ob_start();
    
    fputcsv($fh, $header_names);
    
    $query_args = array(
        'post_type' => WPSC_Cart::POST_TYPE,
        'numberposts' => -1, /* to retrieve all posts */
        'orderby' => 'date',
	'order' => 'DESC',
    );
    $posts_array = get_posts( $query_args );
    
    foreach ($posts_array as $item) {
        $order_id = $item->ID;
        $txn_id = get_post_meta( $order_id, 'wpsc_txn_id', true );
        $order_date = $item->post_date;
        $first_name = get_post_meta( $order_id, 'wpsc_first_name', true );
        $last_name = get_post_meta( $order_id, 'wpsc_last_name', true );
        $email = get_post_meta( $order_id, 'wpsc_email_address', true );
        $ip_address = get_post_meta( $order_id, 'wpsc_ipaddress', true );
        $total_amount = get_post_meta( $order_id, 'wpsc_total_amount', true );
        $shipping_amount = get_post_meta( $order_id, 'wpsc_shipping_amount', true );
        $address = get_post_meta( $order_id, 'wpsc_address', true );
        $phone = get_post_meta( $order_id, 'wpspsc_phone', true );      
        $applied_coupon = get_post_meta( $order_id, 'wpsc_applied_coupon', true );

        $items_ordered = get_post_meta( $order_id, 'wpspsc_items_ordered', true );
        $items_ordered = str_replace(array("\n", "\r", "\r\n", "\n\r"), ' ', $items_ordered);

        $fields = array($order_id, $txn_id, $order_date, $first_name, $last_name, $email, $ip_address, $total_amount, $shipping_amount, $applied_coupon, $address, $items_ordered);
	
        $fields = apply_filters( 'wpspc_export_csv_data', $fields, $order_id ); // TODO: Old hook. Need to remove this.
        $fields = apply_filters( 'wpsc_export_csv_data', $fields, $order_id );

        fputcsv($fh, $fields);

    }
    
    // Get the contents of the output buffer
    $string = ob_get_clean();

    $filename = 'exported_orders_data_'. date('Ymd');
            
    // Output CSV-specific headers
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv";');
    header('Content-Transfer-Encoding: binary');

    // Stream the CSV data
    exit($string);
}