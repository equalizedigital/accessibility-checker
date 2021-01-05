<?php

/**
 * Missing Title
 *
 * @param $content
 * @param $post
 * @return array
 */
function edac_rule_missing_title($content, $post){

	$the_title = get_the_title( $post->ID );
	$title = $content->find('title',0);
	$meta_title = $content->find('meta[property="og:title"]',0);
	//edac_log($title->innertext);
	//edac_log($meta_title->getAttribute('content'));

	if(!$the_title || $the_title == ''){
		
	}

	if(
		isset($title) && $title->innertext == ""
		|| isset($meta_title) && $meta_title->hasAttribute('content') && $meta_title->getAttribute('content') == ""
	)


	
	
		//return ["Missing Title - Post ID: $post->ID"];
	}
}