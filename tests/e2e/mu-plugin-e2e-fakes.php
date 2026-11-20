<?php
/**
 * Endpoint tests only (installed as a must-use plugin on the GitHub Actions test site, never on a real site).
 *
 * - A fake PayPal IPN verification: the plugin sends the payment notification back to PayPal (cmd=_notify-validate)
 *   with wp_safe_remote_post(). This answers "VERIFIED" when the notification has verify_sign=e2e-verified, and "INVALID"
 *   otherwise (like PayPal does for a notification it didn't send). The requests are kept in the 'wpsc_e2e_paypal_requests' option.
 * - Keeps the emails the site sends in the 'wpsc_e2e_emails' option instead of sending them.
 * - WPSC_E2E_Gadget: a class with a __wakeup() method (like the "gadget" classes an attacker looks for in other plugins).
 *   If an object of it is ever created from saved data, the 'wpsc_e2e_gadget_woken' option is set.
 * - Blocks all other outgoing HTTP requests, so the tests don't depend on the internet.
 */

const WPSC_E2E_PAYPAL_VERIFIED_SIGN = 'e2e-verified';

class WPSC_E2E_Gadget {
	public function __wakeup() {
		update_option( 'wpsc_e2e_gadget_woken', '1', false );
	}
}

add_filter(
	'pre_http_request',
	function ( $preempt, $args, $url ) {
		if ( false === strpos( $url, 'paypal.com/cgi-bin/webscr' ) ) {
			return new WP_Error( 'wpsc_e2e_blocked', 'Outgoing HTTP requests are blocked on the endpoint test site: ' . $url );
		}

		$body     = is_array( $args['body'] ) ? $args['body'] : array();
		$verified = isset( $body['cmd'] ) && '_notify-validate' === $body['cmd']
			&& isset( $body['verify_sign'] ) && WPSC_E2E_PAYPAL_VERIFIED_SIGN === $body['verify_sign'];

		$requests   = get_option( 'wpsc_e2e_paypal_requests', array() );
		$requests[] = array(
			'url'  => $url,
			'body' => $body,
		);
		update_option( 'wpsc_e2e_paypal_requests', $requests, false );

		return array(
			'headers'  => array(),
			'body'     => $verified ? 'VERIFIED' : 'INVALID',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	},
	10,
	3
);

add_filter(
	'pre_wp_mail',
	function ( $return, $atts ) {
		$emails   = get_option( 'wpsc_e2e_emails', array() );
		$emails[] = array(
			'to'      => is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : $atts['to'],
			'subject' => $atts['subject'],
			'message' => $atts['message'],
		);
		update_option( 'wpsc_e2e_emails', $emails, false );
		return true;
	},
	10,
	2
);
