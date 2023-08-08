<?php
/**
 * Class file for scan report data
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

/**
 * Class that handles calculating scan report data
 */
class Scan_Report_Data {

	const RULETYPE_WARNING = 'warning';
	const RULETYPE_ERROR = 'error';
	const RULETYPE_COLOR_CONTRAST = 'color_contrast';
	
	/**
	 * Name of table that stores issues
	 * 
	 * @var string
	 */
	private $issues_table;

	/**
	 * Total number of rules that are run on each page during a scan
	 *
	 * @var integer
	 */
	private $rule_count;

	/**
	 * List of page types available to be used for reporting
	 *
	 * @var integer
	 */
	private $valid_post_types;

	/**
	 * Constructor
	 */
	public function __construct() {
	
		global $wpdb;
		$this->issues_table = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
	
		$this->rule_count = count( edac_register_rules() );


		// TODO: set valid page types
		// = array( 'page', 'post' );
	}
	

	/**
	 * Gets summary information about passed tests
	 *
	 * @param array $post_types - null or type of post. If the page_type is not set, 
	 *   summary information about all allowed page_types will be returned.
	 * @return array [page_count, test_count, passed_count, failed_count, passed_percentage  ]
	 */
	public function passed_tests_summary( $post_types = null ) {

		// TODO: filter out invalid page_types

		// TODO: remove ignored, see:
		// ac.php: #962
		// $rule_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM {$table_name} where rule = %s and siteid = %d and postid = %d and ignre = %d", $rule['slug'], $siteid, $postid, 0 ) );

		// TODO: EDAC_ANWW_ACTIVE

		// TODO:
		// see:
		// $summary['contrast_errors'] = intval( $wpdb->get_var( $wpdb->prepare( $query, get_current_blog_id(), $post_id, 'color_contrast_failure', 0 ) ) );
		// remove color contrast from errors count.
		// $summary['errors'] = $summary['errors'] - $summary['contrast_errors'];


		return array();
	}


	/**
	 * Gets issue count filtered by post_types, rule_type, rule_slug.
	 *
	 * @param array $post_types - null or array of types of post. If the page_type is not set, 
	 *  totals information about all allowed page_types will be returned.
	 * @param array $rule_types - null or array of ruletypes.
	 * @param array $rule_slugs - null or array of slugs.
	 * 
	 * @return array [ issue_count ]
	 */
	public function issue_count( $post_types = array(), $rule_types = array(), $rule_slugs = array() ) {
	
		// TODO: filter out invalid page_types
		return array();


		global $wpdb;

		$sql = "SELECT count(id) FROM {$this->issues_table} where 1==1";
	
		if ( count( $post_types ) ) {
			$sql .= ' and type IN(' . edac_array_to_prepared_sql_string( $post_types ) . ')';
		}
	
		if ( count( $rule_types ) ) {
			$sql .= ' and ruletype IN(' . edac_array_to_prepared_sql_string( $rule_types ) . ')';
		}
	
		if ( count( $rule_slugs ) ) {
			$sql .= ' and rule IN(' . edac_array_to_prepared_sql_string( $rule_types ) . ')';
		}
	
	//	'and siteid' = 
	//	and ignr and ignro_global
	
	}


}
