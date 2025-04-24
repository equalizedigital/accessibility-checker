<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG ALT Invalid Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_img_alt_invalid( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom = $content['html'];

	$starts_with_keywords = [
		__( 'graphic of', 'accessibility-checker' ),
		__( 'bullet', 'accessibility-checker' ),
		__( 'image of', 'accessibility-checker' ),
	];

	$ends_with_keywords = [
		__( 'image', 'accessibility-checker' ),
		__( 'graphic', 'accessibility-checker' ),
	];

	$image_extensions = [
		__( '.apng', 'accessibility-checker' ),
		__( '.bmp', 'accessibility-checker' ),
		__( '.gif', 'accessibility-checker' ),
		__( '.ico', 'accessibility-checker' ),
		__( '.cur', 'accessibility-checker' ),
		__( '.jpg', 'accessibility-checker' ),
		__( '.jpeg', 'accessibility-checker' ),
		__( '.jfif', 'accessibility-checker' ),
		__( '.pjpeg', 'accessibility-checker' ),
		__( '.pjp', 'accessibility-checker' ),
		__( '.png', 'accessibility-checker' ),
		__( '.svg', 'accessibility-checker' ),
		__( '.tif', 'accessibility-checker' ),
		__( '.tiff', 'accessibility-checker' ),
		__( '.webp', 'accessibility-checker' ),
	];

	$keywords = [
		__( 'graphic of', 'accessibility-checker' ),
		__( 'bullet', 'accessibility-checker' ),
		__( 'image of', 'accessibility-checker' ),
		__( 'image', 'accessibility-checker' ),
		__( 'graphic', 'accessibility-checker' ),
		__( 'image', 'accessibility-checker' ),
		__( 'graphic', 'accessibility-checker' ),
		__( 'photo', 'accessibility-checker' ),
		__( 'photograph', 'accessibility-checker' ),
		__( 'drawing', 'accessibility-checker' ),
		__( 'painting', 'accessibility-checker' ),
		__( 'artwork', 'accessibility-checker' ),
		__( 'logo', 'accessibility-checker' ),
		__( 'bullet', 'accessibility-checker' ),
		__( 'button', 'accessibility-checker' ),
		__( 'arrow', 'accessibility-checker' ),
		__( 'more', 'accessibility-checker' ),
		__( 'spacer', 'accessibility-checker' ),
		__( 'blank', 'accessibility-checker' ),
		__( 'chart', 'accessibility-checker' ),
		__( 'table', 'accessibility-checker' ),
		__( 'diagram', 'accessibility-checker' ),
		__( 'graph', 'accessibility-checker' ),
		__( '*', 'accessibility-checker' ),
	];

	$contains = [
		'_',
		'img',
		'jpg',
		'jpeg',
		'apng',
		'png',
		'svg',
		'webp',
	];

	$errors = [];

	$images = $dom->find( 'img' );
	if ( $images ) {
		foreach ( $images as $image ) {
			if ( isset( $image ) ) {
				$error       = false;
				$alt         = strtolower( $image->getAttribute( 'alt' ) );
				$alt_trimmed = preg_replace( '/\s+/', ' ', trim( $alt ) );
				$image_code  = $image->outertext;

				// ignore certain images.
				if ( edac_img_alt_ignore_plugin_issues( $image_code ) ) {
					break;
				}

				// ignore images with captions.
				if ( edac_img_alt_ignore_inside_valid_caption( $image_code, $dom ) ) {
					break;
				}

				// check if alt contains only whitespace.
				if ( strlen( $alt ) > 0 && ctype_space( $alt ) === true ) {
					$error = true;
				}

				// check if string begins with.
				if ( false === $error && $starts_with_keywords ) {
					foreach ( $starts_with_keywords as $starts_with_keyword ) {
						if ( ac_starts_with( $alt, $starts_with_keyword ) ) {
							$error = true;
							break;
						}
					}
				}

				// check if string ends with.
				if ( false === $error && $ends_with_keywords ) {
					foreach ( $ends_with_keywords as $ends_with_keyword ) {
						if ( ac_ends_with( $alt, $ends_with_keyword ) ) {
							$error = true;
							break;
						}
					}
				}

				// check for image extensions.
				if ( false === $error && $image_extensions ) {
					foreach ( $image_extensions as $image_extension ) {
						if ( strpos( $alt, $image_extension ) === true ) {
							$error = true;
							break;
						}
					}
				}

				// check for specific keyword matches.
				if ( false === $error && $keywords ) {
					foreach ( $keywords as $keyword ) {
						if ( $alt_trimmed === $keyword ) {
							$error = true;
							break;
						}
					}
				}

				// check if the alt contains a keyword or phrase.
				if ( false === $error && $contains ) {
					foreach ( $contains as $keyword ) {
						if ( stripos( $alt, $keyword ) !== false ) {
							$error = true;
							break;
						}
					}
				}

				// check if the alt is composed of only numbers.
				if ( false === $error ) {
					if ( ctype_digit( $alt_trimmed ) ) {
						$error = true;
					}
				}

				if ( true === $error ) {
					$errors[] = $image_code;
				}
			}
		}
	}
	return $errors;
}

/**
 * Starts With
 *
 * @param string $haystack A string.
 * @param string $needle A string.
 * @return string containing.
 */
function ac_starts_with( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}

/**
 * Ends With
 *
 * @param string $haystack A string.
 * @param string $needle A string.
 * @return string containing.
 */
function ac_ends_with( $haystack, $needle ) {
	$length = strlen( $needle );
	if ( ! $length ) {
		return true;
	}
	return substr( $haystack, -$length ) === $needle;
}
