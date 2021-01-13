<?php

function edac_rule_missing_lang_attr($content, $post)
{	
	$errors = [];
	$elements = $content->find('html');
	if($elements[0]){
		if($elements[0]->getAttribute('lang') != "" || $elements[0]->getAttribute('xml:lang') != "") return;		
		$errors[] = edac_simple_dom_remove_child($elements[0]);
	}
	return $errors;
}