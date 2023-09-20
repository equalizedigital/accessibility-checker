<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Purge deleted posts
 *
 * @param init $post_id ID of the post.
 * @return void
 */
function edac_delete_post( $post_id ) {
	global $wpdb;
	$site_id = get_current_blog_id();

	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'accessibility_checker WHERE postid = %d and siteid = %d', $post_id, $site_id ) );

	edac_delete_post_meta( $post_id );
}

/**
 * Delete post meta
 *
 * @param int $post_id ID of the post.
 * @return void
 */
function edac_delete_post_meta( $post_id ) {

	if ( ! $post_id ) {
		return;
	}

	$post_meta = get_post_meta( $post_id );
	if ( $post_meta ) {
		foreach ( $post_meta as $key => $value ) {
			if ( substr( $key, 0, 5 ) === '_edac' || substr( $key, 0, 6 ) === '_edacp' ) {
				delete_post_meta( $post_id, $key );
			}
		}
	}
}

/**
 * Purge issues by post type
 *
 * @param string $post_type Post Type.
 * @return void
 */
function edac_delete_cpt_posts( $post_type ) {

	if ( ! $post_type ) {
		return;
	}

	global $wpdb;
	$site_id = get_current_blog_id();

	$postmeta_table = $wpdb->postmeta;
	$ac_table       = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );

	$sql = $wpdb->prepare(
		"DELETE T1,T2 from {$postmeta_table} as T1 
		JOIN {$ac_table} as T2 ON t1.post_id = T2.postid
		WHERE t1.meta_key like %s and T2.siteid=%d and T2.type=%s",
		array(
			'_edac%',
			$site_id,
			$post_type,
		)
	);

	//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $sql );
}
