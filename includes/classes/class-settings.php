<?php
/**
 * Class file for scan settings
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Helpers;


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
		return array( 'publish', 'future', 'draft', 'pending', 'private' );
	} 


	/**
	 * Gets a list of post types that are scannable.
	 *
	 * @return array
	 */
	public static function get_scannable_post_types() {

	
		if ( ! class_exists( '\EDACP\Settings' ) ) {
			$post_types = get_option( 'edac_post_types' );
		
			if ( ! is_array( $post_types ) ) {
				$post_types = array( $post_types );
			}
		
			// remove duplicates.
			$post_types = array_unique( $post_types );
			
			$args             = array(
				'public'   => true,
				'_builtin' => true,
			);
			$valid_post_types = get_post_types( $args, 'names', 'and' );
			unset( $valid_post_types['attachment'] );

			// validate post types.
			foreach ( $post_types as $key => $post_type ) {

				if ( ! post_type_exists( $post_type ) || ! array_key_exists( $post_type, $valid_post_types ) ) {
					unset( $post_types[ $key ] );
				}
			}       
		} else {
			$post_types = \EDACP\Settings::get_scannable_post_types();
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

		if ( $post_types && $post_statuses ) {
			$sql = "SELECT COUNT(id) FROM {$wpdb->posts}  WHERE post_type IN(" . 
				Helpers::array_to_sql_safe_list( $post_types ) . ') and post_status IN(' .
				Helpers::array_to_sql_safe_list( $post_statuses ) . 
			')';
	
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$scannable_posts_count = $wpdb->get_var( $sql );

		} else {
			$scannable_posts_count = 0;
		}
		return $scannable_posts_count;
	}
}
