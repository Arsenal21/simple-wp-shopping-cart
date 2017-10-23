<?php

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
