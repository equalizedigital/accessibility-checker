<?php

function edac_rule_img_alt_empty($content, $post){
		
	// rule vars
	$dom = $content['html'];
	$tags = array('img', 'input');
	$errors = [];

	if($tags){
		foreach ($tags as $tag){
			$elements = $dom->find($tag);
			foreach ($elements as $element){
				if (
					isset($element) 
					and ($element->tag == 'img'
					and $element->hasAttribute('alt')
					and $element->getAttribute('alt') == ""
					and $element->getAttribute('role') != "presentation")
					or  ($element->tag == 'input' 
							and $element->hasAttribute('alt') and $element->getAttribute('type') == "image" and $element->getAttribute('alt') == "")
						){

					$image_code = $element->outertext;

					// ignore certain images
					if(ac_img_alt_ignore_plugin_issues($image_code)) goto img_missing_alt_bottom;
				
					// ignore images with captions
					if(ac_img_alt_ignore_inside_valid_caption($image_code, $dom)) goto img_missing_alt_bottom;

					$errors[] = $image_code;

				}
				img_missing_alt_bottom:
			}
		}
	}

	return $errors;

}