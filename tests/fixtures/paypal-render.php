<?php
/** Render real PPCP markup without WordPress or payment I/O, for the JS regression tests. */
namespace TTHQ\WPSC\Lib\PayPal {
    class PayPal_Utility_Functions {
        public static function hook( $name ) { return "wpsc_" . $name; }
    }
    class PayPal_PPCP_Config {
        public static function get_instance() { return new self(); }
        public function get_value( $name ) {
            return array(
                'paypal-live-client-id' => 'test-client',
                'ppcp_btn_height' => 'medium',
            )[ $name ] ?? '';
        }
    }
}
namespace {
    define( 'ABSPATH', __DIR__ . '/' );
    function get_option( $name ) { return ''; }
    function __( $text, $domain ) { return $text; }
    function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
    function esc_js( $value ) { return addslashes( (string) $value ); }
    function esc_url_raw( $value ) { return $value; }
    function absint( $value ) { return abs( (int) $value ); }
    function admin_url( $path ) { return '/wp-admin/' . $path; }
    function wp_create_nonce( $action ) { return 'nonce-' . $action; }
    function wpsc_ppcp_disable_funding_options() { return array(); }
    function add_action( $hook, $callback ) {}
    function apply_filters( $hook, $value ) { return $value; }
    function wpsc_get_paypal_checkout_locale_code() { return ''; }
    function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
    function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
    function wp_cart_add_custom_field() { return ''; }
    class WPSC_Cart {
        public static function get_instance() { return new self(); }
        public function get_cart_id() { return 'cart-' . $GLOBALS['argv'][1]; }
        public function calculate_cart_totals_and_postage() {}
        public function get_sub_total_formatted() { return '10.00'; }
        public function get_postage_cost_formatted() { return '0.00'; }
        public function get_grand_total_formatted() { return '10.00'; }
    }
    $plugin = dirname( __DIR__, 2 ) . '/wordpress-paypal-shopping-cart/';
    require $plugin . 'lib/paypal/class-tthq-paypal-js-button-embed.php';
    require $plugin . 'includes/wpsc-paypal-ppcp-checkout-form-related.php';
    $markup = wpsc_render_paypal_ppcp_checkout_form( array( 'carts_cnt' => (int) $argv[1], 'currency' => 'USD' ) );
    if ( isset( $argv[2] ) && 'sdk' === $argv[2] ) {
        \TTHQ\WPSC\Lib\PayPal\PayPal_JS_Button_Embed::get_instance()->load_paypal_sdk();
    } else {
        echo $markup;
    }
}
