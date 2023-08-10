<?php
/**
 * Class file for scan settings
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Helpers;


/**
 * Class that handles scan settings
 */
class Settings {

	/**
	 * Gets a list of post statuses that are scannable.
	 *
	 * @var array
	 */
	public static function get_scannable_post_statuses(){
		return array( 'publish', 'future', 'draft', 'pending', 'private' );
	} 


	/**
	 * Gets a list of post types that are scannable.
	 *
	 * @return void
	 */
	public static function get_scannable_post_types() {

	
		if ( ! class_exists( '\EDACP\Settings' ) ) {
			$post_types = get_option( 'edac_post_types' );

			// remove duplicates.
			$post_types = array_unique( $post_types );


			// validate post types.
			foreach ( $post_types as $key => $post_type ) {
				if ( ! post_type_exists( $post_type ) ) {
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
	public static function get_scannable_posts_count(){

		global $wpdb;

		$post_types = self::get_scannable_post_types();
		
		$post_statuses = self::get_scannable_post_statuses();

		$scannable_posts_count  = $wpdb->get_var( 
			"SELECT COUNT(id) FROM {$wpdb->posts}  WHERE 
			post_type IN(" . Helpers::array_to_sql_safe_list( $post_types ) . ') and
			post_status IN(' . Helpers::array_to_sql_safe_list( $post_statuses ) . ')'
		);

		return $scannable_posts_count;
	}
}
