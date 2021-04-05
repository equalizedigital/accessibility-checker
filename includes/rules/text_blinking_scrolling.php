<?php

function edac_rule_text_blinking_scrolling($content, $post)
{
	$dom = $content['html'];
	$errors = [];

	/**
	 * Check for blink tag
	 * <blink>This text may blink depending on the browser you use.</blink>
	 */
	$blinks = $dom->find('blink');
	foreach ($blinks as $blink) {
		$errors[] = $blink->outertext;
	}

	/**
	 * Check for marquee tag
	 * <marquee>This text may scroll from right to left depending on the browser you you.</marquee>
	 */
	$marquees = $dom->find('marquee');
	foreach ($marquees as $marquee) {
		$errors[] = $marquee->outertext;
	}

	/**
	 * Check for text-decoration: blink
	 * <p style="text-decoration: blink;">This text may blink depending on the browser you use.</p>
	 */
	$elements = $dom->find('*');
	if ($elements) {
		foreach ($elements as $element) {
			if (isset($element) && stristr($element->getAttribute('style'), 'text-decoration:') && $element->innertext != "") {
				if(strpos(strtolower($element), 'blink')){
					$errors[] = $element->outertext;
				}
			}
		}
	}

	// check styles
	if($content['css_parsed']){
		$errors = array_merge(ac_css_text_decoration_blink_check($content),$errors);
	}

	return $errors;

}

function ac_css_text_decoration_blink_check($content){

	$dom = $content['html'];
	$errors = [];
	$error_code = '';
	$css_array = $content['css_parsed'];

	if ($css_array) {
		foreach ($css_array as $element => $rules) {
			if (array_key_exists('text-decoration', $rules)) {
				
				// replace CSS variables
				$rules['text-decoration'] = edac_replace_css_variables($rules['text-decoration'], $css_array);

				if($rules['text-decoration'] == 'blink' || $rules['text-decoration'] == 'Blink') {
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
