<?php

class WPSC_Admin_Ajax {

    public function __construct()
    {
        add_action("wp_ajax_wpsc_resend_sale_notification_email", array($this, 'wpsc_resend_sale_notification_email'));
    }

    public function wpsc_resend_sale_notification_email(){
        if ( !isset($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'], 'wpsc_resend_sale_notification_email') ){
            wp_send_json_error(array(
                "message" => __( 'Nonce verification failed!', 'wordpress-simple-paypal-shopping-cart' ),
            ));
        }

        $order_id = isset($_POST["order_id"]) ? sanitize_text_field($_POST["order_id"]) : '';
        if (is_null(get_post( $order_id ))) {
            wp_send_json_error(array(
                "message" => __( 'Invalid order id provided!', 'wordpress-simple-paypal-shopping-cart' ),
            ));
        }

        try {
            // Send buyer email address.
            WPSC_Email_Handler::send_purchase_notification_email_to_boyer($order_id);

        } catch (\Exception $e) {
            wp_send_json_error(array(
                "message" => $e->getMessage(),
            ));
        }

        wp_send_json_success(array(
            "message" => __( 'Notification emails sent successfully!', 'wordpress-simple-paypal-shopping-cart' ),
        ));
    }

}

new WPSC_Admin_Ajax();