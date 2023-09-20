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
	 * @param array $items Array of items to be made into a sql safe comma delimted list.
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

	/**
	 * Given an WP option that may contain a string or an array, returns it as an array.
	 *
	 * @param string $option_name name of the option to return.
	 * @return array
	 */
	public static function get_option_as_array( $option_name ) {

		$option = get_option( $option_name );

		if ( is_array( $option ) && ! empty( $option ) ) {
			return $option;
		} else {
			return array();
		}

		if ( is_string( $option ) ) {
			return array( $option );
		}

		return array();
	}
}
