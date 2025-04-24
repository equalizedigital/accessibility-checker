<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG ALT Empty Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_img_alt_empty( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$tags   = [ 'img', 'input' ];
	$errors = [];

	if ( $tags ) {
		foreach ( $tags as $tag ) {
			$elements = $dom->find( $tag );
			foreach ( $elements as $element ) {
				if (
					(
						isset( $element )
						&& 'img' === $element->tag
						&& $element->hasAttribute( 'alt' )
						&& (string) $element->getAttribute( 'alt' ) === ''
						&& $element->getAttribute( 'role' ) !== 'presentation'
					) || (
						'input' === $element->tag
						&& $element->hasAttribute( 'alt' )
						&& $element->getAttribute( 'type' ) === 'image'
						&& (string) $element->getAttribute( 'alt' ) === ''
					)
				) {

					$image_code = $element->outertext;

					// ignore certain images.
					if ( edac_img_alt_ignore_plugin_issues( $image_code ) ) {
						continue;
					}

					// ignore images with captions.
					if ( edac_img_alt_ignore_inside_valid_caption( $image_code, $dom ) ) {
						continue;
					}

					$errors[] = $image_code;
				}
			}
		}
	}

	return $errors;
}
