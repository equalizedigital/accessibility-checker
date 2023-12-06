<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG ALT Long Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_img_alt_long( $content, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = array();
	$images = $dom->find( 'img' );

	foreach ( $images as $image ) {
		if ( isset( $image ) && $image->hasAttribute( 'alt' ) && $image->getAttribute( 'alt' ) !== '' ) {
			$alt = $image->getAttribute( 'alt' );
			if ( strlen( $alt ) > 300 ) {
				$image_code = $image;
				$errors[]   = $image_code;
			}
		}
	}
	return $errors;
}
