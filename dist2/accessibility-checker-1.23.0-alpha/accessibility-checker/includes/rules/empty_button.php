<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Aria Hidden
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 *
 * <button></button>
 * <input type="button">
 * <input type="submit">
 * <input type="reset">
 * role="button"
 */
function edac_rule_empty_button( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.
	$dom     = $content['html'];
	$buttons = $dom->find( 'button, [role=button]' );
	$inputs  = $dom->find( 'input[type=button], input[type=submit], input[type=reset]' );
	$errors  = [];

	// check buttons.
	foreach ( $buttons as $button ) {
		if (
			empty( str_ireplace( [ ' ', '&nbsp;', '-', '_' ], '', trim( $button->plaintext ) ) )
			&& empty( $button->getAttribute( 'aria-label' ) )
			&& empty( $button->getAttribute( 'title' ) )
		) {

			$error_code = $button->outertext;
			$image      = $button->find( 'img' );
			$input      = $button->find( 'input' );
			$i          = $button->find( 'i' );

			if (
				'' !== $error_code
				&& ( ! isset( $image[0] ) || empty( trim( $image[0]->getAttribute( 'alt' ) ) ) )
				&& ( ! isset( $input[0] ) || empty( trim( $input[0]->getAttribute( 'value' ) ) ) )
				&& (
					! isset( $i[0] ) ||
					(
						( empty( trim( $i[0]->getAttribute( 'title' ) ) ) ) &&
						( empty( trim( $i[0]->getAttribute( 'aria-label' ) ) ) )
					)
				)
			) {
				$errors[] = $error_code;
			}
		}
	}

	// check inputs.
	foreach ( $inputs as $input ) {
		if ( empty( $input->getAttribute( 'value' ) ) ) {
			$errors[] = $input->outertext;
		}
	}

	return $errors;
}
