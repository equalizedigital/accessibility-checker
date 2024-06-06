<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Link Blank Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_link_blank( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$content  = $content['html'];
	$errors   = [];
	$elements = $content->find( 'a[target="_blank"]' );
	if ( $elements ) {
		foreach ( $elements as $a ) {

			$error      = false;
			$error_code = $a->outertext;

			// check aria-label.
			if ( $a->hasAttribute( 'aria-label' ) ) {

				$text  = $a->getAttribute( 'aria-label' );
				$error = edac_check_link_blank_text( $text );

				// check aria-labelledby.
			} elseif ( $a->hasAttribute( 'aria-labelledby' ) ) {

				// get aria-labelledby and explode into array since aria-labelledby allows for multiple element ids.
				$label_string = $a->getAttribute( 'aria-labelledby' );
				$labels       = explode( ' ', $label_string );
				$label_text   = [];

				if ( $labels ) {
					foreach ( $labels as $label ) {

						// if element has text push into array.
						$element = $content->find( '#' . $label, 0 );
						if ( $element->plaintext ) {
							$label_text[] = $element->plaintext;
						}
					}

					// implode array and check.
					if ( $label_text ) {
						$text  = implode( ' ', $label_text );
						$error = edac_check_link_blank_text( $text );
					}
				}

				// check plain text.
			} elseif ( $a->plaintext ) {

				$text  = $a->plaintext;
				$error = edac_check_link_blank_text( $text );

				// check image alt text.
			} else {
				$images = $a->find( 'img' );
				foreach ( $images as $image ) {
					$alt   = $image->getAttribute( 'alt' );
					$error = edac_check_link_blank_text( $alt );
					if ( true === $error ) {
						break;
					}
				}
			}

			// push error code into array.
			if ( false === $error ) {
				$errors[] = $error_code;
			}
		}
	}
	return $errors;
}

/**
 * Check for link blank text
 *
 * @param string $text to check.
 * @return bool
 */
function edac_check_link_blank_text( $text ) {

	$text = strtolower( $text );

	$allowed_phrases = [
		__( 'new window', 'accessibility-checker' ),
		__( 'new tab', 'accessibility-checker' ),
	];

	foreach ( $allowed_phrases as $allowed_phrase ) {
		if ( strpos( $text, $allowed_phrase ) !== false ) {
			return true;
		}
	}

	return false;
}
