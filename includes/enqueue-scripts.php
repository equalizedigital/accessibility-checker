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

	global $pagenow;
	$post_types        = get_option( 'edac_post_types' );
	$current_post_type = get_post_type();
	$page              = isset( $_GET['page'] ) ? $_GET['page'] : null;
	$pages             = array(
		'accessibility_checker',
		'accessibility_checker_settings',
		'accessibility_checker_issues',
		'accessibility_checker_ignored',
	);

	if ( is_array( $post_types ) && count( $post_types ) && ( in_array( $current_post_type, $post_types, true ) || in_array( $page, $pages, true ) ) || ( $pagenow !== 'site-editor.php' ) ) {

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

}

/**
 * Enqueue Styles
 */
function edac_enqueue_styles() {
	wp_enqueue_style( 'edac-app', plugin_dir_url( __DIR__ ) . 'build/accessibility-checker-app/css/main.css', false, EDAC_VERSION, 'all' );
}

/**
 * Enqueue Scripts
 */
function edac_enqueue_scripts() {
	
	global $post;

	$post_id = is_object( $post ) ? $post->ID : null;

	if( $post_id && current_user_can( 'edit_post', $post_id ) ) {

		wp_enqueue_script( 'edac-app', plugin_dir_url( __DIR__ ) . 'build/accessibility-checker-app/main.bundle.js', false, EDAC_VERSION, false );

		$post_types        = get_option( 'edac_post_types' );
		$current_post_type = get_post_type();
		if ( is_array( $post_types ) && in_array( $current_post_type, $post_types, true ) ) {
			$active = true;
		} else {
			$active = false;
		}

		wp_localize_script(
			'edac-app',
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

}