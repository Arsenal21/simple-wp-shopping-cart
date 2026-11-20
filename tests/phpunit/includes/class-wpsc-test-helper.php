<?php
/**
 * Helpers for the Simple Shopping Cart tests: cart items, a cart with saved items, and access to private properties.
 */
class WPSC_Test_Helper {

	/**
	 * A cart item like the add to cart handler creates. Prices are strings, like the posted form values.
	 */
	public static function make_item( $name, $price, $quantity = 1, $shipping = '0', $props = array() ) {
		$item = new WPSC_Cart_Item();
		$item->set_name( $name );
		$item->set_price( $price );
		$item->set_price_orig( $price );
		$item->set_quantity( $quantity );
		$item->set_shipping( $shipping );
		foreach ( $props as $prop => $value ) {
			$item->{'set_' . $prop}( $value );
		}
		return $item;
	}

	/**
	 * Gives the visitor's cart (the WPSC_Cart singleton) a cart order post, a cart ID and these items, and saves it
	 * the way the plugin does. It doesn't call create_cart(), because that sets a cookie (a PHP warning in PHPUnit).
	 *
	 * @return WPSC_Cart
	 */
	public static function set_up_cart( $items, $cart_id = '' ) {
		if ( '' === $cart_id ) {
			$cart_id = bin2hex( random_bytes( 16 ) );
		}
		$cart_cpt_id = wp_insert_post(
			array(
				'post_title'  => 'WPSC Cart Order',
				'post_type'   => WPSC_Cart::POST_TYPE,
				'post_status' => 'trash',
			)
		);
		update_post_meta( $cart_cpt_id, 'wpsc_cart_id', $cart_id );
		update_post_meta( $cart_cpt_id, 'wpsc_order_status', 'In Progress' );

		$cart = WPSC_Cart::get_instance();
		$cart->set_cart_id( $cart_id );
		$cart->set_cart_cpt_id( $cart_cpt_id );
		$cart->set_items( $items );
		return $cart;
	}

	/**
	 * Empties the visitor's cart (the WPSC_Cart singleton), so a test doesn't see the cart of the test before it.
	 */
	public static function reset_cart() {
		$cart = WPSC_Cart::get_instance();
		$cart->set_cart_id( 0 );
		$cart->set_cart_cpt_id( 0 );
		self::set_private( $cart, 'items', array() );
		self::set_private( $cart, 'store_pickup', false );
		$cart->set_selected_shipping_region( '' );
		$cart->set_selected_tax_region( '' );
		$cart->set_cart_custom_values( '' );
	}

	/**
	 * Runs the private WPSC_Cart constructor (it loads the cart from the simple_cart_id cookie), on a new object.
	 *
	 * @return WPSC_Cart
	 */
	public static function load_cart_from_cookie() {
		$class = new ReflectionClass( 'WPSC_Cart' );
		$cart  = $class->newInstanceWithoutConstructor();
		$constructor = $class->getConstructor();
		self::make_accessible( $constructor );
		$constructor->invoke( $cart );
		return $cart;
	}

	public static function get_private( $obj, $prop ) {
		$property = new ReflectionProperty( get_class( $obj ), $prop );
		self::make_accessible( $property );
		return $property->getValue( $obj );
	}

	public static function set_private( $obj, $prop, $value ) {
		$property = new ReflectionProperty( get_class( $obj ), $prop );
		self::make_accessible( $property );
		$property->setValue( $obj, $value );
	}

	/**
	 * setAccessible() is needed on PHP 7.4 and 8.0. It has no effect since PHP 8.1 and is deprecated in 8.5.
	 */
	private static function make_accessible( $reflector ) {
		if ( PHP_VERSION_ID < 80100 ) {
			$reflector->setAccessible( true );
		}
	}
}
