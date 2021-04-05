<?php

/**
 * Missing or empty Language attribute
 *
 * @param string $content
 * @param array $post
 * @return array
 */
function edac_rule_missing_lang_attr($content, $post)
{	
	$errors = [];
	$elements = $content['html']->find('html');
	if($elements[0]){
		if(($elements[0]->hasAttribute('lang') && $elements[0]->getAttribute('lang') != "") || ($elements[0]->hasAttribute('xml:lang') && $elements[0]->getAttribute('xml:lang') != "")) return;		
		$errors[] = edac_simple_dom_remove_child($elements[0]);
	}
	return $errors;
}