<?php

function edac_rule_tab_order_modified($content, $post){
	
	// rule vars
	$dom = $content['html'];
	$tags = array('a','input','select','textarea','button','datalist', 'output', 'area');
	$errors = [];
	
	foreach ($tags as $tag){
		$elements = $dom->find($tag);	
			
		foreach ($elements as $element) {
				
			if (isset($element) and $element->getAttribute('tabindex') > 0) {  	

				$error_code = $element->outertext;

				$errors[] = $error_code;

			}

		}

	}
	return $errors;
}