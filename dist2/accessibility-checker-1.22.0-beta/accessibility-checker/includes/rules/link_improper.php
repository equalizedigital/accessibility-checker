<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Improper Use of Link Check
 *
 * Find all of the links on the page that contain only a # in the href attribute or are missing an href attribute completely. If these links do not have role="button" then flag an Improper Use of Link error.
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_link_improper( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = [];
	$links  = $dom->find( 'a' );
	foreach ( $links as $link ) {
		if ( ( ! $link->hasAttribute( 'href' ) || trim( $link->getAttribute( 'href' ) ) === '#' ) && $link->getAttribute( 'role' ) !== 'button' ) {
			$a_tag_code = $link->outertext;
			$errors[]   = $a_tag_code;
		}
	}
	return $errors;
}
