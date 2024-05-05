<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IFrame Missing Title Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_iframe_missing_title( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom         = $content['html'];
	$iframe_tags = $dom->find( 'iframe' );
	$errors      = [];

	foreach ( $iframe_tags as $iframe ) {
		if ( isset( $iframe ) && empty( $iframe->getAttribute( 'title' ) ) && empty( $iframe->getAttribute( 'aria-label' ) ) ) {

			$iframecode = htmlspecialchars( $iframe->outertext );

			$errors[] = $iframecode;

		}
	}
	return $errors;
}
