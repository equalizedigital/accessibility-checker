<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG Linked ALT Missing Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_img_linked_alt_missing( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = [];
	$as     = $dom->find( 'a' );

	foreach ( $as as $a ) {

		// anchors with aria-label or title or valid node text.
		if ( empty( $a->getAttribute( 'aria-label' ) ) && empty( $a->getAttribute( 'title' ) ) && strlen( $a->plaintext ) <= 5 ) {

			$images = $a->find( 'img' );
			foreach ( $images as $image ) {

				if ( isset( $image ) && ( ! $image->hasAttribute( 'alt' ) && $image->getAttribute( 'role' ) !== 'presentation' ) && $image->getAttribute( 'aria-hidden' ) !== 'true' ) {

					$image_code = $a;

					$errors[] = $image_code;

				}
			}
		}
	}
	return $errors;
}
