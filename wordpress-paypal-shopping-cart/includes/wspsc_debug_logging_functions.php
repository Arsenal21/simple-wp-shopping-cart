<?php

/**
 * Generates a unique suffix for filename.
 *
 * @return string File name suffix.
 */
function wspsc_get_log_file_suffix() {
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
function wspsc_get_log_file_name() {
	return WP_CART_LOG_FILENAME . '-' . wspsc_get_log_file_suffix() . '.txt';
}

/**
 * Get the log filename with absolute path.
 *
 * @return string Debug log file.
 */
function wspsc_get_log_file() {
	return WP_CART_PATH . wspsc_get_log_file_name();
}

/**
 * Read debug log file. If log file doesn't exits, reset it.
 *
 * @return void
 */
function wspsc_read_log_file() {
	if ( ! file_exists( wspsc_get_log_file() ) ) {
		wspsc_reset_logfile();
	}
	$logfile = fopen( wspsc_get_log_file(), 'rb' );
	if ( ! $logfile ) {
		wp_die( __( 'Log file dosen\'t exists.', 'wordpress-simple-paypal-shopping-cart' ) );
	}
	header( 'Content-Type: text/plain' );
	fpassthru( $logfile );
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
function wspsc_log_payment_debug( $message, $success, $end = false ) {
	$logfile = wspsc_get_log_file();
	$debug   = get_option( 'wp_shopping_cart_enable_debug' );
	if ( ! $debug ) {
		//Debug is not enabled.
		return;
	}

	// Timestamp
	$text = '[' . date( 'm/d/Y g:i A' ) . '] - ' . ( ( $success ) ? 'SUCCESS :' : 'FAILURE :' ) . $message . "\n";
	if ( $end ) {
		$text .= "\n------------------------------------------------------------------\n\n";
	}
	// Write to log
	$fp = fopen( $logfile, 'a' );
	fwrite( $fp, $text );
	fclose( $fp );
}

function wspsc_log_debug_array( $array_to_write, $success, $end = false ) {
	$logfile = wspsc_get_log_file();
	$debug   = get_option( 'wp_shopping_cart_enable_debug' );
	if ( ! $debug ) {
		//Debug is not enabled.
		return;
	}
	$text = '[' . date( 'm/d/Y g:i A' ) . '] - ' . ( ( $success ) ? 'SUCCESS :' : 'FAILURE :' ) . "\n";
	ob_start();
	print_r( $array_to_write );
	$var = ob_get_contents();
	ob_end_clean();
	$text .= $var;

	if ($end) {
		$text .= "\n------------------------------------------------------------------\n\n";
	}
	// Write to log
	$fp = fopen( $logfile, 'a' );
	fwrite( $fp, $text );
	fclose( $fp ); // close filee
}

/**
 * Resets debug log file. Create log file if not present.
 *
 * @return bool Reset successful
 */
function wspsc_reset_logfile() {
	$log_reset = true;
	$logfile   = wspsc_get_log_file();
	$text      = '[' . date( 'm/d/Y g:i A' ) . '] - SUCCESS : Log file reset';
	$text      .= "\n------------------------------------------------------------------\n\n";
	$fp        = fopen( $logfile, 'w' );
	if ( $fp != false ) {
		@fwrite( $fp, $text );
		@fclose( $fp );
	} else {
		$log_reset = false;
	}

	return $log_reset;
}
