<?php

function edac_rule_broken_aria_reference($content, $post){
    
    // rule vars
    $dom = $content['html'];
    $errors = [];
    $labelledby_elements = $dom->find( '[aria-labelledby]' );

    foreach( $labelledby_elements as $labelledby_element ){
      if( ! edac_has_all_referenced_elements( $labelledby_element, $dom, 'aria-labelledby' ) ) {
        $errors[] = $labelledby_element;
      }  
    } 
    
    $describedby_elements = $dom->find( '[aria-describedby]' );
    foreach( $describedby_elements as $describedby_element ){
      if( ! edac_has_all_referenced_elements( $describedby_element, $dom, 'aria-describedby' ) ) {
        $errors[] = $describedby_element;
      }
       
    }  
    return $errors;
}

function edac_has_all_referenced_elements( $element, $dom, $attr = 'aria-labelledby' ) {
    $label_string = $element->getAttribute($attr);
    $labels = explode( ' ', $label_string );
    foreach( $labels as $label ) {
        if( ! $dom->find( '#' . $label, 0 ) ) {
            return false;
        }
    }
    return true;
}