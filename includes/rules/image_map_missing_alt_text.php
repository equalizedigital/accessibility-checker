<?php

function edac_rule_image_map_missing_alt_text($content, $post){
	
	// rule vars
	$dom = $content['the_content_html'];
	$maps = $dom->find('map');
	$errors = [];

	foreach ($maps as $map){

		$mapcode = $map->outertext;
		$areas = $map->find('area');

		foreach ($areas as $area){
			if (isset($area) and ($area->getAttribute('alt') == "")){

				$error_code = $area;

				$errors[] = $error_code;

			}

		}
		
	}
	return $errors;
}