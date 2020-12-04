<?php

function edac_rule_missing_lang_attr($content, $post)
{
	if(!$content['file_html']) return;

	$dom = $content['file_html'];
	$errors = [];
	
	$elements = $dom->find('html');
	if($elements){
		foreach ($elements as $element) {	
			if($element->getAttribute('lang') != "" || $element->getAttribute('xml:lang') != "") continue;
			$errors[] = edac_simple_dom_remove_child($element);
		}
	}
	return $errors;
}