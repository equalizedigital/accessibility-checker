<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Broken Skip Anchor Link
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_broken_skip_anchor_link( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom             = $content['html'];
	$errors          = [];
	$anchor_elements = $dom->find( 'a' );

	foreach ( $anchor_elements as $anchor_element ) {
		$href = $anchor_element->getAttribute( 'href' );

		// exclude: '<a href="#"></a>' and '<a></a>' checked by the link_improper rule.
		if ( trim( $anchor_element->getAttribute( 'href' ) ) === '#' || ( trim( $anchor_element->getAttribute( 'href' ) ) === '#' && $anchor_element->getAttribute( 'role' ) === 'button' ) ) {
			continue;
		}

		if ( substr( $href, 0, 1 ) === '#' ) {
			if ( ! $dom->find( $href, 0 ) ) {
				$errors[] = $anchor_element;
			}
		}
	}

	return $errors;
}
