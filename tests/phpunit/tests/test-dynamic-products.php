<?php
/**
 * The add to cart button saves the product (name, price, shipping, variations, file URL) when the page is shown.
 * The add to cart request is then checked against this saved data, so only trusted pages may save it (5.3.3):
 * published content by a user who can edit others' posts (Editor or Administrator), and not a preview.
 */
class Test_Dynamic_Products extends WP_UnitTestCase {

	const SHORTCODE = '[wp_cart_button name="Registered Ebook" price="15" shipping="1.5" file_url="https://example.com/ebook.pdf"]';

	public function set_up() {
		parent::set_up();
		WPSC_Dynamic_Products::$instance = null;
	}

	public function test_a_published_page_by_an_administrator_saves_the_product() {
		$this->show_page( 'administrator', 'publish' );

		$product = $this->get_saved_product();
		$this->assertSame( 'Registered Ebook', $product['name'] );
		$this->assertSame( '15', $product['price'] );
		$this->assertSame( '1.5', $product['shipping'] );
		$this->assertSame( 'https://example.com/ebook.pdf', $product['file_url'] );

		$saved_option = get_option( 'wpsc_dynamic_products' );
		$this->assertArrayHasKey( $this->get_key(), $saved_option, 'The product was not saved in the wpsc_dynamic_products option.' );
	}

	public function test_a_published_page_by_an_editor_saves_the_product() {
		$this->show_page( 'editor', 'publish' );

		$this->assertNotEmpty( $this->get_saved_product() );
	}

	/**
	 * @return array role of the page author, page status
	 */
	public function untrusted_pages() {
		return array(
			'author'       => array( 'author', 'publish' ),
			'contributor'  => array( 'contributor', 'publish' ),
			'draft'        => array( 'administrator', 'draft' ),
			'pending'      => array( 'administrator', 'pending' ),
			'private page' => array( 'administrator', 'private' ),
		);
	}

	/**
	 * @dataProvider untrusted_pages
	 */
	public function test_an_untrusted_page_does_not_save_the_product( $role, $status ) {
		$output = $this->show_page( $role, $status );

		$this->assertStringContainsString( 'wp-cart-button-form', $output, 'The button must still be shown.' );
		$this->assertEmpty( $this->get_saved_product(), "A {$status} page by an {$role} saved the product." );
	}

	public function test_a_preview_does_not_save_the_product() {
		$post_id = $this->create_page( 'administrator', 'publish' );
		$this->go_to( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) );
		$GLOBALS['post'] = get_post( $post_id );
		$this->assertTrue( is_preview() );

		do_shortcode( self::SHORTCODE );

		$this->assertEmpty( $this->get_saved_product(), 'A preview saved the product.' );
	}

	public function test_a_shortcode_outside_a_post_does_not_save_the_product() {
		$this->go_to( home_url( '/' ) );
		$GLOBALS['post'] = null;

		do_shortcode( self::SHORTCODE );

		$this->assertEmpty( $this->get_saved_product() );
	}

	/**
	 * An Author can't change the file URL (or other data) of a product that an Administrator's page saved,
	 * by adding a button with the same name and price.
	 */
	public function test_an_untrusted_page_does_not_change_a_saved_product() {
		$this->show_page( 'administrator', 'publish' );

		$this->show_page( 'author', 'publish', '[wp_cart_button name="Registered Ebook" price="15" shipping="0" file_url="https://attacker.example/x.pdf"]' );

		WPSC_Dynamic_Products::$instance = null; //Read it from the database again.
		$product = $this->get_saved_product();
		$this->assertSame( 'https://example.com/ebook.pdf', $product['file_url'] );
		$this->assertSame( '1.5', $product['shipping'] );
	}

	public function test_the_required_capability_can_be_changed_with_a_filter() {
		$capability = function () {
			return 'publish_posts';
		};
		add_filter( 'wpsc_dynamic_product_register_capability', $capability );

		$this->show_page( 'author', 'publish' );

		remove_filter( 'wpsc_dynamic_product_register_capability', $capability );
		$this->assertNotEmpty( $this->get_saved_product(), 'The filter did not let an Author save the product.' );
	}

	public function test_the_product_key_depends_on_the_name_and_the_price() {
		$key = WPSC_Dynamic_Products::generate_product_key( 'Registered Ebook', '15' );

		$this->assertSame( $key, WPSC_Dynamic_Products::generate_product_key( 'Registered Ebook', '15' ) );
		$this->assertNotSame( $key, WPSC_Dynamic_Products::generate_product_key( 'Registered Ebook', '16' ) );
		$this->assertNotSame( $key, WPSC_Dynamic_Products::generate_product_key( 'Other Ebook', '15' ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $key );
	}

	private function create_page( $role, $status ) {
		$author = self::factory()->user->create( array( 'role' => $role ) );
		return self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_author'  => $author,
				'post_status'  => $status,
				'post_content' => self::SHORTCODE,
			)
		);
	}

	/**
	 * Shows the button on a page by a user with this role (the shortcode runs with that page as the current post).
	 */
	private function show_page( $role, $status, $shortcode = self::SHORTCODE ) {
		$post_id = $this->create_page( $role, $status );
		$this->go_to( home_url( '/' ) );
		$GLOBALS['post'] = get_post( $post_id );
		return do_shortcode( $shortcode );
	}

	private function get_key() {
		return WPSC_Dynamic_Products::generate_product_key( 'Registered Ebook', '15' );
	}

	private function get_saved_product() {
		return WPSC_Dynamic_Products::get_instance()->get( $this->get_key() );
	}
}
