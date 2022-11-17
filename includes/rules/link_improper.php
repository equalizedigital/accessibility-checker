<?php

/**
 * Improper Use of Link
 * 
 * Find all of the links on the page that contain only a # in the href attribute or are missing an href attribute completely. If these links do not have role="button" then flag an Improper Use of Link error.
 *
 * @param array $content
 * @param array $post
 * @return void
 */
function edac_rule_link_improper($content, $post){

	$dom = $content['html'];
	$errors = [];
	$links = $dom->find('a');
	foreach ($links as $link) {
        if((!$link->hasAttribute('href') || trim($link->getAttribute('href')) == "#") && $link->getAttribute('role') != "button"){
            $a_tag_code = $link->outertext;
            $errors[] = $a_tag_code;
        }
	}
	return $errors;

} 