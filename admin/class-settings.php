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
		return [ 'publish', 'future', 'draft', 'pending', 'private' ];
	}


	/**
	 * Gets a list of post types that are scannable.
	 *
	 * @return array
	 */
	public static function get_scannable_post_types() {

		// Check if the new settings class exists. This is added to allow for backwards compatibility
		// with the old settings class. The old settings class check should be removed after a few releases.
		$new_settings_class_exists = class_exists( 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings' );
		if ( ! class_exists( '\EDACP\Settings' ) && ! $new_settings_class_exists ) {

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

		return $new_settings_class_exists
			? \EqualizeDigital\AccessibilityCheckerPro\Admin\Settings::get_scannable_post_types()
			: \EDACP\Settings::get_scannable_post_types();
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
