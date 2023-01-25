<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Duplicate Form Label Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_duplicate_form_label( $content, $post ) {

	$dom = $content['html'];
	$errors = array();

	$labels = $dom->find( 'label' );
	if ( ! $labels ) {
		return;
	}
	foreach ( $labels as $label ) {
		$for_attr = $label->getAttribute( 'for' );
		if ( sizeof( $dom->find( 'label[for="' . $for_attr . '"]' ) ) > 1 ) {
			$errors[] = __( 'Duplicate label', 'accessibility_checker' ) . ' for="' . $for_attr . '"';
		}
	}
	return $errors;
}
