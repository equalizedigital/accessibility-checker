<?php
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
function edac_rule_aria_hidden( $content, $post ) {

	$dom      = $content['html'];
	$errors   = array();
	$elements = $dom->find( '[aria-hidden="true"]' );

	if ( $elements ) {
		foreach ( $elements as $element ) {

			if ( stristr( $element->getAttribute( 'class' ), 'wp-block-spacer' ) ) {
				continue;
			}

			$errors[] = $element;
		}
	}

	return $errors;
}
