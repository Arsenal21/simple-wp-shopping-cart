<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates a unique suffix for filename.
 *
 * @return string File name suffix.
 */
function wpsc_get_log_file_suffix() {
	$suffix = get_option( 'wspsc_logfile_suffix' );
	if ( $suffix ) {
		return $suffix;
	}

	$suffix = uniqid();
	update_option( 'wspsc_logfile_suffix', $suffix );

	return $suffix;
}

/**
 * Get the log file with a unique name.
 *
 * @return string Log file name.
 */
function wpsc_get_log_file_name() {
	return WP_CART_LOG_FILENAME . '-' . wpsc_get_log_file_suffix() . '.txt';
}

/**
 * Get the log filename with absolute path.
 *
 * @return string Debug log file.
 */
function wpsc_get_log_file() {
	return WP_CART_PATH . wpsc_get_log_file_name();
}

/**
 * Read debug log file. If log file doesn't exits, reset it.
 *
 * @return void
 */
function wpsc_read_log_file() {
	if ( ! file_exists( wpsc_get_log_file() ) ) {
		wpsc_reset_logfile();
	}
	$logfile = file_get_contents( wpsc_get_log_file() );
	if ( false === $logfile ) {
		wp_die( esc_html__( 'Log file dosen\'t exists.', 'wordpress-simple-paypal-shopping-cart' ) );
	}
	header( 'Content-Type: text/plain' );
	header( 'X-Content-Type-Options: nosniff' );
	// Plain-text download: preserve the log contents exactly.
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $logfile;
	die;
}

/**
 * Logs payment info. Creates a log file if not present.
 *
 * @param $message String Log message
 * @param $success Bool Operation status
 * @param $end Bool Whether to add end line
 *
 * @return void
 */
function wpsc_log_payment_debug( $message, $success, $end = false ) {
	$logfile = wpsc_get_log_file();
	$debug   = get_option( 'wp_shopping_cart_enable_debug' );
	if ( ! $debug ) {
		//Debug is not enabled.
		return;
	}

	// Timestamp
	$text = '[' . wp_date( 'm/d/Y g:i A' ) . '] - ' . ( ( $success ) ? 'SUCCESS: ' : 'FAILURE: ' ) . $message . "\n";
	if ( $end ) {
		$text .= "\n------------------------------------------------------------------\n\n";
	}
	// Write to log
	// Append under a lock so simultaneous payment callbacks keep all log entries.
	file_put_contents( $logfile, $text, FILE_APPEND | LOCK_EX );
}

/**
 * TODO: Need to remove this.
 *
 * Wrapper for 'wpsc_log_payment_debug' function.
 * Used for backward compatibility of addons.
 */
function wspsc_log_payment_debug( $message, $success, $end = false ) {
	wpsc_log_payment_debug($message, $success, $end);
}

function wpsc_log_debug_array( $array_to_write, $success, $end = false ) {
	$logfile = wpsc_get_log_file();
	$debug   = get_option( 'wp_shopping_cart_enable_debug' );
	if ( ! $debug ) {
		//Debug is not enabled.
		return;
	}
	$text = '[' . wp_date( 'm/d/Y g:i A' ) . '] - ' . ( ( $success ) ? 'SUCCESS: ' : 'FAILURE: ' ) . "\n";
	ob_start();
	print_r( $array_to_write );
	$var = ob_get_contents();
	ob_end_clean();
	$text .= $var;

	if ($end) {
		$text .= "\n------------------------------------------------------------------\n\n";
	}
	// Write to log
	// Append under a lock so simultaneous payment callbacks keep all log entries.
	file_put_contents( $logfile, $text, FILE_APPEND | LOCK_EX );
}

/**
 * TODO: Need to remove this.
 *
 * Wrapper for 'wpsc_log_debug_array' function.
 * Used for backward compatibility of addons.
 */
function wspsc_log_debug_array($array_to_write, $success, $end = false) {
	wpsc_log_debug_array($array_to_write, $success, $end);
}

/**
 * Resets debug log file. Create log file if not present.
 *
 * @return bool Reset successful
 */
function wpsc_reset_logfile() {
	$logfile   = wpsc_get_log_file();
	$text      = '[' . wp_date( 'm/d/Y g:i A' ) . '] - SUCCESS: Log file reset';
	$text      .= "\n------------------------------------------------------------------\n\n";
	return false !== file_put_contents( $logfile, $text, LOCK_EX );
}
