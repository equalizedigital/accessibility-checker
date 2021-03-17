<?php

function edac_rule_empty_heading_tag($content, $post){
    
    // rule vars
    $dom = $content['html'];
    $errors = [];

    // Loop heading 1 - 6
    for ($i = 1; $i <= 6; $i++){

        $headings = $dom->find('h'.$i);
        foreach ($headings as $heading){

            $heading_code = $heading->outertext;

            if ((str_ireplace(array(' ','&nbsp;','-','_'),'',htmlentities(trim($heading->plaintext))) == "" or str_ireplace(array(' ','&nbsp;','-','_'),'',trim($heading->plaintext))== "")  and !preg_match('#<img(\S|\s)*alt=(\'|\")(\w|\s)(\w|\s|\p{P}|\(|\)|\p{Sm}|~|`|’|\^|\$)+(\'|\")#',$heading_code)){

                $errors[] = $heading_code;
				
            }
        }
    }
    return $errors;
}