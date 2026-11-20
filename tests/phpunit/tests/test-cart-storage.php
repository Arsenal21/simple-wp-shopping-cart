<?php
/**
 * Saving and loading the visitor's cart (the WPSC_Cart object is saved, serialized, in the 'wpsc_cart_object' post meta),
 * and the simple_cart_id cookie that picks the cart. Fixed in 5.3.4: PHP object injection (Patchstack report).
 */

/**
 * A class with a __wakeup() method, like the "gadget" classes an attacker looks for in other plugins.
 */
class WPSC_Test_Gadget {
	public static $woken = false;

	public function __wakeup() {
		self::$woken = true;
	}
}

class Test_Cart_Storage extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		WPSC_Test_Helper::reset_cart();
		WPSC_Test_Gadget::$woken = false;
		unset( $_COOKIE['simple_cart_id'] );
	}

	public function tear_down() {
		WPSC_Test_Helper::reset_cart();
		unset( $_COOKIE['simple_cart_id'] );
		parent::tear_down();
	}

	public function test_the_cart_is_saved_and_loaded_unchanged() {
		$values = array(
			'quotes'      => 'Bob\'s "Special" SKU',
			'backslashes' => 'C:\\files\\stamp.pdf \\\' \\"',
			'serialized'  => '";s:4:"test";O:8:"stdClass":0:{}',
			'unicode'     => 'Café – 日本',
		);
		$items = array();
		foreach ( $values as $name => $value ) {
			$items[] = WPSC_Test_Helper::make_item( $name, '10', 2, '1', array( 'item_number' => $value, 'stamp_pdf' => $value ) );
		}
		$cart = WPSC_Test_Helper::set_up_cart( $items );
		$cart->set_selected_shipping_region( 'us:0' );
		$cart->save_cart_to_postmeta();

		$loaded = WPSC_Cart::get_cart_from_postmeta( $cart->get_cart_cpt_id() );

		$this->assertInstanceOf( 'WPSC_Cart', $loaded );
		$this->assertSame( 'us:0', $loaded->get_selected_shipping_region() );
		$loaded_items = WPSC_Test_Helper::get_private( $loaded, 'items' );
		$this->assertCount( 4, $loaded_items );
		foreach ( $loaded_items as $item ) {
			$this->assertInstanceOf( 'WPSC_Cart_Item', $item );
			$this->assertSame( $values[ $item->get_name() ], $item->get_item_number(), 'The item number changed: ' . $item->get_name() );
			$this->assertSame( $values[ $item->get_name() ], $item->get_stamp_pdf(), 'The stamp_pdf value changed: ' . $item->get_name() );
			$this->assertSame( 2, $item->get_quantity() );
		}
	}

	/**
	 * The payload from the Patchstack report: the item_number and stamp_pdf values that the old add to cart handler kept slashed.
	 * Before 5.3.4, saving the cart removed the backslashes from the serialized text, so the string lengths were wrong and
	 * stamp_pdf was read as serialized data with an object of the attacker's class.
	 */
	public function test_the_object_injection_payload_stays_plain_text() {
		$gadget  = 'O:16:"WPSC_Test_Gadget":0:{}';
		$payload = '";s:8:"file_url";' . $gadget . 's:9:"thumbnail";N;s:9:"stamp_pdf";N;s:12:"digital_flag";s:30:"';
		$payload_length = strlen( $payload ) + substr_count( $payload, '"' );
		$gap = '";s:11:"' . "\0*\0" . 'file_url";N;s:12:"' . "\0*\0" . 'thumbnail";N;s:12:"' . "\0*\0" . 'stamp_pdf";s:' . $payload_length . ':"';
		$item_number = addslashes( str_repeat( "'", strlen( $gap ) ) ); //As PHP/WordPress slash the posted values.
		$stamp_pdf   = addslashes( $payload );

		$cart = WPSC_Test_Helper::set_up_cart(
			array( WPSC_Test_Helper::make_item( 'Demo Product', '10', 1, '0', array( 'item_number' => $item_number, 'stamp_pdf' => $stamp_pdf ) ) )
		);

		$loaded = WPSC_Cart::get_cart_from_postmeta( $cart->get_cart_cpt_id() );

		$this->assertFalse( WPSC_Test_Gadget::$woken, 'An object of another class was created from the saved cart.' );
		$this->assertInstanceOf( 'WPSC_Cart', $loaded );
		$item = WPSC_Test_Helper::get_private( $loaded, 'items' )[0];
		$this->assertSame( $item_number, $item->get_item_number() );
		$this->assertSame( $stamp_pdf, $item->get_stamp_pdf() );
		$this->assertNull( $item->get_file_url() );
	}

	/**
	 * A cart saved with an object of another class in it (for example by the old code, or written to the database directly)
	 * loads that object as an incomplete class: its __wakeup() and __destruct() don't run.
	 */
	public function test_only_the_cart_classes_are_created_when_loading() {
		$cart = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Demo Product', '10' ) ) );
		$item = WPSC_Test_Helper::make_item( 'Injected', '1', 1, '0', array( 'file_url' => new WPSC_Test_Gadget() ) );
		WPSC_Test_Helper::set_private( $cart, 'items', array( $item ) );
		update_post_meta( $cart->get_cart_cpt_id(), 'wpsc_cart_object', wp_slash( serialize( $cart ) ) );
		WPSC_Test_Gadget::$woken = false;

		$loaded = WPSC_Cart::get_cart_from_postmeta( $cart->get_cart_cpt_id() );

		$this->assertFalse( WPSC_Test_Gadget::$woken, 'WPSC_Test_Gadget::__wakeup() ran.' );
		$this->assertInstanceOf( 'WPSC_Cart', $loaded );
		$loaded_item = WPSC_Test_Helper::get_private( $loaded, 'items' )[0];
		$this->assertInstanceOf( 'WPSC_Cart_Item', $loaded_item );
		$this->assertInstanceOf( '__PHP_Incomplete_Class', $loaded_item->get_file_url() );
	}

	/**
	 * Carts saved by 5.3.3 and older (without wp_slash()) still load after the update.
	 */
	public function test_a_cart_saved_by_an_older_version_still_loads() {
		$cart = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Old Cart Item', '25', 3, '0', array( 'item_number' => 'SKU-42' ) ) ) );
		update_post_meta( $cart->get_cart_cpt_id(), 'wpsc_cart_object', serialize( $cart ) ); //What 5.3.3 saved.

		$loaded = WPSC_Cart::get_cart_from_postmeta( $cart->get_cart_cpt_id() );

		$this->assertInstanceOf( 'WPSC_Cart', $loaded );
		$item = WPSC_Test_Helper::get_private( $loaded, 'items' )[0];
		$this->assertSame( 'SKU-42', $item->get_item_number() );
		$this->assertSame( 3, $item->get_quantity() );
	}

	public function test_nothing_is_loaded_when_the_cart_has_no_saved_data() {
		$post_id = self::factory()->post->create( array( 'post_type' => WPSC_Cart::POST_TYPE ) );

		$this->assertFalse( WPSC_Cart::get_cart_from_postmeta( $post_id ) );
	}

	/**
	 * @return array cart ID in the cookie
	 */
	public function valid_cart_ids() {
		return array(
			'32 hex characters (5.3.0 and newer)' => array( bin2hex( random_bytes( 16 ) ) ),
			'13 hex characters (older, uniqid())' => array( '5f3a9b2c1d4e6' ),
		);
	}

	/**
	 * @dataProvider valid_cart_ids
	 */
	public function test_the_cookie_loads_the_visitors_cart( $cart_id ) {
		$cart        = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Cookie Item', '12', 2 ) ), $cart_id );
		$cart_cpt_id = $cart->get_cart_cpt_id();
		WPSC_Test_Helper::reset_cart();
		$_COOKIE['simple_cart_id'] = $cart_id;

		$loaded = WPSC_Test_Helper::load_cart_from_cookie();

		$this->assertSame( $cart_id, $loaded->get_cart_id() );
		$this->assertSame( $cart_cpt_id, $loaded->get_cart_cpt_id() );
		$this->assertSame( 2, $loaded->get_total_cart_qty() );
		$this->assertEquals( 24, $loaded->get_total_cart_sub_total() );
	}

	/**
	 * @return array cookie value
	 */
	public function invalid_cookies() {
		return array(
			'SQL'             => array( "abc' OR '1'='1" ),
			'path'            => array( '../../wp-config' ),
			'upper case'      => array( 'ABCDEF0123456789ABCDEF0123456789' ),
			'too long'        => array( str_repeat( 'a', 65 ) ),
			'array'           => array( array( 'abc' ) ),
			'empty'           => array( '' ),
		);
	}

	/**
	 * @dataProvider invalid_cookies
	 */
	public function test_an_invalid_cookie_is_ignored( $cookie ) {
		$_COOKIE['simple_cart_id'] = $cookie;

		$loaded = WPSC_Test_Helper::load_cart_from_cookie();

		$this->assertSame( 0, $loaded->get_cart_id() );
		$this->assertSame( 0, $loaded->get_cart_cpt_id() );
		$this->assertFalse( $loaded->get_items() );
	}

	/**
	 * @return array order status
	 */
	public function placed_order_statuses() {
		return array(
			'paid'                           => array( 'Paid' ),
			'pending (manual checkout)'      => array( 'Pending' ),
		);
	}

	/**
	 * Fixed after 5.3.4: a buyer who paid with PayPal Standard but didn't come back to the return URL still has the cookie of the
	 * paid cart. The next page view must start a new cart, and the order must stay unchanged (not deleted).
	 *
	 * @dataProvider placed_order_statuses
	 */
	public function test_the_cookie_of_a_placed_order_starts_a_new_cart( $status ) {
		$cart        = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Paid Item', '30', 1 ) ) );
		$cart_id     = $cart->get_cart_id();
		$cart_cpt_id = $cart->get_cart_cpt_id();
		update_post_meta( $cart_cpt_id, 'wpsc_order_status', $status );
		WPSC_Test_Helper::reset_cart();
		$_COOKIE['simple_cart_id'] = $cart_id;

		$loaded = WPSC_Test_Helper::load_cart_from_cookie();

		$this->assertSame( 0, $loaded->get_cart_id(), 'The placed order was loaded as the cart.' );
		$this->assertSame( 0, $loaded->get_cart_cpt_id() );
		$this->assertFalse( $loaded->get_items() );
		$this->assertNotNull( get_post( $cart_cpt_id ), 'The order was deleted.' );
		$this->assertSame( $status, get_post_meta( $cart_cpt_id, 'wpsc_order_status', true ) );
		$this->assertCount( 1, get_post_meta( $cart_cpt_id, 'wpsc_cart_items', true ), 'The order items changed.' );
	}

	/**
	 * Carts are saved with the "In Progress" status. A cart order without a status (older data) still loads.
	 */
	public function test_a_cart_without_an_order_status_still_loads() {
		$cart    = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Old Item', '5', 1 ) ) );
		$cart_id = $cart->get_cart_id();
		delete_post_meta( $cart->get_cart_cpt_id(), 'wpsc_order_status' );
		WPSC_Test_Helper::reset_cart();
		$_COOKIE['simple_cart_id'] = $cart_id;

		$loaded = WPSC_Test_Helper::load_cart_from_cookie();

		$this->assertSame( $cart_id, $loaded->get_cart_id() );
		$this->assertSame( 1, $loaded->get_total_cart_qty() );
	}

	public function test_a_cookie_for_a_cart_that_does_not_exist_gives_an_empty_cart() {
		$_COOKIE['simple_cart_id'] = bin2hex( random_bytes( 16 ) );

		$loaded = WPSC_Test_Helper::load_cart_from_cookie();

		$this->assertSame( 0, $loaded->get_cart_id() );
		$this->assertFalse( $loaded->get_items() );
	}

	/**
	 * When the saved data isn't a cart object, the cart order is deleted and the visitor gets an empty cart.
	 */
	public function test_a_cart_whose_saved_data_is_not_a_cart_is_reset() {
		$cart        = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Broken', '5' ) ) );
		$cart_id     = $cart->get_cart_id();
		$cart_cpt_id = $cart->get_cart_cpt_id();
		update_post_meta( $cart_cpt_id, 'wpsc_cart_object', wp_slash( serialize( array( 'not', 'a', 'cart' ) ) ) );
		WPSC_Test_Helper::reset_cart();
		$_COOKIE['simple_cart_id'] = $cart_id;

		$loaded = WPSC_Test_Helper::load_cart_from_cookie();

		$this->assertSame( 0, $loaded->get_cart_id() );
		$this->assertNull( get_post( $cart_cpt_id ), 'The cart order with broken data was not deleted.' );
	}
}
