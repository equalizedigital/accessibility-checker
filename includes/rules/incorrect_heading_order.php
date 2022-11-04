<?php

/**
 * Incorrect Heading Order
 *
 * @param array $content
 * @param array $post
 * @return array
 */
function edac_rule_incorrect_heading_order($content, $post){

	if(empty($post->post_content)) return [];

	$dom = $content['html'];
	$starting_heading_level = 1;
	$errors = [];
	$elements = $dom->find('h1,[role=heading][aria-level=1],h2,[role=heading][aria-level=2],h3,[role=heading][aria-level=3],h4,[role=heading][aria-level=4],h5,[role=heading][aria-level=5],h6,[role=heading][aria-level=6]');
	$previous = $starting_heading_level;

	if($elements){
		foreach ($elements as $key => $element) {
			
			if($element->hasAttribute('aria-level')){
				$current = $element->getAttribute('aria-level');
			}else{
				$current = str_replace('h','',$element->tag);
			}

			if(!$previous || $current == $previous) goto end;

			if(
				$current > $previous
				&& $current != $previous+1
			){
				$errors[] = $element->outertext;
			}

			end:
			
			$previous = $current;
		}
	}

	return $errors;
	
}