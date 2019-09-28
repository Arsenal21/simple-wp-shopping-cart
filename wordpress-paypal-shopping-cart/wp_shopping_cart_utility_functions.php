<?php

function wspsc_log_payment_debug($message, $success, $end = false) {
    $logfile = WP_CART_PATH . 'ipn_handle_debug.txt';
    $debug = get_option( 'wp_shopping_cart_enable_debug' );
    if ( !$debug ) {
        //Debug is not enabled.
        return;
    }
    
    // Timestamp
    $text = '[' . date('m/d/Y g:i A') . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . $message . "\n";
    if ($end) {
        $text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log
    $fp = fopen($logfile, 'a');
    fwrite($fp, $text);
    fclose($fp);
}

function wspsc_reset_logfile()
{
    $log_reset = true;
    $logfile = WP_CART_PATH . 'ipn_handle_debug.txt';
    $text = '['.date('m/d/Y g:i A').'] - SUCCESS : Log file reset';
    $text .= "\n------------------------------------------------------------------\n\n";
    $fp = fopen($logfile, 'w');
    if($fp != FALSE) {
            @fwrite($fp, $text);
            @fclose($fp);
    }
    else{
            $log_reset = false;	
    }
    return $log_reset;
}

function wpspc_get_total_cart_qty() {
    $total_items = 0;
    if (!isset($_SESSION['simpleCart'])) {
        return $total_items;
    }
    foreach ($_SESSION['simpleCart'] as $item) {
        $total_items += $item['quantity'];
    }
    return $total_items;
}

function wpspc_get_total_cart_sub_total() {
    $sub_total = 0;
    if (!isset($_SESSION['simpleCart'])) {
        return $sub_total;
    }
    foreach ($_SESSION['simpleCart'] as $item) {
        $sub_total += $item['price'] * $item['quantity'];
    }
    return $sub_total;
}

function wspsc_clean_incomplete_old_cart_orders() {
    //Empty any incomplete old cart orders (that are more than 12 hours old)
    global $wpdb;
    $specific_time = date('Y-m-d H:i:s', strtotime('-12 hours'));
    $wpdb->query(
            $wpdb->prepare("DELETE FROM $wpdb->posts 
                 WHERE post_type = %s 
                 AND post_status = %s 
                 AND post_date < %s 
                ", 'wpsc_cart_orders', 'trash', $specific_time
            )
    );
}
