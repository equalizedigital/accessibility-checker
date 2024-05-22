<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Missing Title Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_missing_title( $content, $post ) {

	$the_title  = get_the_title( $post->ID );
	$title      = $content['html']->find( 'title', 0 );
	$meta_title = $content['html']->find( 'meta[property="og:title"]', 0 );

	if ( ! $the_title || '' === $the_title || 'Untitled' === $the_title || strlen( $the_title ) <= 1 ) {
		return [ "Missing Title - Post ID: $post->ID" ];
	}
	if ( ( ! isset( $title ) || '' === $title->innertext || '-' === $title->innertext )
		&& ( ! isset( $meta_title ) || ( $meta_title->hasAttribute( 'content' ) && ( (string) $meta_title->getAttribute( 'content' ) === '' || strlen( $meta_title->getAttribute( 'content' ) ) <= 1 ) ) )
	) {
		return [ "Missing title tag or meta title tag - Post ID: $post->ID" ];
	}
	return [];
}
