<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG ALT Redundant
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_img_alt_redundant( $content, $post ) {

	$content = $content['html'];
	$dom = $content;
	$errors = array();

	/*
	 * validate redundant alt attributes on images
	 * example: <img src="image.jpg" alt="test"><img src="image.jpg" alt="test">
	 */
	$images = $dom->find( 'img' );
	foreach ( $images as $image ) {
		if ( $image->getAttribute( 'alt' ) != '' ) {
			$pattern  = '/' . "(.*?)alt=[\"\']\b" . preg_quote( strtolower( trim( $image->getAttribute( 'alt' ) ) ), '/' ) . "\b[\"\'](.*?)\b" . preg_quote( strtolower( trim( $image->getAttribute( 'alt' ) ) ), '/' ) . "\b" . '/';
			if ( preg_match( $pattern, $content, $matches ) ) {
				if ( ! stristr( $matches[0], '<a' ) ) {
					$errors[] = $image->outertext;
				}
			}
		}
	}

	/*
	 * redundant alt text on image title and alt
	 * <img src="image.jpg" alt="test" title="test">
	 */
	$images = $dom->find( 'img' );
	foreach ( $images as $image ) {
		if ( $image->getAttribute( 'alt' ) != '' && $image->getAttribute( 'title' ) != '' ) {
			if ( isset( $image ) && edac_compare_strings( $image->getAttribute( 'title' ), $image->getAttribute( 'alt' ) ) ) {
				$errors[] = $image->outertext;
			}
		}
	}

	/*
	 * redundant alt text on image inside anchor
	 * <a href="#"><img src="image.jpg" alt="test" title="test"></a>
	 */
	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {
		$images = $link->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			if ( $image->getAttribute( 'alt' ) != '' ) {
				if ( isset( $link )
					&& isset( $image )
					&& ( strtolower( trim( $link->nodeValue ) ) == strtolower( trim( $image->getAttribute( 'alt' ) ) )
					|| strtolower( trim( $image->getAttribute( 'title' ) ) ) == strtolower( trim( $image->getAttribute( 'alt' ) ) )
					)
				) {
					$errors[] = $link->outertext;
				}
			}
		}
	}

	/*
	 * check alt text on image inside captions marked up with a div
	 <div class="wp-caption">
		 <img src="image.jpg" alt="test">
		 <p class="wp-caption-text">test</p>
	 </div>
	 */
	$figuredivs = $dom->find( 'div' );
	foreach ( $figuredivs as $figure ) {
		if ( stristr( $figure->getAttribute( 'class' ), 'wp-caption' ) ) {
			$figurecaption = $figure->getElementsByTagName( 'p' )[0];
			$anchor = $figure->getElementsByTagName( 'a' )[0];
			if ( '' === $anchor && isset( $figurecaption ) && stristr( $figurecaption->getAttribute( 'class' ), 'wp-caption-text' ) ) {
				$image = $figure->getElementsByTagName( 'img' )[0];
				$figcaptioncode = $figurecaption->plaintext;
				if ( isset( $image )
					&& strtolower( trim( $figcaptioncode ) ) == strtolower( trim( $image->getAttribute( 'alt' ) ) )
					&& $image->getAttribute( 'alt' ) != '' ) {

					$errors[] = $figure;
				}
			}
		}
	}

	return $errors;
}
