<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Aria Hidden
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_aria_hidden( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom      = $content['html'];
	$errors   = array();
	$elements = $dom->find( '[aria-hidden="true"]' );

	$attributes_that_make_this_valid_hidden_use = array(
		'class' => 'wp-block-spacer',
		'role'  => 'presentation',
	);

	if ( $elements ) {
		foreach ( $elements as $element ) {

			foreach ( $attributes_that_make_this_valid_hidden_use as $attribute => $value ) {
				if ( stristr( $element->getAttribute( $attribute ), $value ) ) {
					continue 2;
				}
			}

			$errors[] = $element;
		}
	}

	return $errors;
}
