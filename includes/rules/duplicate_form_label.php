<?php

function edac_rule_duplicate_form_label($content, $post){
    
    // rule vars
    $dom = $content['html'];
    $errors = [];

    $labels = $dom->find('label');
    if (! $labels ) {
        return;
    }
    foreach ($labels as $label){
        $for_attr = $label->getAttribute('for');
        if( sizeof( $dom->find( 'label[for="'.$for_attr.'"]') ) > 1 ) {
            $errors[] = __( 'Duplicate label', 'accessibility_checker') . ' for="' . $for_attr . '"';
        }
    }
    return $errors;
}