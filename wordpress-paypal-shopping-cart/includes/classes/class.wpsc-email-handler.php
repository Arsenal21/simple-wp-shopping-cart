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
            "purchase_date" => $post_date, // The date when order was placed by customer.
            "coupon_code" => get_post_meta($order_id, 'wpsc_applied_coupon', true),
        );
    }
    
    public static function apply_dynamic_tags($text, $data){
        $tags = array_map('WPSC_Email_Handler::create_tags', array_keys($data));
        
        $values = array_values($data);
        
        return stripslashes(str_replace($tags, $values, $text));
    }

    public static function create_tags($key){
        return '{'. $key .'}';
    }

    public static function is_html_content_type() {
	    return get_option('wpsc_email_content_type') == 'html';
    }

    public static function get_from_email() {
	    return get_option( 'wpspc_buyer_from_email' );
    }

    public static function send_buyer_sale_notification_email($order_id){
        $wpsc_cart = WPSC_Cart::get_instance();
        $wpsc_cart->set_cart_cpt_id($order_id);
        $cart_items = $wpsc_cart->get_items();
        
        $order_data = WPSC_Email_Handler::process_order_data_for_email($order_id);
        
        $subject = get_option( 'wpspc_buyer_email_subj', '' );
        $subject = WPSC_Email_Handler::apply_dynamic_tags( $subject, $order_data );
        
        $body = get_option( 'wpspc_buyer_email_body', '' );
        $body = WPSC_Email_Handler::apply_dynamic_tags( $body, $order_data );

        wpsc_log_payment_debug( 'Applying filter - wpsc_buyer_notification_email_body', true );
        $body = apply_filters( 'wpsc_buyer_notification_email_body', $body, $order_data, $cart_items );
        
        $headers = array();
        $headers[] = 'From: ' . self::get_from_email() . "\r\n";
        if ( self::is_html_content_type() ) {
            $headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
            $body = nl2br( $body );
        }

        $buyer_email = isset($order_data['payer_email']) ? sanitize_email($order_data['payer_email']) : '';
        if ( is_email( $buyer_email ) ) {
			wp_mail( $buyer_email, $subject, $body, $headers );
			wpsc_log_payment_debug( 'Sale Notification Email successfully sent to ' . $buyer_email, true );
			update_post_meta( $order_id, 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email );
        } else {
			wpsc_log_payment_debug( 'Email could not be sent to: '. $buyer_email, false );
            throw new \Exception(sprintf(__('Invalid email address: %s. Email could not be sent!', 'wordpress-simple-paypal-shopping-cart' ), $buyer_email));
        }
    }

	public static function send_manual_checkout_notification_emails( $order_id ) {
		$order_data = WPSC_Email_Handler::process_order_data_for_email($order_id);

		$buyer_email = isset($order_data['payer_email']) ? sanitize_email($order_data['payer_email']) : '';

		$send_buyer_payment_instruction_email = get_option( 'wpsc_send_buyer_payment_instruction_email' );
		if ( !empty( $send_buyer_payment_instruction_email ) ) {

			$subject = get_option( 'wpsc_buyer_payment_instruction_email_subject', '' );
			$subject = WPSC_Email_Handler::apply_dynamic_tags( $subject, $order_data );

			$body = get_option( 'wpsc_buyer_payment_instruction_email_body', '' );
			$body = WPSC_Email_Handler::apply_dynamic_tags( $body, $order_data );
			// wpsc_log_payment_debug($body, true);

			$headers = array();
			$headers[] = 'From: ' . self::get_from_email() . "\r\n";
			if ( self::is_html_content_type() ) {
				$headers = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
				$body = nl2br( $body );
			}

			if ( is_email( $buyer_email ) ) {
				wp_mail( $buyer_email, $subject, $body, $headers );
				wpsc_log_payment_debug( 'Payment Instruction Email successfully sent to: ' . $buyer_email, true );
				update_post_meta( $order_id, 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email );
			} else {
				wpsc_log_payment_debug( 'Payment Instruction Email could not be sent to: '. $buyer_email, false );
			}
		}

		$send_manual_checkout_notification_email_to_seller = get_option('wpsc_send_seller_manual_checkout_notification_email');
        if ( !empty($send_manual_checkout_notification_email_to_seller) ){
            // If the manual checkout notify email is empty, then use the notify email address configured from the 'Email Settings' manu.
            $default_notify_email = get_option( 'wpspc_notify_email_address' );
			$notify_email = get_option( 'wpsc_seller_manual_checkout_notification_email_address', $default_notify_email );

			$seller_email_subject = get_option( 'wpsc_seller_manual_checkout_notification_email_subject', '' );
			$seller_email_subject = WPSC_Email_Handler::apply_dynamic_tags( $seller_email_subject, $order_data );

			$seller_email_body = get_option( 'wpsc_seller_manual_checkout_notification_email_body', '' );
			$seller_email_body = WPSC_Email_Handler::apply_dynamic_tags( $seller_email_body, $order_data );

	        $headers = array();
	        $headers[] = 'From: ' . self::get_from_email() . "\r\n";
			if ( self::is_html_content_type() ) {
				$headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
				$seller_email_body = nl2br( $seller_email_body );
			}

			if ( is_email( $notify_email ) ) {
				wp_mail( $notify_email, $seller_email_subject, $seller_email_body, $headers );
				wpsc_log_payment_debug( 'Manual Checkout seller notification email successfully sent to: ' . $notify_email, true );
			} else {
				wpsc_log_payment_debug( 'Manual Checkout seller notification email could not be sent to: '. $notify_email, false );
			}
		}
	}

	public static function get_email_merge_tags_hints(){
		ob_start();
		?>
		<p class="description"><?php _e("This is the body of the email that will be sent. Do not change the text within the braces {}. You can use the following email tags in this email body field:", "wordpress-simple-paypal-shopping-cart");?>
			<br />{first_name} – <?php _e("First name of the buyer", "wordpress-simple-paypal-shopping-cart");?>
			<br />{last_name} – <?php _e("Last name of the buyer", "wordpress-simple-paypal-shopping-cart");?>
			<br />{payer_email} – <?php _e("Email Address of the buyer", "wordpress-simple-paypal-shopping-cart");?>
			<br />{address} – <?php _e("Address of the buyer", "wordpress-simple-paypal-shopping-cart");?>
			<br />{product_details} – <?php _e("The item details of the purchased product (this will include the download link for digital items).", "wordpress-simple-paypal-shopping-cart");?>
			<br />{transaction_id} – <?php _e("The unique transaction ID of the purchase", "wordpress-simple-paypal-shopping-cart");?>
			<br />{order_id} – <?php _e("The order ID reference of this transaction in the cart orders menu", "wordpress-simple-paypal-shopping-cart");?>
			<br />{purchase_amt} – <?php _e("The amount paid for the current transaction", "wordpress-simple-paypal-shopping-cart");?>
			<br />{purchase_date} – <?php _e("The date of the purchase", "wordpress-simple-paypal-shopping-cart");?>
			<br />{coupon_code} – <?php _e("Coupon code applied to the purchase", "wordpress-simple-paypal-shopping-cart");?>
		</p>
		<?php
		return ob_get_clean();
	}
}