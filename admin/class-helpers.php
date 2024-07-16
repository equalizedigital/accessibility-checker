<?php
/**
 * Class file for helpers
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

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
	 * @param int     $number number to format.
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
		}
		return number_format( $number );
	}

	/**
	 * Localizes the format of a percentage.
	 *
	 * @param init    $number number to format.
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
		}
		return sprintf( '%.2f%%', $number * 100 );
	}

	/**
	 * Localizes the format of a date.
	 *
	 * @param string  $date date to format.
	 * @param boolean $include_time whether to include time in the formatted date.
	 * @return integer
	 */
	public static function format_date( $date, $include_time = false ) {

		$timestamp = $date;
		if ( ! is_numeric( $date ) ) { // date as string.
			$timestamp = strtotime( $date );
			if ( ! $timestamp ) { // The passed string is not a valid date.
				return $date;
			}
		}

		$datetime = new \DateTime();
		$datetime->setTimestamp( $timestamp );
		$datetime->setTimezone( wp_timezone() );

		$format = ( ! $include_time )
			? get_option( 'date_format' )
			: get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' );

		if ( ! $format ) {
			$format = 'j M Y';
			if ( $include_time ) {
				$format = 'j M Y \a\t g:i a';
			}
		}

		return $datetime->format( $format );
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
		}
		return [];
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

	/**
	 * Filter out inactive rules from the results returned.
	 *
	 * @param array $results The results to filter.
	 */
	public static function filter_results_to_only_active_rules( $results ): array {
		// determine which rules are active.
		$active_rule_slugs = array_map(
			function ( $rule ) {
				return $rule['slug'];
			},
			edac_register_rules()
		);

		// filter out inactive rules from the results returned.
		foreach ( $results as $index => $result ) {
			if ( ! in_array( $result['rule'], $active_rule_slugs, true ) ) {
				unset( $results[ $index ] );
			}
		}
		return $results;
	}

	/**
	 * Do a capability check for the current user to ensure they have the required capability
	 * to see various widgets or notices.
	 *
	 * @since 1.9.3
	 *
	 * @return bool True if the current user has capabilities required, false otherwise.
	 */
	public static function current_user_can_see_widgets_and_notices(): bool {
		/**
		 * Filter the capability required to view the dashboard widget.
		 *
		 * @since 1.9.3
		 *
		 * @param string $capability The capability required to view the dashboard widget.
		 */
		return current_user_can( apply_filters( 'edac_filter_dashboard_widget_capability', 'edit_posts' ) );
	}
}
