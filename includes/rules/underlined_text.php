<?php

function edac_rule_underlined_text($content, $post){
	
	$errors = [];
	//$content = $content['html'];
	$elements = $content->find('u');

	/*
	 * check for html elements
	 * <u>test</u>
	 */
	if($elements){
		foreach ($elements as $element){
			$errors[] = $element->outertext;
		}
	}

	/*
	 * check for inline styles
	 * <p style="text-decoration: underline;">test</p>
	 */
	$elements = $content->find('*');
	if ($elements) {
		foreach ($elements as $element) {
			if (
				isset($element)
				&& !$element->hasAttribute('href')
				&& stristr($element->getAttribute('style'), 'text-decoration:')
				&& stristr(strtolower($element->getAttribute('style')), 'underline')
			){
				$errors[] = $element->outertext;
			}
		}
	}

	/*
	 * check for underline styles within style tags
	 * <style></style>
	 */
	$styles = $content->find('style');

	if ($styles) {
		foreach ($styles as $style) {
			$errors = array_merge(edac_css_underlined_text_check($content, $style->innertext),$errors);
		}
	}
	

	/*
	* check for underline styles from file
	*/
	foreach ($content->find('link[rel="stylesheet"]') as $stylesheet){
		$stylesheet_url = $stylesheet->href;
		$styles = @file_get_contents($stylesheet_url);
		$errors = array_merge(edac_css_underlined_text_check($content, $styles),$errors);
	}

	
	

	return $errors;
}

/**
 * Check for underline css files
 *
 * @param $content
 * @param $styles
 * @return array
 */
function edac_css_underlined_text_check($content, $styles){

	$dom = $content;
	$errors = [];
	$error_code = '';
	$css_array = edac_parse_css($styles);

	if ($css_array) {
		foreach ($css_array as $element => $rules) {
			if (array_key_exists('text-decoration', $rules)) {

				if(stristr(strtolower($rules['text-decoration']), 'underline')){

					$error_code = $element . '{ ';
					foreach ($rules as $key => $value) {
						$error_code .= $key . ': ' . $value . '; ';
					}
					$error_code .= '}';

					$elements = $dom->find($element);
					if($elements){
						foreach ($elements as $element) {
							if($element->tag != 'a'){
								$errors[] = $element->outertext.' '.$error_code;
							}
						}
					}
				}
			}
		}
	}

	return $errors;

}