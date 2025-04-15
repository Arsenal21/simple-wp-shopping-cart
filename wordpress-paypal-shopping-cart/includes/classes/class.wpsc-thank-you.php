<?php

class WPSC_Thank_You {
	/**
	 * Outputs the order summary for thank you page.
	 */
	public static function wpsc_ty_output_order_summary( $order_id ) {
		$payment_gateway  = get_post_meta( $order_id, 'wpsc_payment_gateway', true );
		$email            = get_post_meta( $order_id, 'wpsc_email_address', true );
		$total_amount     = get_post_meta( $order_id, 'wpsc_total_amount', true );
		$shipping_amount  = get_post_meta( $order_id, 'wpsc_shipping_amount', true );
		$tax_amount  = get_post_meta( $order_id, 'wpsc_tax_amount', true );
		$shipping_region  = get_post_meta( $order_id, 'wpsc_shipping_region', true );
		$shipping_address = get_post_meta( $order_id, 'wpsc_address', true ); // Using shipping address in wpsc_address post meta. This meta-key hasn't changed for backward compatibility.
		$billing_address  = get_post_meta( $order_id, 'wpsc_billing_address', true );
		$wpsc_order_status  = get_post_meta( $order_id, 'wpsc_order_status', true );
        
		// Check if the order status is confirmed. For the case of PayPal Standard, the order status is not confirmed until the IPN is received.
        if ($payment_gateway == 'manual'){
            // skip order status check for manual checkout.
        } else if ( empty( $wpsc_order_status ) || strtolower( sanitize_text_field($wpsc_order_status) ) != 'paid' ) {
			$output = '';
			$output .= '<div style="background-color: #FFFFE0; border: 1px solid #E6DB55; padding: 8px  14px;">';
            $output .= '<p>' . __( 'Our system is currently awaiting payment confirmation from the payment gateway. Please wait a few minutes and refresh this page. You may also navigate away, as we will send you an email once the payment confirmation is received.', "wordpress-simple-paypal-shopping-cart" ) . '</p>';
			$output .= '</div>';
			echo $output;
			return;
		}

		$purchase_data = ( new DateTime( get_post_field( 'post_date', $order_id ) ) )->format( "j M, Y" );

		$downloadable_items = array();
		$cart_items         = get_post_meta( $order_id, 'wpsc_cart_items', true );
		$cart_items_array   = array();
		foreach ( $cart_items as $cart_item ) {
			$cart_items_array[] = array(
				'product_name' => $cart_item->get_name(),
				'quantity'     => $cart_item->get_quantity(),
				'unit_price'   => print_payment_currency( $cart_item->get_price(), WP_CART_CURRENCY_SYMBOL ),
			);

			// Check and collect downloadable files data.
			if ( ! empty( $cart_item->get_file_url() ) ) {
				$downloadable_items[] = array(
					'product_name' => $cart_item->get_name(),
					'file_url'     => $cart_item->get_file_url(),
				);
			}
		}

		?>

        <div>
            <h4><?php _e( "Thank you. Your order has been received.", "wordpress-simple-paypal-shopping-cart" ); ?></h4>
            <div class="wpsc-order-data-box">
                <div class="wpsc-order-data-box-col">
                    <div><?php _e( "Order ID", "wordpress-simple-paypal-shopping-cart" ); ?></div>
                    <div><?php echo esc_attr( $order_id ) ?></div>
                </div>
                <div class="wpsc-order-data-box-col">
                    <div><?php _e( "Date", "wordpress-simple-paypal-shopping-cart" ); ?></div>
                    <div><?php echo esc_attr( $purchase_data ) ?></div>
                </div>
                <div class="wpsc-order-data-box-col">
                    <div><?php _e( "Total", "wordpress-simple-paypal-shopping-cart" ); ?></div>
                    <div><?php echo esc_attr( print_payment_currency( $total_amount, WP_CART_CURRENCY_SYMBOL ) ) ?></div>
                </div>
                <div class="wpsc-order-data-box-col">
                    <div><?php _e( "Email", "wordpress-simple-paypal-shopping-cart" ); ?></div>
                    <div><?php echo esc_attr( $email ) ?></div>
                </div>
                <div class="wpsc-order-data-box-col">
                    <div><?php _e( "Payment Gateway", "wordpress-simple-paypal-shopping-cart" ); ?></div>
                    <div><?php echo esc_attr( wpsc_get_formatted_payment_gateway_name( $payment_gateway ) ) ?></div>
                </div>
            </div>

            <h4><?php _e( "Order Details", "wordpress-simple-paypal-shopping-cart" ); ?></h4>

            <table class="wpsc-order-details-table">
                <thead>
                <tr>
                    <th style="text-align: start"><?php _e( "Product", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                    <th style="text-align: end"><?php _e( "Total", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $cart_items_array as $item ) { ?>
                    <tr>
                        <td>
                            <?php echo esc_attr( $item['product_name'] ); ?> x <?php echo esc_attr( $item['quantity'] ); ?>
                        </td>
                        <td style="text-align: end"><?php echo esc_attr( $item['unit_price'] ); ?></td>
                    </tr>
				<?php } ?>
				<?php if ( ! empty( floatval( $shipping_amount ) ) ) { // Show this row if shipping amount is not 0.0 ?>
                    <tr>
                        <th style="text-align: start"><?php _e( "Shipping Amount: ", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td style="text-align: end"><?php echo esc_attr( print_payment_currency( $shipping_amount, WP_CART_CURRENCY_SYMBOL ) ); ?></td>
                    </tr>
				<?php } ?>
                <?php if ( ! empty( floatval( $tax_amount ) ) ) { // Show this row if tax amount is not 0.0 ?>
                    <tr>
                        <th style="text-align: start"><?php _e( "Tax Amount: ", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <td style="text-align: end"><?php echo esc_attr( print_payment_currency( $tax_amount, WP_CART_CURRENCY_SYMBOL ) ); ?></td>
                    </tr>
				<?php } ?>
                <tr>
                    <th style="text-align: start"><?php _e( "Total Amount: ", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                    <td style="text-align: end"><?php echo esc_attr( print_payment_currency( $total_amount, WP_CART_CURRENCY_SYMBOL ) ); ?></td>
                </tr>
                </tbody>
            </table>

			<?php if ( ! empty( $downloadable_items ) ) { ?>
                <h4><?php _e( "Downloads", "wordpress-simple-paypal-shopping-cart" ); ?></h4>
                <table class="wpsc-order-downloads-table">
                    <thead>
                    <tr>
                        <th style="text-align: start"><?php _e( "Product", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                        <th style="text-align: start"><?php _e( "Download Link", "wordpress-simple-paypal-shopping-cart" ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $downloadable_items as $downloadable_item ) { ?>
                        <tr>
                            <td><?php echo esc_attr( $downloadable_item['product_name'] ) ?></td>
                            <td><a href="<?php echo esc_url( $downloadable_item['file_url'] ) ?>"
                                   target="_blank"><?php _e( "Download", "wordpress-simple-paypal-shopping-cart" ) ?></a>
                            </td>
                        </tr>
					<?php } ?>
                    </tbody>
                </table>
			<?php } ?>

			<?php if ( ! empty( $shipping_address ) ) { ?>
                <div>
                    <h4><?php _e( "Shipping Address", "wordpress-simple-paypal-shopping-cart" ); ?></h4>
                    <div class="wpsc-order-shipping-address">
						<?php echo esc_attr( $shipping_address ); ?>
                    </div>
                </div>
			<?php } ?>

			<?php if ( ! empty( $shipping_region ) ) { ?>
                <div>
                    <h4><?php _e( "Shipping Region", "wordpress-simple-paypal-shopping-cart" ); ?></h4>
                    <div class="wpsc-order-shipping-region">
						<?php echo esc_attr( $shipping_region ); ?>
                    </div>
                </div>
			<?php } ?>

			<?php if ( ! empty( $billing_address ) ) { ?>
                <div>
                    <h4><?php _e( "Billing Address", "wordpress-simple-paypal-shopping-cart" ); ?></h4>
                    <div class="wpsc-order-billing-address">
						<?php echo esc_attr( $billing_address ); ?>
                    </div>
                </div>
			<?php } ?>
        </div>
		<?php
	}

}