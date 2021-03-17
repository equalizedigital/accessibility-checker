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
	$title = $content['html']->find('title',0);
	$meta_title = $content['html']->find('meta[property="og:title"]',0);
	$error = [];

	if(!$the_title || $the_title == '' || $the_title == 'Untitled' || strlen($the_title) <= 1){
		$error = ["Missing Title - Post ID: $post->ID"];
	}elseif(
		(!isset($title) || $title->innertext == "" || $title->innertext == "-")
		&& (!isset($meta_title) || ($meta_title->hasAttribute('content') && ($meta_title->getAttribute('content') == "" || strlen($meta_title->getAttribute('content')) <= 1)))
	){
		$error = ["Missing title tag or meta title tag - Post ID: $post->ID"];
	}

	return $error;

}