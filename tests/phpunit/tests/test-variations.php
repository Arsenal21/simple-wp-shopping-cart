<?php
/**
 * Variation options with a price modifier ("Large::5"): how they are shown, and the price check when one is added to the cart.
 */
class Test_Variations extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		//Start with no saved products in memory (the class keeps them in a singleton).
		WPSC_Dynamic_Products::$instance = null;
	}

	/**
	 * @return array input => label, price, formatted price, display text
	 */
	public function variation_strings() {
		return array(
			'no modifier'                       => array( 'small', '', 0, '', 'small' ),
			'positive modifier'                 => array( 'medium::10', 'medium', 10, '+$10', 'medium +$10' ),
			'negative modifier'                 => array( 'large::-5', 'large', -5, '-$5', 'large -$5' ),
			'spaces in the name (fixed 5.3.4)'  => array( 'CD (Mailed in US Only)::18', 'CD (Mailed in US Only)', 18, '+$18', 'CD (Mailed in US Only) +$18' ),
			'spaces around the separator'       => array( 'CD (Mailed in US Only) :: 18', 'CD (Mailed in US Only)', 18, '+$18', 'CD (Mailed in US Only) +$18' ),
			'space before the separator'        => array( 'Name ::18', 'Name', 18, '+$18', 'Name +$18' ),
			'spaces after the separator'        => array( 'Name:: 18 ', 'Name', 18, '+$18', 'Name +$18' ),
			'zero modifier shows the name only' => array( 'Gift Wrap::0', 'Gift Wrap', 0, '', 'Gift Wrap' ),
			'not a number shows the name only'  => array( 'Odd::abc', 'Odd', 0, '', 'Odd' ),
			'decimal modifier'                  => array( 'Express::2.5', 'Express', 2.5, '+$2.5', 'Express +$2.5' ),
		);
	}

	/**
	 * @dataProvider variation_strings
	 */
	public function test_variation_string_parts( $input, $label, $price, $formatted_price, $display_text ) {
		$parts = wpsc_get_variation_string_parts( $input );

		$this->assertSame( $label, $parts['label'], 'Label' );
		$this->assertEquals( $price, $parts['price'], 'Price' );
		$this->assertSame( $formatted_price, $parts['formatted_price'], 'Formatted price' );
		$this->assertSame( $display_text, $parts['display_text'], 'Display text' );
	}

	public function test_the_separator_can_be_changed_with_a_filter() {
		$separator = function () {
			return '##';
		};
		add_filter( 'wpsc_variation_price_separator', $separator );

		$parts = wpsc_get_variation_string_parts( 'Big Box##7' );
		$this->assertSame( 'Big Box +$7', $parts['display_text'] );
		$this->assertEquals( 7, $parts['price'] );

		$parts = wpsc_get_variation_string_parts( 'Big Box::7' );
		$this->assertSame( 'Big Box::7', $parts['display_text'], 'The default separator must not be used when the filter changes it.' );

		remove_filter( 'wpsc_variation_price_separator', $separator );
	}

	public function test_variation_price_of_the_selected_option() {
		$key = $this->register_shirt();
		$products = WPSC_Dynamic_Products::get_instance();

		$this->assertEquals( 0, $products->get_variation_price( $key, 'var1', 'Small' ) );
		$this->assertEquals( 5, $products->get_variation_price( $key, 'var1', 'Large::5' ) );
		$this->assertEquals( -2, $products->get_variation_price( $key, 'var1', 'Kids::-2' ) );
		$this->assertEquals( 7.5, $products->get_variation_price( $key, 'var1', 'XL :: 7.5' ), 'Spaces around the price must be ignored.' );
		$this->assertEquals( 0, $products->get_variation_price( $key, 'var1', 'Odd::abc' ), 'A price that is not a number must add nothing.' );
		$this->assertEquals( 3, $products->get_variation_price( $key, 'var2', 'Blue::3' ) );
	}

	/**
	 * @return array submitted variation group, submitted option
	 */
	public function tampered_variations() {
		return array(
			'changed price'                      => array( 'var1', 'Large::50' ),
			'price removed'                      => array( 'var1', 'Large' ),
			'trailing space (not the same text)' => array( 'var1', 'Large::5 ' ),
			'option of another group'            => array( 'var2', 'Large::5' ),
			'group the product does not have'    => array( 'var3', 'Small' ),
		);
	}

	/**
	 * @dataProvider tampered_variations
	 */
	public function test_a_tampered_variation_is_rejected( $group, $option ) {
		$key = $this->register_shirt();

		$this->expectException( 'WPDieException' );
		$this->expectExceptionMessage( 'Invalid product variation applied' );
		WPSC_Dynamic_Products::get_instance()->get_variation_price( $key, $group, $option );
	}

	public function test_a_variation_of_a_product_that_is_not_registered_is_rejected() {
		$this->expectException( 'WPDieException' );
		WPSC_Dynamic_Products::get_instance()->get_variation_price( WPSC_Dynamic_Products::generate_product_key( 'Unknown', '20' ), 'var1', 'Small' );
	}

	public function test_variation_options_are_saved_from_the_shortcode() {
		$output = $this->render_on_published_page( '[wp_cart_button name="Test Mug" price="12" var1="Size|Regular|Large::3" var2="Color|White|Black::1.5"]' );

		$this->assertStringContainsString( '<option value="Large::3" data-display-text="Large +$3" data-price="3">Large +$3</option>', $output );
		$product = WPSC_Dynamic_Products::get_instance()->get( WPSC_Dynamic_Products::generate_product_key( 'Test Mug', '12' ) );
		$this->assertSame( array( 'name' => 'Size', 'options' => array( 'Regular', 'Large::3' ) ), $product['var1'] );
		$this->assertSame( array( 'name' => 'Color', 'options' => array( 'White', 'Black::1.5' ) ), $product['var2'] );
	}

	/**
	 * Saves a product with two variation groups, like the add to cart button does on a published page.
	 *
	 * @return string The product key.
	 */
	private function register_shirt() {
		$key = WPSC_Dynamic_Products::generate_product_key( 'Test Shirt', '20' );
		WPSC_Dynamic_Products::get_instance()->save(
			$key,
			array(
				'name'     => 'Test Shirt',
				'price'    => '20',
				'shipping' => '0',
				'var1'     => array(
					'name'    => 'Size',
					'options' => array( 'Small', 'Large::5', 'Kids::-2', 'XL :: 7.5', 'Odd::abc' ),
				),
				'var2'     => array(
					'name'    => 'Color',
					'options' => array( 'Red', 'Blue::3' ),
				),
			)
		);
		return $key;
	}

	private function render_on_published_page( $shortcode ) {
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id = self::factory()->post->create( array( 'post_author' => $admin, 'post_status' => 'publish', 'post_content' => $shortcode ) );
		$this->go_to( get_permalink( $post_id ) );
		$GLOBALS['post'] = get_post( $post_id );
		return do_shortcode( $shortcode );
	}
}
