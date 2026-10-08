<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSC_Utility_Kses {
	/**
	 * Sanitize a legacy gallery attribute fragment without encoding its delimiters.
	 */
	public static function escape_attributes( $attributes, $tag ) {
		$allowed_tags = self::wp_kses_post_tags();
		if ( ! isset( $allowed_tags[ $tag ] ) ) {
			return '';
		}
		$html = wp_kses_attr( $tag, $attributes, $allowed_tags, wp_allowed_protocols() );
		return trim( substr( $html, strlen( $tag ) + 1, -1 ), " /\t\n\r\0\x0B" );
	}

	public static function wp_kses_common_attributes() {
		$common_attributes = array(
			'id'       => true,
			'class'    => true,
			'style'    => true,
			'title'    => true,
			'disabled' => true,
			'aria-*'   => true,
			'data-*'   => true,
		);
		return apply_filters( 'wpsc_kses_common_attributes', $common_attributes );
	}

	public static function wp_kses_select_option_tags( $additional_tags = array() ){

		$common_attributes = self::wp_kses_common_attributes();

		$allowed_tags = array(
			'option' => array_merge( $common_attributes, array(
				'value'    => true,
				'label'    => true,
				'selected' => true,
			) ),

			'optgroup' => array_merge( $common_attributes, array(
				'label' => true,
			) ),
		);

		return apply_filters( 'wpsc_kses_select_option_tags', array_merge( $allowed_tags, $additional_tags ) );
	}

	/**
	 * Returns allowed select field tags and attributes for wp_kses().
	 *
	 * @return array Allowed HTML tags and attributes.
	 */
	public static function wp_kses_select_tags( $additional_tags = array() ) {
		$allowed_tags = array(
			'select' => array_merge( self::wp_kses_common_attributes(), array(
				'name'         => true,
				'multiple'     => true,
				'required'     => true,
				'size'         => true,
				'form'         => true,
				'autocomplete' => true,
				'autofocus'    => true,
				'tabindex'     => true,
			) ),
		);
		$allowed_tags = array_merge( $allowed_tags, self::wp_kses_select_option_tags() );

		return apply_filters( 'wpsc_kses_select_tags', array_merge( $allowed_tags, $additional_tags ) );
	}

	public static function wp_kses_checkbox_tags( $additional_tags = array() ) {

		$common_attributes = self::wp_kses_common_attributes();

		$allowed_tags = array(
			'input' => array_merge( $common_attributes, array(
				'type'         => true,
				'name'         => true,
				'value'        => true,
				'checked'      => true,
				'form'         => true,
				'autocomplete' => true,
				'autofocus'    => true,
				'tabindex'     => true,
				'required'     => true,
			) ),
			'label' => $common_attributes,
		);

		return apply_filters( 'wpsc_kses_checkbox_tags', array_merge( $allowed_tags, $additional_tags ) );
	}

	public static function wp_kses_post_tags_with_form() {
		// Array of common standard and custom form attributes
		$all_attributes = array(
			'action'       => true,
			'method'       => true,
			'type'         => true,
			'name'         => true,
			'value'        => true,
			'label'        => true,
			'placeholder'  => true,
			'id'           => true,
			'class'        => true,
			'style'        => true,
			'checked'      => true,
			'selected'     => true,
			'required'     => true,
			'readonly'     => true,
			'disabled'     => true,
			'multiple'     => true,
			'rows'         => true,
			'cols'         => true,
			'for'          => true,
			'autocomplete' => true,
			'min'          => true,
			'max'          => true,
			'step'         => true,
			'pattern'      => true,
			'src'          => true,
			'alt'          => true,
			'size'         => true,
			'width'        => true,
			'height'       => true,
			'minlength'    => true,
			'maxlength'    => true,
			'tabindex'     => true,
			'target'       => true,
			'enctype'      => true,
			'accept'       => true,
			'accept-charset' => true,
			'form'         => true,
			'aria-*'       => true, // WP support for ARIA attributes
			'data-*'       => true, // WP support for Data attributes
		);

		$allowed_form_tags = array(
			'form'     => $all_attributes,
			'input'    => $all_attributes,
			'select'   => $all_attributes,
			'option'   => $all_attributes,
			'textarea' => $all_attributes,
			'button'   => $all_attributes,
			'label'    => $all_attributes,
			'fieldset' => $all_attributes,
			'legend'   => $all_attributes,
			'optgroup' => $all_attributes,
		);

		return self::wp_kses_post_tags( $allowed_form_tags );
	}

	public static function wp_kses_post_tags($additional_tags = array()){
		$allowed_tags = array_replace_recursive( wp_kses_allowed_html( 'post' ), $additional_tags );

		return apply_filters( 'wp_kses_post_tags', $allowed_tags );
	}
}
