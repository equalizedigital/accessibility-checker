<?php
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
function edac_rule_missing_transcript( $content, $post ) {

	if ( empty( $post->post_content ) ) {
		return array();
	}

	$dom = edac_str_get_html( $post->post_content );
	$errors = array();
	$elements = $dom->find_media_embeds( true );

	$dom->convert_tag_to_marker( array( 'img', 'iframe', 'audio', 'video', '.is-type-video' ) );
	foreach ( $elements as $element ) {
		if ( ! $dom->text_around_element_contains( $element, __( 'transcript', 'edac' ), 25 ) ) {
			$element->innertext = '';
			$errors[] = $element;
		}
	}
	$linked_media = $dom->find_linked_media( true );

	foreach ( $linked_media as $media_link ) {
		if ( ! $dom->text_around_element_contains( $media_link, __( 'transcript', 'edac' ), 25 ) ) {
			$errors[] = $media_link;
		}
	}

	return $errors;
}
