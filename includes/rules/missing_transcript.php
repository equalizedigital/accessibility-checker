<?php

function edac_rule_missing_transcript($content, $post){

	if(empty($post->post_content)) return [];

	$dom = edac_str_get_html($post->post_content);
	$errors = [];
	 //edac_log(get_oembed_response_data( $post, 500) );
	$elements = $dom->find_media_embeds(true);
  
	$dom->convert_tag_to_marker( ['img', 'iframe', 'audio', 'video', '.is-type-video'] );
	foreach( $elements as $element ) {	  
		if ( ! $dom->text_around_element_contains( $element, __( 'transcript', 'edac' ) , 25 ) ) {
			$element->innertext = '';
			$errors[] = $element;
		}	  
	}
	$linked_media = $dom->find_linked_media(true);

	foreach( $linked_media as $media_link ) {
		 //edac_log($media_link);
		if ( ! $dom->text_around_element_contains( $media_link, __( 'transcript', 'edac' ) , 25 ) ) {
			$errors[] = $media_link;
		}
	}
	
	return $errors;
}
