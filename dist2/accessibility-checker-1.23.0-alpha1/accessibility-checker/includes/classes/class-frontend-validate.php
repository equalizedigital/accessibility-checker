<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * A class that handles the validation of the page on the frontend.
 *
 * @since 1.9.0
 */
class Frontend_Validate {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.9.0
	 */
	public function init_hooks() {
		add_action( 'template_redirect', [ $this, 'validate' ] );
	}

	/**
	 * Validates the current post on the WordPress dashboard, under specific conditions.
	 *
	 * This function is triggered only by the `template_redirect` hook. It returns early if not 'index.php', in a
	 * customizer preview, the post is an 'auto-draft' or if the current user does not have permissions to edit posts.
	 * It checks if the current post has been previously validated based on a specific post meta key
	 * ('_edac_post_checked'). If the post has not been validated, it initiates the validation process.
	 *
	 * @modified 1.10.0 Return early if post is an auto-draft.
	 *
	 * @return void The function does not return a value. It triggers validation for an unvalidated post or does nothing.
	 * @since 1.9.0
	 */
	public function validate() {

		global $pagenow;

		if ( 'index.php' === $pagenow && false === is_customize_preview() && current_user_can( 'edit_posts' ) ) {

			global $post;
			$post_id = is_object( $post ) ? $post->ID : null;
			if ( null === $post_id || 'auto-draft' === $post->post_status ) {
				return;
			}

			$checked = get_post_meta( $post->ID, '_edac_post_checked', true );
			if ( ! $checked ) {
				edac_validate( $post->ID, $post, $action = 'load' );
			}
		}
	}
}
