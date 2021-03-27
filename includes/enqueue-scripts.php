<?php

/**
 * Enqueue Admin Styles 
 */
function edac_admin_enqueue_styles(){
	wp_enqueue_style( 'edac', plugin_dir_url( __DIR__ ).'assets/css/accessibility-checker-admin.css', array(), EDAC_VERSION, 'all' );
}

/**
 * Enqueue Admin Scripts 
 */
function edac_admin_enqueue_scripts(){

	global $post;
	$post_id = is_object($post) ? $post->ID : null;
	wp_enqueue_script( 'edac', plugin_dir_url( __DIR__ ). 'assets/js/accessibility-checker-admin.js', array( 'jquery' ), EDAC_VERSION, false );

	wp_localize_script(
		'edac',
		'edac_script_vars',
		array(
			'postID' => $post_id,
			'nonce' => wp_create_nonce('ajax-nonce')
		)
	);
}