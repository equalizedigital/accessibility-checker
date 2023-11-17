<?php
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
function edac_rule_img_alt_empty( $content, $post ) {

	$dom = $content['html'];
	$tags = array( 'img', 'input' );
	$errors = array();

	if ( $tags ) {
		foreach ( $tags as $tag ) {
			$elements = $dom->find( $tag );
			foreach ( $elements as $element ) {
				if (
					isset( $element )
					&& 'img' == ( $element->tag
					&& $element->hasAttribute( 'alt' )
					&& $element->getAttribute( 'alt' ) == ''
					&& $element->getAttribute( 'role' ) != 'presentation' )
					|| ( 'input' == $element->tag
							&& $element->hasAttribute( 'alt' ) && $element->getAttribute( 'type' ) == 'image' && $element->getAttribute( 'alt' ) == '' )
						) {

					$image_code = $element->outertext;

					// ignore certain images.
					if ( edac_img_alt_ignore_plugin_issues( $image_code ) ) {
						goto img_missing_alt_bottom;
					}

					// ignore images with captions.
					if ( edac_img_alt_ignore_inside_valid_caption( $image_code, $dom ) ) {
						goto img_missing_alt_bottom;
					}

					$errors[] = $image_code;

				}
				img_missing_alt_bottom:
			}
		}
	}

	return $errors;

}
