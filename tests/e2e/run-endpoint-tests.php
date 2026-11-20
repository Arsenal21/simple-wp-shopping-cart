<?php
/**
 * Endpoint tests: sends the requests of a real shopping session to a running test site, then checks the saved cart and order.
 * Run by the GitHub Actions "Plugin Tests" workflow (the "Endpoint tests" job sets up the site). Environment variables:
 * E2E_URL (example: http://127.0.0.1:8080), E2E_WP_PATH (the site's folder), E2E_DB_HOST, E2E_DB_NAME, E2E_DB_USER, E2E_DB_PASS.
 *
 * What a buyer does, and the tests do the same requests (there is no browser, so the JavaScript is not run;
 * the runner adds the selected variation to the product name like the ReadForm() script does):
 * 1. Opens the shop page and submits an add to cart form: a normal form post to the page, or (with "AJAX add to cart" on)
 *    a request to admin-ajax.php (action wpsc_add_to_cart). The cart ID is kept in the simple_cart_id cookie.
 * 2. Opens the cart page: changes a quantity, removes an item, applies a coupon (form posts to the page).
 * 3. Pays with PayPal Standard: the cart page has the PayPal form. PayPal then sends the payment notification (IPN) to
 *    /?simple_cart_ipn=1; the plugin sends it back to PayPal to verify it (a fake PayPal answers, see mu-plugin-e2e-fakes.php),
 *    checks it, marks the order paid and sends the emails. Or uses the manual checkout (admin-ajax.php, JSON).
 */
$url      = rtrim( getenv( 'E2E_URL' ), '/' );
$wp_path  = getenv( 'E2E_WP_PATH' );
$db       = new mysqli( getenv( 'E2E_DB_HOST' ), getenv( 'E2E_DB_USER' ), getenv( 'E2E_DB_PASS' ), getenv( 'E2E_DB_NAME' ) );
$prefix   = 'wp_';
$failures = 0;
$known    = 0;
$cookie_jar = null;

//Known issues that aren't fixed (see "Known issues" in tests/TESTS.md). Their checks still run, but don't fail the tests:
//the output shows "KNOWN ISSUE (still happens)" or "KNOWN ISSUE NOW PASSES". The commit that fixes one removes it from this list,
//so its check becomes a normal check. "Suspected" means the code reads that way, but the check hasn't failed in a run yet.
const KNOWN_ISSUES = array(
	'paid-cart-reused'            => 'Suspected: the cart is loaded from the simple_cart_id cookie without checking that its order is already paid. When a buyer pays with PayPal Standard and doesn\'t come back to the return URL (which resets the cart), the next add to cart changes the items of the paid order, and a second payment notification for the cart overwrites the order.',
	'manual-checkout-coupon-code' => 'Suspected: the manual checkout reads the applied coupon with the cart ID (get_applied_coupon_code( $cart_obj->get_cart_id() ) in manual-checkout.php) instead of the order post ID, so the coupon code is not saved on the order (or another post\'s value is read).',
);

function check_known( $issue, $label, $condition, $details = '' ) {
	global $known;
	if ( ! isset( KNOWN_ISSUES[ $issue ] ) ) {
		return check( $label, $condition, $details );
	}
	$known++;
	if ( $condition ) {
		echo "KNOWN ISSUE NOW PASSES: {$label}\n    Remove '{$issue}' from KNOWN_ISSUES if it is fixed: " . KNOWN_ISSUES[ $issue ] . "\n";
	} else {
		echo "KNOWN ISSUE (still happens): {$label}\n    " . KNOWN_ISSUES[ $issue ] . "\n";
		if ( '' !== $details ) {
			echo '    ' . $details . "\n";
		}
	}
	return $condition;
}

function check( $label, $condition, $details = '' ) {
	global $failures;
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		$failures++;
		echo "FAIL: {$label}\n";
		if ( '' !== $details ) {
			echo '    ' . $details . "\n";
		}
	}
	return $condition;
}

function section( $title ) {
	echo "\n=== {$title} ===\n";
}

function wp_cli( $command ) {
	global $wp_path;
	$output = array();
	exec( 'wp --path=' . escapeshellarg( $wp_path ) . ' ' . $command . ' 2>&1', $output, $exit_code );
	if ( 0 !== $exit_code ) {
		echo "WP-CLI command failed: wp {$command}\n" . implode( "\n", $output ) . "\n";
		exit( 1 );
	}
	return $output;
}

/** The last line of WP-CLI output that is a JSON object (other lines can be PHP notices). */
function last_json( $output ) {
	foreach ( array_reverse( $output ) as $line ) {
		if ( 0 === strpos( ltrim( $line ), '{' ) ) {
			return json_decode( $line, true );
		}
	}
	return null;
}

/**
 * The saved cart of a cart ID (read with the plugin's code, see get-cart.php).
 */
function get_cart( $cart_id ) {
	$output = wp_cli( 'eval-file ' . escapeshellarg( __DIR__ . '/get-cart.php' ) . ' ' . escapeshellarg( (string) $cart_id ) );
	$cart   = last_json( $output );
	if ( ! is_array( $cart ) ) {
		echo 'Could not read the cart: ' . implode( "\n", $output ) . "\n";
		exit( 1 );
	}
	return $cart;
}

/** Item name => quantity x price, to compare carts and show them when a check fails. */
function cart_summary( $cart ) {
	$summary = array();
	foreach ( $cart['items'] as $item ) {
		$summary[ $item['name'] ] = $item['quantity'] . ' x ' . $item['price'];
	}
	ksort( $summary );
	return $summary;
}

function find_item( $cart, $name ) {
	foreach ( $cart['items'] as $item ) {
		if ( $item['name'] === $name ) {
			return $item;
		}
	}
	return null;
}

/** Starts a browser session: the following requests keep their cookies. */
function new_session() {
	global $cookie_jar;
	$cookie_jar = tempnam( sys_get_temp_dir(), 'wpsc-e2e-cookies-' );
	return $cookie_jar;
}

/** Switches to a session from new_session(), or to no cookies (null). */
function use_session( $file ) {
	global $cookie_jar;
	$cookie_jar = $file;
}

/** A cookie of the current session (null when it isn't set). */
function cookie_value( $name ) {
	global $cookie_jar;
	if ( null === $cookie_jar || ! is_file( $cookie_jar ) ) {
		return null;
	}
	foreach ( file( $cookie_jar, FILE_IGNORE_NEW_LINES ) as $line ) {
		if ( 0 === strpos( $line, '#HttpOnly_' ) ) {
			$line = substr( $line, 10 );
		} elseif ( '' === $line || '#' === $line[0] ) {
			continue;
		}
		$fields = explode( "\t", $line );
		if ( count( $fields ) >= 7 && $fields[5] === $name ) {
			return $fields[6];
		}
	}
	return null;
}

/**
 * Sends a request (redirects are not followed). $endpoint is a path on the site or a full URL.
 * $fields is an array (sent as a form) or a string (sent as it is, with the Content-Type header in $headers).
 *
 * @return array The HTTP status code, the body and the Location header.
 */
function http_request( $method, $endpoint, $fields = null, $headers = array() ) {
	global $url, $cookie_jar;
	$location = '';
	$curl     = curl_init( 0 === strpos( $endpoint, 'http' ) ? $endpoint : $url . $endpoint );
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_USERAGENT      => 'WPSC-E2E-Tests/1.0',
			CURLOPT_HEADERFUNCTION => function ( $curl, $header ) use ( &$location ) {
				if ( 0 === stripos( $header, 'Location:' ) ) {
					$location = trim( substr( $header, 9 ) );
				}
				return strlen( $header );
			},
		)
	);
	if ( null !== $fields ) {
		curl_setopt( $curl, CURLOPT_POSTFIELDS, is_array( $fields ) ? http_build_query( $fields ) : $fields );
	}
	if ( null !== $cookie_jar ) {
		curl_setopt( $curl, CURLOPT_COOKIEFILE, $cookie_jar );
		curl_setopt( $curl, CURLOPT_COOKIEJAR, $cookie_jar );
	}
	$body   = (string) curl_exec( $curl );
	$status = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	//The cookie file is written when the handle is closed (curl_close() does nothing since PHP 8.0).
	if ( PHP_VERSION_ID < 80000 ) {
		curl_close( $curl );
	}
	unset( $curl );
	return array(
		'status'   => $status,
		'body'     => $body,
		'location' => $location,
		'json'     => json_decode( $body, true ),
	);
}

/** The response text without the HTML, shortened (printed when a check fails). */
function response_text( $response ) {
	$text = trim( preg_replace( '/\s+/', ' ', strip_tags( preg_replace( '#<(style|script)\b.*?</\1>#s', '', $response['body'] ) ) ) );
	return 'HTTP ' . $response['status'] . ': ' . substr( $text, 0, 300 );
}

function db_value( $sql, $types = '', $params = array() ) {
	global $db;
	$statement = $db->prepare( $sql );
	if ( $types ) {
		$statement->bind_param( $types, ...$params );
	}
	$statement->execute();
	$row = $statement->get_result()->fetch_row();
	return $row ? $row[0] : null;
}

function db_write( $sql, $types, $params ) {
	global $db;
	$statement = $db->prepare( $sql );
	$statement->bind_param( $types, ...$params );
	$statement->execute();
}

//Options are read and written directly in the database. Each request to the site reads them again (there is no object cache).
function get_site_option_value( $name ) {
	global $prefix;
	$value = db_value( "SELECT option_value FROM {$prefix}options WHERE option_name = ?", 's', array( $name ) );
	if ( null === $value ) {
		return false;
	}
	$unserialized = @unserialize( $value, array( 'allowed_classes' => false ) );
	return ( false === $unserialized && 'b:0;' !== $value ) ? $value : $unserialized;
}

function set_site_option_value( $name, $value ) {
	global $prefix;
	$value = is_scalar( $value ) ? (string) $value : serialize( $value );
	db_write( "INSERT INTO {$prefix}options (option_name, option_value, autoload) VALUES (?, ?, 'off') ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)", 'ss', array( $name, $value ) );
}

function delete_site_option_value( $name ) {
	global $prefix;
	db_write( "DELETE FROM {$prefix}options WHERE option_name = ?", 's', array( $name ) );
}

function post_meta( $post_id, $key ) {
	global $prefix;
	return db_value( "SELECT meta_value FROM {$prefix}postmeta WHERE post_id = ? AND meta_key = ? ORDER BY meta_id DESC LIMIT 1", 'is', array( $post_id, $key ) );
}

function emails() {
	$emails = get_site_option_value( 'wpsc_e2e_emails' );
	return is_array( $emails ) ? $emails : array();
}

function find_email( $to, $subject ) {
	foreach ( emails() as $email ) {
		if ( $email['to'] === $to && $email['subject'] === $subject ) {
			return $email;
		}
	}
	return null;
}

function reset_fakes() {
	delete_site_option_value( 'wpsc_e2e_emails' );
	delete_site_option_value( 'wpsc_e2e_paypal_requests' );
}

/**
 * The forms of a page that have this class (or all forms), with their field values like a browser would send them:
 * the hidden, text and number inputs; the first option of a select (or the selected one); not the image/submit buttons.
 * The select options are also listed (value and data-display-text), so a test can pick one.
 */
function get_forms( $html, $class = '' ) {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html );
	libxml_clear_errors();
	$forms = array();
	foreach ( $dom->getElementsByTagName( 'form' ) as $form ) {
		if ( '' !== $class && ! in_array( $class, explode( ' ', $form->getAttribute( 'class' ) ), true ) ) {
			continue;
		}
		$fields  = array();
		$selects = array();
		foreach ( $form->getElementsByTagName( 'input' ) as $input ) {
			$type = strtolower( $input->getAttribute( 'type' ) );
			if ( '' === $input->getAttribute( 'name' ) || in_array( $type, array( 'image', 'submit', 'checkbox', 'radio' ), true ) ) {
				continue;
			}
			$fields[ $input->getAttribute( 'name' ) ] = $input->getAttribute( 'value' );
		}
		foreach ( $form->getElementsByTagName( 'select' ) as $select ) {
			$name    = $select->getAttribute( 'name' );
			$options = array();
			foreach ( $select->getElementsByTagName( 'option' ) as $option ) {
				$options[] = array(
					'value'        => $option->getAttribute( 'value' ),
					'display_text' => $option->getAttribute( 'data-display-text' ),
					'selected'     => $option->hasAttribute( 'selected' ),
				);
			}
			$selects[ $name ] = $options;
			$fields[ $name ]  = isset( $options[0] ) ? $options[0]['value'] : '';
			foreach ( $options as $option ) {
				if ( $option['selected'] ) {
					$fields[ $name ] = $option['value'];
				}
			}
		}
		$forms[] = array(
			'action'  => $form->getAttribute( 'action' ),
			'fields'  => $fields,
			'selects' => $selects,
		);
	}
	return $forms;
}

function find_form( $forms, $field, $value = null ) {
	foreach ( $forms as $form ) {
		if ( array_key_exists( $field, $form['fields'] ) && ( null === $value || $form['fields'][ $field ] === $value ) ) {
			return $form;
		}
	}
	return null;
}

/**
 * Opens the shop page and submits the add to cart form of a product.
 *
 * @param string $product    The product name (product_tmp field of the form).
 * @param array  $variations Variation select name => option value to choose (default: the first option).
 * @param array  $changes    Form fields to change (a tampered request); null removes the field.
 * @param string $mode       'normal' (form post to the page) or 'ajax' (admin-ajax.php).
 */
function add_to_cart( $product, $variations = array(), $changes = array(), $mode = 'normal' ) {
	global $ids;
	$page = http_request( 'GET', $ids['shop_url'] );
	$form = find_form( get_forms( $page['body'], 'wp-cart-button-form' ), 'product_tmp', $product );
	if ( null === $form ) {
		check( "The add to cart form of {$product} is on the shop page", false, response_text( $page ) );
		return null;
	}
	$fields = $form['fields'];

	//What the ReadForm() script does: add the selected variations to the product name.
	foreach ( $form['selects'] as $name => $options ) {
		$selected = isset( $variations[ $name ] ) ? $variations[ $name ] : $options[0]['value'];
		$fields[ $name ] = $selected;
		foreach ( $options as $option ) {
			if ( $option['value'] === $selected ) {
				$fields['wspsc_product'] .= ' (' . $option['display_text'] . ')';
			}
		}
	}

	foreach ( $changes as $name => $value ) {
		if ( null === $value ) {
			unset( $fields[ $name ] );
		} else {
			$fields[ $name ] = $value;
		}
	}

	if ( 'ajax' === $mode ) {
		//What the AJAX add to cart script sends.
		unset( $fields['addcart'] );
		$fields['action']          = 'wpsc_add_to_cart';
		$fields['getCart']         = '0';
		$fields['getCompactCart']  = '0';
		$fields['getCompactCart2'] = '0';
		return http_request( 'POST', '/wp-admin/admin-ajax.php', $fields );
	}
	$fields['wspsc_add_cart_submit'] = 'Add to Cart';
	return http_request( 'POST', $ids['shop_url'], $fields );
}

function add_to_cart_succeeded( $response, $mode ) {
	if ( 'ajax' === $mode ) {
		return is_array( $response['json'] ) && true === $response['json']['success'];
	}
	return 200 === $response['status'] && false === strpos( $response['body'], 'Error!' );
}

/** The PayPal Standard checkout form on the cart page. */
function get_paypal_form( $cart_page ) {
	$forms = get_forms( $cart_page['body'], 'wpsc_checkout_form_standard' );
	return isset( $forms[0] ) ? $forms[0] : null;
}

/**
 * The payment notification PayPal sends for the PayPal Standard form on the cart page (a completed cart payment).
 */
function build_ipn( $paypal_form, $txn_id ) {
	$fields = $paypal_form['fields'];
	$ipn    = array(
		'txn_type'            => 'cart',
		'payment_status'      => 'Completed',
		'txn_id'              => $txn_id,
		'receiver_email'      => $fields['business'],
		'business'            => $fields['business'],
		'payer_email'         => 'buyer@example.com',
		'first_name'          => 'E2E',
		'last_name'           => 'Buyer',
		'mc_currency'         => $fields['currency_code'],
		'custom'              => $fields['custom'],
		'transaction_subject' => '',
		'verify_sign'         => 'e2e-verified',
	);
	$total = 0;
	$i     = 1;
	while ( isset( $fields[ 'item_name_' . $i ] ) ) {
		$item_total = round( (float) $fields[ 'amount_' . $i ] * (int) $fields[ 'quantity_' . $i ], 2 );
		$ipn[ 'item_name' . $i ]   = $fields[ 'item_name_' . $i ];
		$ipn[ 'item_number' . $i ] = $fields[ 'item_number_' . $i ];
		$ipn[ 'quantity' . $i ]    = $fields[ 'quantity_' . $i ];
		$ipn[ 'mc_gross_' . $i ]   = number_format( $item_total, 2, '.', '' );
		$total += $item_total;
		$i++;
	}
	$shipping               = isset( $fields['shipping_1'] ) ? (float) $fields['shipping_1'] : 0;
	$tax                    = isset( $fields['tax_cart'] ) ? (float) $fields['tax_cart'] : 0;
	$ipn['num_cart_items']  = (string) ( $i - 1 );
	$ipn['mc_shipping']     = number_format( $shipping, 2, '.', '' );
	$ipn['tax']             = number_format( $tax, 2, '.', '' );
	$ipn['mc_gross']        = number_format( $total + $shipping + $tax, 2, '.', '' );
	return $ipn;
}

/** Sends a payment notification like PayPal does (no browser cookies). */
function send_ipn( $ipn ) {
	global $cookie_jar;
	$session = $cookie_jar;
	use_session( null );
	$response = http_request( 'POST', '/?simple_cart_ipn=1', $ipn );
	use_session( $session );
	return $response;
}

/** Opens the cart page and submits one of its forms (with $field, and $value when given), with these field changes. */
function submit_cart_form( $field, $value, $changes ) {
	global $ids;
	$page = http_request( 'GET', $ids['cart_url'] );
	$form = find_form( get_forms( $page['body'] ), $field, $value );
	if ( null === $form ) {
		check( "The cart page has the form with the {$field} field", false, response_text( $page ) );
		return null;
	}
	return http_request( 'POST', $ids['cart_url'], array_merge( $form['fields'], $changes ) );
}

/** Sends the manual checkout request like the manual checkout script does (JSON). */
function manual_checkout( $payload ) {
	return http_request( 'POST', '/wp-admin/admin-ajax.php?action=wpsc_manual_payment_checkout', json_encode( $payload ), array( 'Content-Type: application/json' ) );
}

//Set up the test data.
$setup_output = wp_cli( 'eval-file ' . escapeshellarg( __DIR__ . '/setup-site.php' ) );
$ids          = last_json( $setup_output );
if ( ! is_array( $ids ) ) {
	echo "The test data could not be created:\n" . implode( "\n", $setup_output ) . "\n";
	exit( 1 );
}

$shirt_large = 'E2E Shirt (Large +$5)';
$shirt_small = 'E2E Shirt (Small)';
$ebook       = 'E2E Ebook';

/*
 * Add to cart, with AJAX add to cart off and on (two copies of the handler: wpsc_cart_actions_handler() and WPSC_Cart_Ajax_Handler).
 */
foreach ( array( 'normal', 'ajax' ) as $mode ) {
	section( "Add to cart ({$mode})" );
	if ( 'ajax' === $mode ) {
		set_site_option_value( 'wpsc_enable_ajax_add_to_cart', '1' );
	} else {
		delete_site_option_value( 'wpsc_enable_ajax_add_to_cart' );
	}
	new_session();

	//An item number with an apostrophe: before 5.3.4 it was saved with a backslash (Bob\'s-1).
	$response = add_to_cart( 'E2E Shirt', array( 'variation1' => 'Large::5' ), array( 'item_number' => "Bob's-1" ), $mode );
	check( "[{$mode}] The shirt (Large) is added to the cart", add_to_cart_succeeded( $response, $mode ), response_text( $response ) );
	$cart_id = cookie_value( 'simple_cart_id' );
	check( "[{$mode}] The cart ID cookie is set (32 hex characters)", 1 === preg_match( '/^[a-f0-9]{32}$/', (string) $cart_id ), 'Cookie: ' . var_export( $cart_id, true ) );
	$cart = get_cart( $cart_id );
	$item = find_item( $cart, $shirt_large );
	check( "[{$mode}] The cart has the shirt with the variation in its name", null !== $item && 1 === count( $cart['items'] ), json_encode( $cart['items'] ) );
	if ( $item ) {
		check( "[{$mode}] The price includes the variation (20 + 5)", '25' === $item['price'], 'Price: ' . $item['price'] );
		check( "[{$mode}] The shipping is the product's shipping", '2' === $item['shipping'], 'Shipping: ' . $item['shipping'] );
		check( "[{$mode}] The item number is saved without a backslash (fixed in 5.3.4)", "Bob's-1" === $item['item_number'], 'Item number: ' . $item['item_number'] );
	}
	check( "[{$mode}] The cart order is in progress", 'In Progress' === $cart['order_status'], 'Status: ' . $cart['order_status'] );

	$response = add_to_cart( 'E2E Shirt', array( 'variation1' => 'Large::5' ), array(), $mode );
	$cart     = get_cart( $cart_id );
	$item     = find_item( $cart, $shirt_large );
	check( "[{$mode}] Adding the same item again makes the quantity 2", add_to_cart_succeeded( $response, $mode ) && $item && 2 === $item['quantity'], json_encode( cart_summary( $cart ) ) );

	$response = add_to_cart( 'E2E Ebook', array(), array(), $mode );
	$cart     = get_cart( $cart_id );
	$item     = find_item( $cart, $ebook );
	check( "[{$mode}] The ebook is added as a second item", add_to_cart_succeeded( $response, $mode ) && $item && 2 === count( $cart['items'] ), json_encode( cart_summary( $cart ) ) );
	if ( $item ) {
		check( "[{$mode}] The ebook's file URL comes from the saved product", 'https://example.com/e2e-ebook.pdf' === $item['file_url'], 'File URL: ' . var_export( $item['file_url'], true ) );
		check( "[{$mode}] The ebook is a digital item", true === $item['digital'] );
	}

	//Tampered requests: each one is rejected, and the cart doesn't change.
	$cart_before = cart_summary( $cart );
	$tampered    = array(
		'a wrong nonce'                  => array( array( '_wpnonce' => 'abc1234567' ), 'Nonce Security Check Failed' ),
		'a lower price'                  => array( array( 'price' => '1' ), 'price field may have been tampered' ),
		'a product that was not saved'   => array( array( 'product_tmp' => 'E2E Unknown Product', 'wspsc_product' => 'E2E Unknown Product' ), 'price field may have been tampered' ),
		'a lower shipping cost'          => array( array( 'shipping' => '0' ), 'shipping price validation failed' ),
		'a variation with another price' => array( array( 'variation1' => 'Large::0' ), 'Invalid product variation' ),
	);
	foreach ( $tampered as $label => $test ) {
		$response = add_to_cart( 'E2E Shirt', array( 'variation1' => 'Large::5' ), $test[0], $mode );
		check(
			"[{$mode}] Add to cart with {$label} is rejected",
			! add_to_cart_succeeded( $response, $mode ) && false !== strpos( $response['body'], $test[1] ),
			response_text( $response )
		);
		$cart = get_cart( $cart_id );
		check( "[{$mode}] ... and the cart doesn't change", cart_summary( $cart ) === $cart_before, json_encode( cart_summary( $cart ) ) );
	}

	//The PHP object injection payload from the Patchstack report (fixed in 5.3.4): it stays plain text in the cart.
	delete_site_option_value( 'wpsc_e2e_gadget_woken' );
	new_session();
	$gadget  = 'O:15:"WPSC_E2E_Gadget":0:{}';
	$payload = '";s:8:"file_url";' . $gadget . 's:9:"thumbnail";N;s:9:"stamp_pdf";N;s:12:"digital_flag";s:30:"';
	$gap     = '";s:11:"' . "\0*\0" . 'file_url";N;s:12:"' . "\0*\0" . 'thumbnail";N;s:12:"' . "\0*\0" . 'stamp_pdf";s:' . ( strlen( $payload ) + substr_count( $payload, '"' ) ) . ':"';
	$quotes  = str_repeat( "'", strlen( $gap ) );
	$response = add_to_cart( 'E2E Shirt', array(), array( 'item_number' => $quotes, 'stamp_pdf' => $payload ), $mode );
	check( "[{$mode}] The object injection request is handled like a normal add to cart", add_to_cart_succeeded( $response, $mode ), response_text( $response ) );
	http_request( 'GET', '/' ); //The next page view loads the saved cart.
	check( "[{$mode}] No object of another class is created from the saved cart", false === get_site_option_value( 'wpsc_e2e_gadget_woken' ) );
	$cart = get_cart( cookie_value( 'simple_cart_id' ) );
	$item = isset( $cart['object_items'][0] ) ? $cart['object_items'][0] : null;
	check(
		"[{$mode}] The saved cart has the item number and stamp_pdf values as plain text",
		1 === count( $cart['object_items'] ) && $item && $quotes === $item['item_number'] && $payload === $item['stamp_pdf'] && null === $item['file_url'],
		json_encode( $cart['object_items'] )
	);
}
delete_site_option_value( 'wpsc_enable_ajax_add_to_cart' );

/*
 * The cart page: the PayPal Standard form, quantity change, remove an item, coupon.
 */
section( 'Cart page' );
$buyer_session = new_session();
add_to_cart( 'E2E Shirt', array( 'variation1' => 'Large::5' ) );
add_to_cart( 'E2E Ebook' );
$cart_id = cookie_value( 'simple_cart_id' );

$cart_page   = http_request( 'GET', $ids['cart_url'] );
$paypal_form = get_paypal_form( $cart_page );
check( 'The cart page has the PayPal Standard checkout form', null !== $paypal_form, response_text( $cart_page ) );
if ( $paypal_form ) {
	$f = $paypal_form['fields'];
	check( 'The form goes to the PayPal sandbox', 0 === strpos( $paypal_form['action'], 'https://www.sandbox.paypal.com/' ), $paypal_form['action'] );
	check( 'The form pays the PayPal email address in the settings, in USD', 'seller@example.com' === $f['business'] && 'USD' === $f['currency_code'] && '_cart' === $f['cmd'] );
	check(
		'The form has the items, their prices and quantities',
		$shirt_large === $f['item_name_1'] && '25.00' === $f['amount_1'] && '1' === $f['quantity_1'] && 'SHIRT-1' === $f['item_number_1']
			&& $ebook === $f['item_name_2'] && '9.99' === $f['amount_2'] && '1' === $f['quantity_2'],
		json_encode( $f )
	);
	check( 'The shipping is the shirt shipping (2) + the base shipping (1)', '3.00' === $f['shipping_1'], 'shipping_1: ' . $f['shipping_1'] );
	check( 'The notification URL is the IPN handler', $url . '/?simple_cart_ipn=1' === $f['notify_url'], $f['notify_url'] );
	check( 'The return URL resets the cart and shows the order', false !== strpos( $f['return'], 'reset_wp_cart=1' ) && false !== strpos( $f['return'], 'cart_id=' . $cart_id ), $f['return'] );
	check( 'The custom field has the cart ID', false !== strpos( urldecode( $f['custom'] ), 'wp_cart_id=' . $cart_id ), urldecode( $f['custom'] ) );
}

submit_cart_form( 'cquantity', null, array( 'quantity' => '3', 'wspsc_product' => $shirt_large ) );
$cart = get_cart( $cart_id );
check( 'Changing the quantity on the cart page saves it', '3' === (string) ( find_item( $cart, $shirt_large )['quantity'] ?? '' ), json_encode( cart_summary( $cart ) ) );

$remove_page = http_request( 'GET', $ids['cart_url'] );
$remove_form = null;
foreach ( get_forms( $remove_page['body'], 'wp_cart_remove_item_form' ) as $form ) {
	if ( $ebook === $form['fields']['wspsc_product'] ) {
		$remove_form = $form;
	}
}
check( 'The cart page has a remove button for the ebook', null !== $remove_form );
if ( $remove_form ) {
	http_request( 'POST', $ids['cart_url'], $remove_form['fields'] );
}
$cart = get_cart( $cart_id );
check( 'Removing an item on the cart page removes it', array( $shirt_large => '3 x 25' ) === cart_summary( $cart ), json_encode( cart_summary( $cart ) ) );

$response = submit_cart_form( 'wpsc_coupon_code', null, array( 'wpsc_coupon_code' => 'e2e10' ) );
check( 'The coupon is applied (the code is not case-sensitive)', $response && false !== strpos( $response['body'], 'Discount applied successfully' ), $response ? response_text( $response ) : '' );
$cart = get_cart( $cart_id );
check( 'The coupon takes 10% off the price', array( $shirt_large => '3 x 22.5' ) === cart_summary( $cart ), json_encode( cart_summary( $cart ) ) );

/*
 * PayPal Standard payment notification (IPN).
 */
section( 'PayPal payment notification' );
$cart_page   = http_request( 'GET', $ids['cart_url'] );
$paypal_form = get_paypal_form( $cart_page );
$f           = $paypal_form['fields'];
check( 'The PayPal form has the discounted price and the shipping for 3 shirts (3 x 2 + 1)', '22.50' === $f['amount_1'] && '3' === $f['quantity_1'] && '7.00' === $f['shipping_1'], json_encode( $f ) );
check( 'The custom field has the coupon code', false !== strpos( urldecode( $f['custom'] ), 'coupon_code=e2e10' ) || false !== strpos( urldecode( $f['custom'] ), 'coupon_code=E2E10' ), urldecode( $f['custom'] ) );

reset_fakes();
$ipn      = build_ipn( $paypal_form, 'E2E-TXN-1' );
$response = send_ipn( $ipn );
check( 'The payment notification is answered with HTTP 200', 200 === $response['status'], response_text( $response ) );
$paypal_requests = get_site_option_value( 'wpsc_e2e_paypal_requests' );
check(
	'The plugin verifies the notification with the PayPal sandbox (cmd=_notify-validate)',
	is_array( $paypal_requests ) && 1 === count( $paypal_requests ) && 0 === strpos( $paypal_requests[0]['url'], 'https://www.sandbox.paypal.com/' )
		&& '_notify-validate' === $paypal_requests[0]['body']['cmd'] && 'E2E-TXN-1' === $paypal_requests[0]['body']['txn_id'],
	json_encode( $paypal_requests )
);
$cart    = get_cart( $cart_id );
$order   = $cart['cart_cpt_id'];
check( 'The order is paid', 'Paid' === $cart['order_status'] && 'publish' === $cart['post_status'], $cart['order_status'] . ' / ' . $cart['post_status'] );
check( 'The order has the transaction ID, the total and the buyer', 'E2E-TXN-1' === post_meta( $order, 'wpsc_txn_id' ) && '74.50' === post_meta( $order, 'wpsc_total_amount' ) && 'buyer@example.com' === post_meta( $order, 'wpsc_email_address' ), post_meta( $order, 'wpsc_txn_id' ) . ' / ' . post_meta( $order, 'wpsc_total_amount' ) );
check( 'The order has the shipping, the coupon and the gateway', '7.00' === post_meta( $order, 'wpsc_shipping_amount' ) && 0 === strcasecmp( 'E2E10', (string) post_meta( $order, 'wpsc_applied_coupon' ) ) && 'paypal_standard' === post_meta( $order, 'wpsc_payment_gateway' ), post_meta( $order, 'wpsc_shipping_amount' ) . ' / ' . post_meta( $order, 'wpsc_applied_coupon' ) );
check( 'The order lists the items', false !== strpos( (string) post_meta( $order, 'wpsc_items_ordered' ), $shirt_large . ' x 3 - $67.50' ), (string) post_meta( $order, 'wpsc_items_ordered' ) );
$buyer_email  = find_email( 'buyer@example.com', 'Thank you for the purchase' );
$seller_email = find_email( 'notify@example.com', 'Notification of product sale' );
check( 'The buyer gets the email with the items and the transaction ID', $buyer_email && false !== strpos( $buyer_email['message'], 'Dear E2E Buyer' ) && false !== strpos( $buyer_email['message'], $shirt_large . ' x 3' ) && false !== strpos( $buyer_email['message'], 'E2E-TXN-1' ), json_encode( emails() ) );
check( 'The seller gets the sale notification', $seller_email && false !== strpos( $seller_email['message'], 'Sale to buyer@example.com' ), json_encode( emails() ) );
check( 'Only these two emails are sent', 2 === count( emails() ), json_encode( emails() ) );

send_ipn( $ipn );
check( 'The same notification again sends no more emails', 2 === count( emails() ), json_encode( emails() ) );
check( 'The same notification again doesn\'t change the order', 'E2E-TXN-1' === post_meta( $order, 'wpsc_txn_id' ) && 'Paid' === get_cart( $cart_id )['order_status'] );

//The buyer paid but didn't come back to the return URL (the simple_cart_id cookie still has the paid cart).
add_to_cart( 'E2E Ebook' );
$new_cart_id = cookie_value( 'simple_cart_id' );
$paid_cart   = get_cart( $cart_id );
check_known(
	'paid-cart-reused',
	'Adding to the cart after a payment (without the return URL) starts a new cart and doesn\'t change the paid order',
	$new_cart_id !== $cart_id && array( $shirt_large => '3 x 22.5' ) === cart_summary( $paid_cart ),
	'Cart ID: ' . ( $new_cart_id === $cart_id ? 'the same' : 'new' ) . '. Paid order items: ' . json_encode( cart_summary( $paid_cart ) )
);

//The normal way: the buyer comes back to the return URL, which resets the cart.
new_session();
add_to_cart( 'E2E Ebook' );
$returned_cart_id = cookie_value( 'simple_cart_id' );
$return_form      = get_paypal_form( http_request( 'GET', $ids['cart_url'] ) );
$response         = http_request( 'GET', $return_form['fields']['return'] );
check( 'The return URL removes the cart ID cookie and redirects', null === cookie_value( 'simple_cart_id' ) && '' !== $response['location'], response_text( $response ) . ' Location: ' . $response['location'] );
add_to_cart( 'E2E Ebook' );
check( 'The next add to cart starts a new cart', null !== cookie_value( 'simple_cart_id' ) && $returned_cart_id !== cookie_value( 'simple_cart_id' ) );
check( 'The old cart keeps its items (for the order details)', array( $ebook => '1 x 9.99' ) === cart_summary( get_cart( $returned_cart_id ) ), json_encode( cart_summary( get_cart( $returned_cart_id ) ) ) );

//Notifications that must not mark the order paid. They are sent for one cart, then the correct one marks it paid.
section( 'Rejected payment notifications' );
new_session();
add_to_cart( 'E2E Shirt', array( 'variation1' => 'Small' ) );
$cart_id     = cookie_value( 'simple_cart_id' );
$paypal_form = get_paypal_form( http_request( 'GET', $ids['cart_url'] ) );
$ipn         = build_ipn( $paypal_form, 'E2E-TXN-2' );
check( 'The notification for one shirt (Small) is 20 + 3 shipping', '23.00' === $ipn['mc_gross'] && $shirt_small === $ipn['item_name1'], json_encode( $ipn ) );
$rejected = array(
	'not verified by PayPal'              => array( 'verify_sign' => 'forged' ),
	'paid to another PayPal account'      => array( 'receiver_email' => 'attacker@example.com' ),
	'paid less than the items cost'       => array( 'mc_gross_1' => '1.00', 'mc_gross' => '4.00' ),
	'paid in another currency'            => array( 'mc_currency' => 'EUR' ),
	'a payment that is not completed yet' => array( 'payment_status' => 'Pending' ),
);
reset_fakes();
foreach ( $rejected as $label => $changes ) {
	$response = send_ipn( array_merge( $ipn, $changes ) );
	$cart     = get_cart( $cart_id );
	check( "A notification {$label} doesn't mark the order paid", 200 === $response['status'] && 'Paid' !== $cart['order_status'] && 0 === count( emails() ), $cart['order_status'] . '; ' . response_text( $response ) . '; emails: ' . count( emails() ) );
}
$response = send_ipn( array_merge( $ipn, array( 'custom' => urlencode( 'wp_cart_id=' . str_repeat( 'f', 32 ) ) ) ) );
check( 'A notification for a cart that doesn\'t exist is ignored (no error)', 200 === $response['status'] && false === stripos( $response['body'], 'fatal error' ) && 0 === count( emails() ), response_text( $response ) );
send_ipn( $ipn );
check( 'The correct notification then marks the order paid', 'Paid' === get_cart( $cart_id )['order_status'] && 2 === count( emails() ) );

/*
 * Manual checkout.
 */
section( 'Manual checkout' );
new_session();
add_to_cart( 'E2E Ebook' );
$cart_id = cookie_value( 'simple_cart_id' );
submit_cart_form( 'wpsc_coupon_code', null, array( 'wpsc_coupon_code' => 'E2E10' ) );
$manual_page  = http_request( 'GET', $ids['cart_url'] );
$manual_form  = find_form( get_forms( $manual_page['body'] ), 'wpsc_manual_payment_form_nonce' );
check( 'The cart page has the manual checkout form', null !== $manual_form, response_text( $manual_page ) );
$manual_nonce = $manual_form ? $manual_form['fields']['wpsc_manual_payment_form_nonce'] : '';
$buyer        = array(
	'nonce'      => $manual_nonce,
	'first_name' => 'E2E',
	'last_name'  => 'Manual',
	'email'      => 'manual@example.com',
	'phone'      => '555-0100',
	'address'    => array(
		'street'      => null,
		'city'        => null,
		'country'     => null,
		'state'       => null,
		'postal_code' => null,
	),
);
reset_fakes();

$response = manual_checkout( array_merge( $buyer, array( 'nonce' => 'abc1234567' ) ) );
check( 'Manual checkout with a wrong nonce is rejected', is_array( $response['json'] ) && false === $response['json']['success'] && false !== strpos( $response['body'], 'Nonce verification failed' ), response_text( $response ) );
$response = manual_checkout( array_merge( $buyer, array( 'email' => 'not-an-email' ) ) );
check( 'Manual checkout without a valid email is rejected', is_array( $response['json'] ) && false === $response['json']['success'] && false !== strpos( $response['body'], 'validation error' ), response_text( $response ) );
set_site_option_value( 'wpsc_enable_manual_checkout', '' );
$response = manual_checkout( $buyer );
check( 'Manual checkout is rejected when it is turned off', is_array( $response['json'] ) && false === $response['json']['success'] && false !== strpos( $response['body'], 'not enabled' ), response_text( $response ) );
set_site_option_value( 'wpsc_enable_manual_checkout', '1' );
check( 'The rejected requests don\'t create an order', 'In Progress' === get_cart( $cart_id )['order_status'] && 0 === count( emails() ) );

$response = manual_checkout( $buyer );
check( 'Manual checkout places the order', is_array( $response['json'] ) && true === $response['json']['success'], response_text( $response ) );
$cart  = get_cart( $cart_id );
$order = $cart['cart_cpt_id'];
check( 'The order is pending (waiting for the payment) and published in the orders list', 'Pending' === $cart['order_status'] && 'publish' === $cart['post_status'], $cart['order_status'] . ' / ' . $cart['post_status'] );
check( 'The order has the buyer, the discounted total (9.99 - 10% = 8.99) and the gateway', 'manual@example.com' === post_meta( $order, 'wpsc_email_address' ) && '8.99' === post_meta( $order, 'wpsc_total_amount' ) && 'manual' === post_meta( $order, 'wpsc_payment_gateway' ), post_meta( $order, 'wpsc_email_address' ) . ' / ' . post_meta( $order, 'wpsc_total_amount' ) );
check_known( 'manual-checkout-coupon-code', 'The manual checkout order has the coupon code', 'E2E10' === post_meta( $order, 'wpsc_applied_coupon' ), 'wpsc_applied_coupon: ' . var_export( post_meta( $order, 'wpsc_applied_coupon' ), true ) );
check( 'The buyer gets the payment instructions email', null !== find_email( 'manual@example.com', 'Payment instructions' ), json_encode( emails() ) );
check( 'The seller gets the manual checkout notification', null !== find_email( 'notify@example.com', 'New manual checkout order' ), json_encode( emails() ) );
check( 'The cart ID cookie is removed after the order', null === cookie_value( 'simple_cart_id' ) );
$redirect_to = is_array( $response['json'] ) && isset( $response['json']['data']['redirect_to'] ) ? $response['json']['data']['redirect_to'] : '';
check( 'The buyer is sent to the Thank You page of the order', 0 === strpos( $redirect_to, $ids['return_url'] ) && false !== strpos( $redirect_to, 'cart_id=' . $cart_id ), $redirect_to );
if ( '' !== $redirect_to ) {
	$thank_you = http_request( 'GET', $redirect_to );
	check( 'The Thank You page shows the order', 200 === $thank_you['status'] && false !== strpos( $thank_you['body'], $ebook ), response_text( $thank_you ) );
}

/*
 * PHP errors on the test site.
 */
section( 'PHP errors' );
$debug_log = $wp_path . '/wp-content/debug.log';
$log_lines = is_file( $debug_log ) ? file( $debug_log, FILE_IGNORE_NEW_LINES ) : array();
$fatal     = array_filter(
	$log_lines,
	function ( $line ) {
		return false !== stripos( $line, 'PHP Fatal error' );
	}
);
check( 'No PHP fatal errors on the test site', 0 === count( $fatal ), implode( "\n    ", $fatal ) );
$plugin_notices = array_unique(
	array_map(
		function ( $line ) {
			return preg_replace( '/^\[[^\]]+\] /', '', $line );
		},
		array_filter(
			$log_lines,
			function ( $line ) {
				return false !== strpos( $line, 'wordpress-paypal-shopping-cart' ) && false === stripos( $line, 'PHP Fatal error' );
			}
		)
	)
);
if ( $plugin_notices ) {
	echo "The plugin's PHP warnings and notices (they don't fail the tests):\n    " . implode( "\n    ", $plugin_notices ) . "\n";
} else {
	echo "No PHP warnings or notices from the plugin.\n";
}

echo "\n" . ( $failures ? "{$failures} check(s) failed." : 'All checks passed.' ) . ( $known ? " {$known} known issue check(s), see above." : '' ) . "\n";
exit( $failures ? 1 : 0 );
