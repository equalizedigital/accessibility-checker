<?php

function edac_rule_missing_lang_attr($content, $post)
{
	if(!$content) return;

	$dom = $content;
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