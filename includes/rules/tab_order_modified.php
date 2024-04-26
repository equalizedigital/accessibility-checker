<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Tab Order Modified Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_tab_order_modified( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$tags   = [ 'a', 'input', 'select', 'textarea', 'button', 'datalist', 'output', 'area' ];
	$errors = [];

	foreach ( $tags as $tag ) {
		$elements = $dom->find( $tag );

		foreach ( $elements as $element ) {

			if ( isset( $element ) && $element->getAttribute( 'tabindex' ) > 0 ) {

				$error_code = $element->outertext;

				$errors[] = $error_code;

			}
		}
	}
	return $errors;
}
