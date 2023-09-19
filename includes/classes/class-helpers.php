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
	 * @param [array] $items Array of items to be made into a sql safe comma delimted list.
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
	 * Localizes the format of a number.
	 *
	 * @param [type]  $number
	 * @param integer $decimals number of decimals.
	 * @return integer
	 */
	public static function format_number( $number, $precision = 0 ) {

		if ( ( ! is_numeric( $number ) ) ) {
			return $number;
		}
		
		$locale = get_locale();

		$formatter = new \NumberFormatter( $locale, \NumberFormatter::DECIMAL );
		$formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $precision ); // decimals to include.
		$formatter->setAttribute( \NumberFormatter::GROUPING_USED, 1 ); // Include thousands separator
		
		return $formatter->format( $number );
	}

	public static function percentage($number, $precision = 2){

		if ( ( ! is_numeric( $number ) ) ) {
			return $number;
		}

		if($number > 1){
			$number = $number / 100;
		}

		
		$locale = get_locale();

		$formatter = new \NumberFormatter( $locale, \NumberFormatter::PERCENT );
		$formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $precision ); // decimals to include.
		
		return $formatter->format( $number );
		
	}
}
