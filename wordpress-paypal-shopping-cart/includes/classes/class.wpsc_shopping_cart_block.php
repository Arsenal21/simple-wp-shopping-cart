<?php
/**
 * Shopping Cart block.
 */

class WPSC_SHOPPING_CART {

	protected $deps;

	/**
	 * @var string Gutenberg block script handler.
	 */
	protected $block_script_handler = 'wpsc_shopping_cart_block_script';

	/**
	 * Block Constructor.
	 */
	public function __construct( $deps ) {
		$this->deps           = is_array( $deps ) ? $deps : array( $deps );
		$this->register();
	}

	/**
	 * Registers shopping cart block.
	 */
	protected function register() {
		wp_register_script(
			$this->block_script_handler,
			WP_CART_URL . '/assets/js/block/wpsc-shopping-cart.js',
			$this->deps,
			WP_CART_VERSION,
			true
		);

		$block_meta = array(
			'title'       => 'Simple Cart - Shopping Cart',
			'name'        => 'wp-shopping-cart/shopping-cart',
			'description' => __( 'Displays the shopping cart for the Simple Shopping Cart plugin', 'wordpress-simple-paypal-shopping-cart' ),
		);
		$wpsc_sc_block_meta = 'const wpsc_sc_block_block_meta = ' . wp_json_encode( $block_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wpsc_sc_block_meta,
			'before'
		);

		$display_options_meta          = array(
			'label'       => __( "Cart display options", 'wordpress-simple-paypal-shopping-cart' ),
			'description' => __( 'Select the template of the cart', 'wordpress-simple-paypal-shopping-cart' ),
			'options'     => array(
				array(
					'label' => __( 'Display cart if not empty', 'wordpress-simple-paypal-shopping-cart' ),
					'value' => 'show_wp_shopping_cart',
				),
				array(
					'label' => __( 'Display cart always', 'wordpress-simple-paypal-shopping-cart' ),
					'value' => 'always_show_wp_shopping_cart',
				),
				array(
					'label' => __( 'Display compact cart 1', 'wordpress-simple-paypal-shopping-cart' ),
					'value' => 'wp_compact_cart',
				),
				array(
					'label' => __( 'Display compact cart 2', 'wordpress-simple-paypal-shopping-cart' ),
					'value' => 'wp_compact_cart2',
				),
			),
		);
		$wpsc_sc_display_options_meta = 'const wpsc_sc_block_display_option_meta = ' . wp_json_encode( $display_options_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wpsc_sc_display_options_meta,
			'before'
		);

		$attributes = array(
			'display_option' => array(
				'type'    => 'string',
				'default' => 'show_wp_shopping_cart',
			),
		);

		register_block_type(
			$block_meta['name'],
			array(
				'attributes'      => $attributes,
				'editor_script'   => $this->block_script_handler,
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * @param $atts array Block Attributes
	 *
	 * @return string Cart output
	 */
	public function render_block( $atts ) {

		$sc_str = ! empty( $atts['display_option'] ) ? sanitize_text_field($atts['display_option']) : 'show_wp_shopping_cart';

		$wp_context = isset( $_GET['context'] ) ? sanitize_text_field( stripslashes ( $_GET['context'] ) ) : '';
		$is_backend = defined( 'REST_REQUEST' ) && REST_REQUEST === true && $wp_context === 'edit';
		if ( $is_backend ) {
			return '<p style="padding: 10px 16px; margin-bottom: 12px; background-color: #eee; border: 1px solid gray;">' . __( 'The shopping cart will be rendered here on the front-end.', 'wordpress-simple-paypal-shopping-cart' ) . '</p>';
		}

		return do_shortcode( '[' . $sc_str . ']' );
	}
}