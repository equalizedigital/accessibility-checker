<?php

function edac_rule_image_map_missing_alt_text($content, $post){
	
	// rule vars
	$dom = $content['html'];
	$maps = $dom->find('map');
	$errors = [];

	foreach ($maps as $map){

		$mapcode = $map->outertext;
		$areas = $map->find('area');

		foreach ($areas as $area){

			$alt = str_replace(' ', '', $area->getAttribute('alt'));

			if (isset($alt) && ($alt == "")){

				$errors[] = $area;

			}

		}
		
	}
	return $errors;
}