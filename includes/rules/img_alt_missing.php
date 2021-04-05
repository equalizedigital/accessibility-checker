<?php

function edac_rule_img_alt_missing($content, $post){
	
	// rule vars
	$dom = $content['html'];
	$tags = array('img', 'input');
	$errors = [];
	
	foreach ($tags as $tag){
		$elements = $dom->find($tag);	

		foreach ($elements as $element){
			
			if (isset($element) 
			and ($element->tag == 'img' 
			and !$element->hasAttribute('alt') 
			and $element->getAttribute('role') != "presentation") 
			and $element->getAttribute('aria-hidden') != "true"
			or($element->tag == 'input' 
			and !$element->hasAttribute('alt') 
			and $element->getAttribute('type') == "image")
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

	return $errors;

}

// ignore plugin issues that are being resolved automatically
function ac_img_alt_ignore_plugin_issues($content){

	// ignore spacer pixle
	$skipvalue = 'advanced-wp-columns/assets/js/plugins/views/img/1x1-pixel.png';	
	if(strstr($content,$skipvalue))	return 1;
	
	// ignore google ad code
	if(strstr($content,'src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/834593360/?guid=ON&amp;script=0"'))	return 1; 

	return 0;
}

//check if image is inside a caption and has valid alt text
function ac_img_alt_ignore_inside_valid_caption($image_code, $content){
		
	$dom = $content;
		
	// captions inside figure tags (html5)
	$figures = $dom->find('figure');
	foreach ($figures as $figure){
		$images = $figure->find('img');
		foreach($images as $image){
			if($image->getAttribute('src') != "" and strstr($image_code, $image->getAttribute('src')) and trim($figure->plaintext) != ""){
				return 1;
			}
		}
	}
		
	// captions inside div tags (pre html5)
	$divs = $dom->find('div');
	foreach ($divs as $div){
		if(stristr($div->getAttribute('class'), 'wp-caption')){	
			$images = $div->find('img');
			foreach ($images as $image) {	
				if($image->getAttribute('src') != "" and strstr($image_code, $image->getAttribute('src')) and strlen($div->plaintext) > 5){
					return 1;
				}
			}
		}
	}
		
	// anchors with aria-label or title or valid node text
	$as = $dom->find('a');
	foreach ($as as $a) {
		if($a->getAttribute('aria-label') != "" or $a->getAttribute('title') != "" or strlen($a->plaintext) > 5){
			$images = $a->find('img');
			foreach ($images as $image) {	
				if($image->getAttribute('src') != "" and strstr($image_code, $image->getAttribute('src'))) {
					return 1; 
				}
			}
		}
	}
		
	return 0;
}