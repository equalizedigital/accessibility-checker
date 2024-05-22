<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Link PDF Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_link_pdf( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = [];
	$as     = $dom->find( 'a' );

	foreach ( $as as $a ) {

		if ( $a->getAttribute( 'href' ) ) {
			if ( strpos( strtolower( $a ), '.pdf' ) ) {
				$link_code = $a;

				$errors[] = $link_code;
			}
		}
	}
	return $errors;
}
