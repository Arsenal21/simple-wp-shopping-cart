<?php

/**
 * @deprecated This method has been deprecated. Use get_total_cart_qty() from WSPSC_Cart instead.
 * @return int Returns the total number of items in the cart.
 */
function wpspc_get_total_cart_qty() {
    $wspsc_cart = WSPSC_Cart::get_instance();
    $total_items = $wspsc_cart->get_total_cart_qty();
    return $total_items;
}

/**
 * @deprecated This method has been deprecated. Use get_total_cart_sub_total() from WSPSC_Cart instead.
 * @return int Returns the total number of items in the cart.
 */
function wpspc_get_total_cart_sub_total() {
    $wspsc_cart = WSPSC_Cart::get_instance();
    $total_sub_total = $wspsc_cart->get_total_cart_sub_total();
    return $total_sub_total;
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

function wspsc_check_and_start_session() {
	//No session needed for admin side. Only use session on front-end
	if ( is_admin() ) {
		return false;
	}

        //No session if wp_doing_ajax()
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return false;
	}

	//Don't start session if headers are already sent
	if ( headers_sent() ) {
		return false;
	}

	//Don't break Site Health test
        $request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_url( $_SERVER['REQUEST_URI'] ) : '';
	if ( ! empty( $request_uri ) && ! empty( strpos( strtolower( $request_uri ), 'wp-site-health/v1/tests/loopback-requests' ) ) ) {
		return false;
	}

	//Start session
        if ( session_status() == PHP_SESSION_NONE ) {
            session_start();
        }

    return true;
}