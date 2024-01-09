<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Register custom meta boxes
 *
 * @return void
 */
function edac_register_meta_boxes() {
	$post_types = \EDAC\Admin\Options::get( 'post_types' );
	if ( $post_types ) {
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'edac-meta-box',
				__( 'Accessibility Checker', 'accessibility-checker' ),
				'edac_custom_meta_box_cb',
				$post_type,
				'normal',
				'high'
			);
		}
	}
}

/**
 * Render the custom meta box html
 *
 * @return void
 */
function edac_custom_meta_box_cb() {
	include_once plugin_dir_path( __DIR__ ) . 'partials/custom-meta-box.php';
}
