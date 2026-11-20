<?php
/**
 * PHPUnit bootstrap for the Simple Shopping Cart plugin tests.
 *
 * It needs the WordPress test library (installed by tests/bin/install-wp-tests.sh) and a test database.
 * The GitHub Actions "Plugin Tests" workflow sets this up. WP_TESTS_DIR is the folder of the WordPress test library.
 */

require __DIR__ . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/vendor/yoast/phpunit-polyfills' );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Run tests/bin/install-wp-tests.sh first." . PHP_EOL;
	exit( 1 );
}

//Gives access to tests_add_filter().
require_once "{$_tests_dir}/includes/functions.php";

//Load the plugin.
tests_add_filter(
	'muplugins_loaded',
	function () {
		//The plugin reads the currency symbol into the WP_CART_CURRENCY_SYMBOL constant when it loads, so it is set first
		//(like a site where the settings were saved). Activation doesn't set a currency.
		update_option( 'cart_payment_currency', 'USD' );
		update_option( 'cart_currency_symbol', '$' );

		require dirname( __DIR__, 2 ) . '/wordpress-paypal-shopping-cart/wp_shopping_cart.php';
	}
);

//Then run what the plugin's activation does (save the default settings).
//It runs on 'init' priority 1, like a real activation that happens after WordPress is loaded.
//Note: the test library deletes all posts after WordPress loads, so a page created here is gone when the tests run
//(the settings stay).
tests_add_filter(
	'init',
	function () {
		wpsc_plugin_install();
	},
	1
);

//Start the WordPress test environment.
require "{$_tests_dir}/includes/bootstrap.php";

//Test helpers.
require_once __DIR__ . '/includes/class-wpsc-test-helper.php';
