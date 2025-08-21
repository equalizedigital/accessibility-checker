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
	 * Post on save
	 *
	 * @param int    $post_ID The ID of the post being saved.
	 * @param object $post    The post object being saved.
	 * @param bool   $update  Whether this is an update or not.
	 *
	 * @modified 1.10.0 to add a return when post_status is trash.
	 * @modified 1.23.0 moved to new class and then swapped to void return as no longer reading the return values anywhere.
	 *
	 * @return void The post ID.
	 */
	public static function delete_issue_data_on_post_trashing( $post_ID, $post, $update ) {
		// check post type.
		$post_types = Settings::get_scannable_post_types();
		if ( is_array( $post_types ) && ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		// prevents first past of save_post due to meta boxes on post editor in gutenberg.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is used to detect automated saves vs user-initiated saves, not processing form data
		if ( empty( $_POST ) ) {
			return;
		}

		// ignore revisions.
		if ( wp_is_post_revision( $post_ID ) ) {
			return;
		}

		// ignore autosaves.
		if ( wp_is_post_autosave( $post_ID ) ) {
			return;
		}

		// check if update.
		if ( ! $update ) {
			return;
		}

		// Post in, or going to, trash.
		if ( 'trash' === $post->post_status ) {
			// Gutenberg does not fire the `wp_trash_post` action when moving posts to the
			// trash. Instead, it uses `rest_delete_{$post_type}` which passes a different shape
			// Instead of hooking in there for every post type supported the data gets purged
			// here instead which produces the same result.
			Purge_Post_Data::delete_post( $post_ID );
		}
	}
}
