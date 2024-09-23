<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Missing Form Label Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_missing_form_label( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$fields = $dom->find( 'input, textarea, select' );
	$errors = [];

	foreach ( $fields as $field ) {
		if ( in_array( $field->getAttribute( 'type' ), [ 'submit', 'hidden', 'button', 'reset' ], true ) ) {
			continue;
		}

		// Not an error if it's an image input with an alt.
		if ( 'image' === $field->getAttribute( 'type' ) && ! empty( $field->getAttribute( 'alt' ) ) ) {
			continue;
		}

		if ( ! ac_input_has_label( $field, $dom ) ) {
			$errors[] = $field->outertext;
		}
	}
	return $errors;
}

/**
 * Check if has Label
 *
 * @param obj $field Object to check.
 * @param obj $dom Object to check.
 * @return bool
 */
function ac_input_has_label( $field, $dom ) {
	if ( $field->getAttribute( 'aria-labelledby' ) ) {
		return true;
	}
	if ( $field->getAttribute( 'aria-label' ) ) {
		return true;
	}
	if ( $dom->find( 'label[for="' . $field->getAttribute( 'id' ) . '"]', -1 ) !== null ) {

		return true;
	}
	return edac_field_has_label_parent( $field );
}

/**
 * Check if has label parent
 *
 * @param obj $field Object to check.
 * @return bool
 */
function edac_field_has_label_parent( $field ) {
	if ( null === $field ) {
		return false;
	}
	$parent = $field->parent();
	if ( null === $parent ) {
		return false;
	}

	$tag = $parent->tag;
	if ( 'label' === $tag ) {
		return true;
	}
	if ( 'form' === $tag || 'body' === $tag ) {
		return false;
	}
	return edac_field_has_label_parent( $field->parent() );
}
