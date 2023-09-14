<?php
/**
 * Class file for helpers
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

/**
 * Class that holds helpers
 */
class Helpers {

	/**
	 * Gets a sql prepared/safe list of items from an array.
	 * Needed b/c wpdb->prepare breaks quotes for IN.
	 *
	 * @param [array] $items
	 * @return string
	 */
	public static function array_to_sql_safe_list( $items ) {

		$items = array_map(
			function ( $item ) {
				global $wpdb;

				return $wpdb->prepare( '%s', $item );
			},
			$items
		);

		return implode( ',', $items );
	}

}
