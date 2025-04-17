<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Helpers;
use EDAC\Admin\Insert_Rule_Data;
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

/**
 * Show draft posts.
 *
 * This function alters the main query on the front-end to show draft and pending posts when a specific
 * token is present in the URL. This token is stored as an option in the database and is regenerated every time
 * it's used, to prevent unauthorized access to drafts and pending posts.
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 */
function edac_show_draft_posts( $query ) {

	// Do not run if it's not the main query.
	if ( ! $query->is_main_query() ) {
		return;
	}

	// Do not run on admin pages, feeds, REST API or AJAX calls.
	if ( is_admin() || is_feed() || wp_doing_ajax() || ( function_exists( 'rest_doing_request' ) && rest_doing_request() ) ) {
		return;
	}

	// Do not run if the query variable 'edac_cache' is not set.
	// phpcs:ignore WordPress.Security.NonceVerification
	$url_cache = isset( $_GET['edac_cache'] ) ? sanitize_text_field( $_GET['edac_cache'] ) : '';
	if ( ! $url_cache ) {
		return;
	}

	// Retrieve the token from the URL.
	// phpcs:ignore WordPress.Security.NonceVerification
	$url_token = isset( $_GET['edac_token'] ) ? sanitize_text_field( $_GET['edac_token'] ) : false;

	// If the token is not set we do nothing and return early.
	if ( false === $url_token ) {
		return;
	}

	// If the passed token is no longer valid, we do nothing and return early.
	if ( false === edac_is_valid_nonce( 'draft-or-pending-status', $url_token ) ) {
		return;
	}

	// If we've reached this point, alter the query to include 'publish', 'draft', and 'pending' posts.
	$query->set( 'post_status', [ 'publish', 'draft', 'pending' ] );
}
