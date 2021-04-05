<?php

/**
 * Small Text
 *
 * @param $content
 * @param $post
 * @return array
 */
function edac_rule_text_small($content, $post){

	$fontsearchpatterns = array();
	$fontsearchpatterns[] = "|font\-size:\s?([\d]+)pt|i";
	$fontsearchpatterns[] = "|font\-size:\s?([\d]+)px|i";
	$fontsearchpatterns[] = "|font:\s?[\w\s\d*\s]*([\d]+)pt|i";
	$fontsearchpatterns[] = "|font:\s?[\w\s\d*\s]*([\d]+)px|i";
	$errors = [];

	/*
	 * check for inline font-size styles
	 * <p style="font-size: 10px;">test</p>
	 */
	$dom = $content['html'];
	$elements = $dom->find('*');
	if ($elements) {
		foreach ($elements as $element) {

			if (isset($element) && stristr($element->getAttribute('style'), 'font-size:') && $element->innertext != "") {

				// Get font size
				foreach ($fontsearchpatterns as $pattern) {
					if (preg_match_all($pattern, $element, $matches, PREG_PATTERN_ORDER)) {
						$matchsize = sizeof($matches);
						for ($i = 0; $i < $matchsize; $i++) {
							if (isset($matches[0][$i]) and $matches[0][$i] != "") {
								$absolute_fontsize_errorcode = htmlspecialchars($matches[0][$i]);

								preg_match_all('!\d+!', $absolute_fontsize_errorcode, $matches);

								if(
									stristr($absolute_fontsize_errorcode, 'px') == 'px' && implode(' ',$matches[0]) <= 10
									|| stristr($absolute_fontsize_errorcode, 'pt') == 'pt' && implode(' ',$matches[0]) <= 13
								){
									$errors[] = $element;
								}
							}
						}
					}
				}
			}
		}
	}

	// check styles
	if($content['css_parsed']){
		$errors = array_merge(ac_css_small_text_check($content),$errors);
	}

	return $errors;

}

/**
 * Check for small test in css files
 *
 * @param $content
 * @param $styles
 * @return array
 */
function ac_css_small_text_check($content){

	$dom = $content['html'];
	$errors = [];
	$error_code = '';
	$css_array = $content['css_parsed'];

	if ($css_array) {
		foreach ($css_array as $element => $rules) {
			if (array_key_exists('font-size', $rules)) {

				// replace CSS variables
				$rules['font-size'] = edac_replace_css_variables($rules['font-size'], $css_array);
				
				$value = str_replace('.', '', preg_replace('/\d/', '', $rules['font-size'] ));

				if($value == 'px' && $rules['font-size'] <= 10 || $value == 'pt' && $rules['font-size'] <= 13){

					$error_code = $element . '{ ';
					foreach ($rules as $key => $value) {
						$error_code .= $key . ': ' . $value . '; ';
					}
					$error_code .= '}';

					$elements = $dom->find($element);
					if($elements){
						foreach ($elements as $element) {
							if($element->tag == 'p'){
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