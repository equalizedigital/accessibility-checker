<?php

function edac_rule_video_present($content, $post){
	
	// rule vars
	$dom = $content['html'];
	$file_extensions = ['.3gp','.asf','.asx','.avi','.flv','.m4p','.mov','.mp4','.mpeg','.mpeg2','.mpg','.mpv','.ogg','.ogv','.qtl','.smi','.smil','.wax','.webm','.wmv','.wmp','.wmx'];
	$keywords = ['youtube','youtu.be','vimeo'];
	$errors = [];

	// check for video blocks
	$elements = $dom->find('.is-type-video');
	if($elements){
		foreach ($elements as $element) {
			$errors[] = $element->outertext;
		}
	}

	// check for iframe embeds
	$elements = $dom->find('iframe');
	if($elements){
		$file_extensions = array_merge($file_extensions, $keywords);
		foreach ($elements as $element) {
			$src_text = $element->getAttribute('src');
			foreach($file_extensions as $file_extension){
				if(strpos(strtolower($src_text), $file_extension)){
					$errors[] = $element->outertext;
				}
			}
		}
	}	

	// check for video file extensions
	$elements = $dom->find('[src]');
	if($elements){
		foreach ($elements as $element){
			$src_text = $element->getAttribute('src');
			foreach($file_extensions as $file_extension){
				if(strpos(strtolower($src_text), $file_extension)){
					$errors[] = $element->outertext;
				}
			}
		}
	}
   
	return $errors;
}
