<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Incorrect Heading Order Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_incorrect_heading_order( $content, $post ) {
	if ( empty( $post->post_content ) ) {
		return array();
	}

	$dom                    = $content['html'];
	$starting_heading_level = 1;
	$errors                 = array();
	$elements               = $dom->find( 'h1,[role=heading][aria-level=1],h2,[role=heading][aria-level=2],h3,[role=heading][aria-level=3],h4,[role=heading][aria-level=4],h5,[role=heading][aria-level=5],h6,[role=heading][aria-level=6]' );
	$previous               = $starting_heading_level;

	if ( $elements ) {
		foreach ( $elements as $key => $element ) {
			if ( $element->hasAttribute( 'aria-level' ) ) {
				$current = $element->getAttribute( 'aria-level' );
			} else {
				$current = str_replace( 'h', '', $element->tag );
			}

			// Only process the logic if $previous is set and $current is not equal to $previous.
			if ( $previous && $current !== $previous ) {
				if ( $current > $previous && $current !== $previous + 1 ) {
					$errors[] = $element->outertext;
				}
			}

			$previous = $current;
		}
	}

	return $errors;
}
