<?php
/**
 * Class file for dealing with scannable posts
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Options;
use EDAC\Admin\Helpers;

/**
 * Class tha deals with scannable posts
 */
class Scannable_Posts {

	/**
	 * A list of post statuses that are scannable.
	 *
	 * @return array
	 */
	const ALLOWED_STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Get a list of post types that are scannable.
	 *
	 * @return array
	 */
	public static function get_allowed_types() {

		if ( ! class_exists( '\EDACP\Settings' ) ) {

			// Not pro.
	
			$post_types = Options::get( 'post_types' );

			// remove duplicates.
			$post_types = array_unique( $post_types );

			// validate post types.
			$args             = array(
				'public'   => true,
				'_builtin' => true,
			);
			$valid_post_types = get_post_types( $args, 'names', 'and' );
			unset( $valid_post_types['attachment'] );

			foreach ( $post_types as $key => $post_type ) {

				if ( ! post_type_exists( $post_type ) || ! array_key_exists( $post_type, $valid_post_types ) ) {
					unset( $post_types[ $key ] );
				}
			}
			return $post_types;
		}

		// Pro.
		return \EDACP\Settings::get_scannable_post_types();
	}


	/**
	 * Gets a count of posts that are scannable.
	 *
	 * @return integer
	 */
	public static function get_count() {

		global $wpdb;

		$post_types = self::get_allowed_types();

		$post_statuses = self::ALLOWED_STATUSES;

		//phpcs:ignore Generic.Commenting.Todo.TaskFound
		// TODO: can we replace array_to_sql_safe_list?
		if ( ! empty( $post_types ) && ! empty( $post_statuses ) ) {
			$sql = "SELECT COUNT(id) FROM {$wpdb->posts}  WHERE post_type IN(" .
				Helpers::array_to_sql_safe_list( $post_types ) . ') and post_status IN(' .
				Helpers::array_to_sql_safe_list( $post_statuses ) .
			')';

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_var( $sql );
		}
		return 0;
	}
}
