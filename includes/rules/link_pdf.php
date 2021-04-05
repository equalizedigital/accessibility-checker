<?php

function edac_rule_link_pdf($content, $post){
	
	// rule vars
    $dom = $content['html'];
    $errors = [];

    $as = $dom->find('a');
	foreach ($as as $a){

        if($a->getAttribute('href')){
            if(strpos(strtolower($a), '.pdf')){
                $link_code = $a;

                $errors[] = $link_code;
            }
        }
    }
    return $errors;
}