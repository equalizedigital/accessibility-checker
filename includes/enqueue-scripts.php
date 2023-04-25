<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Enqueue Admin Styles
 */
function edac_admin_enqueue_styles() {
	wp_enqueue_style( 'edac', plugin_dir_url( __DIR__ ) . 'assets/css/accessibility-checker-admin.css', array(), EDAC_VERSION, 'all' );
}

/**
 * Enqueue Admin Scripts
 */
function edac_admin_enqueue_scripts() {

	global $post;
	$post_id = is_object( $post ) ? $post->ID : null;
	wp_enqueue_script( 'edac', plugin_dir_url( __DIR__ ) . 'assets/js/accessibility-checker-admin-min.js', array( 'jquery' ), EDAC_VERSION, false );

	wp_localize_script(
		'edac',
		'edac_script_vars',
		array(
			'postID' => $post_id,
			'nonce'  => wp_create_nonce( 'ajax-nonce' ),
		)
	);
}

/**
 * Enqueue Styles
 */
function edac_enqueue_styles() {
	wp_enqueue_style( 'edac', plugin_dir_url( __DIR__ ) . 'assets/css/accessibility-checker.css', array(), EDAC_VERSION, 'all' );
}

/**
 * Enqueue Scripts
 */
function edac_enqueue_scripts() {

	global $post;
	$post_id = is_object( $post ) ? $post->ID : null;
	wp_enqueue_script( 'edac', plugin_dir_url( __DIR__ ) . 'assets/js/accessibility-checker-min.js', array( 'jquery' ), EDAC_VERSION, false );

	$post_types        = get_option( 'edac_post_types' );
	$current_post_type = get_post_type();
	if ( in_array( $current_post_type, $post_types, true ) ) {
		$active = true;
	} else {
		$active = false;
	}

	wp_localize_script(
		'edac',
		'edac_script_vars',
		array(
			'postID'   => $post_id,
			'nonce'    => wp_create_nonce( 'ajax-nonce' ),
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'loggedIn' => is_user_logged_in(),
			'active'   => $active,
		)
	);

}