<?php

function edac_rule_empty_table_header($content, $post){
    
    // rule vars
    $dom = $content['html'];
    $errors = [];

    $table_headings = $dom->find('th');
    foreach ($table_headings as $table_heading){
        $th_code = $table_heading->plaintext;
        if ( empty( preg_replace( '/\s+/', '', $th_code ) ) ) {
            $errors[] = $table_heading->outertext;
        }
     
    }
    return $errors;
}