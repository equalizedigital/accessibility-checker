<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Underlined Text Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_underlined_text( $content, $post ) {

	$errors = array();
	$elements = $content['html']->find( 'u' );

	/*
	 * check for html elements
	 * <u>test</u>
	 */
	if ( $elements ) {
		foreach ( $elements as $element ) {
			$errors[] = $element->outertext;
		}
	}

	/*
	 * check for inline styles
	 * <p style="text-decoration: underline;">test</p>
	 */
	$elements = $content['html']->find( '*' );
	if ( $elements ) {
		foreach ( $elements as $element ) {
			if (
				isset( $element )
				&& ! $element->hasAttribute( 'href' )
				&& stristr( $element->getAttribute( 'style' ), 'text-decoration:' )
				&& stristr( strtolower( $element->getAttribute( 'style' ) ), 'underline' )
			) {
				$errors[] = $element->outertext;
			}
		}
	}

	// check styles.
	if ( $content['css_parsed'] ) {
		$errors = array_merge( edac_css_underlined_text_check( $content ), $errors );
	}

	return $errors;
}

/**
 * Check for underline css files
 *
 * @param obj $content to be checked.
 * @return array
 */
function edac_css_underlined_text_check( $content ) {

	$dom = $content['html'];
	$errors = array();
	$error_code = '';
	$css_array = $content['css_parsed'];

	if ( $css_array ) {
		foreach ( $css_array as $element => $rules ) {
			if ( array_key_exists( 'text-decoration', $rules ) ) {

				// replace CSS variables.
				$rules['text-decoration'] = edac_replace_css_variables( $rules['text-decoration'], $css_array );

				if ( stristr( strtolower( $rules['text-decoration'] ), 'underline' ) ) {

					$error_code = $element . '{ ';
					foreach ( $rules as $key => $value ) {
						$error_code .= $key . ': ' . $value . '; ';
					}
					$error_code .= '}';

					$elements = $dom->find( $element );
					if ( $elements ) {
						foreach ( $elements as $element ) {
							if ( 'a' !== $element->tag ) {
								$errors[] = $element->outertext . ' ' . $error_code;
							}
						}
					}
				}
			}
		}
	}

	return $errors;

}
