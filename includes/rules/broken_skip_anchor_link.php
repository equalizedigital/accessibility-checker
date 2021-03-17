<?php

function edac_rule_broken_skip_anchor_link($content, $post){

    $dom = $content['html'];
    $errors = [];
    $anchor_elements = $dom->find( 'a' );

    foreach( $anchor_elements as $anchor_element ){
        $href = $anchor_element->getAttribute('href');
        
        if( substr( $href, 0, 1 ) == '#' ) {
            if( ! $dom->find( $href, 0 ) ) {
                $errors[] = $anchor_element;
            }
        }
    } 
     
    return $errors;
}