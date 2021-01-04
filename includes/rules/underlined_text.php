<?php

function edac_rule_underlined_text($content, $post){
	$dom = $content;
	$errors = [];
	$elements = $dom->find('u');
	if($elements){
		foreach ($elements as $element){
			$errors[] = $element->outertext;
		}
	}
	return $errors;
}
