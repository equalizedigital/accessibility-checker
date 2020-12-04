<?php

function edac_rule_underlined_text($content, $post){
	$dom = $content['the_content_html'];
	$errors = [];
	$elements = $dom->find('u');
	if($elements){
		foreach ($elements as $element){
			$errors[] = $element->outertext;
		}
	}
	return $errors;
}
