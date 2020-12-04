<?php

function edac_rule_missing_headings($content, $post){

	// rule vars
	$dom = $content['the_content_html'];
	$h1 = count($dom->find('h1'));
	$h2 = count($dom->find('h2'));
	$h3 = count($dom->find('h3'));
	$h4 = count($dom->find('h4'));
	$h5 = count($dom->find('h5'));
	$h6 = count($dom->find('h6'));	
	$headings = ($h1+$h2+$h3+$h4+$h5+$h6);

	if($headings == 0){
		$errorcode = __('Missing headings - Post ID: ', 'edac').$post->ID;
		return [$errorcode];
	}
}