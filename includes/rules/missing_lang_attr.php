<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Missing or Empty Language Attribute Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_missing_lang_attr( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$errors   = [];
	$elements = $content['html']->find( 'html' );
	if ( $elements[0] ) {
		if ( ( $elements[0]->hasAttribute( 'lang' ) && $elements[0]->getAttribute( 'lang' ) !== '' ) || ( $elements[0]->hasAttribute( 'xml:lang' ) && $elements[0]->getAttribute( 'xml:lang' ) !== '' ) ) {
			return;
		}
		$errors[] = edac_simple_dom_remove_child( $elements[0] );
	}
	return $errors;
}
