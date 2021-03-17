<?php

function edac_rule_img_alt_long($content, $post){
		
	// rule vars
	$dom = $content['html'];
	$errors = [];

	$images = $dom->find('img');
	foreach ($images as $image) {
		if(isset($image) && $image->hasAttribute('alt') && $image->getAttribute('alt') != ""){
			$alt = $image->getAttribute('alt');
			if(strlen($alt) > 100){
				$image_code = $image;
				$errors[] = $image_code;
			}
		}
	}
	return $errors;
}