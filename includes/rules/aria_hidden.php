<?php

function edac_rule_aria_hidden($content, $post){

	$dom = $content['html'];
	$errors = [];
	$elements = $dom->find('[aria-hidden="true"]');

	if($elements){
		foreach ($elements as $element) {
			$errors[] = $element;
		}
	}

	return $errors;
}