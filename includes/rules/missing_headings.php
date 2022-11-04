<?php

function edac_rule_missing_headings($content, $post){

	// rule vars
	$dom = str_get_html($post->post_content);
	if(empty($dom)) goto error;
	
	$h2 = count($dom->find('h2,[role=heading][aria-level=2]'));
	$h3 = count($dom->find('h3,[role=heading][aria-level=3]'));
	$h4 = count($dom->find('h4,[role=heading][aria-level=4]'));
	$h5 = count($dom->find('h5,[role=heading][aria-level=5]'));
	$h6 = count($dom->find('h6,[role=heading][aria-level=6]'));
	$headings = ($h2+$h3+$h4+$h5+$h6);

	if($headings == 0){
		error:
		$errorcode = __('Missing headings - Post ID: ', 'edac').$post->ID;
		return [$errorcode];
	}
}