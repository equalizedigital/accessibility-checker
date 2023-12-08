<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
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
function edac_rule_empty_link( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = array();
	$links  = $dom->find( 'a' );

	foreach ( $links as $link ) {
		$error = false;

		if (
			str_ireplace( array( ' ', '&nbsp;', '-', '_' ), '', trim( $link->plaintext ) ) === ''
			&& $link->hasAttribute( 'href' )
			&& $link->getAttribute( 'aria-label' ) === ''
			&& $link->getAttribute( 'title' ) === ''
		) {

			// This link does not have plaintext within the tag &
			// does have an href &
			// does not have an aria-label &
			// does not have a title.

			$a_tag_code = $link->outertext;

			if (
				'' !== $a_tag_code
				&& ! $link->hasAttribute( 'id' )
				&& ! $link->hasAttribute( 'name' )
			) {

				// This link does not have an id &
				// does not have a name.

				$image = $link->find( 'img' );
				if ( ! $error && isset( $input[0] ) && trim( $image[0]->getAttribute( 'alt' ) ) === '' ) {

					// The first image inside the link does not have an alt.
					// Throw error.
					$error = $a_tag_code;
				}

				$input = $link->find( 'input' );
				if ( ! $error && isset( $input[0] ) && trim( $image[0]->getAttribute( 'value' ) ) === '' ) {

					// The first input inside the link does not have a value.
					// Throw error.
					$error = $a_tag_code;
				}

				$i = $link->find( 'i' );
				if ( ! $error && isset( $input[0] ) &&
				trim( $i[0]->getAttribute( 'title' ) ) === '' &&
				trim( $i[0]->getAttribute( 'aria-label' ) ) === ''
				) {

					// The first i inside the link does not have a title &
					// does not have an aria-lable.
					// Throw error.
					$error = $a_tag_code;
				}
			}

			if ( $error ) {
				$errors[] = $error;
			}
		}
	}
	return $errors;
}
