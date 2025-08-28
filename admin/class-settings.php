<?php
/**
 * Class file for scan settings
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class that handles plugin settings
 */
class Settings {

	/**
	 * Gets a list of post statuses that are scannable.
	 *
	 * @var array
	 */
	public static function get_scannable_post_statuses() {
		/**
		 * Filters the list of post statuses that are scannable.
		 *
		 * @since 1.29.0
		 *
		 * @param array $scannable_post_statuses List of scannable post statuses.
		 */
		return apply_filters(
			'edac_scannable_post_statuses',
			[ 'publish', 'future', 'draft', 'pending', 'private' ]
		);
	}


	/**
	 * Gets a list of post types that are scannable.
	 *
	 * @param bool $skip_filtering Whether to skip filtering when passing through alternative settings class.
	 *
	 * @return array
	 */
	public static function get_scannable_post_types( $skip_filtering = false ) {

		if (
			class_exists( 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings' ) &&
			method_exists( 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings', 'get_scannable_post_types' )
		) {
			return \EqualizeDigital\AccessibilityCheckerPro\Admin\Settings::get_scannable_post_types( $skip_filtering );
		}

		$post_types = Helpers::get_option_as_array( 'edac_post_types' );

		// remove duplicates.
		$post_types = array_unique( $post_types );

		// validate post types.
		$args             = [
			'public'   => true,
			'_builtin' => true,
		];
		$valid_post_types = get_post_types( $args, 'names', 'and' );
		unset( $valid_post_types['attachment'] );

		foreach ( $post_types as $key => $post_type ) {

			if ( ! post_type_exists( $post_type ) || ! array_key_exists( $post_type, $valid_post_types ) ) {
				unset( $post_types[ $key ] );
			}
		}
		return $post_types;
	}


	/**
	 * Gets a count of posts that are scannable.
	 *
	 * @return integer
	 */
	public static function get_scannable_posts_count() {

		global $wpdb;

		$post_types = self::get_scannable_post_types();

		$post_statuses = self::get_scannable_post_statuses();

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
