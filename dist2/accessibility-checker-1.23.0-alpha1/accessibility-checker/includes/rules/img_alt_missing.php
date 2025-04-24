<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG ALT Missing Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_img_alt_missing( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$tags   = [ 'img', 'input' ];
	$errors = [];

	foreach ( $tags as $tag ) {
		$elements = $dom->find( $tag );

		foreach ( $elements as $element ) {
			if (
				(
					isset( $element ) &&
					(
						'img' === $element->tag
						&& ! $element->hasAttribute( 'alt' )
						&& $element->getAttribute( 'role' ) !== 'presentation'
					)
					&& $element->getAttribute( 'aria-hidden' ) !== 'true'
				) || (
					'input' === $element->tag
					&& ! $element->hasAttribute( 'alt' )
					&& $element->getAttribute( 'type' ) === 'image'
				)
			) {

				$image_code = $element->outertext;

				// ignore certain images.
				if ( edac_img_alt_ignore_plugin_issues( $image_code ) ) {
					continue;
				}

				// ignore images with captions.
				if ( edac_img_alt_ignore_inside_valid_caption( $image_code, $dom ) ) {
					continue;
				}

				$errors[] = $image_code;
			}
		}
	}

	return $errors;
}


/**
 * Ignore plugin issues that are being resolved automatically
 *
 * @param string $content a string to evaluate.
 * @return bool
 */
function edac_img_alt_ignore_plugin_issues( $content ) {

	// ignore spacer pixle.
	$skipvalue = 'advanced-wp-columns/assets/js/plugins/views/img/1x1-pixel.png';
	if ( strstr( $content, $skipvalue ) ) {
		return 1;
	}

	// ignore google ad code.
	if ( strstr( $content, 'src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/834593360/?guid=ON&amp;script=0"' ) ) {
		return 1;
	}

	return 0;
}

/**
 * Check if image is inside a caption and has valid alt text
 *
 * @param string $image_code to evaluate.
 * @param obj    $content dom content to evaluate.
 * @return bool
 */
function edac_img_alt_ignore_inside_valid_caption( $image_code, $content ) {

	$dom = $content;

	// captions inside figure tags (html5).
	$figures = $dom->find( 'figure' );
	foreach ( $figures as $figure ) {
		$images = $figure->find( 'img' );
		foreach ( $images as $image ) {
			if ( $image->getAttribute( 'src' ) !== '' && strstr( $image_code, $image->getAttribute( 'src' ) ) && trim( $figure->plaintext ) !== '' ) {
				return 1;
			}
		}
	}

	// captions inside div tags (pre html5).
	$divs = $dom->find( 'div' );
	foreach ( $divs as $div ) {
		if ( stristr( $div->getAttribute( 'class' ), 'wp-caption' ) ) {
			$images = $div->find( 'img' );
			foreach ( $images as $image ) {
				if ( $image->getAttribute( 'src' ) !== '' && strstr( $image_code, $image->getAttribute( 'src' ) ) && strlen( $div->plaintext ) > 5 ) {
					return 1;
				}
			}
		}
	}

	// anchors with aria-label or title or valid node text.
	$as = $dom->find( 'a' );
	foreach ( $as as $a ) {
		if ( $a->getAttribute( 'aria-label' ) !== '' || $a->getAttribute( 'title' ) !== '' || strlen( $a->plaintext ) > 5 ) {
			$images = $a->find( 'img' );
			foreach ( $images as $image ) {
				if ( $image->getAttribute( 'src' ) !== '' && strstr( $image_code, $image->getAttribute( 'src' ) ) ) {
					return 1;
				}
			}
		}
	}

	return 0;
}
