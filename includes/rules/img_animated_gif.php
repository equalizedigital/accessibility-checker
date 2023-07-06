<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IMG Animate GIF Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 *
 * checks all gif images that are animated
 * checks for giphy and tenor gif embeds
 */
function edac_rule_img_animated_gif( $content, $post ) {
	$dom = $content['html'];
	$errors = array();

	// check for image gifs.
	$gifs = $dom->find( 'img[src$=gif]' );
	if ( $gifs ) {
		foreach ( $gifs as $gif ) {

			if ( edac_img_gif_is_animated( $gif->getAttribute( 'src' ) ) == false ) {
				continue;
			}

			$errors[] = $gif->outertext;
		}
	}

	// check for image Webp.
	$webps = $dom->find( 'img[src$=webp]' );
	if ( $webps ) {
		foreach ( $webps as $webp ) {

			if ( edac_img_webp_is_animated( $webp->getAttribute( 'src' ) ) == false ) {
				continue;
			}

			$errors[] = $webp->outertext;
		}
	}

	// check for giphy gifs.
	$giphy_gifs = $dom->find( 'iframe' );
	if ( $giphy_gifs ) {
		foreach ( $giphy_gifs as $gif ) {
			$src = $gif->getAttribute( 'src' );
			if ( strpos( $src, 'https://giphy.com/embed' ) !== false ) {
				$errors[] = $gif->outertext;
			}
		}
	}

	// check for tenor gifs.
	$tenor_gifs = $dom->find( '.tenor-gif-embed' );
	if ( $tenor_gifs ) {
		foreach ( $tenor_gifs as $gif ) {
			$errors[] = $gif->outertext;
		}
	}

	return $errors;
}

/**
 * Checks if a gif image is anaimated
 *
 * @param  string $filename The filename.
 * @return bool
 */
function edac_img_gif_is_animated( $filename ) {
	if ( ! ( $fh = @fopen( $filename, 'rb' ) ) ) {
		return false;
	}
	$count = 0;

	/*
	An animated gif contains multiple "frames", with each frame having a
	header made up of:
	* a static 4-byte sequence (\x00\x21\xF9\x04)
	* 4 variable bytes
	* a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)
	We read through the file til we reach the end of the file, or we've found
	at least 2 frame headers
	*/
	$chunk = false;
	while ( ! feof( $fh ) && $count < 2 ) {
		// add the last 20 characters from the previous string, to make sure the searched pattern is not split.
		$chunk = ( $chunk ? substr( $chunk, -20 ) : '' ) . fread( $fh, 1024 * 100 ); // read 100kb at a time.
		$count += preg_match_all( '#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches );
	}

	fclose( $fh );
	return $count > 1;
}

/**
 * Checks if a webp image is animated
 *
 * @param  string $filename The filename.
 * @return bool
 */
function edac_img_webp_is_animated( $filename ) {
	if ( ! ( $fh = @fopen( $filename, 'rb' ) ) ) {
		return false;
	}
	
	// See: https://stackoverflow.com/questions/45190469/how-to-identify-whether-webp-image-is-static-or-animated.
	$result = false;
	fseek( $fh, 12 );
	if ( 'VP8X' === fread( $fh, 4 ) ) {
		fseek( $fh, 20 );
		$byte = fread( $fh, 1 );
		$result = ( ( ord( $byte ) >> 1 ) & 1 ) ? true : false;
	}
	fclose( $fh );
	return $result;

}
