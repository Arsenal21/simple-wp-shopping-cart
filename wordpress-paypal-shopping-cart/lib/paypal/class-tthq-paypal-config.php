<?php

namespace TTHQ\WPSC\Lib\PayPal;

class PayPal_PPCP_Config {

	private static $instance;
	
	public static $plugin_shortname = '';

	public static $log_text_method;
	public static $log_array_method;

	private $ppcp_settings_key = '';
	private $ppcp_settings_values = array();

	private function __construct() {

	}

	// Private clone method to prevent cloning of the instance
	private function __clone() {}

	public function load_settings_from_db()
	{
		$this->ppcp_settings_values = (array) get_option($this->ppcp_settings_key);
	}

	// Public method to get the instance of the class
	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function set_plugin_shortname($str){
		self::$plugin_shortname = $str;
	}
	
	public static function set_log_text_method(callable $method){
		self::$log_text_method = $method;
	}

	public static function set_log_array_method(callable $method){
		self::$log_array_method = $method;
	}

	public function set_ppcp_settings_key($ppcp_settings_key)
	{
		$this->ppcp_settings_key = $ppcp_settings_key;
	}

	public function get_value($key)
	{
		return isset($this->ppcp_settings_values[$key]) ? $this->ppcp_settings_values[$key] : '';
	}

	public function set_value($key, $value)
	{
		$this->ppcp_settings_values[$key] = $value;
	}

	public function save()
	{
		update_option($this->ppcp_settings_key, $this->ppcp_settings_values);
	}
}