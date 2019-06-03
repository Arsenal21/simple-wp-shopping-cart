<?php

class WPSPSCSessions {

    protected static $instance	 = null;
    var $transient_id		 = false;
    private $trans_name;

    function __construct() {
	$this->init();
    }

    public static function get_instance() {
	if ( null == self::$instance ) {
	    self::$instance = new self;
	}
	return self::$instance;
    }

    function init() {
	$cookie_transient_id = filter_input( INPUT_COOKIE, 'wpspsc_transient_id', FILTER_SANITIZE_STRING );
	if ( empty( $cookie_transient_id ) ) {
	    if ( ! headers_sent() ) {
		setcookie( "wpspsc_transient_id", $this->get_transient_id(), 0, COOKIEPATH, COOKIE_DOMAIN );
	    }
	} else {
	    $this->transient_id = $cookie_transient_id;
	}
	$this->trans_name = "wpspsc_session_data_" . $this->get_transient_id();
    }

    private function get_transient_id() {
	if ( empty( $this->transient_id ) ) {
	    $this->transient_id = md5( uniqid( 'wpspsc', true ) );
	}
	return $this->transient_id;
    }

    private function is_frontend() {
	if ( ! is_admin() || wp_doing_ajax() ) {
	    return true;
	}
	return false;
    }

    function set_data( $name, $data = false ) {
	if ( ! $this->is_frontend() ) {
	    return false;
	}
	$curr_data = get_transient( $this->trans_name );
	if ( empty( $curr_data ) ) {
	    $curr_data = array();
	}
	$curr_data[ $name ] = $data;
	delete_transient( $this->trans_name );
	set_transient( $this->trans_name, $curr_data, 60 * 60 * 12 );
    }

    function get_data( $name, $default = false ) {
	if ( ! $this->is_frontend() ) {
	    return false;
	}
	$curr_data = get_transient( $this->trans_name );
	if ( ! isset( $curr_data[ $name ] ) ) {
	    return $default;
	}
	return $curr_data[ $name ];
    }

}

WPSPSCSessions::get_instance();
