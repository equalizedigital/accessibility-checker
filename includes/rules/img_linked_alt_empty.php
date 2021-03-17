<?php

function edac_rule_img_linked_alt_empty($content, $post){
	
	// rule vars
    $dom = $content['html'];
    $errors = [];

    $as = $dom->find('a');
	foreach ($as as $a){

        // anchors with aria-label or title or valid node text
		if($a->getAttribute('aria-label') == "" && $a->getAttribute('title') == "" && strlen($a->plaintext) <= 5){

			$images = $a->find('img');
			foreach ($images as $image){

                if( isset($image)
                    and $image->hasAttribute('alt')
                    and $image->getAttribute('alt') == ""
                    and $image->getAttribute('role') != "presentation"){

                    $image_code = $a;

                    $errors[] = $image_code;
                    
                }
			}
		}
    }
    return $errors;
}