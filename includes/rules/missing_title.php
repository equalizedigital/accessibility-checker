<?php

/**
 * Missing Title
 *
 * @param $content
 * @param $post
 * @return array
 */
function edac_rule_missing_title($content, $post){
    $title = get_the_title( $post->ID );
    if(!$title || $title == ''){
        return ["Missing Title - Post ID: $post->ID"];
    }
}