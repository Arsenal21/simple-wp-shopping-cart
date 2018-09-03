<?php

class WPSPCAdminUtils {

    static function e_checked( $a, $b = "1", $empty_means_checked = false ) {
	if ( $a == $b || ($empty_means_checked && empty( $a )) ) {
	    echo ' checked';
	} else {
	    echo '';
	}
    }

    static function gen_options( $opts, $value = false ) {
	$out	 = '';
	$tpl	 = '<option value="%s"%s>%s</option>' . "\r\n";
	foreach ( $opts as $opt ) {
	    $selected	 = ($value !== false && $value == $opt[ 0 ]) ? ' selected' : '';
	    $out		 .= sprintf( $tpl, $opt[ 0 ], $selected, $opt[ 1 ] );
	}
	return $out;
    }

}
