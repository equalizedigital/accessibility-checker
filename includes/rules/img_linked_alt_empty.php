<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG Linked ALT Empty Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_img_linked_alt_empty( $content, $post ) {

	$dom = $content['html'];
	$errors = array();

	$as = $dom->find( 'a' );
	foreach ( $as as $a ) {

		// anchors with aria-label or title or valid node text.
		if ( $a->getAttribute( 'aria-label' ) == '' && $a->getAttribute( 'title' ) == '' && strlen( $a->plaintext ) <= 5 ) {

			$images = $a->find( 'img' );
			foreach ( $images as $image ) {

				if ( isset( $image )
					&& $image->hasAttribute( 'alt' )
					&& $image->getAttribute( 'alt' ) == ''
					&& $image->getAttribute( 'role' ) != 'presentation' ) {

					$image_code = $a;

					$errors[] = $image_code;

				}
			}
		}
	}
	return $errors;
}
