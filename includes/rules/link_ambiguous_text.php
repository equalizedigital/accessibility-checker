<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Link Ambiguous Text Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 *
 * Logic order: aria-label, aria-labelledby, plain text, img alt
 *
 * aria-label:
 * <a href="link.html" aria-label="Read more">Read More</a>
 *
 * aria-labelledby:
 * <a href="link.html" aria-labelledby="my-label">Read More</a>
 * <div id="my-label">Read More</div>
 *
 * aria-labelledby multiple labels:
 * <a href="link.html" aria-labelledby="my-label-one my-label-two">Read More</a>
 * <div id="my-label-one">Read</div>
 * <div id="my-label-two">More</div>
 *
 * plain text:
 * <a href="link.html">Read More</a>
 *
 * img alt:
 * <a href="link.html"><img src="image.jpg" alt="Read More"></a>
 */
function edac_rule_link_ambiguous_text( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = [];

	// get and loop through anchor links.
	$as = $dom->find( 'a' );
	foreach ( $as as $a ) {

		$error      = false;
		$error_code = $a->outertext;

		// check aria-label.
		if ( $a->hasAttribute( 'aria-label' ) && '' !== $a->getAttribute( 'aria-label' ) ) {

			$text  = $a->getAttribute( 'aria-label' );
			$error = edac_check_ambiguous_phrase( $text );

			// check aria-labelledby.
		} elseif ( $a->hasAttribute( 'aria-labelledby' ) ) {

			// get aria-labelledby and explode into array since aria-labelledby allows for multiple element ids.
			$label_string = $a->getAttribute( 'aria-labelledby' );
			$labels       = explode( ' ', $label_string );
			$label_text   = [];

			if ( $labels ) {
				foreach ( $labels as $label ) {

					// if element has text push into array.
					$element = $dom->find( '#' . $label, 0 );
					if ( $element->plaintext ) {
						$label_text[] = $element->plaintext;
					}
				}

				// implode array and check.
				if ( $label_text ) {
					$text  = implode( ' ', $label_text );
					$error = edac_check_ambiguous_phrase( $text );
					if ( $error ) {
						$error_code = $error_code . ' Label Text: ' . $text;
					}
				}
			}

			// check plain text.
		} elseif ( $a->plaintext ) {

			$text  = $a->plaintext;
			$error = edac_check_ambiguous_phrase( $text );

			// check image alt text.
		} else {
			$images = $a->find( 'img' );
			foreach ( $images as $image ) {
				$alt   = $image->getAttribute( 'alt' );
				$error = edac_check_ambiguous_phrase( $alt );
				if ( true === $error ) {
					break;
				}
			}
		}

		// push error code into array.
		if ( $error ) {
			$errors[] = $error_code;
		}
	}
	return $errors;
}

/**
 * Check for ambiguous phrase
 *
 * @param string $text Text to check.
 * @return bool
 */
function edac_check_ambiguous_phrase( $text ) {

	$text = strtolower( $text );

	// phrases.
	$ambiguous_phrases = [
		__( 'click', 'accessibility-checker' ),
		__( 'click here', 'accessibility-checker' ),
		__( 'here', 'accessibility-checker' ),
		__( 'go here', 'accessibility-checker' ),
		__( 'more', 'accessibility-checker' ),
		__( 'more...', 'accessibility-checker' ),
		__( 'details', 'accessibility-checker' ),
		__( 'more details', 'accessibility-checker' ),
		__( 'link', 'accessibility-checker' ),
		__( 'this page', 'accessibility-checker' ),
		__( 'continue', 'accessibility-checker' ),
		__( 'continue reading', 'accessibility-checker' ),
		__( 'read more', 'accessibility-checker' ),
		__( 'open', 'accessibility-checker' ),
		__( 'download', 'accessibility-checker' ),
		__( 'button', 'accessibility-checker' ),
		__( 'keep reading', 'accessibility-checker' ),
		__( 'learn more', 'accessibility-checker' ),
	];

	// remove all but letters.
	$text = preg_replace( '/[^a-z]+/i', ' ', $text );

	// remove whitespace from beginning and end of phrase.
	$text = trim( $text );

	// check if text is equal to.
	foreach ( $ambiguous_phrases as $ambiguous_phrase ) {
		if ( $text === $ambiguous_phrase ) {
			return true;
		}
	}
	return false;
}
