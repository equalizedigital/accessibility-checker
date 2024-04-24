<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Missing Transcript Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_missing_transcript( $content, $post ) { // phpcs:ignore -- $content is reserved for future use or for compliance with a specific interface.

	if ( empty( $post->post_content ) ) {
		return [];
	}

	$dom      = edac_str_get_html( $post->post_content );
	$errors   = [];
	$elements = [];
	if ( $dom ) {
		$elements = $dom->find_media_embeds( true );
	}

	$dom->convert_tag_to_marker( [ 'img', 'iframe', 'audio', 'video', '.is-type-video' ] );
	foreach ( $elements as $element ) {
		if ( ! $dom->text_around_element_contains( $element, __( 'transcript', 'accessibility-checker' ), 25 ) ) {
			$element->innertext = '';
			$errors[]           = $element;
		}
	}
	$linked_media = $dom->find_linked_media( true );

	foreach ( $linked_media as $media_link ) {
		$href = $media_link->href;

		// Skip certain types of links.
		if ( strpos( $href, 'mailto:' ) === 0 
			|| strpos( $href, 'tel:' ) === 0 
			|| strpos( $href, '#' ) === 0 
			|| strpos( $href, 'javascript:' ) === 0
		) {
			continue;
		}
		
		if ( ! $dom->text_around_element_contains( $media_link, __( 'transcript', 'accessibility-checker' ), 25 ) ) {
			$errors[] = $media_link;
		}
	}

	return $errors;
}
