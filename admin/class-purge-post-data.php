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

		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );

		/**
		 * Fires before deleting posts of a specific post type.
		 *
		 * @since 1.31.0
		 *
		 * @param string $post_type Post Type.
		 */
		do_action( 'edac_before_delete_cpt_posts', $post_type );

		// Get post IDs from the accessibility checker table that match the criteria.
		// Multi-table DELETE with JOIN is not supported in SQLite, so we use two separate queries.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT postid FROM %i WHERE siteid=%d AND type=%s',
				$table_name,
				get_current_blog_id(),
				$post_type
			)
		);

		if ( ! empty( $post_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
			// Delete the accessibility plugin's postmeta records for these posts.
			// $placeholders contains only '%d' values from array_fill() and is safe to interpolate.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Safe variable used for table name, caching not required for one time operation.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND post_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is built from array_fill() with '%d' values only.
					array_merge( [ '_edac%' ], $post_ids )
				)
			);
		}

		// Delete accessibility checker records for this post type.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
		return $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE siteid=%d AND type=%s',
				$table_name,
				get_current_blog_id(),
				$post_type
			)
		);
	}
}
