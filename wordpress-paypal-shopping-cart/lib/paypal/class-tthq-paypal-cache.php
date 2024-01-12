<?php

namespace TTHQ\WPSC\Lib\PayPal;

/**
 * This class is used to cache the bearer token.
 * We will use a Singleton class to make it simple to use.
 */

class PayPal_Cache {
	protected static $instance;

	public function __construct() {
		//NOP
	}

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Gets a value.
	 * @param string $key The key under which the value is stored.
	 */
	public function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * Whether a value is stored or not.
	 * @param string $key The key for the value.
	 * @return bool
	 */
	public function has( $key ) {
		$value = $this->get( $key );
		return false !== $value;
	}

	/**
	 * Deletes a cache.
	 * @param string $key The key.
	 */
	public function delete( $key ) {
		delete_transient( $key );
	}

	/**
	 * Caches a value.
	 * @param string $key The key under which the value should be cached.
	 * @param mixed $value The value to cache.
	 * @param int $expiration Time until expiration in seconds.
	 * @return bool
	 */
	public function set( $key, $value, $expiration = 0 ) {
		return (bool) set_transient( $key, $value, $expiration );
	}

}