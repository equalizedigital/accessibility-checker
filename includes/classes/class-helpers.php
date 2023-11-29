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
	 * Localizes the format of a number.
	 *
	 * @param [type]  $number
	 * @param integer $precision number of decimals.
	 * @return integer
	 */
	public static function format_number( $number, $precision = 0 ) {

		if ( ( ! is_numeric( $number ) ) ) {
			return $number;
		}
		
		$locale = get_locale();

		if ( class_exists( 'NumberFormatter' ) ) {
			$formatter = new \NumberFormatter( $locale, \NumberFormatter::DECIMAL );
			$formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $precision ); // decimals to include.
			$formatter->setAttribute( \NumberFormatter::GROUPING_USED, 1 ); // Include thousands separator.
		
			return $formatter->format( $number );

		} else {
			return number_format( $number );
		}
	}

	/**
	 * Localizes the format of a percentage.
	 *
	 * @param [type]  $number
	 * @param integer $precision number of decimals.
	 * @return integer
	 */
	public static function format_percentage( $number, $precision = 2 ) {

		if ( ( ! is_numeric( $number ) ) ) {
			return $number;
		}

		if ( $number > 1 ) {
			$number = $number / 100;
		}

		
		$locale = get_locale();

		if ( class_exists( 'NumberFormatter' ) ) {
	
			$formatter = new \NumberFormatter( $locale, \NumberFormatter::PERCENT );
			$formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $precision ); // decimals to include.
			
			return $formatter->format( $number );
		
		} else {
			return sprintf( '%.2f%%', $number * 100 );
		}
	}

	/**
	 * Localizes the format of a date.
	 *
	 * @param [type]  $number
	 * @param integer $precision number of decimals.
	 * @return integer
	 */
	public static function format_date( $date, $include_time = false ) {

		if ( ! is_numeric( $date ) ) { // date as string.
			
			$timestamp = strtotime( $date ); 

			if ( ! $timestamp ) { // The passed string is not a valid date.
				return $date;
			}       
		} else { // unix timestamp.
			$timestamp = $date; 
		}

		$datetime = new \DateTime();
		$datetime->setTimestamp( $timestamp );
		$datetime->setTimezone( wp_timezone() );

		if ( ! $include_time ) {
			$format = get_option( 'date_format' );
		} else {
			$format = get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' );
		}

		if ( ! $format ) {
			if ( $include_time ) {
				$format = 'j M Y \a\t g:i a';
			} else {
				$format = 'j M Y';
			}
		}

		$formatted_date = $datetime->format( $format );

		return $formatted_date;
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


	/**
	 * Determine if a domain is hosted on a local loopback
	 *
	 * @param string $domain The domain to check.
	 * @return boolean
	 */
	public static function is_domain_loopback( $domain ) {
	
		// Check if this is an ipv4 address in the loopback range.  
	
		$record         = gethostbyname( $domain );
		$loopback_start = ip2long( '127.0.0.0' );
		$loopback_end   = ip2long( '127.255.255.255' );
		$ip_long        = ip2long( $record );
	
		if ( $ip_long >= $loopback_start && $ip_long <= $loopback_end ) {
			return true;
		}       

			
		// Check if this is an ipv6 loopback.
	
		try {
			$records = dns_get_record( $domain, DNS_AAAA );
		} catch ( \Throwable $th ) {
			return false;           
		}
			
		foreach ( $records as $record ) {
		
			// Do ipv6 check.
			if ( isset( $record['type'] ) && 'AAAA' === $record['type'] ) {
			
				// Normalize the IPv6 address for comparison.
				$normalized_ipv6 = inet_pton( $record['ipv6'] );

				// Normalize the loopback address.
				$loopback_ipv6 = inet_pton( '::1' );

				if ( $normalized_ipv6 === $loopback_ipv6 ) {
					return true;
				}     
			}
		}
	
		return false;
	}
}
