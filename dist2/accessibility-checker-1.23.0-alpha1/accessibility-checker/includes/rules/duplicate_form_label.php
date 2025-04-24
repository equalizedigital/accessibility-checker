<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
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
function edac_rule_duplicate_form_label( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = [];
	$labels = $dom->find( 'label' );

	if ( ! $labels ) {
		return;
	}
	foreach ( $labels as $label ) {
		$for_attr = $label->getAttribute( 'for' );
		if ( count( $dom->find( 'label[for="' . $for_attr . '"]' ) ) > 1 ) {
			$errors[] = __( 'Duplicate label', 'accessibility-checker' ) . ' for="' . $for_attr . '"';
		}
	}
	return $errors;
}
