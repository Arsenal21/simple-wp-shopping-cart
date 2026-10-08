<?php
/** Run: php tests/paypal-recipient.php. Isolated tests; WordPress and PayPal I/O are stubbed. */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
define( 'WP_CART_VERSION', 'test' );
define( 'ABSPATH', __DIR__ . '/' );
$options = array();
$writes = array();
$reply = 'VERIFIED';
function get_option( $key, $default = false ) { return $GLOBALS['options'][$key] ?? $default; }
function is_email( $value ) { return filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function status_header( $code ) {}
function wpsc_get_log_file_name() { return ''; }
function wpsc_get_log_file() { return ''; }
function wp_cart_get_custom_var_array( $value ) { parse_str( $value, $data ); return $data; }
function wpsc_get_cart_cpt_id_by_cart_id( $value ) { return 123; }
function get_post_status( $id ) { return 'draft'; }
function get_post_meta( $id, $key, $single = true ) {
    if ( 'wpsc_cart_items' === $key ) { return array( new RecipientTestItem() ); }
    return 'wpsc_txn_id' === $key ? ( $GLOBALS['existing_txn'] ?? '' ) : '';
}
function wp_update_post( $value ) { $GLOBALS['writes'][] = $value; }
function update_post_meta( $id, $key, $value ) {
    $GLOBALS['writes'][$key] = $value;
    if ( 'wpsc_order_status' === $key ) { throw new RecipientTestPaid(); }
}
function wp_unslash( $value ) { return $value; }
function wp_safe_remote_post( $url, $params ) { return array( 'body' => $GLOBALS['reply'] ); }
function is_wp_error( $value ) { return false; }
class WPSC_Cart { const POST_TYPE = 'wpsc_cart_orders'; }
class RecipientTestItem {
    public function get_price() { return 500; }
    public function get_quantity() { return 1; }
}
class RecipientTestPaid extends Exception {}
require dirname( __DIR__ ) . '/wordpress-paypal-shopping-cart/paypal.php';
class RecipientTestHandler extends paypal_ipn_handler {
    public $messages = array();
    public function debug_log( $message, $success, $end = false ) { $this->messages[] = array( $message, $success ); }
    public function debug_log_array( $data, $success, $end = false ) {}
}
$passed = 0;
function run_case( $label, $merchant, $recipient, $expected, $strict = null, $overrides = array(), $response = 'VERIFIED', $existing = '', $expected_log = '' ) {
    global $options, $writes, $reply, $existing_txn, $passed;
    $options = array( 'cart_paypal_email' => $merchant, 'cart_payment_currency' => 'USD' );
    if ( null !== $strict ) { $options['wp_shopping_cart_strict_email_check'] = $strict; }
    $writes = array(); $reply = $response; $existing_txn = $existing;
    $_POST = array_merge( array(
        'custom' => 'wp_cart_id=test', 'txn_id' => 'test-txn', 'txn_type' => 'web_accept',
        'payment_status' => 'Completed', 'transaction_subject' => '', 'first_name' => 'Test',
        'last_name' => 'Buyer', 'payer_email' => 'buyer@example.test', 'receiver_email' => $recipient,
        'item_number' => '1', 'item_name' => 'Widget', 'quantity' => '1', 'mc_gross' => '500.00', 'mc_currency' => 'USD',
    ), $overrides );
    if ( null === $recipient ) { unset( $_POST['receiver_email'] ); }
    $handler = new RecipientTestHandler();
    try {
        if ( $handler->validate_ipn() ) { $handler->validate_and_dispatch_product(); }
    } catch ( RecipientTestPaid $e ) {
        // Stop before fulfillment/email side effects, after the real handler writes Paid.
    }
    $paid = isset( $writes['wpsc_order_status'] ) && 'Paid' === $writes['wpsc_order_status'];
    if ( $expected !== $paid || ( ! $expected && $writes ) ) { throw new RuntimeException( 'FAIL: ' . $label ); }
    if ( $expected_log !== '' ) {
        $found = false;
        foreach ( $handler->messages as $entry ) {
            if ( false !== strpos( $entry[0], $expected_log ) && $entry[1] === $expected ) { $found = true; }
        }
        if ( ! $found ) { throw new RuntimeException( 'FAIL: missing diagnostic or wrong log severity: ' . $label ); }
    }
    ++$passed;
    echo "PASS: $label\n";
}
foreach ( array( null, '', 'checked="checked"' ) as $strict ) {
    run_case( 'matching recipient; old option=' . var_export( $strict, true ), 'merchant@example.test', 'merchant@example.test', true, $strict );
    run_case( 'wrong recipient; old option=' . var_export( $strict, true ), 'merchant@example.test', 'attacker@example.test', false, $strict );
}
run_case( 'case differences', 'Merchant@Example.Test', 'merchant@example.test', true );
run_case( 'surrounding whitespace', ' merchant@example.test ', "\tmerchant@example.test\n", true );
foreach ( array( null, '', 'bad-address', array( 'merchant@example.test' ), '<b>merchant@example.test</b>' ) as $recipient ) {
    run_case( 'invalid or missing recipient: ' . json_encode( $recipient ), 'merchant@example.test', $recipient, false );
}
foreach ( array( false, '', 'bad-address', array( 'merchant@example.test' ) ) as $merchant ) {
    run_case( 'invalid or missing merchant: ' . json_encode( $merchant ), $merchant, 'merchant@example.test', false );
}
run_case( 'payer and business cannot override wrong recipient', 'merchant@example.test', 'attacker@example.test', false, '', array( 'payer_email' => 'merchant@example.test', 'business' => 'merchant@example.test' ) );
run_case( 'alias business field allowed when primary recipient matches', 'merchant@example.test', 'merchant@example.test', true, '', array( 'business' => 'alias@example.test' ) );
run_case( 'configured alias must be changed to primary email', 'alias@example.test', 'merchant@example.test', false );
run_case( 'invalid PayPal verification', 'merchant@example.test', 'merchant@example.test', false, '', array(), 'INVALID' );
run_case( 'underpayment still rejected', 'merchant@example.test', 'merchant@example.test', false, '', array( 'mc_gross' => '0.01' ) );
run_case( 'wrong currency still rejected', 'merchant@example.test', 'merchant@example.test', false, '', array( 'mc_currency' => 'EUR' ) );
run_case( 'pending payment not fulfilled', 'merchant@example.test', 'merchant@example.test', false, '', array( 'payment_status' => 'Pending' ) );
run_case( 'duplicate transaction not fulfilled', 'merchant@example.test', 'merchant@example.test', false, '', array(), 'VERIFIED', 'test-txn' );
run_case( 'configuration failure diagnostic', '', 'merchant@example.test', false, null, array(), 'VERIFIED', '', 'configured PayPal email address is missing or invalid' );
run_case( 'missing recipient diagnostic', 'merchant@example.test', null, false, null, array(), 'VERIFIED', '', 'receiver_email is missing, empty or not a string' );
run_case( 'malformed recipient diagnostic', 'merchant@example.test', '<b>merchant@example.test</b>', false, null, array(), 'VERIFIED', '', 'receiver_email is not a valid email address' );
run_case( 'mismatch diagnostic contains expected and actual addresses', 'merchant@example.test', 'attacker@example.test', false, null, array(), 'VERIFIED', '', 'Configured email: merchant@example.test; receiver_email: attacker@example.test' );
run_case( 'non-string field diagnostic identifies field', 'merchant@example.test', array( 'attacker@example.test' ), false, null, array(), 'VERIFIED', '', 'non-string value for field "receiver_email"' );
run_case( 'matching recipient success diagnostic', 'merchant@example.test', 'merchant@example.test', true, null, array(), 'VERIFIED', '', 'PayPal recipient verification passed. receiver_email: merchant@example.test' );
echo "$passed checks passed.\n";
