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
	$elements = $dom->find('h1,h2,h3,h4,h5,h6');
	$previous = $starting_heading_level;

	if($elements){
		foreach ($elements as $key => $element) {

			$current = str_replace('h','',$element->tag);

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