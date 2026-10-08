<?php
/**
 * Run: php tests/plugin-check-output.php /path/to/wordpress
 * Uses WordPress's actual KSES implementation without a database or payment I/O.
 */
$core = $argv[1] ?? getenv( 'WP_CORE_PATH' );
if ( ! $core || ! is_file( $core . '/wp-includes/kses.php' ) ) {
    fwrite( STDERR, "Supply the path to a WordPress installation.\n" );
    exit( 1 );
}
define( 'ABSPATH', rtrim( $core, '/' ) . '/' );
define( 'WPINC', 'wp-includes' );
require ABSPATH . WPINC . '/plugin.php';
require ABSPATH . WPINC . '/functions.php';
require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/kses.php';
// Newer WordPress releases use the HTML API internally in KSES.
foreach ( array( 'compat-utf8.php', 'compat.php', 'class-wp-token-map.php', 'utf8.php' ) as $file ) {
    if ( is_file( ABSPATH . WPINC . '/' . $file ) ) {
        require_once ABSPATH . WPINC . '/' . $file;
    }
}
preg_match_all( "~require ABSPATH \\. WPINC \\. '(/html-api/[^']+)';~", file_get_contents( ABSPATH . 'wp-settings.php' ), $html_api );
foreach ( $html_api[1] as $file ) {
    require_once ABSPATH . WPINC . $file;
}
$options = array( 'blog_charset' => 'UTF-8', 'wspsc_private_key_one' => 'test-key' );
add_filter( 'pre_option', function ( $pre, $name ) use ( &$options ) {
    return $options[ $name ] ?? '';
}, 10, 2 );
// Stub only services unrelated to HTML escaping.
function __( $text, $domain = '' ) { return $text; }
function wp_enqueue_script( $handle ) {}
function wp_create_nonce( $action ) { return 'test-nonce'; }
function cart_current_page_url() { return 'https://example.test/shop/?a=1&b=2'; }
function get_post() { return null; }
class WPSC_Dynamic_Products {
    public static function generate_product_key( $name, $price ) { return 'test-product'; }
}
class WPSC_Cart {
    public static function get_instance() { return new self(); }
    public function get_cart_id() { return ''; }
}
$plugin = dirname( __DIR__ ) . '/wordpress-paypal-shopping-cart/';
require $plugin . 'includes/wpsc-utility-kses.php';
require $plugin . 'includes/wpsc-utility-functions.php';
require $plugin . 'includes/wpsc-shortcodes-related.php';
$checks = 0;
function verify( $condition, $message ) {
    global $checks;
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
    ++$checks;
}

$_SERVER['REQUEST_URI'] = '/shop/';
$raw = print_wp_cart_button_for_product( 'Tea & "Biscuits"', '10', '2', 'Size|Small|Large', '', '', array( 'button_image' => 'https://example.test/buy.png' ) );
$safe = wp_kses( $raw, WPSC_Utility_Kses::wp_kses_post_tags_with_form() );
function canonical_html( $html ) {
    $dom = new DOMDocument();
    $dom->loadHTML( $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
    return $dom->C14N();
}
verify( canonical_html( $raw ) === canonical_html( $safe ), 'The product form must survive KSES unchanged.' );
foreach ( array( '<form ', 'name="_wpnonce"', 'type="image"', 'src="https://example.test/buy.png"', 'name="variation1"', 'data-display-text=', 'data-price=', 'name="wspsc_product"', 'name="price"', 'name="shipping"', 'name="addcart"' ) as $required ) {
    verify( false !== strpos( $safe, $required ), 'Missing required product markup: ' . $required );
}
verify( false === strpos( $raw, 'onsubmit=' ) && false === strpos( $raw, 'onchange=' ), 'Product forms should use the registered JS event listeners.' );
$controls = '<form method="post" data-wpsc-confirm="Delete?"><input type="hidden" name="id" value="7" /><input type="checkbox" checked="checked" /><select name="country"><optgroup label="Countries"><option value="US" selected="selected">USA</option></optgroup></select><textarea name="message" rows="4">Text</textarea><button type="submit">Save</button></form>';
verify( canonical_html( $controls ) === canonical_html( wp_kses( $controls, WPSC_Utility_Kses::wp_kses_post_tags_with_form() ) ), 'Form fields, selections, and confirmation data must survive.' );
$hostile = '<form action="javascript:alert(1)" onsubmit="alert(1)"><input type="image" src="javascript:alert(2)" onerror="alert(3)" /><script>alert(4)</script></form>';
$filtered = wp_kses( $hostile, WPSC_Utility_Kses::wp_kses_post_tags_with_form() );
foreach ( array( 'javascript:', 'onsubmit=', 'onerror=', '<script' ) as $forbidden ) {
    verify( false === strpos( $filtered, $forbidden ), 'Unsafe markup survived: ' . $forbidden );
}
$allowed = WPSC_Utility_Kses::wp_kses_post_tags( array( 'a' => array( 'href' => true ) ) );
verify( true === $allowed['a']['href'], 'Merging allowlists must not turn attribute flags into arrays.' );
$options_html = wpsc_get_countries_opts( 'US' );
verify( canonical_html( $options_html ) === canonical_html( wp_kses( $options_html, WPSC_Utility_Kses::wp_kses_select_option_tags() ) ), 'Country options and selection must survive.' );
$attrs = WPSC_Utility_Kses::escape_attributes( 'width="100" height="50" onload="alert(1)"', 'img' );
verify( 'width="100" height="50"' === $attrs, 'Gallery dimensions must survive without event handlers or extra tags.' );
$attrs = WPSC_Utility_Kses::escape_attributes( 'class="lightbox" rel="gallery" data-id="5" href="javascript:alert(1)" onclick="alert(2)"', 'a' );
verify( false !== strpos( $attrs, 'rel="gallery"' ) && false !== strpos( $attrs, 'data-id="5"' ), 'Gallery lightbox attributes must survive.' );
verify( false === strpos( $attrs, 'javascript:' ) && false === strpos( $attrs, 'onclick' ), 'Unsafe gallery attributes must be removed.' );
$attrs = WPSC_Utility_Kses::escape_attributes( 'style="width: 120px"', 'div' );
verify( false !== strpos( $attrs, 'width: 120px' ), 'Gallery layout width must survive.' );
echo "$checks output checks passed.\n";
