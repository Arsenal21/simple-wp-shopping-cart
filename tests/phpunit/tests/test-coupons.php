<?php
/**
 * Discount coupons: wpsc_apply_cart_discount() and wpsc_reapply_discount_coupon_if_needed().
 * A coupon is a percentage off each item's original price.
 */
class Test_Coupons extends WP_UnitTestCase {

	const DELTA = 0.00001;

	public function set_up() {
		parent::set_up();
		WPSC_Test_Helper::reset_cart();

		$coupons = new WPSPSC_Coupons_Collection();
		$coupons->add_coupon_item( new WPSPSC_COUPON_ITEM( 'SAVE10', '10', '' ) );
		$coupons->add_coupon_item( new WPSPSC_COUPON_ITEM( 'SAVE15', '15', '2999-12-31' ) );
		$coupons->add_coupon_item( new WPSPSC_COUPON_ITEM( 'OLD20', '20', '2000-01-01' ) );
		$coupons->add_coupon_item( new WPSPSC_COUPON_ITEM( 'TODAY5', '5', gmdate( 'Y-m-d' ) ) );
		WPSPSC_Coupons_Collection::save_object( $coupons );
	}

	public function tear_down() {
		WPSC_Test_Helper::reset_cart();
		parent::tear_down();
	}

	private function set_up_cart() {
		return WPSC_Test_Helper::set_up_cart(
			array(
				WPSC_Test_Helper::make_item( 'Hoodie', '20', 2 ),
				WPSC_Test_Helper::make_item( 'Free Sample', '0', 1 ),
			)
		);
	}

	private function get_prices( $cart ) {
		$prices = array();
		foreach ( $cart->get_items() as $item ) {
			$prices[ $item->get_name() ] = (float) $item->get_price();
		}
		return $prices;
	}

	public function test_a_coupon_lowers_the_item_prices() {
		$cart = $this->set_up_cart();

		wpsc_apply_cart_discount( 'SAVE10' );

		$this->assertEquals( array( 'Hoodie' => 18.0, 'Free Sample' => 0.0 ), $this->get_prices( $cart ) );
		$this->assertStringContainsString( 'Discount applied successfully! Total Discount: $4.00', $cart->get_cart_action_msg() );
		$this->assertEqualsWithDelta( 36, $cart->calculate_cart_totals_and_postage(), self::DELTA );

		$coupons = WPSPSC_Coupons_Collection::get_instance();
		$this->assertSame( 'SAVE10', $coupons->get_applied_coupon_code( $cart->get_cart_cpt_id() ) );
		$this->assertSame( '1', $coupons->get_discount_applied_once( $cart->get_cart_cpt_id() ) );
	}

	public function test_the_coupon_code_is_not_case_sensitive() {
		$cart = $this->set_up_cart();

		wpsc_apply_cart_discount( 'save10' );

		$this->assertEquals( 18.0, $this->get_prices( $cart )['Hoodie'] );
	}

	public function test_the_discounted_price_is_rounded_to_the_cent() {
		$cart = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Book', '19.99', 1 ) ) );

		wpsc_apply_cart_discount( 'SAVE15' );

		$this->assertEquals( 16.99, $this->get_prices( $cart )['Book'], '19.99 - 15% (2.9985) = 16.9915, rounded to 16.99.' );
		$this->assertStringContainsString( 'Total Discount: $3.00', $cart->get_cart_action_msg() );
	}

	public function test_a_coupon_can_only_be_applied_once() {
		$cart = $this->set_up_cart();
		wpsc_apply_cart_discount( 'SAVE10' );

		wpsc_apply_cart_discount( 'SAVE15' );

		$this->assertStringContainsString( 'Discount can only be applied once per checkout!', $cart->get_cart_action_msg() );
		$this->assertEquals( 18.0, $this->get_prices( $cart )['Hoodie'], 'A second coupon changed the price.' );
	}

	/**
	 * @return array coupon code => error message
	 */
	public function rejected_coupons() {
		return array(
			'unknown code' => array( 'NOPE', 'Coupon code used does not exist!' ),
			'empty code'   => array( '', 'Coupon code used does not exist!' ),
			'expired'      => array( 'OLD20', 'Coupon code expired!' ),
		);
	}

	/**
	 * @dataProvider rejected_coupons
	 */
	public function test_a_rejected_coupon_does_not_change_the_prices( $code, $message ) {
		$cart = $this->set_up_cart();

		wpsc_apply_cart_discount( $code );

		$this->assertStringContainsString( $message, $cart->get_cart_action_msg() );
		$this->assertEquals( 20.0, $this->get_prices( $cart )['Hoodie'] );
		$this->assertFalse( WPSPSC_Coupons_Collection::get_instance()->get_discount_applied_once( $cart->get_cart_cpt_id() ) );
	}

	public function test_a_coupon_that_expires_today_can_be_used() {
		$cart = $this->set_up_cart();

		wpsc_apply_cart_discount( 'TODAY5' );

		$this->assertEquals( 19.0, $this->get_prices( $cart )['Hoodie'] );
	}

	public function test_no_coupon_is_found_when_none_are_set_up() {
		delete_option( 'wpspsc_coupons_collection' );
		delete_option( 'wpsc_coupons_collection' );
		$cart = $this->set_up_cart();

		wpsc_apply_cart_discount( 'SAVE10' );

		$this->assertStringContainsString( 'Coupon code used does not exist!', $cart->get_cart_action_msg() );
	}

	/**
	 * When the cart changes after a coupon was applied, the coupon is applied again to all items, from their original prices
	 * (so an item already in the cart is not discounted twice).
	 */
	public function test_the_coupon_is_applied_again_when_the_cart_changes() {
		$cart = $this->set_up_cart();
		wpsc_apply_cart_discount( 'SAVE10' );

		$items   = $cart->get_items();
		$items[] = WPSC_Test_Helper::make_item( 'Cap', '10', 1 );
		$cart->add_items( $items );
		wpsc_reapply_discount_coupon_if_needed();

		$this->assertEquals( array( 'Hoodie' => 18.0, 'Free Sample' => 0.0, 'Cap' => 9.0 ), $this->get_prices( $cart ) );
	}

	public function test_nothing_is_applied_again_when_no_coupon_was_used() {
		$cart = $this->set_up_cart();

		$this->assertFalse( wpsc_reapply_discount_coupon_if_needed() );
		$this->assertEquals( 20.0, $this->get_prices( $cart )['Hoodie'] );
	}

	public function test_emptying_the_cart_removes_the_coupon() {
		$cart = $this->set_up_cart();
		wpsc_apply_cart_discount( 'SAVE10' );

		$cart->reset_cart();

		$coupons = WPSPSC_Coupons_Collection::get_instance();
		$this->assertFalse( $coupons->get_applied_coupon_code( $cart->get_cart_cpt_id() ) );
		$this->assertFalse( $coupons->get_discount_applied_once( $cart->get_cart_cpt_id() ) );
		$this->assertFalse( $cart->get_items() );
	}
}
