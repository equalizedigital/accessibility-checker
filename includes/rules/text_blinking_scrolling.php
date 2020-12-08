<?php

function edac_rule_text_blinking_scrolling($content, $post)
{
	$dom = $content['the_content_html'];
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

	/**
	 * check for styles within style tags
	 * <style></style>
	 */
	if($content['file_html']){
		$dom_styles = $content['file_html'];
		$styles = $dom_styles->find('style');

		if ($styles) {
			foreach ($styles as $style) {
				$errors = array_merge(ac_css_text_decoration_blink_check($content, $style->innertext),$errors);
			}
		}
		

		/**
		 * check for styles from file
		 */
		foreach ($dom_styles->find('link[rel="stylesheet"]') as $stylesheet){
			$stylesheet_url = $stylesheet->href;
			$styles = @file_get_contents($stylesheet_url);
			$errors = array_merge(ac_css_text_decoration_blink_check($content, $styles),$errors);
		}
	}

	return $errors;

}

function ac_css_text_decoration_blink_check($content, $styles){

	$dom = $content['the_content_html'];
	$errors = [];
	$error_code = '';
	$css_array = edac_parse_css($styles);

	if ($css_array) {
		foreach ($css_array as $element => $rules) {
			if (array_key_exists('text-decoration', $rules)) {
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
