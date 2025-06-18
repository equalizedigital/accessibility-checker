<?php
/**
 * Purge Post Data stored in the database that holds scan data about the posts.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Uses sql queries to get and purge post data from the database for given post
 * ids or for custom posts by post_type string.
 *
 * @since 1.10.0
 */
class Purge_Post_Data {

	/**
	 * Purge deleted posts
	 *
	 * @since 1.10.0
	 *
	 * @param int $post_id ID of the post.
	 *
	 * @return void
	 */
	public static function delete_post( int $post_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE postid = %d and siteid = %d',
				edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' ),
				$post_id,
				get_current_blog_id()
			)
		);

		self::delete_post_meta( $post_id );
	}

	/**
	 * Delete post meta
	 *
	 * @since 1.10.0
	 *
	 * @param int $post_id ID of the post.
	 *
	 * @return void
	 */
	public static function delete_post_meta( int $post_id ) {

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
	 * @since 1.10.0
	 *
	 * @param string $post_type Post Type.
	 *
	 * @return bool|int|\mysqli_result|void
	 */
	public static function delete_cpt_posts( string $post_type ) {

		if ( ! $post_type || ! post_type_exists( $post_type ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE T1,T2 from $wpdb->postmeta as T1 JOIN %i as T2 ON T1.post_id = T2.postid WHERE T1.meta_key like %s and T2.siteid=%d and T2.type=%s",
				edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' ),
				'_edac%',
				get_current_blog_id(),
				$post_type
			)
		);
	}
}
