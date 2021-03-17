<?php

function edac_rule_missing_table_header($content, $post){
    
    // rule vars
    $dom = $content['html'];
    $errors = [];

    $tables = $dom->find('table');
    if (! $tables ) {
        return;
    }
    foreach ($tables as $table){

        if( ! ac_th_match_td( $table ) ) {
            $errors[] = $table;
        }
        
    }
    return $errors;
}

function ac_th_match_td( $table ) {
    $table_rows = $table->find( 'tr' );
    $header_count = 0;
    $max_rows = 0;
    foreach( $table_rows as $table_row ){
        if ( $header_count == 0 ) {
            $header_count = count( $table_row->find( 'th' ) );
        }
        $max_rows = max( $max_rows, count( $table_row->find( 'td' ) ) ); 
    }
    return $max_rows <= $header_count;
}