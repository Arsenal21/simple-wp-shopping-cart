<?php
/**
 * Shopping Cart block.
 */

class WSPSC_SHOPPING_CART {

	protected $deps;

	/**
	 * @var string Gutenberg block script handler.
	 */
	protected $block_script_handler = 'wspsc_shopping_cart_block_script';

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
			WP_CART_URL . '/assets/js/block/wspsc-shopping-cart.js',
			$this->deps,
			WP_CART_VERSION,
			true
		);

		//block styles for shopping cart
		wp_register_style(
			'wspsc_sc_cart_styles',
			WP_CART_URL . '/wp_shopping_cart_style.css',
			null,
			WP_CART_VERSION,
		);

		$block_meta = array(
			'title'       => 'WP Simple Cart - Shopping Cart',
			'name'        => 'wp-shopping-cart/shopping-cart',
			'description' => __( 'Displays the shopping cart', 'wordpress-simple-paypal-shopping-cart' ),
		);
		$wspsc_sc_block_meta = 'const wspsc_sc_block_block_meta = ' . wp_json_encode( $block_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wspsc_sc_block_meta,
			'before'
		);

		$compact_view_meta = array(
			'label'       => __("Compact Mode", 'wordpress-simple-paypal-shopping-cart'),
			'description' => __( 'Displays a cart with less info', 'wordpress-simple-paypal-shopping-cart' ),
		);
		$wspsc_sc_compact_view_meta = 'const wspsc_sc_block_compact_view_meta = ' . wp_json_encode( $compact_view_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wspsc_sc_compact_view_meta,
			'before'
		);

		$attributes = array(
			'compact_mode'      => array(
				'type'    => 'boolean',
				'default' => false,
			),
		);

		register_block_type(
			$block_meta['name'],
			array(
				'attributes'      => $attributes,
				'editor_script'   => $this->block_script_handler,
				'editor_style'    => array( 'wspsc_sc_cart_styles', 'dashicons' ),
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * @param $atts array Block Attributes
	 */
	public function render_block( $atts ) {
		// sanitize all fields.
		$atts = array_map(function ($field){
			if (is_array($field)){
				return array_map('sanitize_text_field', $field);
			}
			return sanitize_text_field($field);
		}, $atts);

		$sc_str = 'show_wp_shopping_cart';

		if ( $atts['compact_mode'] ) {
			$sc_str = 'wp_compact_cart';
		}

		$output = '';

		$is_backend = defined('REST_REQUEST') && REST_REQUEST === true && filter_input(INPUT_GET, 'context', FILTER_SANITIZE_STRING) === 'edit';
		if ($is_backend) {
			$output .= '<p class="wspsc_demo_preview_notice">'. __( 'This is a preview of the cart. If the cart is empty, nothing will be displayed here as well as in the front-end.' , 'wordpress-simple-paypal-shopping-cart').'</p>';
		}

		$output .= do_shortcode( '[' . $sc_str . ']' );

		return $output;
	}
}