<?php
/**
 * Functions that have been deprecated and should not be used.
 * They are still kept here for backwards-compatibility.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Insert_Rule_Data;
use EDAC\Admin\Purge_Post_Data;
use EDAC\Admin\Post_Save;

// Deprecated constants.
if ( ! defined( 'EDAC_ANWW_ACTIVE' ) ) {
	/**
	 * Indicates whether the Accessibility New Window Warnings (ANWW) plugin is active.
	 *
	 * This constant is provided for backward compatibility, as it might have been
	 * expected by other plugins or older versions for checking ANWW plugin activity.
	 * It evaluates to `true` if `ANWW_VERSION` is defined, and `false` otherwise.
	 *
	 * @since 1.24.0 Introduced for backward compatibility and immediately deprecated.
	 * @deprecated 1.24.0 Please use `defined( 'ANWW_VERSION' )` directly to check ANWW plugin status.
	 */
	define( 'EDAC_ANWW_ACTIVE', defined( 'ANWW_VERSION' ) );
}

/**
 * Alias of the is_plugin_active() function.
 *
 * @deprecated 1.6.11
 *
 * @param string $plugin_slug The plugin slug.
 * @return bool
 */
function edac_check_plugin_active( $plugin_slug ) {
	_deprecated_function( __FUNCTION__, '1.6.11', 'is_plugin_active()' );
	return is_plugin_active( $plugin_slug );
}

/**
 * Summary Data
 *
 * @deprecated 1.9.0
 *
 * @param int $post_id ID of the post.
 * @return array
 */
function edac_summary( $post_id ) {
	_deprecated_function( __FUNCTION__, '1.9.0', 'EDAC\Inc\Summary_Generator' );

	return ( new EDAC\Inc\Summary_Generator( $post_id ) )->generate_summary();
}

/**
 * Purge deleted posts
 *
 * @deprecated 2.0.0
 *
 * @param int $post_id ID of the post.
 * @return void
 */
function edac_delete_post( $post_id ) {
	_deprecated_function( __FUNCTION__, '1.10.0', 'EDAC\Admin\Purge_Post_Data::delete_post' );
	Purge_Post_Data::delete_post( $post_id );
}

/**
 * Delete post meta
 *
 * @deprecated 1.10.0
 *
 * @param int $post_id ID of the post.
 * @return void
 */
function edac_delete_post_meta( $post_id ) {
	_deprecated_function( __FUNCTION__, '1.10.0', 'EDAC\Admin\Purge_Post_Data::delete_post_meta' );
	Purge_Post_Data::delete_post_meta( $post_id );
}

/**
 * Purge issues by post type
 *
 * @deprecated 1.10.0
 *
 * @param string $post_type Post Type.
 * @return void
 */
function edac_delete_cpt_posts( $post_type ) {
	_deprecated_function( __FUNCTION__, '1.10.0', 'EDAC\Admin\Purge_Post_Data::delete_cpt_posts' );
	Purge_Post_Data::delete_cpt_posts( $post_type );
}

/**
 * Register custom meta boxes
 *
 * @deprecated 1.10.0
 *
 * @return void
 */
function edac_register_meta_boxes() {
	_deprecated_function( __FUNCTION__, '1.10.0', 'EDAC\Admin\Meta_Boxes::register_meta_boxes' );
	( new EDAC\Admin\Meta_Boxes() )->register_meta_boxes();
}

/**
 * Render the custom meta box html
 *
 * @deprecated 1.10.0
 *
 * @return void
 */
function edac_custom_meta_box_cb() {
	_deprecated_function( __FUNCTION__, '1.10.0', 'EDAC\Admin\Meta_Boxes::render' );
	( new EDAC\Admin\Meta_Boxes() )->render();
}

/**
 * Insert rule date into database
 *
 * @deprecated 1.10.0
 *
 * @param object $post     The post object.
 * @param string $rule     The rule.
 * @param string $ruletype The rule type.
 * @param string $rule_obj The object.
 * @return void|int
 */
function edac_insert_rule_data( $post, $rule, $ruletype, $rule_obj ) {
	_deprecated_function( __FUNCTION__, '1.10.0', 'EDAC\Admin\Insert_Rule_Data' );
	return ( new Insert_Rule_Data() )->insert( $post, $rule, $ruletype, $rule_obj );
}

/**
 * Post save handler
 *
 * @deprecated 1.23.0
 *
 * @param int    $post_ID The ID of the post being saved.
 * @param object $post    The post object being saved.
 * @param bool   $update  Whether this is an existing post being updated.
 * @return int   The post ID.
 */
function edac_save_post( $post_ID, $post, $update ) {
	_deprecated_function( __FUNCTION__, '1.23.0', 'EDAC\Admin\Post_Save::save_post' );
	Post_Save::delete_issue_data_on_post_trashing( $post_ID, $post, $update );
	return $post_ID;
}

/**
 * Validate post content
 *
 * @deprecated 1.23.0 This functionality has been removed without replacement
 *
 * @param int    $post_ID The ID of the post.
 * @param object $post    The post object.
 * @param string $action  The action being performed.
 * @return void
 */
function edac_validate( $post_ID, $post, $action ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Parameters kept for backwards compatibility
	_deprecated_function( __FUNCTION__, '1.23.0', 'This functionality has been removed' );
}
