<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Purge_Post_Data;

/**
 * Post on save
 *
 * @param int    $post_ID The ID of the post being saved.
 * @param object $post    The post object being saved.
 * @param bool   $update  Whether this is an existing post being updated.
 *
 * @modified 1.10.0 to add a return when post_status is trash.
 *
 * @return void
 */
function edac_save_post( $post_ID, $post, $update ) {
	// check post type.
	$post_types = get_option( 'edac_post_types' );
	if ( is_array( $post_types ) && ! in_array( $post->post_type, $post_types, true ) ) {
		return;
	}

	// prevents first past of save_post due to meta boxes on post editor in gutenberg.
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

	// handle the case when the custom post is quick edited.
	if ( isset( $_POST['_inline_edit'] ) ) {
		$inline_edit = sanitize_text_field( $_POST['_inline_edit'] );
		if ( wp_verify_nonce( $inline_edit, 'inlineeditnonce' ) ) {
			return;
		}
	}

	// Post in, or going to, trash.
	if ( 'trash' === $post->post_status ) {
		// Gutenberg does not fire the `wp_trash_post` action when moving posts to the
		// trash. Instead it uses `rest_delete_{$post_type}` which passes a different shape
		// so instead of hooking in there for every post type supported the data gets
		// purged here instead which produces the same result.
		Purge_Post_Data::delete_post( $post_ID );
		return;
	}
}

/**
 * Remove corrected posts
 *
 * @param int    $post_ID The ID of the post.
 * @param string $type    The type of the post.
 * @param int    $pre     The flag indicating the removal stage (1 for before validation php based rules, 2 for after validation).
 * @param string $ruleset    The type of the ruleset to correct (php or js).
 *
 * @return void
 */
function edac_remove_corrected_posts( $post_ID, $type, $pre = 1, $ruleset = 'php' ) {
	global $wpdb;

	$rules          = edac_register_rules();
	$js_rule_slugs  = [];
	$php_rule_slugs = [];
	// Separate the JS rules and the PHP rules.
	foreach ( $rules as $rule ) {
		if ( isset( $rule['ruleset'] ) && 'js' === $rule['ruleset'] ) {
			$js_rule_slugs[] = $rule['slug'];
		} else {
			$php_rule_slugs[] = $rule['slug'];
		}
	}
	// Operate only on the slugs for the ruleset we are checking in this call.
	$rule_slugs = 'js' === $ruleset ? $js_rule_slugs : $php_rule_slugs;
	if ( 0 === count( $rule_slugs ) ) {
		return;
	}

	if ( 1 === $pre ) {

		// Set record flag before validating content.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
		$wpdb->query(
			$wpdb->prepare(
				sprintf(
					"UPDATE {$wpdb->prefix}accessibility_checker SET recordcheck = %%d WHERE siteid = %%d and postid = %%d and type = %%s AND rule IN (%s)",
					implode( ',', array_fill( 0, count( $rule_slugs ), '%s' ) )
				),
				array_merge(
					[ 0, get_current_blog_id(), $post_ID, $type ],
					$rule_slugs
				)
			)
		);

	} elseif ( 2 === $pre ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
		$wpdb->query(
			$wpdb->prepare(
				sprintf(
					"DELETE FROM {$wpdb->prefix}accessibility_checker WHERE siteid = %%d and postid = %%d and type = %%s and recordcheck = %%d AND rule IN (%s)",
					implode( ',', array_fill( 0, count( $rule_slugs ), '%s' ) )
				),
				array_merge(
					[ get_current_blog_id(), $post_ID, $type, 0 ],
					$rule_slugs
				)
			)
		);
	}
}
