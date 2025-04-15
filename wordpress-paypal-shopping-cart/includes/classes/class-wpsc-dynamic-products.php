<?php

class WPSC_Dynamic_Products {

	public static $instance;

	private $products;

	/**
	 * @var string Dynamic product option name to use.
	 */
	private const WPSC_DYNAMIC_PRODUCTS_OPTION = 'wpsc_dynamic_products';

	/**
	 * @var string[] List of params of essential product shortcode data.
	 */
	private static $product_params = array('name', 'file_url', 'price', 'shipping');

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		// Load saved 'wpsc_dynamic_products' option.
		$this->products = get_option( self::WPSC_DYNAMIC_PRODUCTS_OPTION, array() );
	}

	public function add( $product_data ) {

		if (!is_array($product_data) || empty($product_data)){
			return;
		}

		// Use product name as key.
		$product_key = isset($product_data['name']) ? sanitize_key($product_data['name']) : '';
		if (empty($product_key)){
			return;
		}

//		wpsc_log_payment_debug("Key: " . $product_key, true);
//		wpsc_log_payment_debug('Adding Product Data: ', true);
//		wpsc_log_debug_array($product_data, true);

		if (empty($this->products[$product_key])){
			$this->products[$product_key] = array();
		}

		// Collect essential product data to save.
		foreach (self::$product_params as $param){
			if (isset($product_data[$param])){
				$this->products[$product_key][$param] = $product_data[$param];
			}
		}

		$this->save();
	}

	public function get( $product_name ) {
		$product_key = sanitize_key($product_name);

		return isset($this->products[$product_key]) ? $this->products[$product_key] : array();
	}

	public function get_param($product_name, $product_param){
		$product_data = $this->get($product_name);

//		wpsc_log_payment_debug('Getting Product Data: ', true);
//		wpsc_log_payment_debug("Product Param: " . $product_param, true);
//		wpsc_log_debug_array($product_data, true);

		return isset($product_data[$product_param]) ? $product_data[$product_param] : null;
	}

	private function save() {
		update_option( self::WPSC_DYNAMIC_PRODUCTS_OPTION, $this->products );
	}
}
