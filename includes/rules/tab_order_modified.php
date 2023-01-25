<?php
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
function edac_rule_tab_order_modified( $content, $post ) {

	$dom = $content['html'];
	$tags = array( 'a', 'input', 'select', 'textarea', 'button', 'datalist', 'output', 'area' );
	$errors = array();

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
