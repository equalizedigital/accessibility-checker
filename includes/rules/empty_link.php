<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Empty Link Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_empty_link( $content, $post ) {
	$dom = $content['html'];
	$errors = array();

	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {

		if (
			str_ireplace( array( ' ', '&nbsp;', '-', '_' ), '', trim( $link->plaintext ) ) == ''
			&& $link->hasAttribute( 'href' )
			&& $link->getAttribute( 'aria-label' ) == ''
			&& $link->getAttribute( 'title' ) == ''
		) {
			$a_tag_code = $link->outertext;
			$image = $link->find( 'img' );
			$input = $link->find( 'input' );
			$i = $link->find( 'i' );

			if (
				'' !== $a_tag_code
				&& ! $link->hasAttribute( 'id' )
				&& ! $link->hasAttribute( 'name' )
				&& ( ! isset( $image[0] ) || trim( $image[0]->getAttribute( 'alt' ) ) == '' )
				&& ( ! isset( $input[0] ) || trim( $input[0]->getAttribute( 'value' ) ) == '' )
				&& ( ! isset( $i[0] ) || ( trim( $i[0]->getAttribute( 'title' ) ) == '' ) && trim( $i[0]->getAttribute( 'aria-label' ) ) == '' )
			) {
				$errors[] = $a_tag_code;
			}
		}
	}
	return $errors;
}
