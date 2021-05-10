<?php

/*
 * A <p> element contains less than 50 characters and is either:
 * 20 pixels or bigger, or
 * 16 pixels or bigger and bold and/or italicized.
 */

function edac_rule_possible_heading($content, $post){

	$dom = $content['html'];
	$errors = [];

	/*
	 * check for inline styles
	 * <p style="font-size: 20px;">Possible Heading</p>
	 * <p style="font-size: 16px; font-weight: bold|bolder|700|800|900;">Possible Heading</p>
	 * <p style="font-size: 16px; font-style: italic|oblique;">Possible Heading</p>
	 * <p style="font-size: 16px;"><b>Possible Heading</b></p>
	 * <p style="font-size: 16px;"><strong>Possible Heading</strong></p>
	 * <p style="font-size: 16px;"><i>Possible Heading</i></p>
	 * <p style="font-size: 16px;"><em>Possible Heading</em></p>
	 */
	$fontsearchpatterns = array();
	$fontsearchpatterns[] = "|font\-size:\s?([\d]+)pt|i";
	$fontsearchpatterns[] = "|font\-size:\s?([\d]+)px|i";
	$fontsearchpatterns[] = "|font:\s?[\w\s\d*\s]*([\d]+)pt|i";
	$fontsearchpatterns[] = "|font:\s?[\w\s\d*\s]*([\d]+)px|i";
	
	$elements = $dom->find('p');
	if ($elements) {	
		foreach ($elements as $element) {

			/* if (isset($element) && stristr($element->getAttribute('style'), 'font-size:') && $element->innertext != "" && strlen($element->innertext) < 50) {

				// Get font size
				foreach ($fontsearchpatterns as $pattern) {
					if (preg_match_all($pattern, $element, $matches, PREG_PATTERN_ORDER)) {	
						$matchsize = sizeof($matches);
						for ($i = 0; $i < $matchsize; $i++) {
							if (isset($matches[0][$i]) and $matches[0][$i] != "") {
								$absolute_fontsize_errorcode = htmlspecialchars($matches[0][$i]);

								preg_match_all('!\d+!', $absolute_fontsize_errorcode, $matches);

								if(
									implode(' ',$matches[0]) >= 20 ||
									(
										implode(' ',$matches[0]) >= 16 && 
										(
											preg_match('(bold|bolder|700|800|900)', stristr($element->getAttribute('style'), 'font-weight:')) === 1 ||
											preg_match('(italic|oblique)', stristr($element->getAttribute('style'), 'font-style:')) === 1 ||
											$element->find('b') ||
											$element->find('strong') ||
											$element->find('i') ||
											$element->find('em')
										)
									)
								){
									$errors[] = $element;
								}
							}
						}
					}
				}
			} */

			if (isset($element) && $element->innertext != "" && strlen($element->innertext) < 50) {

				// parse inline styles and run logic
				$styles = $element->getAttribute('style');
				$css_array = edac_parse_css($styles);
				if ($css_array) {
					foreach ($css_array as $rules) {

						if (array_key_exists('font-size', $rules)) {
			
							if(preg_match('(rem|em|%|inherit)', $rules['font-size']) === 1) { continue; } 
			
							if($rules['font-size'] >= 20){
								$errors[] = $element;
							}elseif($rules['font-size'] >= 16){

								if(
									preg_match('(bold|bolder|700|800|900)', stristr($element->getAttribute('style'), 'font-weight:')) === 1 ||
									preg_match('(italic|oblique)', stristr($element->getAttribute('style'), 'font-style:')) === 1 ||
									$element->find('b') ||
									$element->find('strong') ||
									$element->find('i') ||
									$element->find('em')
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
		$errors = array_merge(ac_css_font_size_weight_check($content),$errors);
	}

	return $errors;

}

function ac_css_font_size_weight_check($content){

	$dom = $content['html'];
	$errors = [];
	$error_code = '';
	$css_array = $content['css_parsed'];

	if ($css_array) {
		foreach ($css_array as $element => $rules) {

			if (array_key_exists('font-size', $rules)) {

				// replace CSS variables
				$rules['font-size'] = edac_replace_css_variables($rules['font-size'], $css_array);

				if(preg_match('(rem|em|%|inherit)', $rules['font-size']) === 1) { continue; }

				if($rules['font-size'] >= 20){
					goto save_error;
				}

				if($rules['font-size'] >= 16){
					$has_bold_or_italic = false;
					if(array_key_exists('font-weight', $rules)){

						// replace CSS variables
						$rules['font-weight'] = edac_replace_css_variables($rules['font-weight'], $css_array);

						if(
							$rules['font-weight'] == 'bold' ||
							$rules['font-weight'] == 'bolder' ||
							$rules['font-weight'] == '700' ||
							$rules['font-weight'] == '800' ||
							$rules['font-weight'] == '900'
						){
							$has_bold_or_italic = true;
						}
					}
					if(array_key_exists('font-style', $rules)){

						// replace CSS variables
						$rules['font-style'] = edac_replace_css_variables($rules['font-style'], $css_array);

						if(
							$rules['font-style'] == 'italic' ||
							$rules['font-style'] == 'oblique'
						){
							$has_bold_or_italic = true;
						}
					}
					if($has_bold_or_italic == true){ 
						goto save_error;
					}else{
						continue;
					}
				}else{
					continue;
				}
					
				save_error:

				$error_code = $element . '{ ';
				foreach ($rules as $key => $value) {
					$error_code .= $key . ': ' . $value . '; ';
				}
				$error_code .= '}';

				$elements = $dom->find($element);
				if($elements){
					foreach ($elements as $element) {
						if($element->tag == 'p' && strlen($element->innertext) < 50){
							$errors[] = $element->outertext.' '.$error_code;
						}
					}
				}
			}
		}
	}

	return $errors;

}