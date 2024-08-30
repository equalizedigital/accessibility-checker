<?php
/**
 * Class file for scans stats
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EqualizeDigital\AccessibilityCheckerPro\Admin\Scans;

/**
 * Class that handles calculating scans stats
 */
class Scans_Stats {

	/**
	 * Number of seconds to return results from cache.
	 *
	 * @var integer
	 */
	private $cache_time = 0;

	/**
	 * Max number of issues to consider for results.
	 *
	 * @var integer
	 */
	private $record_limit = 100000;

	/**
	 * Total number of rules that are run on each page during a scan
	 *
	 * @var integer
	 */
	private $rule_count;

	/**
	 * Prefix used cache name
	 *
	 * @var string
	 */
	private $cache_name_prefix;

	/**
	 * Constructor
	 *
	 * @param integer $cache_time number of seconds to return the results from cache.
	 */
	public function __construct( $cache_time = 60 * 60 * 24 ) {

		$this->cache_time        = $cache_time;
		$this->cache_name_prefix = 'edac_scans_stats_' . EDAC_VERSION . '_' . $this->record_limit;
		$this->rule_count        = count( edac_register_rules() );
	}

	/**
	 * Load all stats into the cache. Should be called by a background scheduled.
	 *
	 * @return void
	 */
	public function load_cache() {

		// Cache the summary.
		$this->summary();

		// Cache the post_types.
		$scannable_post_types = Settings::get_scannable_post_types();

		$post_types = get_post_types(
			[
				'public' => true,
			]
		);

		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type, $scannable_post_types, true ) ) {
				$this->issues_summary_by_post_type( $post_type );
			}
		}
	}

	/**
	 * Clear the summary and post type scans stats that have been cached
	 *
	 * @return void
	 */
	public function clear_cache() {

		// Delete the cached summary stats.
		$transient_name = $this->cache_name_prefix . '_summary';
		delete_transient( $transient_name );

		// Delete the cached post_type stats.
		$post_types = get_post_types(
			[
				'public' => true,
			]
		);
		unset( $post_types['attachment'] );
		foreach ( $post_types as $post_type ) {
			$transient_name = $this->cache_name_prefix . '_issues_summary_by_post_type_' . $post_type;
			delete_transient( $transient_name );
		}
	}

	/**
	 * Gets summary information about all scans
	 *
	 * @return array .
	 */
	public function summary() {

		global $wpdb;

		$transient_name = $this->cache_name_prefix . '_summary';

		$cache = get_transient( $transient_name );

		if ( $this->cache_time && $cache ) {

			if ( $cache['expires_at'] >= time() && $cache['cached_at'] + $this->cache_time >= time()
			) {

				$full_scan_completed_at = (int) get_option( 'edacp_fullscan_completed_at' );
				if ( $full_scan_completed_at <= $cache['cached_at'] ) {
					// There hasn't been a full scan completed since we've cached, so return these results.
					$cache['cache_hit'] = true;
					return $cache;
				}
			}
		}

		$data = [];

		$scannable_posts_count = Settings::get_scannable_posts_count();
		$tests_count           = $scannable_posts_count * $this->rule_count;
		$siteid                = get_current_blog_id();

		$data['scannable_posts_count']      = (int) $scannable_posts_count;
		$data['rule_count']                 = (int) $this->rule_count;
		$data['tests_count']                = (int) $tests_count;
		$data['scannable_post_types_count'] = (int) count( Settings::get_scannable_post_types() );

		$post_types = get_post_types(
			[
				'public' => true,
			]
		);
		unset( $post_types['attachment'] );

		$data['public_post_types_count'] = (int) count( $post_types );

		$issues_query = new Issues_Query( [], $this->record_limit, Issues_Query::FLAG_INCLUDE_ALL_POST_TYPES );

		$data['is_truncated']  = $issues_query->has_truncated_results();
		$data['posts_scanned'] = (int) $issues_query->distinct_posts_count();
		$data['rules_failed']  = 0;

		// Get a count of rules that are not in the issues table.
		$rule_slugs = array_map(
			function ( $item ) {
				return $item['slug'];
			},
			edac_register_rules()
		);

		foreach ( $rule_slugs as $rule_slug ) {
			$rule_query = new Issues_Query(
				[
					'rule_slugs' => [ $rule_slug ],
				],
				1,
				Issues_Query::FLAG_INCLUDE_ALL_POST_TYPES
			);

			if ( $rule_query->count() ) {
					++$data['rules_failed'];
			}
		}
		$data['rules_passed'] = $this->rule_count - $data['rules_failed'];

		$data['passed_percentage'] = 100;
		if ( $data['posts_scanned'] > 0 && $tests_count > 0 ) {
			$data['passed_percentage'] = round( ( $data['rules_passed'] / $this->rule_count ) * 100, 2 );
		}

		$warning_issues_query      = new Issues_Query(
			[
				'post_types' => Settings::get_scannable_post_types(),
				'rule_types' => [ Issues_Query::RULETYPE_WARNING ],
			],
			$this->record_limit
		);
		$data['warnings']          = (int) $warning_issues_query->count();
		$data['distinct_warnings'] = (int) $warning_issues_query->distinct_count();

		$contrast_issues_query = new Issues_Query(
			[
				'post_types' => Settings::get_scannable_post_types(),
				'rule_types' => [ Issues_Query::RULETYPE_COLOR_CONTRAST ],
			],
			$this->record_limit
		);

		$data['contrast_errors']          = (int) $contrast_issues_query->count();
		$data['distinct_contrast_errors'] = (int) $contrast_issues_query->distinct_count();

		$error_issues_query      = new Issues_Query(
			[
				'post_types' => Settings::get_scannable_post_types(),
				'rule_types' => [ Issues_Query::RULETYPE_ERROR ],
			],
			$this->record_limit
		);
		$data['errors']          = (int) $error_issues_query->count();
		$data['distinct_errors'] = (int) $error_issues_query->distinct_count();

		$data['errors_without_contrast']          = $data['errors'] - $data['contrast_errors'];
		$data['distinct_errors_without_contrast'] = $data['distinct_errors'] - $data['distinct_contrast_errors'];

		$ignored_issues_query         = new Issues_Query(
			[
				'post_types' => Settings::get_scannable_post_types(),
			],
			$this->record_limit,
			Issues_Query::FLAG_ONLY_IGNORED
		);
		$data['ignored']              = (int) $ignored_issues_query->count();
		$data['distinct_ignored']     = (int) $ignored_issues_query->distinct_count();
		$data['posts_without_issues'] = 0;
		$data['avg_issues_per_post']  = 0;

		if ( $data['posts_scanned'] > 0
			&& ! empty( Settings::get_scannable_post_types() )
			&& ! empty( Settings::get_scannable_post_statuses() )
		) {

			$sql = "SELECT COUNT({$wpdb->posts}.ID) FROM {$wpdb->posts}
			LEFT JOIN " . $wpdb->prefix . "accessibility_checker ON {$wpdb->posts}.ID = " .
			$wpdb->prefix . 'accessibility_checker.postid WHERE ' .
			$wpdb->prefix . 'accessibility_checker.postid IS NULL and post_type IN(' .
			Helpers::array_to_sql_safe_list(
				Settings::get_scannable_post_types()
			) . ') and post_status IN(' . Helpers::array_to_sql_safe_list(
				Settings::get_scannable_post_statuses()
			) . ')';

         // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
			$data['posts_without_issues'] = $wpdb->get_var( $sql );
			$data['avg_issues_per_post']  = round( ( $data['warnings'] + $data['errors'] ) / $data['posts_scanned'], 2 );
		}

		$data['avg_issue_density_percentage'] =
		$wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
			$wpdb->prepare(
				'SELECT avg(meta_value) from ' . $wpdb->postmeta . '
				JOIN ' . $wpdb->prefix . 'accessibility_checker ON postid=post_id
				WHERE meta_key = %s and meta_value > %d
				and ' . $wpdb->prefix . 'accessibility_checker.siteid=%d and ignre=%d and ignre_global=%d LIMIT %d',
				[ '_edac_issue_density', 0, $siteid, 0, 0, $this->record_limit ]
			)
		);

		$data['avg_issue_density_percentage'] = ( null === $data['avg_issue_density_percentage'] )
		? 'N/A'
		: round( $data['avg_issue_density_percentage'], 2 );

		$data['fullscan_running']      = false;
		$data['fullscan_state']        = '';
		$data['fullscan_completed_at'] = 0;

		// For back compat reasons the old class_exists is kept and moved to an else block.
		// After a few releases the else should be removed.
		if ( class_exists( '\EqualizeDigital\AccessibilityCheckerPro\Admin\Scans' ) ) {
			$scans      = new Scans();
			$scan_state = $scans->scan_state();

			$data['fullscan_state'] = $scan_state;
			if ( Scans::SCAN_STATE_PHP_SCAN_RUNNING === $scan_state
				|| Scans::SCAN_STATE_JS_SCAN_RUNNING === $scan_state
			) {
				$data['fullscan_running'] = true;
			}
			$data['fullscan_completed_at'] = $scans->scan_date( 'php' );
		} elseif ( class_exists( '\EDACP\Scans' ) ) {
			$scans      = new \EDACP\Scans();
			$scan_state = $scans->scan_state();

			$data['fullscan_state'] = $scan_state;
			if ( \EDACP\Scans::SCAN_STATE_PHP_SCAN_RUNNING === $scan_state
				|| \EDACP\Scans::SCAN_STATE_JS_SCAN_RUNNING === $scan_state
			) {
				$data['fullscan_running'] = true;
			}
			$data['fullscan_completed_at'] = $scans->scan_date( 'php' );

		}

		$data['cache_id']   = $transient_name;
		$data['cached_at']  = time();
		$data['expires_at'] = time() + $this->cache_time;
		$data['cache_hit']  = false;

		// Handle formatting. Assumes all are numbers except for those listed in exceptions.
		$formatting            = [];
		$formatting_exceptions = [
			'is_truncated',
			'passed_percentage',
			'avg_issue_density_percentage',
			'fullscan_running',
			'fullscan_state',
			'fullscan_completed_at',
			'cache_id',
			'cached_at',
			'expires_at',
			'cache_hit',
		];

		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, $formatting_exceptions, true ) ) {
				$formatting[ $key . '_formatted' ] = Helpers::format_number( $value );
			}
		}

		// Handle exceptions.
		$formatting['fullscan_completed_at_formatted'] = 'N/A';
		if ( $data['fullscan_completed_at'] > 0 ) {
			$formatting['fullscan_completed_at_formatted'] = Helpers::format_date( $data['fullscan_completed_at'], true );
		}
		$formatting['passed_percentage_formatted']            = Helpers::format_percentage( $data['passed_percentage'] );
		$formatting['avg_issue_density_percentage_formatted'] = Helpers::format_percentage( $data['avg_issue_density_percentage'] );

		$formatting['cached_at_formatted'] = Helpers::format_date( $data['cached_at'], true );

		$data = array_merge( $data, $formatting );

		if ( $data['posts_scanned'] > 0 ) {
			set_transient( $transient_name, $data, $this->cache_time );
		} else {
			// no posts have been scanned, so clear any previously cache results.
			$this->clear_cache();
		}

		return $data;
	}

	/**
	 * Gets issues summary information about a post type
	 *
	 * @param  string $post_type post type.
	 * @return array .
	 */
	public function issues_summary_by_post_type( $post_type ) {

		$transient_name = $this->cache_name_prefix . '_issues_summary_by_post_type_' . $post_type;

		$cache = get_transient( $transient_name );

		if ( $this->cache_time && $cache ) {

			if ( $cache['expires_at'] >= time() && $cache['cached_at'] + $this->cache_time >= time()
			) {

				$full_scan_completed_at = (int) get_option( 'edacp_fullscan_completed_at' );
				if ( $full_scan_completed_at <= $cache['cached_at'] ) {
					// There hasn't been a full scan completed since we've cached, so return these results.
					$cache['cache_hit'] = true;
					return $cache;
				}
			}
		}

		$data = [];

		$error_issues_query      = new Issues_Query(
			[
				'rule_types' => [ Issues_Query::RULETYPE_ERROR ],
				'post_types' => [ $post_type ],
			],
			$this->record_limit
		);
		$data['errors']          = $error_issues_query->count();
		$data['distinct_errors'] = $error_issues_query->distinct_count();

		$warning_issues_query      = new Issues_Query(
			[
				'rule_types' => [ Issues_Query::RULETYPE_WARNING ],
				'post_types' => [ $post_type ],
			],
			$this->record_limit
		);
		$data['warnings']          = $warning_issues_query->count();
		$data['distinct_warnings'] = $warning_issues_query->distinct_count();

		$color_contrast_issues_query      = new Issues_Query(
			[
				'rule_types' => [ Issues_Query::RULETYPE_COLOR_CONTRAST ],
				'post_types' => [ $post_type ],
			],
			$this->record_limit
		);
		$data['contrast_errors']          = $color_contrast_issues_query->count();
		$data['distinct_contrast_errors'] = $color_contrast_issues_query->distinct_count();

		$data['errors_without_contrast']          = $data['errors'] - $data['contrast_errors'];
		$data['distinct_errors_without_contrast'] = $data['distinct_errors'] - $data['distinct_contrast_errors'];

		foreach ( $data as $key => $val ) {
			$data[ $key . '_formatted' ] = Helpers::format_number( $val );
		}

		$data['cache_id']   = $transient_name;
		$data['cached_at']  = time();
		$data['expires_at'] = time() + $this->cache_time;
		$data['cache_hit']  = false;

		set_transient( $transient_name, $data, $this->cache_time );

		return $data;
	}
}
