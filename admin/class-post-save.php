<?php
/**
 * Class for handling the post save actions.
 *
 * @since 1.23.0
 * 
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\Purge_Post_Data;

/**
 * Class Post_Save
 *
 * Handles actions and filters related to saving posts in the accessibility checker.
 *
 * @package EDAC\Admin
 * @since 1.23.0
 */
class Post_Save {
	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_filter( 'save_post', [ $this, 'save_post' ], 10, 3 );
	}

	/**
	 * Post on save
	 *
	 * @param int    $post_ID The ID of the post being saved.
	 * @param object $post    The post object being saved.
	 * @param bool   $update  Whether this is an existing post being updated.
	 *
	 * @modified 1.10.0 to add a return when post_status is trash.
	 *
	 * @return int The post ID.
	 */
	public function save_post( $post_ID, $post, $update ) {
		// check post type.
		$post_types = get_option( 'edac_post_types' );
		if ( is_array( $post_types ) && ! in_array( $post->post_type, $post_types, true ) ) {
			return $post_ID;
		}

		// prevents first past of save_post due to meta boxes on post editor in gutenberg.
		if ( empty( $_POST ) ) {
			return $post_ID;
		}

		// ignore revisions.
		if ( wp_is_post_revision( $post_ID ) ) {
			return $post_ID;
		}

		// ignore autosaves.
		if ( wp_is_post_autosave( $post_ID ) ) {
			return $post_ID;
		}

		// check if update.
		if ( ! $update ) {
			return $post_ID;
		}

		// handle the case when the custom post is quick edited.
		if ( isset( $_POST['_inline_edit'] ) ) {
			$inline_edit = sanitize_text_field( $_POST['_inline_edit'] );
			if ( wp_verify_nonce( $inline_edit, 'inlineeditnonce' ) ) {
				return $post_ID;
			}
		}

		// Post in, or going to, trash.
		if ( 'trash' === $post->post_status ) {
			// Gutenberg does not fire the `wp_trash_post` action when moving posts to the
			// trash. Instead it uses `rest_delete_{$post_type}` which passes a different shape
			// so instead of hooking in there for every post type supported the data gets
			// purged here instead which produces the same result.
			Purge_Post_Data::delete_post( $post_ID );
			return $post_ID;
		}

		return $post_ID;
	}
}
