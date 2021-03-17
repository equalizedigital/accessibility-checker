<?php

/**
 * Empty Button Rule
 *
 * @param $content
 * @param $post
 * @return array
 * 
 * <button></button>
 * <input type="button">
 * <input type="submit">
 * <input type="reset">
 * role="button"
 */
function edac_rule_empty_button($content, $post)
{
	$dom = $content['html'];
	$buttons = $dom->find('button, [role=button]');
	$inputs = $dom->find('input[type=button], input[type=submit], input[type=reset]');
	$errors = [];

	// check buttons
	foreach ($buttons as $button) {
		if (
			str_ireplace(array(' ', '&nbsp;', '-', '_'), '', trim($button->plaintext)) == ""
			and $button->getAttribute('aria-label') == ""
			and $button->getAttribute('title') == ""
		) {

			$error_code = $button->outertext;
			$image = $button->find('img');
			$input = $button->find('input');
			$i = $button->find('i');

			if (
				$error_code != ""
				and (!isset($image[0]) or trim($image[0]->getAttribute('alt')) == "")
				and (!isset($input[0]) or trim($input[0]->getAttribute('value')) == "")
				and (!isset($i[0]) or (trim($i[0]->getAttribute('title')) == "") and trim($i[0]->getAttribute('aria-label')) == "")
			) {
				$errors[] = $error_code;
			}
		}
	}

	// check inputs
	foreach ($inputs as $input) {
		if($input->getAttribute('value') == ""){
			$errors[] = $input->outertext;
		}
	}

	return $errors;
}
