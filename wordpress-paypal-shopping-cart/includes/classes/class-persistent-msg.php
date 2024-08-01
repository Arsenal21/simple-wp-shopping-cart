<?php

/**
 * This class is used to handle persistent messages.
 */

class WPSC_Persistent_Msg {

	private static $instance;

	/**
	 * @var string The prefix for persistent msg transient.
	 */
	private static string $prefix = 'wpsc_persistent_msg_';

	private static int $expiration = 3600; // 1 hour

	private function __construct() {}

	public static function get_instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the full transient key with prefix.
	 *
	 * @param string $key The transient key without prefix.
	 *
	 * @return string
	 */
	private static function get_transient_key( string $key ) {
		return self::$prefix . self::get_filtered_key_name( $key );
	}

	/**
	 * Returns a css class based on message type.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function get_msg_type_selector( string $type ): string {
		switch ( trim( strtolower( $type ) ) ) {
			case 'success':
				return 'wpspsc_success_message';
			case 'error':
				return 'wpspsc_error_message';
			default:
				return '';
		}
	}

	/**
	 * TODO: Remove special characters and spaces. So that there is no issue with transient key.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private static function get_filtered_key_name( string $key ): string {
		// TODO: Need to work here.
		$output = str_replace(' ', '_', $key);
		$output = strtolower($output);
		return  $output;
	}

	/**
	 * Saves the message to the transient.
	 *
	 * @param string $key The transient key without prefix.
	 * @param string $msg The message to be persisted.
	 * @param string $type The persisted message type.
	 *
	 * @return void
	 */
	public function set_msg( string $key, string $msg, string $type = 'info' ): void {
		if ( empty( $key ) || empty( $msg ) ) {
			return;
		}

		$transient_key   = self::get_transient_key( $key );
		$transient_value = array(
			'message' => $msg,
			'type'    => $type,
		);

		// wspsc_log_payment_debug( ">>> set_msg " . $transient_key . " transient.", true );1
		// wspsc_log_debug_array($transient_value, true );

		set_transient( $transient_key, $transient_value, self::$expiration );
	}

	/**
	 * Retrieves the persisted message.
	 *
	 * @param string $key The transient key without prefix.
	 *
	 * @return string The message.
	 */
	public function get_msg( string $key ): string {
		if ( empty( $key ) ) {
			return '';
		}

		$transient_key = self::get_transient_key( $key );

		$msg_data = get_transient( $transient_key );

		if ( empty( $msg_data ) ) {
			return '';
		}

		// wspsc_log_payment_debug( ">>> get_msg " . $transient_key . " transient.", true );
		// wspsc_log_debug_array($msg_data, true );

		self::clear_msg( $key );

		$type    = $msg_data['type'];
		$message = $msg_data['message'];

		return self::get_formatted_msg($message, $type);
	}

	/**
	 * Formats the persisted message.
	 *
	 * @param string $msg The persisted message.
	 * @param string $type The persisted message type.
	 *
	 * @return string
	 */
	public static function get_formatted_msg(string $msg, string $type): string {
		return '<div class="' . self::get_msg_type_selector( $type ) . '">' . $msg . '</div>';
	}

	/**
	 * Clears the persisted message by deleting the transient key.
	 *
	 * @param string $key The transient key without prefix.
	 *
	 * @return void
	 */
	public function clear_msg( string $key ): void {
		wspsc_log_payment_debug( ">>> clearing " . $key . " transient.", true );

		$transient_key = self::get_transient_key( $key );

		delete_transient( $transient_key );
	}

}