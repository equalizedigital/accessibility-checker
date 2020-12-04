<?php

function edac_rule_link_ambiguous_text($content, $post){

	// rule vars
	$dom = $content['the_content_html'];
	$errors = [];

	$as = $dom->find('a');
	foreach ($as as $a){

		$error = false;
		$error_code = $a->outertext;

		if($a->plaintext){
			$text = strtolower($a->plaintext);
			$error = ac_check_ambiguous_phrase($text);
		}else{
			$images = $a->find('img');
			foreach ($images as $image){
				$alt = strtolower($image->getAttribute('alt'));
				$error = ac_check_ambiguous_phrase($alt);
				if($error == true){
					break;
				}
			}
		}

		if($error){
			$errors[] = $error_code;
		}
	}
	return $errors;
}

function ac_check_ambiguous_phrase($text){

	$ambiguous_phrases = ['click here','here','go here','more','more...','details','more details','link','this page','continue','continue reading','read more','open','download','button','keep reading'];

	// check if text contains phrase
	if(strpos($text,__('click here','edac')) == true || strpos($text,__('click','edac')) == true){
		return true;
	}

	// check if text is equal to
	foreach ($ambiguous_phrases as $ambiguous_phrase) {
		if($text == __($ambiguous_phrase,'edac')){
			return true;
			break;
		}
	}

	return false;

}