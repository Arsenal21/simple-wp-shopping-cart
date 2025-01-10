<?php
/**
 * Manages all blocks.
 */

class WPSC_Blocks {

	/**
	 * Common block script dependencies.
	 *
	 * @var string[]
	 */
	public $deps = array( 'wp-blocks', 'wp-element', 'wp-components' );

	/**
	 * Initiate Block Class.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initiate_blocks' ) );
	}

	/**
	 * Registers Blocks.
	 *
	 * @return void
	 */
	public function initiate_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		if ( version_compare( get_bloginfo( 'version' ), '5.8.0', '<' ) ) {
			$this->deps[] = 'wp-editor';
		}

		/**
		 * Includes wp block editor component dependencies to create blocks.
		 */
		wp_register_script(
            'wpsc_block_script_dependencies',
            WP_CART_URL . '/assets/js/block/wpsc-block-dependencies.js',
			$this->deps,
            WP_CART_VERSION,
			true
		);

		// Register all blocks.
		require_once( WP_CART_PATH . '/includes/classes/class.wpsc_add_to_cart_block.php' );
		require_once( WP_CART_PATH . '/includes/classes/class.wpsc_product_box_block.php' );
		require_once( WP_CART_PATH . '/includes/classes/class.wpsc_shopping_cart_block.php' );

		new WPSC_ADD_TO_CART( 'wpsc_block_script_dependencies' );
		new WPSC_PRODUCT_BOX( 'wpsc_block_script_dependencies' );
		new WPSC_SHOPPING_CART( 'wpsc_block_script_dependencies' );
	}

}

new WPSC_Blocks();
