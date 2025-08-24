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
		$ac_table        = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$postmeta_table  = $wpdb->postmeta;
		$site_id         = get_current_blog_id();
		$meta_key_like   = '_edac%';

		if ( ! $ac_table ) {
			return false;
		}

		// Step 1: Find all post IDs of the given post_type that have associated records in the ac_table
		// and also have the specific meta_key in postmeta.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$sql_select_post_ids = $wpdb->prepare(
			'SELECT DISTINCT T2.postid
			FROM %i AS T1
			JOIN %i AS T2 ON T1.post_id = T2.postid
			WHERE T1.meta_key LIKE %s
			AND T2.siteid = %d
			AND T2.type = %s',
			$postmeta_table, // T1.
			$ac_table,       // T2.
			$meta_key_like,
			$site_id,
			$post_type
		);

		$post_ids_to_affect = $wpdb->get_col( $sql_select_post_ids );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $post_ids_to_affect ) ) {
			return true; // No records match the criteria.
		}

		// Sanitize all post IDs to ensure they are integers.
		$post_ids_to_affect = array_map( 'absint', $post_ids_to_affect );

		$post_ids_placeholders = implode( ', ', array_fill( 0, count( $post_ids_to_affect ), '%d' ) );

		// Step 2: Delete from postmeta table.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query_args_postmeta = array_merge( [ $meta_key_like ], $post_ids_to_affect );
		$sql_delete_postmeta = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"DELETE FROM {$postmeta_table} WHERE meta_key LIKE %s AND post_id IN ( {$post_ids_placeholders} )",
			$query_args_postmeta
		);
		$wpdb->query( $sql_delete_postmeta );

		// Step 3: Delete from accessibility_checker table.
		$query_args_ac = array_merge( [ $site_id, $post_type ], $post_ids_to_affect );
		$sql_delete_ac_data = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"DELETE FROM {$ac_table} WHERE siteid = %d AND type = %s AND postid IN ( {$post_ids_placeholders} )",
			$query_args_ac
		);
		$wpdb->query( $sql_delete_ac_data );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return true;
	}
}
