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
	 * Initialize the hooks for the editor meta box.
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
	}

	/**
	 * Register custom meta boxes for each post type.
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	public function register_meta_boxes(): void {
		$post_types = Settings::get_scannable_post_types();
		if ( $post_types ) {
			foreach ( $post_types as $post_type ) {
				add_meta_box(
					'edac-meta-box',
					__( 'Accessibility Checker', 'accessibility-checker' ),
					[ $this, 'render' ],
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
		/**
		 * Fires before the meta box is rendered.
		 *
		 * @since 1.10.0
		 */
		do_action( 'edac_before_meta_box' );
		include_once plugin_dir_path( __DIR__ ) . 'partials/custom-meta-box.php';
		/**
		 * Fires after the meta box is rendered.
		 *
		 * @since 1.10.0
		 */
		do_action( 'edac_after_meta_box' );
	}
}
