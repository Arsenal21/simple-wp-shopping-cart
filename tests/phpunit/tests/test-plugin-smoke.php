<?php
/**
 * Basic checks that the plugin loads, activates and shows its shortcodes in WordPress.
 */
class Test_Plugin_Smoke extends WP_UnitTestCase {

	const PLUGIN_FILE = 'wordpress-paypal-shopping-cart/wp_shopping_cart.php';

	public function set_up() {
		parent::set_up();
		//Load the home page, so the shortcodes run like on a front-end page (the plugin uses the current page URL in the product form).
		$this->go_to( home_url( '/' ) );
	}

	public function test_plugin_is_loaded() {
		$this->assertTrue( defined( 'WP_CART_VERSION' ), 'The plugin version constant is not defined.' );
		$this->assertTrue( class_exists( 'WPSC_Cart' ), 'The WPSC_Cart class is not loaded.' );
		$this->assertTrue( class_exists( 'WPSC_Cart_Item' ), 'The WPSC_Cart_Item class is not loaded.' );
		$this->assertTrue( class_exists( 'WPSC_Dynamic_Products' ), 'The WPSC_Dynamic_Products class is not loaded.' );
		$this->assertInstanceOf( 'WPSC_Cart', WPSC_Cart::get_instance() );
	}

	public function test_version_constant_matches_the_plugin_header() {
		$header = get_file_data( dirname( __DIR__, 3 ) . '/' . self::PLUGIN_FILE, array( 'version' => 'Version' ) );
		$this->assertSame( $header['version'], WP_CART_VERSION, 'The WP_CART_VERSION constant and the "Version:" plugin header are different.' );
	}

	public function test_activation_saves_the_default_settings() {
		$this->assertSame( 'Your Shopping Cart', get_option( 'wp_cart_title' ) );
		$this->assertSame( 'Your cart is empty', get_option( 'wp_cart_empty_text' ) );
		$this->assertEquals( 1, get_option( 'wpspc_send_buyer_email' ), 'The buyer email must be on by default.' );
		$this->assertSame( get_bloginfo( 'admin_email' ), get_option( 'wpspc_notify_email_address' ), 'The seller notification must go to the admin email by default.' );
		$this->assertStringContainsString( '{product_details}', get_option( 'wpspc_buyer_email_body' ) );
		$this->assertSame( 'US, GB, CA, AU', get_option( 'wpsc_stripe_allowed_shipping_countries' ) );
		$this->assertNotEmpty( get_option( 'wspsc_private_key_one' ), 'The private key was not created.' );
		$this->assertNotEmpty( get_option( 'wspsc_plugin_activated_time' ), 'The activation time was not saved.' );
	}

	/**
	 * A plugin update runs the activation again: the saved settings and the private key must stay.
	 */
	public function test_activating_again_keeps_the_saved_settings() {
		update_option( 'wp_cart_title', 'My Basket' );
		update_option( 'wpspc_send_buyer_email', '' );
		$private_key    = get_option( 'wspsc_private_key_one' );
		$activated_time = get_option( 'wspsc_plugin_activated_time' );

		wpsc_plugin_install();

		$this->assertSame( 'My Basket', get_option( 'wp_cart_title' ) );
		$this->assertSame( '', get_option( 'wpspc_send_buyer_email' ), 'A turned off buyer email was turned on again.' );
		$this->assertSame( $private_key, get_option( 'wspsc_private_key_one' ), 'The private key changed.' );
		$this->assertSame( $activated_time, get_option( 'wspsc_plugin_activated_time' ), 'The activation time changed.' );
	}

	/**
	 * Known issue: activation saves the site URL as the return URL first, so the Thank You page is never created on a new install.
	 * See "Known issues" in tests/TESTS.md. Remove the markTestSkipped() line in the commit that fixes it.
	 */
	public function test_activation_creates_the_thank_you_page_on_a_new_install() {
		$this->markTestSkipped( 'Known issue: the Thank You page is not created on a new install (see tests/TESTS.md).' );

		delete_option( 'cart_return_from_paypal_url' );

		wpsc_plugin_install();

		$return_url = get_option( 'cart_return_from_paypal_url' );
		$page_id    = url_to_postid( $return_url );
		$this->assertNotEmpty( $page_id, 'The return URL is not a page: ' . $return_url );
		$this->assertStringContainsString( '[wpsc_thank_you]', get_post( $page_id )->post_content );
	}

	public function test_activation_keeps_a_return_url_that_is_already_set() {
		update_option( 'cart_return_from_paypal_url', 'https://example.com/my-thank-you/' );

		wpsc_plugin_install();

		$this->assertSame( 'https://example.com/my-thank-you/', get_option( 'cart_return_from_paypal_url' ) );
		$this->assertEmpty( get_posts( array( 'post_type' => 'page', 's' => 'wpsc_thank_you' ) ), 'A Thank You page was created although the site has a return URL.' );
	}

	public function test_front_end_shortcodes_are_registered() {
		$shortcodes = array(
			'wpsc_show_shopping_cart',
			'show_wp_shopping_cart',
			'wpsc_always_show_shopping_cart',
			'always_show_wp_shopping_cart',
			'wpsc_add_to_cart_button',
			'wp_cart_button',
			'wpsc_display_product',
			'wp_cart_display_product',
			'wpsc_compact_cart',
			'wp_compact_cart',
			'wpsc_compact_cart2',
			'wp_compact_cart2',
			'wpsc_thank_you',
		);
		foreach ( $shortcodes as $shortcode ) {
			$this->assertTrue( shortcode_exists( $shortcode ), "The [{$shortcode}] shortcode is not registered." );
		}
	}

	/**
	 * The orders post type is only registered in wp-admin, for a user who can see the orders menu.
	 */
	public function test_orders_post_type_is_registered_in_the_admin() {
		//set_current_screen() is in wp-admin, which isn't loaded on the front end.
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'dashboard' );
		try {
			wp_cart_init_handler();
			$this->assertTrue( post_type_exists( WPSC_Cart::POST_TYPE ), 'The orders post type is not registered in wp-admin.' );
		} finally {
			set_current_screen( 'front' );
		}
	}

	public function test_add_to_cart_button_shows_the_form() {
		$output = do_shortcode( '[wp_cart_button name="Smoke Test Product" price="10.50" shipping="2" item_number="SKU-1"]' );

		$this->assertStringContainsString( '<form method="post" class="wp-cart-button-form"', $output, 'The add to cart form is missing.' );
		$this->assertStringContainsString( 'name="_wpnonce"', $output, 'The nonce field is missing.' );
		$this->assertStringContainsString( 'value="Add to Cart"', $output, 'The default button text is missing.' );
		$this->assertStringContainsString( 'name="wspsc_product" value="Smoke Test Product"', $output );
		$this->assertStringContainsString( 'name="price" value="10.50"', $output );
		$this->assertStringContainsString( 'name="shipping" value="2"', $output );
		$this->assertStringContainsString( 'name="item_number" value="SKU-1"', $output );
		$this->assertStringContainsString( 'name="addcart" value="1"', $output );
	}

	public function test_add_to_cart_button_shows_the_variations() {
		$output = do_shortcode( '[wp_cart_button name="Smoke Test Shirt" price="20" var1="Size|Small|Large::5"]' );

		$this->assertStringContainsString( '<select name="variation1"', $output, 'The variation dropdown is missing.' );
		$this->assertStringContainsString( '<option value="Small" data-display-text="Small" data-price="0">Small</option>', $output );
		$this->assertStringContainsString( '<option value="Large::5" data-display-text="Large +$5" data-price="5">Large +$5</option>', $output );
	}

	public function test_add_to_cart_button_needs_a_name_and_a_price() {
		$this->assertStringContainsString( 'You must specify a product name', do_shortcode( '[wp_cart_button price="10"]' ) );
		$this->assertStringContainsString( 'You must specify a price', do_shortcode( '[wp_cart_button name="No Price"]' ) );
		$this->assertStringContainsString( 'Special characters like [, ], <, > are not supported', do_shortcode( '[wp_cart_button name="Bad &lt;Name&gt;" price="10"]' ) );
	}

	public function test_display_product_shows_the_name_price_and_button() {
		$output = do_shortcode( '[wp_cart_display_product name="Smoke Test Box" price="1234.5" description="A test product"]' );

		$this->assertStringContainsString( 'Smoke Test Box', $output );
		$this->assertStringContainsString( 'A test product', $output );
		$this->assertStringContainsString( '$1,234.50', $output, 'The formatted price is missing.' );
		$this->assertStringContainsString( 'wp-cart-button-form', $output, 'The add to cart form is missing.' );
	}

	public function test_empty_cart_shortcodes() {
		$this->assertSame( '<div class="wpsc_shopping_cart_container"></div>', do_shortcode( '[wpsc_show_shopping_cart]' ), 'The cart must show nothing when it is empty.' );
		$this->assertStringContainsString( 'Your cart is empty', do_shortcode( '[wpsc_always_show_shopping_cart]' ) );
		$this->assertStringContainsString( 'Cart is empty', do_shortcode( '[wpsc_compact_cart]' ) );
		$this->assertStringContainsString( '0 Items', do_shortcode( '[wpsc_compact_cart2]' ) );
	}

	public function test_thank_you_shortcode_needs_a_cart_id_and_a_nonce() {
		$this->assertStringContainsString( 'Cart ID value is missing', do_shortcode( '[wpsc_thank_you]' ) );

		$_GET['cart_id'] = 'abc123';
		try {
			$this->assertStringContainsString( 'Nonce verification failed', do_shortcode( '[wpsc_thank_you]' ) );
		} finally {
			unset( $_GET['cart_id'] );
		}
	}
}
