<?php
/**
 * Class for handling the editor meta box.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class Meta_Box
 *
 * Handles the editor meta box registration and rendering.
 */
class Meta_Boxes {

	/**
	 * Register custom meta boxes for each post type.
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	public function register_meta_boxes(): void {
		$post_types = get_option( 'edac_post_types' );
		if ( $post_types ) {
			foreach ( $post_types as $post_type ) {
				add_meta_box(
					'edac-meta-box',
					__( 'Accessibility Checker', 'accessibility-checker' ),
					array( $this, 'render' ),
					$post_type,
					'normal',
					'high'
				);
			}
		}
	}

	/**
	 * Render the custom meta box html.
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	public function render(): void {
		include_once plugin_dir_path( __DIR__ ) . 'partials/custom-meta-box.php';
	}
}
