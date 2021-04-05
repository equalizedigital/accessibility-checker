<?php
function edac_rule_iframe_missing_title($content, $post){

	// rule vars
	$dom = $content['html'];	
	$iframe_tags = $dom->find('iframe');
	$errors = [];

	foreach ($iframe_tags as $iframe) {
		if (isset($iframe) and $iframe->getAttribute('title') == "" and $iframe->getAttribute('aria-label') == ""){
			
			$iframecode = htmlspecialchars($iframe->outertext);

			$errors[] = $iframecode;

		}
	}
	return $errors;
}

?>