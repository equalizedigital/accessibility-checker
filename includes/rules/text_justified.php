<?php

/**
 * Check for justified text
 *
 * @param string $content
 * @param array $post
 * @return array
 */
function edac_rule_text_justified($content, $post){

	// rule vars
	$fontsearchpatterns = array();
	$fontsearchpatterns[] = "|(text-)?align:\s?justify|i";
	$errors = [];

	/*
	 * check for justify font-size styles
	 * < class="text-align: justify">test</>
	 */
	$dom = $content['html'];
	$elements = $dom->find('*');
	if ($elements) {
		foreach ($elements as $element) {

			if (isset($element) && stristr($element->getAttribute('style'), 'text-align:') && $element->innertext != "") {

				foreach ($fontsearchpatterns as $pattern) {
					if (preg_match_all($pattern, $element, $matches, PREG_PATTERN_ORDER)) {
						$matchsize = sizeof($matches);
						for ($i = 0; $i < $matchsize; $i++) {
							if (isset($matches[0][$i]) and $matches[0][$i] != "") {
								$errors[] = $element;
							}
						}
					}
				}
			}
		}
	}

	// check styles
	if($content['css_parsed']){
		$errors = array_merge(edac_css_justified_text_check($content),$errors);
	}

	return $errors;
}

/**
 * Check for text-align: justify in css files
 *
 * @param $content
 * @param $styles
 * @return array
 */
function edac_css_justified_text_check($content){

	$dom = $content['html'];
	$errors = [];
	$error_code = '';
	$css_array = $content['css_parsed'];

	if ($css_array) {
		foreach ($css_array as $element => $rules) {
			if (array_key_exists('text-align', $rules)) {

				// replace CSS variables
				$rules['text-align'] = edac_replace_css_variables($rules['text-align'], $css_array);
				
				$value = preg_replace('/\d/', '', $rules['text-align'] );

				if($value == 'justify'){

					$error_code = $element . '{ ';
					foreach ($rules as $key => $value) {
						$error_code .= $key . ': ' . $value . '; ';
					}
					$error_code .= '}';

					$elements = $dom->find($element);
					if($elements){
						foreach ($elements as $element) {
							$errors[] = $element->outertext.' '.$error_code;
						}
					}
				}
			}
		}
	}

	return $errors;

}