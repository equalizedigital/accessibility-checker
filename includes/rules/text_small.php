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
	$dom = $content['the_content_html'];
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

								if(implode(' ',$matches[0]) <= 10){

									$errors[] = $element;

								}
							}
						}
					}
				}
			}
		}
	}

	/*
	 * check for font-size styles within style tags
	 * <style></style>
	 */
	if($content['file_html']){
		$dom = $content['file_html'];

		$styles = $dom->find('style');

		if ($styles) {
			foreach ($styles as $style) {
				$errors = array_merge(ac_css_small_text_check($content, $style->innertext),$errors);
			}
		}
		

		/*
		* check for font-size styles from file
		*/
		foreach ($dom->find('link[rel="stylesheet"]') as $stylesheet){
			$stylesheet_url = $stylesheet->href;
			$styles = file_get_contents($stylesheet_url);
			$errors = array_merge(ac_css_small_text_check($content, $styles),$errors);
		}
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
function ac_css_small_text_check($content, $styles){

	$dom = $content['the_content_html'];
	$errors = [];
	$error_code = '';
	$css_array = edac_parse_css($styles);

	if ($css_array) {
		foreach ($css_array as $element => $rules) {
			if (array_key_exists('font-size', $rules)) {

				if(preg_match('(rem|em|%|inherit)', $rules['font-size']) === 1) { continue; } 

				if($rules['font-size'] <= 10){

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