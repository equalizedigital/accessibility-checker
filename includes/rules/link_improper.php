<?php

/**
 * Improper Use of Link
 * 
 * Find all of the links on the page that contain only a # in the href attribute or are missing an href attribute completely. If these links do not have role="button" then flag an Improper Use of Link error. Though it would be better if it were an actual button element, adding role="menuitem" and aria-haspopup="true" to a link is sufficient for screen reader users also.
 * 
 * <a>improper</a>
 * <a role="button">improper</a>
 * <a href="#">improper</a>
 * <a href="#" role="menuitem">improper</a>
 * <a href="#" aria-haspopup="true">improper</a>
 * <a href="#" role="menuitem" aria-haspopup="true">proper</a>
 * <a href="#" role="button">proper</a>
 *
 * @param array $content
 * @param array $post
 * @return void
 */
function edac_rule_link_improper($content, $post){
	$dom = $content['html'];
	$errors = [];
	$links = $dom->find('a');
	foreach ($links as $link) {
		$error = false;
		if(!$link->hasAttribute('href')){
			$error = true;
		}elseif(trim($link->getAttribute('href')) == '#'){
			$error = true;
			if($link->getAttribute('role') == "button" || ($link->getAttribute('role') == 'menuitem' && $link->getAttribute('aria-haspopup') == "true")){
				$error = false;
			}
		}
		if($error){
			$a_tag_code = $link->outertext;
            $errors[] = $a_tag_code;
		}
	}
	return $errors;
} 