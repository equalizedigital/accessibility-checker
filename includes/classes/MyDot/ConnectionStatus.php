<?php
/**
 * Checks whether email reports can reach this site.
 *
 * @package Accessibility_Checker
 * @since x.x.x
 */

namespace EqualizeDigital\AccessibilityChecker\MyDot;

/**
 * Finds known reasons why My Dot cannot pull report stats from this site.
 *
 * @since x.x.x
 */
class ConnectionStatus {

	/**
	 * The site is on a local address that My Dot cannot reach.
	 */
	public const ISSUE_LOCAL = 'local';

	/**
	 * The site asks for htpasswd credentials.
	 */
	public const ISSUE_BASIC_AUTH = 'basic_auth';

	/**
	 * My Dot has not pulled stats within the expected time.
	 */
	public const ISSUE_STALE = 'stale';

	/**
	 * Option holding the time of the last pull, or of the first check when no pull happened yet.
	 */
	public const LAST_ACTIVITY_OPTION = 'edac_reports_last_activity';

	/**
	 * Transient holding the result of the htpasswd check.
	 */
	public const BASIC_AUTH_TRANSIENT = 'edac_reports_basic_auth_check';

	/**
	 * Days to wait after a missed pull before showing a warning.
	 */
	private const GRACE_DAYS = 2;

	/**
	 * Interval to use when My Dot did not send one.
	 */
	private const DEFAULT_INTERVAL_DAYS = 7;

	/**
	 * Minimum seconds between saving the last report activity.
	 */
	private const ACTIVITY_THROTTLE_SECONDS = HOUR_IN_SECONDS;

	/**
	 * Get the most relevant connection issue for this site.
	 *
	 * @param bool $is_connected Whether the site is connected for email reports.
	 * @return string One of the ISSUE_ constants, or an empty string when no issue is known.
	 */
	public static function get_issue( bool $is_connected ): string {
		if ( self::is_local_site() ) {
			return self::ISSUE_LOCAL;
		}

		if ( ! $is_connected ) {
			return self::is_behind_basic_auth() ? self::ISSUE_BASIC_AUTH : '';
		}

		// A connected site is only flagged after a missed pull. Pulls arriving means
		// our service is getting through whatever protection the site has.
		if ( ! self::is_pull_overdue() ) {
			return '';
		}

		return self::is_behind_basic_auth() ? self::ISSUE_BASIC_AUTH : self::ISSUE_STALE;
	}

	/**
	 * Check whether this site runs on a local address.
	 *
	 * @return bool
	 */
	public static function is_local_site(): bool {
		$host     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$is_local = 'local' === wp_get_environment_type() || self::is_local_host( $host );

		/**
		 * Filter whether this site counts as local for email reports.
		 *
		 * Local sites cannot enable email reports. Return false for a site that
		 * My Dot can reach even though this check calls it local, for example a
		 * tunnel, or a staging site on a private IP or a .test domain.
		 *
		 * @since x.x.x
		 *
		 * @param bool $is_local Whether the site counts as local.
		 */
		return (bool) apply_filters( 'edac_is_local_site', $is_local );
	}

	/**
	 * Check whether a host name points to a local address.
	 *
	 * Host names are not resolved, so a name that only resolves to a private
	 * address is not detected. That site gets the missed pull warning instead.
	 *
	 * @param string $host Host name or IP address.
	 * @return bool
	 */
	public static function is_local_host( string $host ): bool {
		$host = strtolower( trim( $host, '.[]' ) );

		if ( 'localhost' === $host ) {
			return true;
		}

		foreach ( [ '.local', '.localhost', '.test' ] as $suffix ) {
			if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}

		if ( false === filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		return false === filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Check whether the site asks anonymous visitors for htpasswd credentials.
	 *
	 * Requests the route My Dot pulls from. The result is cached so the settings
	 * page does not wait on a request each time. A failed request is only cached
	 * for a short time because it says nothing about the protection.
	 *
	 * @return bool
	 */
	public static function is_behind_basic_auth(): bool {
		$cached = get_transient( self::BASIC_AUTH_TRANSIENT );
		if ( false !== $cached ) {
			return 'yes' === $cached;
		}

		// The request goes to this same site, whose certificate is often self-signed,
		// so verification is off unless a host turns it on with the core loopback filter.
		$response = wp_remote_head(
			rest_url( 'accessibility-checker/v1/scans-stats' ),
			[
				'timeout'   => 3, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- same limit as VIP, this runs on an admin page.
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core loopback filter, see comment above.
			]
		);

		if ( is_wp_error( $response ) ) {
			set_transient( self::BASIC_AUTH_TRANSIENT, 'no', 5 * MINUTE_IN_SECONDS );
			return false;
		}

		$is_protected = false;
		if ( 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$header = wp_remote_retrieve_header( $response, 'www-authenticate' );
			$header = is_array( $header ) ? implode( ',', $header ) : (string) $header;
			// Match the scheme only, not the word inside a quoted realm.
			$is_protected = 1 === preg_match( '/(^|,)\s*basic(\s|,|$)/i', (string) preg_replace( '/"[^"]*"/', '""', $header ) );
		}

		set_transient( self::BASIC_AUTH_TRANSIENT, $is_protected ? 'yes' : 'no', HOUR_IN_SECONDS );

		return $is_protected;
	}

	/**
	 * Check whether My Dot missed its expected pull.
	 *
	 * @return bool
	 */
	public static function is_pull_overdue(): bool {
		$last_activity = (int) get_option( self::LAST_ACTIVITY_OPTION, 0 );

		// Sites connected before this check have no pull time, so start counting
		// from the first page view. The write waits for shutdown so rendering
		// never touches the database.
		if ( 0 === $last_activity ) {
			if ( false === has_action( 'shutdown', [ __CLASS__, 'record_activity' ] ) ) {
				add_action( 'shutdown', [ __CLASS__, 'record_activity' ] );
			}
			return false;
		}

		$interval_days = absint( get_option( 'edac_collection_interval_days', self::DEFAULT_INTERVAL_DAYS ) );
		if ( 0 === $interval_days ) {
			$interval_days = self::DEFAULT_INTERVAL_DAYS;
		}

		return ( time() - $last_activity ) > ( $interval_days + self::GRACE_DAYS ) * DAY_IN_SECONDS;
	}

	/**
	 * Save the current time as the last report activity.
	 *
	 * Writes are throttled because My Dot pulls at intervals of days, so a
	 * request a few minutes after the last one does not need a new write.
	 *
	 * @return void
	 */
	public static function record_activity(): void {
		$last_activity = (int) get_option( self::LAST_ACTIVITY_OPTION, 0 );
		if ( $last_activity >= ( time() - self::ACTIVITY_THROTTLE_SECONDS ) ) {
			return;
		}

		update_option( self::LAST_ACTIVITY_OPTION, time(), false );
	}

	/**
	 * Remove saved connection data.
	 *
	 * @return void
	 */
	public static function clear(): void {
		delete_option( self::LAST_ACTIVITY_OPTION );
		delete_transient( self::BASIC_AUTH_TRANSIENT );
	}
}
