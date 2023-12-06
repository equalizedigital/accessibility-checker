<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Empty Form Label Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_empty_form_label( $content, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $post is reserved for future use or for compliance with a specific interface.

	// rule vars.
	$dom    = $content['html'];
	$labels = $dom->find( 'label' );
	$errors = array();

	foreach ( $labels as $label ) {

		$label_text = str_ireplace( array( '*', __( 'required', 'accessibility-checker' ) ), '', $label->plaintext );
		if ( empty( preg_replace( '/\s+/', '', $label_text ) ) ) {
			$errors[] = $label->outertext;
		}
	}
	return $errors;
}
