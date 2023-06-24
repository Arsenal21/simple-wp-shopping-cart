<?php
/**
 * Add to Cart block.
 */

class WSPSC_ADD_TO_CART {

	protected $deps;

	/**
	 * @var string Gutenberg block script handler.
	 */
	protected $block_script_handler = 'wspsc_add_to_cart_btn_block_script';

	/**
	 * Block Constructor.
	 */
	public function __construct( $deps ) {
		$this->deps = is_array( $deps ) ? $deps : array( $deps );
		$this->register();
	}

	/**
	 * Registers add-to-cart-button block.
	 */
	protected function register() {
		wp_register_script(
			$this->block_script_handler,
			WP_CART_URL . '/assets/js/block/wspsc-add-to-cart-btn.js',
			$this->deps,
			rand( 1, 1000 ),
			true
		);

		//block styles for cart button
		wp_register_style(
			'wspsc_sc_cart_styles',
			WP_CART_URL . '/wp_shopping_cart_style.css',
			null,
			rand(0,500),
		);

		$block_meta          = array(
			'title'       => 'WP Simple Cart - Add to Cart Button',
			'name'        => 'wp-shopping-cart/add-to-cart-btn',
			'description' => __( "Places an 'Add to Cart' button.", 'wordpress-simple-paypal-shopping-cart' ),
		);
		$wspsc_cb_block_meta = 'const wspsc_cb_block_block_meta = ' . wp_json_encode( $block_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wspsc_cb_block_meta,
			'before'
		);

		$attrs_meta          = array(
			'general' => array(
				'title'       => __( "General", 'wordpress-simple-paypal-shopping-cart' ),
				'initialOpen' => true,
				'scrollAfterOpen' => true,
				'description' => __( 'Customize the cart button', 'wordpress-simple-paypal-shopping-cart' ),
				'fields'      => array(
					'name'         => array(
						'label'       => __( "Name (required)", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'price'        => array(
						'label'       => __( "Price (required)", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'item_number'  => array(
						'label'       => __( "Item Number", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'shipping'     => array(
						'label'       => __( "Shipping Cost", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'thumbnail'    => array(
						'label'       => __( "Product Thumbnail", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'button_text'  => array(
						'label'       => __( "Button Text", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'button_image' => array(
						'label'       => __( "Button Image", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'file_url'     => array(
						'label'       => __( "File URL", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'stamp_pdf'    => array(
						'label'       => __( "Stamp PDF", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
				),
			),

			'variation' => array(
				'title'       => __( "Product Variations", 'wordpress-simple-paypal-shopping-cart' ),
				'initialOpen' => false,
				'scrollAfterOpen' => true,
				'description' => __( 'Add multiple variation type using a pattern. For example: Size|small|medium|large.', 'wordpress-simple-paypal-shopping-cart' ),
				'fields'      => array(
					'var1' => array(
						'label'       => __( "Variation 1", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'var2' => array(
						'label'       => __( "Variation 2", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'var3' => array(
						'label'       => __( "Variation 3", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( '', 'wordpress-simple-paypal-shopping-cart' ),
					),
				),
			),
		);
		$wspsc_cb_attrs_meta = 'const wspsc_cb_block_attrs_meta = ' . wp_json_encode( $attrs_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wspsc_cb_attrs_meta,
			'before'
		);

		$attributes = array(
			'name'         => array(
				'type'    => 'string',
				'default' => '',
			),
			'item_number'  => array(
				'type'    => 'string',
				'default' => '',
			),
			'price'        => array(
				'type'    => 'string',
				'default' => '',
			),
			'shipping'     => array(
				'type'    => 'string',
				'default' => '',
			),
			'var1'         => array(
				'type'    => 'string',
				'default' => '',
			),
			'var2'         => array(
				'type'    => 'string',
				'default' => '',
			),
			'var3'         => array(
				'type'    => 'string',
				'default' => '',
			),
			'thumbnail'    => array(
				'type'    => 'string',
				'default' => '',
			),
			'button_text'  => array(
				'type'    => 'string',
				'default' => '',
			),
			'button_image' => array(
				'type'    => 'string',
				'default' => '',
			),
			'file_url'     => array(
				'type'    => 'string',
				'default' => '',
			),
			'stamp_pdf'    => array(
				'type'    => 'string',
				'default' => '',
			),
		);

		register_block_type(
			$block_meta['name'],
			array(
				'attributes'      => $attributes,
				'editor_script'   => $this->block_script_handler,
				'editor_style'   => 'wspsc_sc_cart_styles',
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * @param $atts array Block Attributes
	 */
	public function render_block( $atts ) {
		// sanitize all fields.
		$atts = array_map( function ( $field ) {
			if ( is_array( $field ) ) {
				return array_map( 'sanitize_text_field', $field );
			}

			return sanitize_text_field( $field );
		}, $atts );

		$sc_str = 'wp_cart_button';

		foreach ( $atts as $key => $value ) {
			if ( $value ) {
				$sc_str .= " " . $key . "='" . $value . "'";
			}
		}

		$output = '';
		$output .= do_shortcode( '[' . $sc_str . ']' );
		return $output;
	}
}