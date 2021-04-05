<?php

function edac_rule_missing_form_label($content, $post){
    
    // rule vars
    $dom = $content['html'];
    
    $labels = $dom->find('label');

    $fields = $dom->find('input');
    $ignore_types = [ 'submit', 'hidden', 'button', 'reset' ];
    $errors = [];
    
    foreach( $fields as $field ) {
        if ( in_array( $field->getAttribute('type'), $ignore_types ) ) {
            continue;
        }
        if ( ! ac_input_has_label($field, $dom) ) {
            $errors[] = $field->outertext;
        }
    }
    return $errors;
}

function ac_input_has_label($field, $dom){
    if ( $field->getAttribute( 'aria-labelledby' ) ) {
        return true;
    } elseif ($field->getAttribute( 'aria-label' ) ) {
        return true;
    } elseif( $dom->find( 'label[for="'.$field->getAttribute('id').'"]', -1) != '' ) {
        return true;
    } else {
       return ac_field_has_label_parent( $field );
    }
    return false;
}

function ac_field_has_label_parent( $field ) {
    if ( $field == NULL ) {
        return false;
    }
    $parent = $field->parent();
    if ( $parent == NULL ) {
        return false;
    }
    
    $tag = $parent->tag;
    if ( $tag == 'label' ) {
        return true;
    } elseif ( $tag == 'form' || $tag == 'body' ) {
        return false;
    }
    $parent = $field->parent();
    return ac_field_has_label_parent( $parent );
}
