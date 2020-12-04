<?php

function edac_rule_link_blank($content, $post){

	$content = $content['the_content_html'];
	$errors = [];
	$elements = $content->find('a[target="_blank"]');
	if($elements){
		foreach ($elements as $element) {
			$errors[] = $element->outertext;
		}
	}
	return $errors;

} 