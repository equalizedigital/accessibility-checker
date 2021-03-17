<?php

function edac_rule_empty_form_label($content, $post){
    
    // rule vars
    $dom = $content['html'];
    $labels = $dom->find('label');
    $errors = [];

    foreach ($labels as $label){
      
     
       $label_text = str_ireplace( ['*', __('required', 'edac') ],'', $label->plaintext );
        if ( empty( preg_replace( '/\s+/', '', $label_text ) ) ) {
            $errors[] = $label->outertext;
        }
     
    }
    return $errors;
}