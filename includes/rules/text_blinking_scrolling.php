<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Text Blinking Scrolling Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_text_blinking_scrolling( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = array();

	/**
	 * Check for blink tag
	 * <blink>This text may blink depending on the browser you use.</blink>
	 */
	$blinks = $dom->find( 'blink' );
	foreach ( $blinks as $blink ) {
		$errors[] = $blink->outertext;
	}

	/**
	 * Check for marquee tag
	 * <marquee>This text may scroll from right to left depending on the browser you you.</marquee>
	 */
	$marquees = $dom->find( 'marquee' );
	foreach ( $marquees as $marquee ) {
		$errors[] = $marquee->outertext;
	}

	/**
	 * Check for text-decoration: blink
	 * <p style="text-decoration: blink;">This text may blink depending on the browser you use.</p>
	 */
	$elements = $dom->find( '*' );
	if ( $elements ) {
		foreach ( $elements as $element ) {
			if ( isset( $element ) && stristr( $element->getAttribute( 'style' ), 'text-decoration:' ) && '' !== $element->innertext ) {
				if ( strpos( strtolower( $element ), 'blink' ) ) {
					$errors[] = $element->outertext;
				}
			}
		}
	}

	// check styles.
	if ( $content['css_parsed'] ) {
		$errors = array_merge( ac_css_text_decoration_blink_check( $content ), $errors );
	}

	return $errors;
}

/**
 * CSS Text Decoration Blink Check
 *
 * @param obj $content object to check.
 * @return array
 */
function ac_css_text_decoration_blink_check( $content ) {

	$dom        = $content['html'];
	$errors     = array();
	$error_code = '';
	$css_array  = $content['css_parsed'];

	if ( $css_array ) {
		foreach ( $css_array as $element => $rules ) {
			if ( array_key_exists( 'text-decoration', $rules ) ) {

				// replace CSS variables.
				$rules['text-decoration'] = edac_replace_css_variables( $rules['text-decoration'], $css_array );

				if ( 'blink' === $rules['text-decoration'] || 'Blink' === $rules['text-decoration'] ) {
					$error_code = $element . '{ ';
					foreach ( $rules as $key => $value ) {
						$error_code .= $key . ': ' . $value . '; ';
					}
					$error_code .= '}';

					$elements = $dom->find( $element );
					if ( $elements ) {
						foreach ( $elements as $element ) {
							$errors[] = $element->outertext . ' ' . $error_code;
						}
					}
				}
			}
		}
	}

	return $errors;
}
