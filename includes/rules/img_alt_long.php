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
function edac_rule_img_alt_long( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = [];
	$images = $dom->find( 'img' );
	
	/**
	 * Filter the max alt text length checked by the img_alt_long rule before it is considered an issue.
	 *
	 * @since 1.11.0
	 *
	 * @param int $length The length used in the rule to determine the max length before flagging as an issue.
	 */
	$max_alt_length = max( 1, absint( apply_filters( 'edac_max_alt_length', 300 ) ) );

	foreach ( $images as $image ) {
		if ( isset( $image ) && $image->hasAttribute( 'alt' ) && $image->getAttribute( 'alt' ) !== '' ) {
			$alt = $image->getAttribute( 'alt' );
			if ( strlen( $alt ) > $max_alt_length ) {
				$image_code = $image;
				$errors[]   = $image_code;
			}
		}
	}
	return $errors;
}
