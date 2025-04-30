<?php

/**
 * This class is used to handle persistent messages.
 */

class WPSC_Persistent_Msg {

	private static $instance;

	/**
	 * @var string The prefix for persistent msg transient.
	 */
	private static $prefix = 'wpsc_persistent_msg_';

	private static $cart_id;

	private static $expiration = 3600; // 1 hour

	private function __construct() {}

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Appends the transient key prefix with the current cart id to make a unique transient key.
	 *
	 * @param int $cart_id
	 *
	 * @return void
	 */
	public function set_cart_id( $cart_id ) {
		self::$cart_id = $cart_id;
	}

	/**
	 * Returns the full transient key with prefix.
	 *
	 * @param string $action_name The action name to generate transient key with.
	 *
	 * @return string
	 */
	private static function generate_transient_key_from_action( $action_name ) {
		$output = self::$prefix;

		if (!empty(self::$cart_id)) {
			$output .= self::$cart_id . "_";
		}

		$output .= self::get_filtered_action_name( $action_name );

		return $output;
	}

	/**
	 * TODO: Remove special characters and spaces. So that there is no issue with transient key.
	 *
	 * @param string $action_name The action name to generate transient key with.
	 *
	 * @return string
	 */
	private static function get_filtered_action_name( $action_name ) {
		$output = sanitize_text_field($action_name);
		$output = str_replace(' ', '_', $output);
		$output = strtolower($output);
		return  $output;
	}

	/**
	 * Saves the message to the transient.
	 *
	 * @param string $action_name The action name to generate transient key with.
	 * @param string $msg The message to be persisted.
	 *
	 * @return void
	 */
	public function set_msg( $action_name, $msg ) {
		if ( empty( $action_name ) || empty( $msg ) ) {
			return;
		}

		$transient_key   = self::generate_transient_key_from_action( $action_name );
		$transient_value = array(
			'message' => $msg,
		);

		set_transient( $transient_key, $transient_value, self::$expiration );
	}

	/**
	 * Retrieves the persisted message.
	 *
	 * @param string $action_name The action name to generate transient key with.
	 *
	 * @return string The message.
	 */
	public function get_msg( $action_name ) {
		if ( empty( $action_name ) ) {
			return '';
		}

		$transient_key = self::generate_transient_key_from_action( $action_name );

		$msg_data = get_transient( $transient_key );

		if ( empty( $msg_data ) ) {
			return '';
		}

		self::clear_msg( $action_name );

		$message = $msg_data['message'];

		return self::get_formatted_msg($message);
	}

	/**
	 * Formats the persisted message.
	 *
	 * @param string $msg The persisted message.
	 *
	 * @return string
	 */
	public static function get_formatted_msg($msg) {
		return '<div class="wpsc-error-message">' . $msg . '</div>';
	}

	/**
	 * Clears the persisted message by deleting the transient key.
	 *
	 * @param string $action_name The action name to generate transient key with.
	 *
	 * @return void
	 */
	public function clear_msg( $action_name ) {
		$transient_key = self::generate_transient_key_from_action( $action_name );

		delete_transient( $transient_key );
	}

}