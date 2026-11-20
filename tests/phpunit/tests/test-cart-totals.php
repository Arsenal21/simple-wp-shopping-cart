<?php
/**
 * Cart totals: WPSC_Cart::calculate_cart_totals_and_postage() (subtotal, shipping, tax, grand total).
 */
class Test_Cart_Totals extends WP_UnitTestCase {

	const DELTA = 0.00001;

	public function set_up() {
		parent::set_up();
		WPSC_Test_Helper::reset_cart();
	}

	public function tear_down() {
		WPSC_Test_Helper::reset_cart();
		parent::tear_down();
	}

	/**
	 * Two items with item shipping: subtotal 2 x 10.50 + 5.00 = 26.00, item shipping 2 x 1.00 + 0.50 = 2.50.
	 */
	private function set_up_physical_cart() {
		return WPSC_Test_Helper::set_up_cart(
			array(
				WPSC_Test_Helper::make_item( 'T-Shirt', '10.50', 2, '1' ),
				WPSC_Test_Helper::make_item( 'Sticker', '5', 1, '0.50' ),
			)
		);
	}

	public function test_an_empty_cart_has_no_total() {
		$this->assertSame( 0, WPSC_Cart::get_instance()->calculate_cart_totals_and_postage() );
	}

	public function test_subtotal_item_shipping_and_base_shipping() {
		update_option( 'cart_base_shipping_cost', '3' );
		$cart = $this->set_up_physical_cart();

		$grand_total = $cart->calculate_cart_totals_and_postage();

		$this->assertEqualsWithDelta( 31.5, $grand_total, self::DELTA );
		$this->assertSame( '26.00', $cart->get_sub_total_formatted() );
		$this->assertEqualsWithDelta( 2.5, $cart->get_item_shipping_total(), self::DELTA, 'Item shipping is per item and multiplied by the quantity.' );
		$this->assertEqualsWithDelta( 5.5, $cart->get_total_shipping_cost(), self::DELTA, 'Item shipping + base shipping.' );
		$this->assertSame( '5.50', $cart->get_postage_cost_formatted() );
		$this->assertSame( '31.50', $cart->get_grand_total_formatted() );
		$this->assertSame( 3, $cart->get_total_cart_qty() );
	}

	/**
	 * The base shipping cost is only added when an item in the cart has a shipping cost (for example not for digital products).
	 */
	public function test_no_shipping_when_no_item_has_a_shipping_cost() {
		update_option( 'cart_base_shipping_cost', '3' );
		$cart = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Ebook', '9.99', 2, '0', array( 'digital_flag' => '1' ) ) ) );

		$this->assertEqualsWithDelta( 19.98, $cart->calculate_cart_totals_and_postage(), self::DELTA );
		$this->assertSame( '0.00', $cart->get_postage_cost_formatted() );
		$this->assertTrue( $cart->all_cart_items_digital() );
	}

	public function test_free_shipping_when_the_subtotal_is_over_the_threshold() {
		update_option( 'cart_base_shipping_cost', '3' );
		$cart = $this->set_up_physical_cart();

		update_option( 'cart_free_shipping_threshold', '25' );
		$this->assertEqualsWithDelta( 26, $cart->calculate_cart_totals_and_postage(), self::DELTA, 'Subtotal 26 is over 25: shipping must be free.' );
		$this->assertSame( '0.00', $cart->get_postage_cost_formatted() );

		update_option( 'cart_free_shipping_threshold', '26' );
		$this->assertEqualsWithDelta( 31.5, $cart->calculate_cart_totals_and_postage(), self::DELTA, 'Subtotal equal to the threshold: shipping is not free (it must be over the threshold).' );
	}

	/**
	 * The tax is a percentage of the subtotal. Shipping is not taxed.
	 */
	public function test_tax_is_added_on_the_subtotal() {
		update_option( 'cart_base_shipping_cost', '3' );
		update_option( 'wpsc_tax_percentage', '10' );
		$cart = $this->set_up_physical_cart();

		$this->assertEqualsWithDelta( 34.1, $cart->calculate_cart_totals_and_postage(), self::DELTA );
		$this->assertEquals( 10, $cart->get_tax() );
		$this->assertEqualsWithDelta( 2.6, $cart->get_tax_amount(), self::DELTA );
		$this->assertSame( '2.60', $cart->get_tax_amount_formatted() );
		$this->assertSame( '34.10', $cart->get_grand_total_formatted() );
	}

	/**
	 * @return array selected tax region => tax percentage used
	 */
	public function tax_regions() {
		return array(
			'country'                         => array( 'us:0', 8 ),
			'state'                           => array( 'california:1', 9.5 ),
			'region not in the settings'      => array( 'gb:0', 10 ),
			'region type does not match'      => array( 'us:1', 10 ),
			'no region selected'              => array( '', 10 ),
			'region selected without a value' => array( '-1', 10 ),
		);
	}

	/**
	 * @dataProvider tax_regions
	 */
	public function test_tax_by_region( $selected_region, $tax_percentage ) {
		update_option( 'wpsc_tax_percentage', '10' );
		update_option( 'wpsc_enable_tax_by_region', '1' );
		update_option(
			'wpsc_tax_region_variations',
			array(
				array( 'type' => '0', 'loc' => 'US', 'amount' => 8.0 ),
				array( 'type' => '1', 'loc' => 'California', 'amount' => 9.5 ),
			)
		);
		$cart = $this->set_up_physical_cart();
		$cart->set_selected_tax_region( $selected_region );

		$cart->calculate_cart_totals_and_postage();

		$this->assertEquals( $tax_percentage, $cart->get_tax() );
		$this->assertEqualsWithDelta( 26 * $tax_percentage / 100, $cart->get_tax_amount(), self::DELTA );
	}

	public function test_shipping_by_region_is_added_to_the_shipping() {
		update_option( 'cart_base_shipping_cost', '3' );
		update_option( 'enable_shipping_by_region', '1' );
		update_option( 'wpsc_shipping_region_variations', array( array( 'type' => '0', 'loc' => 'GB', 'amount' => 7.0 ) ) );
		$cart = $this->set_up_physical_cart();

		$cart->set_selected_shipping_region( 'gb:0' );
		$this->assertEqualsWithDelta( 38.5, $cart->calculate_cart_totals_and_postage(), self::DELTA );
		$this->assertEqualsWithDelta( 12.5, $cart->get_total_shipping_cost(), self::DELTA, 'Item shipping 2.50 + base 3 + region 7.' );

		$cart->set_selected_shipping_region( 'fr:0' );
		$this->assertEqualsWithDelta( 31.5, $cart->calculate_cart_totals_and_postage(), self::DELTA, 'A region that is not in the settings must add nothing.' );
	}

	public function test_shipping_by_region_is_not_added_when_no_item_has_a_shipping_cost() {
		update_option( 'enable_shipping_by_region', '1' );
		update_option( 'wpsc_shipping_region_variations', array( array( 'type' => '0', 'loc' => 'GB', 'amount' => 7.0 ) ) );
		$cart = WPSC_Test_Helper::set_up_cart( array( WPSC_Test_Helper::make_item( 'Ebook', '9.99' ) ) );
		$cart->set_selected_shipping_region( 'gb:0' );

		$this->assertEqualsWithDelta( 9.99, $cart->calculate_cart_totals_and_postage(), self::DELTA );
	}

	public function test_store_pickup_removes_the_shipping() {
		update_option( 'cart_base_shipping_cost', '3' );
		$cart = $this->set_up_physical_cart();

		$cart->set_store_pickup( true );
		$this->assertEqualsWithDelta( 31.5, $cart->calculate_cart_totals_and_postage(), self::DELTA, 'Store pickup is ignored when it is turned off in the settings.' );

		update_option( 'wpsc_enable_store_pickup', '1' );
		$cart->set_store_pickup( true );
		$this->assertEqualsWithDelta( 26, $cart->calculate_cart_totals_and_postage(), self::DELTA );
		$this->assertSame( '0.00', $cart->get_postage_cost_formatted() );
	}

	public function test_the_postage_can_be_changed_with_a_filter() {
		$postage = function () {
			return 4.25;
		};
		add_filter( 'wpsc_custom_postage_cost', $postage );
		$cart = $this->set_up_physical_cart();

		$grand_total = $cart->calculate_cart_totals_and_postage();

		remove_filter( 'wpsc_custom_postage_cost', $postage );
		$this->assertEqualsWithDelta( 30.25, $grand_total, self::DELTA );
	}

	public function test_price_formatting() {
		$this->assertSame( '$1,234.50', print_payment_currency( 1234.5, '$' ) );
		$this->assertSame( '€0.99', print_payment_currency( '0.99', '€' ) );
		$this->assertSame( '$10,00', print_payment_currency( 10, '$', ',' ) );
		$this->assertSame( '12.30', wpsc_number_format_price( 12.3 ) );
		$this->assertSame( '1234.57', wpsc_number_format_price( 1234.567 ), 'No thousands separator (this value is sent to the payment gateways).' );
	}

	public function test_currency_symbols_are_removed_from_a_shortcode_price() {
		$this->assertSame( '10', wpsc_strip_char_from_price_amount( '10' ) );
		$this->assertSame( '19.99', wpsc_strip_char_from_price_amount( '$19.99' ) );
		$this->assertSame( '1500.00', wpsc_strip_char_from_price_amount( '1,500.00 USD' ) );
	}
}
