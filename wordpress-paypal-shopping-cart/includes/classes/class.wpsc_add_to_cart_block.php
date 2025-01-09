<?php
/**
 * Add to Cart block.
 */

class WPSC_ADD_TO_CART {

	protected $deps;

	/**
	 * @var string Gutenberg block script handler.
	 */
	protected $block_script_handler = 'wpsc_add_to_cart_btn_block_script';

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
			WP_CART_URL . '/assets/js/block/wpsc-add-to-cart-btn.js',
			$this->deps,
			WP_CART_VERSION,
			true
		);

		//block styles for cart button
		wp_register_style(
			'wpsc_cart_block_styles',
			WP_CART_URL . '/assets/wpsc-front-end-styles.css',
			null,
			WP_CART_VERSION
		);

		$block_meta          = array(
			'title'       => 'Simple Cart - Add to Cart Button',
			'name'        => 'wp-shopping-cart/add-to-cart-btn',
			'description' => __( "Inserts an 'add to cart' button for a product/item.", 'wordpress-simple-paypal-shopping-cart' )
		);
		$wpsc_cb_block_meta = 'const wpsc_cb_block_block_meta = ' . wp_json_encode( $block_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wpsc_cb_block_meta,
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
						'description' => __( 'Specify the product name.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'price'        => array(
						'label'       => __( "Price (required)", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Specify the price of the product without the currency symbol.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'shipping'     => array(
						'label'       => __( "Shipping Cost", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Add a shipping cost for this product if required.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'button_text'  => array(
						'label'       => __( "Button Text", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Customize the cart button text.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'button_image' => array(
						'label'       => __( "Button Image URL", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Add image URL for using an image as the cart button.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'file_url'     => array(
						'label'       => __( "File URL", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Specify the file URL of the item in case it is a digital download.', 'wordpress-simple-paypal-shopping-cart' ),
					),
//					'item_number'  => array(
//						'label'       => __( "Item Number", 'wordpress-simple-paypal-shopping-cart' ),
//						'description' => '',
//					),
//					'thumbnail'    => array(
//						'label'       => __( "Product Thumbnail", 'wordpress-simple-paypal-shopping-cart' ),
//						'description' => '',
//					),
//					'stamp_pdf'    => array(
//						'label'       => __( "Stamp PDF", 'wordpress-simple-paypal-shopping-cart' ),
//						'description' => '',
//					),
					'digital' => array(
						'label'       => __( "It's a digital product", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Check this if you want to mark it as a digital product.', 'wordpress-simple-paypal-shopping-cart' ),
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
						'description' => '',
					),
					'var2' => array(
						'label'       => __( "Variation 2", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => '',
					),
					'var3' => array(
						'label'       => __( "Variation 3", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => '',
					),
				),
			)
		);
		$wpsc_cb_attrs_meta = 'const wpsc_cb_block_attrs_meta = ' . wp_json_encode( $attrs_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wpsc_cb_attrs_meta,
			'before'
		);

		$attributes = array(
			'name'         => array(
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
//			'item_number'  => array(
//				'type'    => 'string',
//				'default' => '',
//			),
//			'thumbnail'    => array(
//				'type'    => 'string',
//				'default' => '',
//			),
//			'stamp_pdf'    => array(
//				'type'    => 'string',
//				'default' => '',
//			),
			'digital'    => array(
				'type'    => 'boolean',
				'default' => false,
			)
		);

		register_block_type(
			$block_meta['name'],
			array(
				'attributes'      => $attributes,
				'editor_script'   => $this->block_script_handler,
				'editor_style'    => 'wpsc_cart_block_styles',
				'render_callback' => array( $this, 'render_block' )
			)
		);
	}

	/**
	 * @param $atts array Block Attributes.
	 *
	 * @return string Cart button output.
	 */
	public function render_block( $atts ) {
		// sanitize all fields.
		$atts = array_map( function ( $field ) {
			if ( is_array( $field ) ) {
				return array_map( 'sanitize_text_field', $field );
			}

			return sanitize_text_field( $field );
		}, $atts );

		if ( empty( $atts['name']) && empty( $atts['price'] ))  {
			return '<p style="color: red;">' . __( 'You must specify the required fields of this block first!' ) . '</p>';
		}

		if ( empty( $atts['name'] ) ) {
			return '<p style="color: red;">' . __( 'Error! You must specify product name.' ) . '</p>';
		}

		if ( empty( $atts['price'] ) ) {
			return '<p style="color: red;">' . __( 'Error! You must specify product price.' ) . '</p>';
		} else if ( ! is_numeric( $atts['price'] ) ) {
			// Prevents fatal error if invalid price is provided.
			return '<p style="color: red;">' . __( 'Error! You must specify a valid product price.' ) . '</p>';
		}

		if ( ! empty( $atts['shipping'] ) && ! is_numeric( $atts['shipping'] ) ) {
			// Prevents fatal error if invalid shipping is provided.
			return '<p style="color: red;">' . __( 'Error! You must specify a valid shipping cost.' ) . '</p>';
		}

		$sc_str = 'wp_cart_button';

		foreach ( $atts as $key => $value ) {
			if ( $value ) {
				$sc_str .= ' '. $key . '="' . esc_attr( $value ) . '"';
			}
		}

		return do_shortcode( '[' . $sc_str . ']' );
	}
}