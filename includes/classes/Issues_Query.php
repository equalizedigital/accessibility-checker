<?php
/**
 * Class file for querying issues from the db
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Helpers;
use EDAC\Settings;

/**
 * Class that handles calculating scan report data
 */
class Issues_Query {

	const RULETYPE_WARNING = 'warning';
	const RULETYPE_ERROR = 'error';
	const RULETYPE_COLOR_CONTRAST = 'color_contrast';
	
	const IGNORE_FLAG_EXCLUDE_IGNORED = 'exclude_ignored';
	const IGNORE_FLAG_INCLUDE_IGNORED = 'include_ignored';
	const IGNORE_FLAG_ONLY_IGNORED = 'only_ignored';
	
	
	/**
	 * Name of table that stores issues
	 * 
	 * @var string
	 */
	private $table;

	
	
	/**
	 * Holds the sql safe elements used to build the query
	 *
	 * @var array
	 */
	private $query = array(
		'select' => 'select count(id)',
		'from' => '',
		'where_base' => '',
		'filters' => '',
	);


	/**
	 * Constructor
	 *
	 * @param array $filter [post_types, rule_types, rule_slugs].
	 */
	public function __construct( $filter = array(), $ignored_flag = self::IGNORE_FLAG_EXCLUDE_IGNORED ) {
	
		$valid_filters = array(
			'post_types',
			'rule_types',
			'rule_slugs',
		);

		$validated_filters = [];
		foreach($filter as $key => $val){
			if(in_array($key, $valid_filters)){
				$validated_filters[$key] = $val;
			}
		}
	
		// Setup FROM
		global $wpdb;
		$this->table = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$this->query['from'] = " FROM {$this->table} ";
		
		// Setup base WHERE
		$siteid = get_current_blog_id();
		switch ($ignored_flag){
			case self::IGNORE_FLAG_EXCLUDE_IGNORED:
				$this->query['where_base'] = $wpdb->prepare( 'where siteid=%d and ignre=%d and ignre_global=%d ', array( $siteid, 0, 0 ) );
				break;
	
			case self::IGNORE_FLAG_INCLUDE_IGNORED:
				$this->query['where_base'] = $wpdb->prepare( 'where siteid=%d', array( $siteid ) );
				break;
	
			case self::IGNORE_FLAG_ONLY_IGNORED:
				$this->query['where_base'] = $wpdb->prepare( 'where siteid=%d and (ignre=%d or ignre_global=%d) ', array( $siteid, 1, 1 ) );
				break;
			
			default:
				$this->query['where_base'] = $wpdb->prepare( 'where siteid=%d and ignre=%d and ignre_global=%d ', array( $siteid, 0, 0 ) );
		
		}

		
	
		
		$filter_defaults = array(
			'post_types' => array(),
			'rule_types' => array(),
			'rule_slugs' => array(),
		);
		$filter = array_replace_recursive( $filter_defaults, $validated_filters );

		$this->add_filters( $filter );

	}


	/**
	 * Returns the prepared (safe) sql to be used for the query
	 *
	 * @return string $sql .
	 */
	public function get_sql() {
		return $this->query['select'] . ' ' . $this->query['from'] . ' ' . $this->query['where_base'] . ' ' . $this->query['filters'];
	}


	/**
	 * Returns the query array that can be used to build a custom query
	 *
	 * @return array $query .
	 */
	public function get_query() {
		return $this->query;
	}


	/**
	 * Gets issue count.
	 *    
	 * @return integer issue_count .
	 */
	public function count() {

		global $wpdb;

		$this->query['select'] = 'SELECT COUNT(id) ';
		
		return $wpdb->get_var( $this->get_sql() );
	
	}

	/**
	 * Gets distinct issue count.
	 *    
	 * @return integer issue_count .
	 */
	public function distinct_count() {

		global $wpdb;

		$this->query['select'] = 'SELECT COUNT( DISTINCT rule, object ) ';
		
		return $wpdb->get_var( $this->get_sql() );
	
	}

	/**
	 * Get the ids of the issues.
	 *    
	 * @return array issues .
	 */
	public function get_ids() {

		global $wpdb;

		$this->query['select'] = 'SELECT id';
		
		return $wpdb->get_results( $this->get_sql() );
	
	}


	/**
	 * Adds the filters to be used by the query.
	 * 
	 * @param array $filter .
	 * 
	 * @return void
	 */
	private function add_filters( $filter ) {
		
		if ( array_key_exists( 'post_types', $filter ) && count( $filter['post_types'] ) ) {

			$scannable_post_types = Settings::get_scannable_post_types();
			$post_types = array_intersect( $filter['post_types'], $scannable_post_types );
			if ( count( $post_types ) ) {
				$this->query['filters'] .= ' and type IN (' . Helpers::array_to_sql_safe_list( $post_types ) . ') ';
			}
		}
	
		if ( array_key_exists( 'rule_types', $filter ) && count( $filter['rule_types'] ) ) {

			// Special handler for color contrast rule/ruletype.
			if ( in_array( self::RULETYPE_COLOR_CONTRAST, $filter['rule_types'] ) ) {
				
				// We are filtering by color contrast but color contrast is a rule not a ruletype.
				// Remove color_contrast from the rule_type filter.
				$key = array_search( self::RULETYPE_COLOR_CONTRAST, $filter['rule_types'] );
				if ( false !== $key ) {
					unset( $filter['rule_types'][ $key ] );
				}

				// Then add color_contrast_failure to the rule_slugs filter.
				$key = array_search( 'color_contrast_failure', $filter['rule_slugs'] );
				if ( false == $key ) {
					$filter['rule_slugs'][] = 'color_contrast_failure';
				}           
			}

			if ( count( $filter['rule_types'] ) ) {
				$this->query['filters'] .= ' and ruletype IN (' . Helpers::array_to_sql_safe_list( $filter['rule_types'] ) . ') ';
			}
		}

	
		if ( array_key_exists( 'rule_slugs', $filter ) && count( $filter['rule_slugs'] ) ) {
			$this->query['filters'] .= ' and rule IN (' . Helpers::array_to_sql_safe_list( $filter['rule_slugs'] ) . ') ';
		}

	}


}
