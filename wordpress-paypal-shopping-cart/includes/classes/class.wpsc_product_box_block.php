<?php
/**
 * Product Box block.
 */

class WPSC_PRODUCT_BOX {

	protected $deps;

	/**
	 * @var string Gutenberg block script handler.
	 */
	protected $block_script_handler = 'wpsc_product_box_block_script';

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
			WP_CART_URL . '/assets/js/block/wpsc-product-box.js',
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
			'title'       => 'Simple Cart - Product Box',
			'name'        => 'wp-shopping-cart/product-box',
			'description' => __( "Renders a product box with the item name, description, price and an 'add to cart' button.", 'wordpress-simple-paypal-shopping-cart' )
		);
		$wpsc_pb_block_meta = 'const wpsc_pb_block_block_meta = ' . wp_json_encode( $block_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wpsc_pb_block_meta,
			'before'
		);

		$attrs_meta          = array(
			'general' => array(
				'title'           => __( "General", 'wordpress-simple-paypal-shopping-cart' ),
				'initialOpen'     => true,
				'scrollAfterOpen' => true,
				'description'     => __( 'Customize the general product related info', 'wordpress-simple-paypal-shopping-cart' ),
				'fields'          => array(
					'name'         => array(
						'label'       => __( "Name (required)", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Specify the product name.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'price'        => array(
						'label'       => __( "Price (required)", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Specify the price of the product without currency symbol.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'description'  => array(
						'label'       => __( "Description", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Write a description for the product.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'shipping'     => array(
						'label'       => __( "Shipping Cost", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Add a shipping cost for this product if required.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'file_url'     => array(
						'label'       => __( "File URL", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Specify the file URL of the item in case it is a digital download.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'thumbnail'    => array(
						'label'       => __( "Thumbnail URL (required)", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Specify the image URL for the product thumbnail.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'thumb_alt'    => array(
						'label'       => __( "Thumbnail Alt Text", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Add an alternative text for the thumbnail.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'thumb_target' => array(
						'label'       => __( "Thumbnail Target URL", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'This option enables you to add a target URL to the thumbnail, allowing you to send customers to the details page of the product.', 'wordpress-simple-paypal-shopping-cart' ),
					),
					'digital' => array(
						'label'       => __( "It's a digital product", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => __( 'Check this if you want to mark it as a digital product.', 'wordpress-simple-paypal-shopping-cart' ),
					),
				),
			),

			'cart-button' => array(
				'title'           => __( "Cart Button", 'wordpress-simple-paypal-shopping-cart' ),
				'initialOpen'     => false,
				'scrollAfterOpen' => true,
				'description'     => '',
				'fields'          => array(
					'button_text'  => array(
						'label'       => __( "Button Text", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => '',
					),
					'button_image' => array(
						'label'       => __( "Button Image", 'wordpress-simple-paypal-shopping-cart' ),
						'description' => '',
					),
				),
			),

			'variation' => array(
				'title'           => __( "Product Variations", 'wordpress-simple-paypal-shopping-cart' ),
				'initialOpen'     => false,
				'scrollAfterOpen' => true,
				'description'     => __( 'Add product variations using a pattern. For example: Size|small|medium|large.', 'wordpress-simple-paypal-shopping-cart' ),
				'fields'          => array(
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
		$wpsc_pb_attrs_meta = 'const wpsc_pb_block_attrs_meta = ' . wp_json_encode( $attrs_meta );

		wp_add_inline_script(
			$this->block_script_handler,
			$wpsc_pb_attrs_meta,
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
			'description'  => array(
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
			'thumbnail'    => array(
				'type'    => 'string',
				'default' => '',
			),
			'thumb_target' => array(
				'type'    => 'string',
				'default' => '',
			),
			'thumb_alt'    => array(
				'type'    => 'string',
				'default' => '',
			),
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
	 * @param $atts array Block Attributes
	 *
	 * @return string Product box output.
	 */
	public function render_block( $atts ) {
		// sanitize all fields.
		$atts = array_map( function ( $value ) {
			if ( is_array( $value ) ) {
				return array_map( 'sanitize_text_field', $value );
			}

			return sanitize_text_field( $value );
		}, $atts );

		if ( empty( $atts['name']) && empty( $atts['price'] ) && empty( $atts['thumbnail'] ) )  {
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

		if ( empty( $atts['thumbnail'] ) ) {
			return '<p style="color: red;">' . __( 'Error! You must specify product thumbnail.' ) . '</p>';
		}

		if ( ! empty( $atts['shipping'] ) && ! is_numeric( $atts['shipping'] ) ) {
			// Prevents fatal error if invalid shipping is provided.
			return '<p style="color: red;">' . __( 'Error! You must specify a valid shipping cost.' ) . '</p>';
		}
		$sc_str = 'wp_cart_display_product';

		foreach ( $atts as $key => $value ) {
			if ( $value ) {
				$sc_str .= ' '. $key . '="' . esc_attr( $value ) . '"';
			}
		}

		return do_shortcode( '[' . $sc_str . ']' );
	}
}