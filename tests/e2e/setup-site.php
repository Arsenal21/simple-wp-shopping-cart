<?php
/**
 * Endpoint tests: creates the test data on the test site. Run with: wp eval-file tests/e2e/setup-site.php
 * Prints a JSON line with the page IDs and URLs the tests need (the last line of the output).
 */

//Plugin settings: PayPal Standard in sandbox mode, debug logging (the log helps when a test fails), shipping, coupons,
//manual checkout and the emails.
$settings = array(
	'cart_paypal_email'                                     => 'seller@example.com',
	'cart_payment_currency'                                 => 'USD',
	'cart_currency_symbol'                                  => '$',
	'wp_shopping_cart_enable_sandbox'                       => '1',
	'wp_shopping_cart_enable_debug'                         => '1',
	'cart_base_shipping_cost'                               => '1',
	'wpspsc_enable_coupon'                                  => '1',
	'wpsc_enable_manual_checkout'                           => '1',
	'wpspc_send_buyer_email'                                => '1',
	'wpspc_send_seller_email'                               => '1',
	'wpspc_buyer_from_email'                                => 'E2E Shop <shop@example.com>',
	'wpspc_notify_email_address'                            => 'notify@example.com',
	'wpspc_buyer_email_subj'                                => 'Thank you for the purchase',
	'wpspc_buyer_email_body'                                => "Dear {first_name} {last_name}\n\n{product_details}\n\nTransaction: {transaction_id}",
	'wpspc_seller_email_subj'                               => 'Notification of product sale',
	'wpspc_seller_email_body'                               => "Sale to {payer_email}\n\n{product_details}",
	'wpsc_send_buyer_payment_instruction_email'             => '1',
	'wpsc_buyer_payment_instruction_email_subject'          => 'Payment instructions',
	'wpsc_buyer_payment_instruction_email_body'             => "Please pay for order {order_id}.\n\n{product_details}",
	'wpsc_send_seller_manual_checkout_notification_email'   => '1',
	'wpsc_seller_manual_checkout_notification_email_subject' => 'New manual checkout order',
	'wpsc_seller_manual_checkout_notification_email_body'   => "Order {order_id}\n\n{product_details}",
);
foreach ( $settings as $name => $value ) {
	update_option( $name, $value );
}

//A 10% coupon.
$coupons = new WPSPSC_Coupons_Collection();
$coupons->add_coupon_item( new WPSPSC_COUPON_ITEM( 'E2E10', '10', '' ) );
WPSPSC_Coupons_Collection::save_object( $coupons );

//The pages are by the administrator (user 1), so the add to cart buttons save their products (only trusted authors can).
$shop_page = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_title'   => 'E2E Shop',
		'post_content' => '[wp_cart_button name="E2E Shirt" price="20" shipping="2" item_number="SHIRT-1" var1="Size|Small|Large::5"]'
			. "\n\n" . '[wp_cart_button name="E2E Ebook" price="9.99" file_url="https://example.com/e2e-ebook.pdf" digital="1"]',
	)
);
$cart_page = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_title'   => 'E2E Cart',
		'post_content' => '[wpsc_show_shopping_cart]',
	)
);
update_option( 'cart_checkout_page_url', get_permalink( $cart_page ) );

echo "\n" . wp_json_encode(
	array(
		'shop_page'  => $shop_page,
		'shop_url'   => get_permalink( $shop_page ),
		'cart_page'  => $cart_page,
		'cart_url'   => get_permalink( $cart_page ),
		'return_url' => get_option( 'cart_return_from_paypal_url' ),
	)
) . "\n";
