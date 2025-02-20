<?php


class WPSC_Email_Handler {


    public static function process_order_data_for_email( $order_id ){
        $post_obj = get_post($order_id);
        $post_date = get_the_date("Y-m-d", $post_obj);

        return array(
            "first_name" => get_post_meta($order_id, 'wpsc_first_name', true),
            "last_name" => get_post_meta($order_id, 'wpsc_last_name', true),
            "payer_email" => get_post_meta($order_id, 'wpsc_email_address', true),
            "address" => get_post_meta($order_id, 'wpsc_address', true),
            "product_details" => get_post_meta($order_id, 'wpspsc_items_ordered', true),
            "transaction_id" => get_post_meta($order_id, 'wpsc_txn_id', true),
            "order_id" => $order_id,
            "purchase_amt" => get_post_meta($order_id, 'wpsc_total_amount', true),
            "purchase_date" => $post_date, // TODO: Need to decide which date to use
            "coupon_code" => get_post_meta($order_id, 'wpsc_applied_coupon', true),
        );
    }
    
    public static function apply_dynamic_tags($text, $data){
        $tags = array_map('WPSC_Email_Handler::create_tags', array_keys($data));
        
        $vals = array_values($data);
        
        return stripslashes(str_replace($tags, $vals, $text));
    }

    public static function create_tags($key){
        return '{'. $key .'}';
    }

    public static function send_purchase_notification_email_to_boyer($order_id){
        $wpsc_cart = WPSC_Cart::get_instance();
        $wpsc_cart->set_cart_id($order_id);
        $cart_items = $wpsc_cart->get_items();
        
        $order_data = WPSC_Email_Handler::process_order_data_for_email($order_id);
        
        $from_email = get_option( 'wpspc_buyer_from_email' );
        $subject = get_option( 'wpspc_buyer_email_subj' );
        $subject = WPSC_Email_Handler::apply_dynamic_tags( $subject, $order_data );
        
        $body = get_option( 'wpspc_buyer_email_body' );
        $args['email_body'] = $body;
        $body = WPSC_Email_Handler::apply_dynamic_tags( $body, $order_data );
        
        $is_html_content_type = get_option('wpsc_email_content_type') == 'html' ? true : false;
        
        wpsc_log_payment_debug( 'Applying filter - wpsc_buyer_notification_email_body', true );
        $body = apply_filters( 'wpsc_buyer_notification_email_body', $body, $order_data, $cart_items );
        
        $headers = array();
        $headers[] = 'From: ' . $from_email . "\r\n";
        if ( $is_html_content_type ) {
            $headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
            $body = nl2br( $body );
        }
        $buyer_email = $order_data['payer_email'];
        if ( is_email( $buyer_email ) ) {
            if (get_option( 'wpspc_send_buyer_email' )) {
                wp_mail( $buyer_email, $subject, $body, $headers );
                wpsc_log_payment_debug( 'Product Email successfully sent to ' . $buyer_email, true );
                update_post_meta( $order_id, 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email );
            }
        } else {
            throw new \Exception("Invalid buyer email address!" );
        }
    }

    public static function send_purchase_notification_email_to_seller($order_id){
        $wpsc_cart = WPSC_Cart::get_instance();
        $wpsc_cart->set_cart_id($order_id);
        $cart_items = $wpsc_cart->get_items();

        $order_data = WPSC_Email_Handler::process_order_data_for_email($order_id);

        $from_email = get_option( 'wpspc_buyer_from_email' );

        $notify_email = get_option( 'wpspc_notify_email_address' );
        $seller_email_subject = get_option( 'wpspc_seller_email_subj' );
        $seller_email_subject = WPSC_Email_Handler::apply_dynamic_tags( $seller_email_subject, $order_data);

        $seller_email_body = get_option( 'wpspc_seller_email_body' );
        $seller_email_body = WPSC_Email_Handler::apply_dynamic_tags( $seller_email_body, $order_data);

        $is_html_content_type = get_option('wpsc_email_content_type') == 'html' ? true : false;

        wpsc_log_payment_debug( 'Applying filter - wpsc_seller_notification_email_body', true );
        $seller_email_body = apply_filters( 'wpsc_seller_notification_email_body', $seller_email_body, $order_data, $cart_items );

        $headers = array();
        $headers[] = 'From: ' . $from_email . "\r\n";
        if ( $is_html_content_type ) {
            $headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
            $seller_email_body = nl2br( $seller_email_body );
        }
        if ( is_email( $notify_email ) ) {
            if (get_option( 'wpspc_send_seller_email' )) {
                wp_mail( $notify_email, $seller_email_subject, $seller_email_body, $headers );
                wpsc_log_payment_debug( 'Notify Email successfully sent to ' . $notify_email, true );
            }
        } else {
            throw new \Exception("Invalid seller email address!" );
        }
    }
}