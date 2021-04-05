<?php

function edac_rule_link_non_html_file($content, $post){
	
	// rule vars
    $dom = $content['html'];
    $file_extensions = ['.rtf', '.wpd', '.ods', '.odt', '.odp', '.sxw', '.sxc', '.sxd', '.sxi', '.pages', '.key'];
    $errors = [];

    $as = $dom->find('a');
	foreach ($as as $a){

        if($a->getAttribute('href')){
            if($file_extensions){
                foreach($file_extensions as $file_extension){
                    if(strpos(strtolower($a), $file_extension)){
                        $link_code = $a;

                        $errors[] = $link_code;
                    }
                }
            }
        }
    }
    return $errors;
}