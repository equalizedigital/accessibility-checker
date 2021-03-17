<?php

function edac_rule_img_alt_invalid($content, $post){

	// rule vars
	$dom = $content['html'];
	$starts_with_keywords = ['graphic of','bullet','image of'];
	$ends_with_keywords = ['image','graphic'];
	$image_extensions = ['.apng','.bmp','.gif','.ico','.cur','.jpg','.jpeg','.jfif','.pjpeg','.pjp','.png','.svg','.tif','.tiff','.webp'];
	$keywords = ['graphic of','bullet','image of','image','graphic','image','graphic','photo','photograph','drawing','painting','artwork','logo','bullet','button','arrow','more','spacer','blank','chart','table','diagram','graph','*'];
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
						if(ac_starts_with(__($alt,'edac'),$starts_with_keyword)){
							$error = true;
							goto img_alt_invalid_bottom;
						}
					}
				}

				// check if string ends with
				if($ends_with_keywords){
					foreach ($ends_with_keywords as $ends_with_keyword) {
						if(ac_ends_with(__($alt,'edac'),$ends_with_keyword)){
							$error = true;
							goto img_alt_invalid_bottom;
						}
					}
				}
				
				// check for image extensions
				if($image_extensions){
					foreach ($image_extensions as $image_extension){
						if(strpos($alt,__($image_extension,'edac')) == true){
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
						if($alt == __($keyword,'edac')){
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