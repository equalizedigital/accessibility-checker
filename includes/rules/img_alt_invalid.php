<?php

function edac_rule_img_alt_invalid($content, $post){

	$dom = $content['html'];

	$starts_with_keywords = [
		__('graphic of','edac'),
		__('bullet','edac'),
		__('image of','edac')
	];

	$ends_with_keywords = [
		__('image','edac'),
		__('graphic','edac')
	];

	$image_extensions = [
		__('.apng','edac'),
		__('.bmp','edac'),
		__('.gif','edac'),
		__('.ico','edac'),
		__('.cur','edac'),
		__('.jpg','edac'),
		__('.jpeg','edac'),
		__('.jfif','edac'),
		__('.pjpeg','edac'),
		__('.pjp','edac'),
		__('.png','edac'),
		__('.svg','edac'),
		__('.tif','edac'),
		__('.tiff','edac'),
		__('.webp','edac')
	];

	$keywords = [
		__('graphic of','edac'),
		__('bullet','edac'),
		__('image of','edac'),
		__('image','edac'),
		__('graphic','edac'),
		__('image','edac'),
		__('graphic','edac'),
		__('photo','edac'),
		__('photograph','edac'),
		__('drawing','edac'),
		__('painting','edac'),
		__('artwork','edac'),
		__('logo','edac'),
		__('bullet','edac'),
		__('button','edac'),
		__('arrow','edac'),
		__('more','edac'),
		__('spacer','edac'),
		__('blank','edac'),
		__('chart','edac'),
		__('table','edac'),
		__('diagram','edac'),
		__('graph','edac'),
		__('*','edac')
	];

	$errors = [];

	$images = $dom->find('img');
	if($images){
		foreach ($images as $image){
			if (isset($image)){
				$error = false;
				$alt = strtolower($image->getAttribute('alt'));
				$image_code = $image->outertext;

				// ignore certain images
				if(ac_img_alt_ignore_plugin_issues($image_code)) goto img_alt_invalid_bottom;
				
				// ignore images with captions
				if(ac_img_alt_ignore_inside_valid_caption($image_code, $dom)) goto img_alt_invalid_bottom;
				
				// check if alt contains only whitespace
				if(strlen($alt) > 0 && ctype_space($alt) == true){
					$error = true;
					goto img_alt_invalid_bottom;
				}

				// check if string begins with
				if($starts_with_keywords){
					foreach ($starts_with_keywords as $starts_with_keyword) {
						if(ac_starts_with($alt,$starts_with_keyword)){
							$error = true;
							goto img_alt_invalid_bottom;
						}
					}
				}

				// check if string ends with
				if($ends_with_keywords){
					foreach ($ends_with_keywords as $ends_with_keyword) {
						if(ac_ends_with($alt,$ends_with_keyword)){
							$error = true;
							goto img_alt_invalid_bottom;
						}
					}
				}
				
				// check for image extensions
				if($image_extensions){
					foreach ($image_extensions as $image_extension){
						if(strpos($alt,$image_extension) == true){
							$error = true;
							goto img_alt_invalid_bottom;
						}
					}
				}

				// check for keywords
				if($keywords){
					foreach ($keywords as $keyword){
						// remove spaces
						$alt = str_replace(' ','',$alt);
						if($alt == $keyword){
							$error = true;
							goto img_alt_invalid_bottom;
						}
					}
				}
				
				img_alt_invalid_bottom:
				if($error == true){
					$errors[] = $image_code;
				}
			}
		}
	}
	return $errors;
}

function ac_starts_with( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}

function ac_ends_with( $haystack, $needle ) {
	$length = strlen( $needle );
	if( !$length ) {
		return true;
	}
	return substr( $haystack, -$length ) === $needle;
}