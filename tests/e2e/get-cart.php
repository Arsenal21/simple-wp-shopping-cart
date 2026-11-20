<?php
/**
 * Endpoint tests: prints the saved cart of a cart ID as JSON. Run with: wp eval-file tests/e2e/get-cart.php <cart ID>
 * Reads it with the plugin's own code (the 'wpsc_cart_items' post meta, and the cart object with WPSC_Cart::get_cart_from_postmeta()).
 */
$cart_id     = isset( $args[0] ) ? $args[0] : '';
$cart_cpt_id = wpsc_get_cart_cpt_id_by_cart_id( $cart_id );

$result = array(
	'cart_cpt_id'  => $cart_cpt_id,
	'post_status'  => $cart_cpt_id ? get_post_status( $cart_cpt_id ) : '',
	'order_status' => $cart_cpt_id ? get_post_meta( $cart_cpt_id, 'wpsc_order_status', true ) : '',
	'items'        => array(),
	'object_items' => array(),
);

$describe = function ( $item ) {
	if ( ! $item instanceof WPSC_Cart_Item ) {
		return array( 'class' => is_object( $item ) ? get_class( $item ) : gettype( $item ) );
	}
	$file_url = $item->get_file_url();
	return array(
		'name'        => $item->get_name(),
		'price'       => (string) $item->get_price(),
		'quantity'    => (int) $item->get_quantity(),
		'shipping'    => (string) $item->get_shipping(),
		'item_number' => $item->get_item_number(),
		'stamp_pdf'   => $item->get_stamp_pdf(),
		'file_url'    => is_object( $file_url ) ? 'object: ' . get_class( $file_url ) : $file_url,
		'digital'     => $item->is_digital_item(),
	);
};

if ( $cart_cpt_id ) {
	$items = get_post_meta( $cart_cpt_id, 'wpsc_cart_items', true );
	foreach ( is_array( $items ) ? $items : array() as $item ) {
		$result['items'][] = $describe( $item );
	}

	$cart_object = WPSC_Cart::get_cart_from_postmeta( $cart_cpt_id );
	if ( $cart_object instanceof WPSC_Cart ) {
		$property = new ReflectionProperty( 'WPSC_Cart', 'items' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}
		foreach ( (array) $property->getValue( $cart_object ) as $item ) {
			$result['object_items'][] = $describe( $item );
		}
	}
}

echo wp_json_encode( $result );
