<?php
/**
 * Checks whether email reports can reach this site.
 *
 * @package Accessibility_Checker
 * @since 1.53.0
 */

namespace EqualizeDigital\AccessibilityChecker\MyDot;

/**
 * Finds known reasons why My Dot cannot pull report stats from this site.
 *
 * @since 1.53.0
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

		// Our own check always gets a 401 on a protected site, even when My Dot has
		// the credentials, so a connected site only has an issue after a missed pull.
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
		 * Local sites cannot enable email reports. Return false for a local site
		 * that My Dot can reach, for example through a tunnel.
		 *
		 * @since 1.53.0
		 *
		 * @param bool $is_local Whether the site counts as local.
		 */
		return (bool) apply_filters( 'edac_is_local_site', $is_local );
	}

	/**
	 * Check whether a host name points to a local address.
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
	 * The result is cached so the settings page does not wait on a request each time.
	 *
	 * @return bool
	 */
	public static function is_behind_basic_auth(): bool {
		$cached = get_transient( self::BASIC_AUTH_TRANSIENT );
		if ( false !== $cached ) {
			return 'yes' === $cached;
		}

		$response = wp_remote_head(
			home_url( '/' ),
			[
				'timeout'   => 3, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- same limit as VIP, this runs on an admin page.
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter for requests to the site itself.
			]
		);

		$is_protected = false;
		if ( ! is_wp_error( $response ) && 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$header       = wp_remote_retrieve_header( $response, 'www-authenticate' );
			$header       = is_array( $header ) ? implode( ',', $header ) : (string) $header;
			$is_protected = false !== stripos( $header, 'basic' );
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

		// Sites connected before this check have no pull time, so start counting from now.
		if ( 0 === $last_activity ) {
			self::record_activity();
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
	 * @return void
	 */
	public static function record_activity(): void {
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
