<?php
/**
 * Class file for scan report data
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Settings;
use EDAC\Issues_Query;

/**
 * Class that handles calculating scan report data
 */
class Scan_Report_Data {

	/**
	 * Number of seconds to return results from cache.
	 *
	 * @var integer
	 */
	private $cache_time = 0;

	/**
	 * Total number of rules that are run on each page during a scan
	 *
	 * @var integer
	 */
	private $rule_count;

	
	/**
	 * Constructor
	 *
	 * @param integer $cache_type number of seconds to return the results from cache.
	 */
	public function __construct( $cache_time = 600 ) {
	
		$this->cache_time = $cache_time;
		$this->rule_count = count( edac_register_rules() );
	
	}
	

	/**
	 * Gets summary information about all scans
	 *
	 * @return array .
	 */
	public function scan_summary() {
	
		global $wpdb;

		$transient_name = 'edac_scan_summary';

		$cache = get_transient( $transient_name );
	
		
		if ( $this->cache_time && $cache ) {
			return $cache;
		}


		$data = array();

	
		$scannable_posts_count  = Settings::get_scannable_posts_count();
		$tests_count = $scannable_posts_count * $this->rule_count;

		$data['scannable_posts_count'] = (int) $scannable_posts_count;
		$data['rule_count'] = (int) $this->rule_count;
		$data['tests_count'] = (int) $tests_count;
		
		$issues_query = new \EDAC\Issues_Query();

		$data['posts_scanned'] = (int) $scannable_posts_count;
		$data['rules_failed'] = (int) $issues_query->distinct_count();
		$data['rules_passed'] = (int) ( $tests_count - $data['rules_failed'] );
		$data['passed_percentage'] = round( ( $data['rules_passed'] / $tests_count ) * 100, 2 );
	
		$warning_issues_query = new \EDAC\Issues_Query( array( 'rule_types' => array( Issues_Query::RULETYPE_WARNING ) ) );
		$data['warnings'] = (int) $warning_issues_query->count();
		$data['distinct_warnings'] = (int) $warning_issues_query->distinct_count();

		$contrast_issues_query = new \EDAC\Issues_Query( array( 'rule_types' => array( Issues_Query::RULETYPE_COLOR_CONTRAST ) ) );
		$data['contrast_errors'] = (int) $contrast_issues_query->count();
		$data['distinct_contrast_errors'] = (int) $contrast_issues_query->distinct_count();
		
		$error_issues_query = new \EDAC\Issues_Query( array( 'rule_types' => array( Issues_Query::RULETYPE_ERROR ) ) );
		$data['errors'] = (int) $error_issues_query->count();
		$data['distinct_errors'] = (int) $error_issues_query->distinct_count();
		
		$data['errors_without_contrast'] = $data['errors'] - $data['contrast_errors'];
		$data['distinct_errors_without_contrast'] = $data['distinct_errors'] - $data['distinct_contrast_errors'];
	
		$ignored_issues_query = new \EDAC\Issues_Query( array(), \EDAC\Issues_Query::IGNORE_FLAG_ONLY_IGNORED );
		$data['ignored'] = (int) $ignored_issues_query->count();


		$data['posts_without_issues'] = $wpdb->get_var( 
			$wpdb->prepare(
				'SELECT count(wp_posts.ID) from ' . $wpdb->posts . '  
				LEFT JOIN ' . $wpdb->prefix . 'accessibility_checker ON 
				wp_posts.ID = ' . $wpdb->prefix . 'accessibility_checker.postid 
				WHERE ' . $wpdb->prefix . 'accessibility_checker.postid IS NULL and 1=%d;',
				array( 1 )
			)
		);

		$data['avg_issues_per_post'] = ( $data['warnings'] + $data['errors'] ) / $data['posts_scanned'];
	

		$data['issue_density_percentage'] = 
			$wpdb->get_var( 
				$wpdb->prepare(
					'SELECT avg(meta_value) from ' . $wpdb->postmeta . ' 
				WHERE meta_key = %s and meta_value > %d;',
					array( '_edac_issue_density', 0 )
				)
			);
		
		if ( null === $data['issue_density_percentage'] ) {
			$data['issue_density_percentage'] = 'N/A';
		} else {
			$data['issue_density_percentage'] = round( $data['issue_density_percentage'], 2 );

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
	 * Gets summary information about issues by post type
	 *
	 * @param string $post_type
	 * @return array .
	 */
	public function issue_summary_by_post_type( $post_type ) {
	
		$transient_name = 'edac_issue_summary_by_post_type__' . $post_type;

		$cache = get_transient( $transient_name );
	
		if ( $this->cache_time && $cache ) {
			return $cache;
		}


		$data = array();

	
		$error_issues_query = new \EDAC\Issues_Query(
			array( 
				'rule_types' => array( Issues_Query::RULETYPE_ERROR ),
				'post_types' => array( $post_type ),
			)
		);
		$data['errors'] = $error_issues_query->count();
		$data['distinct_errors'] = $error_issues_query->distinct_count();
		
	

		$warning_issues_query = new \EDAC\Issues_Query(
			array( 
				'rule_types' => array( Issues_Query::RULETYPE_WARNING ),
				'post_types' => array( $post_type ),
			)
		);
		$data['warnings'] = $warning_issues_query->count();
		$data['distinct_warnings'] = $warning_issues_query->distinct_count();
		
	
		$color_contrast_issues_query = new \EDAC\Issues_Query(
			array( 
				'rule_types' => array( Issues_Query::RULETYPE_COLOR_CONTRAST ),
				'post_types' => array( $post_type ),
			)
		);
		$data['contrast_errors'] = $color_contrast_issues_query->count();
		$data['distinct_contrast_errors'] = $color_contrast_issues_query->distinct_count();
		$data['distinct_contrast_errors_sql'] = $color_contrast_issues_query->get_sql();
		
	
		$data['errors_without_contrast'] = $data['errors'] - $data['contrast_errors'];
		$data['distinct_errors_without_contrast'] = $data['distinct_errors'] - $data['distinct_contrast_errors'];
	
		
	
		$data['cache_id'] = $transient_name; 
		$data['cached_at'] = time(); 
		
		set_transient( $transient_name, $data, $this->cache_time );

	
		return $data;
	}

}
