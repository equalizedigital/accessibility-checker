<?php
/**
 * Class file for scans stats
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Settings;
use EDAC\Helpers;
use EDAC\Issues_Query;


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
	 * @param integer $cache_type number of seconds to return the results from cache.
	 */
	public function __construct( $cache_time = 60 * 60 * 24, $record_limit = 100000 ) {
	
		$this->cache_time = $cache_time;
		$this->cache_name_prefix = 'edac_scans_stats_' . $record_limit;
		$this->record_limit = $record_limit;
		$this->rule_count = count( edac_register_rules() );
	
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

			$full_scan_completed_at = (int) get_option( 'edacp_fullscan_completed_at' );

			if ( $full_scan_completed_at < $cache['cached_at'] ) {
				// There hasn't been a full scan completed since we've cached, so return these results.
				return $cache;
			}
		}

	
		
		$data = array();

	
		$scannable_posts_count  = Settings::get_scannable_posts_count();
		$tests_count = $scannable_posts_count * $this->rule_count;


		$data['scannable_posts_count'] = (int) $scannable_posts_count;
		$data['rule_count'] = (int) $this->rule_count;
		$data['tests_count'] = (int) $tests_count;
		
		$data['scannable_post_types_count'] = (int) count( Settings::get_scannable_post_types() );
		
		$post_types = get_post_types(
			array(
				'public' => true,
			) 
		);
		unset( $post_types['attachment'] );

		$data['public_post_types_count'] = (int) count( $post_types );
		

		$issues_query = new \EDAC\Issues_Query( array(), $this->record_limit );
	
		$data['is_truncated'] = $issues_query->has_truncated_results();
	
		$data['posts_scanned'] = (int) $scannable_posts_count;
		$data['rules_failed'] = (int) $issues_query->distinct_count();
		$data['rules_passed'] = (int) ( $tests_count - $data['rules_failed'] );
		$data['passed_percentage'] = round( ( $data['rules_passed'] / $tests_count ) * 100, 2 );
	
		$warning_issues_query = new \EDAC\Issues_Query( 
			array( 'rule_types' => array( Issues_Query::RULETYPE_WARNING ) ), 
			$this->record_limit 
		);
		$data['warnings'] = (int) $warning_issues_query->count();
		$data['distinct_warnings'] = (int) $warning_issues_query->distinct_count();

		$contrast_issues_query = new \EDAC\Issues_Query( 
			array( 'rule_types' => array( Issues_Query::RULETYPE_COLOR_CONTRAST ) ),
			$this->record_limit 
		);
		$data['contrast_errors'] = (int) $contrast_issues_query->count();
		$data['distinct_contrast_errors'] = (int) $contrast_issues_query->distinct_count();
		
		$error_issues_query = new \EDAC\Issues_Query( 
			array( 'rule_types' => array( Issues_Query::RULETYPE_ERROR ) ),
			$this->record_limit 
		);
		$data['errors'] = (int) $error_issues_query->count();
		$data['distinct_errors'] = (int) $error_issues_query->distinct_count();
		
		$data['errors_without_contrast'] = $data['errors'] - $data['contrast_errors'];
		$data['distinct_errors_without_contrast'] = $data['distinct_errors'] - $data['distinct_contrast_errors'];
	
		$ignored_issues_query = new \EDAC\Issues_Query( 
			array(), 
			$this->record_limit,
			\EDAC\Issues_Query::IGNORE_FLAG_ONLY_IGNORED
		);
		$data['ignored'] = (int) $ignored_issues_query->count();
		$data['distinct_ignored'] = (int) $ignored_issues_query->distinct_count();
		
		
		$data['posts_without_issues']  = $wpdb->get_var( 
			"SELECT COUNT({$wpdb->posts}.ID) FROM {$wpdb->posts}  
			LEFT JOIN " . $wpdb->prefix . "accessibility_checker ON {$wpdb->posts}.ID = " .
			$wpdb->prefix . 'accessibility_checker.postid WHERE ' . 
			$wpdb->prefix . 'accessibility_checker.postid IS NULL and post_type IN(' . 
			Helpers::array_to_sql_safe_list( 
				Settings::get_scannable_post_types() 
			) . ') and
			post_status IN(' . Helpers::array_to_sql_safe_list( 
				Settings::get_scannable_post_statuses()
			) . ')'
		);

		$data['avg_issues_per_post'] = round( ( $data['warnings'] + $data['errors'] ) / $data['posts_scanned'], 2 );
	

		$data['avg_issue_density_percentage'] = 
			$wpdb->get_var( 
				$wpdb->prepare(
					'SELECT avg(meta_value) from ' . $wpdb->postmeta . ' 
				WHERE meta_key = %s and meta_value > %d;',
					array( '_edac_issue_density', 0 )
				)
			);
		
		if ( null === $data['avg_issue_density_percentage'] ) {
			$data['avg_issue_density_percentage'] = 'N/A';
		} else {
			$data['avg_issue_density_percentage'] = round( $data['avg_issue_density_percentage'], 2 );

		}
	


		$data['fullscan_running'] = false;
		$data['fullscan_state'] = '';
		
		if ( class_exists( '\EDACP\Scans' ) ) {
			$scans = new \EDACP\Scans();
			$scan_state = $scans->scan_state();
			
			$data['fullscan_state'] = $scan_state;
			if (
				\EDACP\Scans::SCAN_STATE_PHP_SCAN_RUNNING == $scan_state ||
				\EDACP\Scans::SCAN_STATE_JS_SCAN_RUNNING == $scan_state
			) {
				$data['fullscan_running'] = true;
			} 
			$data['fullscan_completed_at'] = (int) get_option( 'edacp_fullscan_completed_at' );
	
		} else {
			$data['fullscan_completed_at'] = 0;
		}

		$data['cache_id'] = $transient_name; 
		$data['cached_at'] = time(); 
		

		set_transient( $transient_name, $data, $this->cache_time );

	
		return $data;
	}

	/**
	 * Gets issues summary information about a post type
	 *
	 * @param string $post_type
	 * @return array .
	 */
	public function issues_summary_by_post_type( $post_type ) {
	
		$transient_name = $this->cache_name_prefix . '_issues_summary_by_post_type_' . $post_type;

		$cache = get_transient( $transient_name );
	
		if ( $this->cache_time && $cache ) {

			$full_scan_completed_at = (int) get_option( 'edacp_fullscan_completed_at' );

			if ( $full_scan_completed_at < $cache['cached_at'] ) {
				// There hasn't been a full scan completed since we've cached, so return these results.
				return $cache;
			}       
		}


		$data = array();

	
		$error_issues_query = new \EDAC\Issues_Query(
			array( 
				'rule_types' => array( Issues_Query::RULETYPE_ERROR ),
				'post_types' => array( $post_type ),
			),
			$this->record_limit
		);
		$data['errors'] = $error_issues_query->count();
		$data['distinct_errors'] = $error_issues_query->distinct_count();
		
	

		$warning_issues_query = new \EDAC\Issues_Query(
			array( 
				'rule_types' => array( Issues_Query::RULETYPE_WARNING ),
				'post_types' => array( $post_type ),
			),
			$this->record_limit
		);
		$data['warnings'] = $warning_issues_query->count();
		$data['distinct_warnings'] = $warning_issues_query->distinct_count();
		
	
		$color_contrast_issues_query = new \EDAC\Issues_Query(
			array( 
				'rule_types' => array( Issues_Query::RULETYPE_COLOR_CONTRAST ),
				'post_types' => array( $post_type ),
			),
			$this->record_limit
		);
		$data['contrast_errors'] = $color_contrast_issues_query->count();
		$data['distinct_contrast_errors'] = $color_contrast_issues_query->distinct_count();
		
	
		$data['errors_without_contrast'] = $data['errors'] - $data['contrast_errors'];
		$data['distinct_errors_without_contrast'] = $data['distinct_errors'] - $data['distinct_contrast_errors'];
	
		
	
		$data['cache_id'] = $transient_name; 
		$data['cached_at'] = time(); 
		
		set_transient( $transient_name, $data, $this->cache_time );

	
		return $data;
	}

}
