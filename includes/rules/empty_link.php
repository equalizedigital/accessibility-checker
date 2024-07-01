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
	$errors = [];
	$links  = $dom->find( 'a' );

	foreach ( $links as $link ) {
		$error = false;

		if (
			(string) str_ireplace( [ ' ', '&nbsp;', '-', '_' ], '', trim( $link->plaintext ) ) === ''
			&& $link->hasAttribute( 'href' )
			&& empty( $link->getAttribute( 'aria-label' ) )
			&& empty( $link->getAttribute( 'title' ) )
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
				$input = $link->find( 'input' );
				$i     = $link->find( 'i' );

				// If there's no image, input or i tag it's just an empty link and should be flagged.
				if ( empty( $image ) && empty( $input ) && empty( $i ) ) {
					$error = $a_tag_code;
				}

				if ( ! $error && isset( $image[0] ) && empty( trim( $image[0]->getAttribute( 'alt' ) ) ) ) {

					// The first image inside the link does not have an alt.
					// Throw error.
					$error = $a_tag_code;
				}

				if ( ! $error && isset( $input[0] ) && empty( trim( $input[0]->getAttribute( 'value' ) ) ) ) {

					// The first input inside the link does not have a value.
					// Throw error.
					$error = $a_tag_code;
				}

				if ( ! $error &&
					isset( $i[0] ) &&
					empty( trim( $i[0]->getAttribute( 'title' ) ) ) &&
					empty( trim( $i[0]->getAttribute( 'aria-label' ) ) )
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
