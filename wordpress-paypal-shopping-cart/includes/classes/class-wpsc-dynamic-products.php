<?php

class WPSC_Dynamic_Products {

	public static $instance;

	private $products;

	/**
	 * @var string Dynamic product option name to use.
	 */
	private const WPSC_DYNAMIC_PRODUCTS_OPTION = 'wpsc_dynamic_products';

	/**
	 * @var string[] List of params/field-keys of essential product shortcode data.
	 */
	private static $product_params = array('name', 'file_url', 'price', 'shipping', 'var1', 'var2', 'var3');

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

	public function save( $product_key, $product_data ) {
		if (!is_array($product_data) || empty($product_data)){
			return;
		}

		$product_name = isset($product_data['name']) && !empty($product_data['name']) ? $product_data['name'] : '';
		if (empty($product_name)){
			return;
		}

		if (empty($this->products[$product_key])){
			$this->products[$product_key] = array();
		}

		// Collect essential product data to save.
		foreach (self::$product_params as $param){
			if (isset($product_data[$param])){
				$this->products[$product_key][$param] = $product_data[$param];
			}
		}

		update_option( self::WPSC_DYNAMIC_PRODUCTS_OPTION, $this->products );
	}

	public function get( $product_key ) {
		return isset($this->products[$product_key]) ? $this->products[$product_key] : array();
	}

	public function get_data_by_param($product_key, $product_param){
		$product_data = $this->get($product_key);

		return isset($product_data[$product_param]) ? $product_data[$product_param] : null;
	}

	public function get_variation_price($product_key, $variation_group, $applied_variation_str) {
		$var = $this->get_data_by_param($product_key, $variation_group);

		// Check if the submitted price is tampered or not.
		if (empty($var) || !isset($var['options']) || !in_array($applied_variation_str, $var['options']) ){
			wp_die('Error! Invalid product variation applied.', "wordpress-simple-paypal-shopping-cart");
		}

		// Extract variation price from variation string.

		$applied_variation_arr = explode( wpsc_get_variation_price_separator(), $applied_variation_str);
		if(count($applied_variation_arr) !== 2){
			return 0; // No price modifier part added.
		}

		$applied_variation_price_amt = $applied_variation_arr[1];
		if (!is_numeric($applied_variation_price_amt)) {
			return 0; // Incorrect price format, no price modifier applies.
		}

		return floatval($applied_variation_price_amt);
	}

	/**
	 * Use hashed concatenated product name and price as key.
	 */
	public static function generate_product_key($product_name, $price){
		$key = stripslashes( sanitize_text_field( $product_name . '|' . $price ) );

		return md5( $key );
	}
}
