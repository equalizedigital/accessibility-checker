<?php
/**
 * Class file for querying issues from the db
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class that handles calculating scan report data
 */
class Issues_Query {

	const FLAG_EXCLUDE_IGNORED        = 0; // default.
	const FLAG_INCLUDE_IGNORED        = 1;
	const FLAG_ONLY_IGNORED           = 2;
	const FLAG_INCLUDE_ALL_POST_TYPES = 4;  // If enabled, will ignore the post_type filter and return results for all post types.


	const RULETYPE_WARNING        = 'warning';
	const RULETYPE_ERROR          = 'error';
	const RULETYPE_COLOR_CONTRAST = 'color_contrast';


	/**
	 * Name of table that stores issues
	 *
	 * @var string
	 */
	private $table;


	/**
	 * Holds the max number of records we'll query
	 *
	 * @var [integer]
	 */
	private $record_limit;


	/**
	 * Holds the sql safe elements used to build the query
	 *
	 * @var array
	 */
	private $query = [
		'select'     => 'select count(*)',
		'from'       => '',
		'where_base' => '',
		'filters'    => '',
		'limit'      => '',
	];


	/**
	 * Constructor
	 *
	 * @param array   $filter [post_types, rule_types, rule_slugs].
	 * @param integer $record_limit Max number of records we'll query.
	 * @param string  $flags Flag used to determine how ignored issues sould be handled.
	 */
	public function __construct( $filter = [], $record_limit = 100000, $flags = self::FLAG_EXCLUDE_IGNORED ) {
		$valid_filters = [
			'post_types',
			'rule_types',
			'rule_slugs',
		];

		$validated_filters = [];
		foreach ( $filter as $key => $val ) {
			if ( in_array( $key, $valid_filters, true ) ) {
				$validated_filters[ $key ] = $val;
			}
		}

		$this->record_limit = $record_limit;

		// Setup FROM.
		global $wpdb;
		$this->table         = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$this->query['from'] = " FROM {$this->table} ";

		// Setup base WHERE.
		$siteid = get_current_blog_id();

		if ( $flags & self::FLAG_INCLUDE_IGNORED ) {
			$this->query['where_base'] = $wpdb->prepare( 'WHERE siteid=%d', [ $siteid ] );

		} elseif ( $flags & self::FLAG_ONLY_IGNORED ) {
			$this->query['where_base'] = $wpdb->prepare( 'WHERE siteid=%d and (ignre=%d or ignre_global=%d) ', [ $siteid, 1, 1 ] );

		} else { // This is the default.
			$this->query['where_base'] = $wpdb->prepare( 'WHERE siteid=%d and ignre=%d and ignre_global=%d ', [ $siteid, 0, 0 ] );
		}

		$filter_defaults = [
			'post_types' => [],
			'rule_types' => [],
			'rule_slugs' => [],
		];

		$filter = array_replace_recursive( $filter_defaults, $validated_filters );

		// flag for including all post types is set, so remove that from the filters.
		if ( $flags & self::FLAG_INCLUDE_ALL_POST_TYPES ) {
			unset( $filter['post_types'] );
		}

		$this->add_filters( $filter );

		if ( empty( $filter['post_types'] ) && false === ( $flags & self::FLAG_INCLUDE_ALL_POST_TYPES ) ) {
			// no post_types were pass in, but the flag for including all post types is not set.
			$this->query['filters'] .= ' and 1!=1'; // forces false so no results are returned.
		}

		// Setup LIMIT.
		$this->query['limit'] = $wpdb->prepare( 'LIMIT %d', [ $record_limit ] );
	}


	/**
	 * Returns the prepared (safe) sql to be used for the query
	 *
	 * @return string $sql .
	 */
	public function get_sql() {
		return $this->query['select'] . ' ' . $this->query['from'] . ' ' . $this->query['where_base'] . ' ' . $this->query['filters'] . ' ' . $this->query['limit'];
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
	 * Are the results from all the issues or did we truncate
	 *
	 * @return boolean
	 */
	public function has_truncated_results() {
		global $wpdb;

		$sql = 'SELECT COUNT(*) ' . $this->query['from'];

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_issues_count = $wpdb->get_var( $sql );
		if ( $total_issues_count > $this->record_limit ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets issue count.
	 *
	 * @return integer issue_count .
	 */
	public function count() {

		global $wpdb;

		$this->query['select'] = 'SELECT COUNT(id) ';

		$sql = $this->get_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql );
	}

	/**
	 * Gets distinct issue count.
	 *
	 * @return integer issue_count .
	 */
	public function distinct_count() {

		global $wpdb;

		$this->query['select'] = 'SELECT COUNT( DISTINCT rule, object ) ';

		$sql = $this->get_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql );
	}

	/**
	 * Gets distinct posts count.
	 *
	 * @return integer posts_count .
	 */
	public function distinct_posts_count() {

		global $wpdb;

		$this->query['select'] = 'SELECT COUNT( DISTINCT postid ) ';

		$sql = $this->get_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql );
	}

	/**
	 * Get the ids of the issues.
	 *
	 * @return array issues .
	 */
	public function get_ids() {

		global $wpdb;

		$this->query['select'] = 'SELECT id';

		$sql = $this->get_sql();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $sql );
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
			$post_types           = array_intersect( $filter['post_types'], $scannable_post_types );
			if ( count( $post_types ) ) {
				$this->query['filters'] .= ' and type IN (' . Helpers::array_to_sql_safe_list( $post_types ) . ') ';
			}
		}

		if ( array_key_exists( 'rule_types', $filter ) && ! empty( $filter['rule_types'] ) ) {

			// Special handler for color contrast rule/ruletype.
			if ( in_array( self::RULETYPE_COLOR_CONTRAST, $filter['rule_types'], true ) ) {

				// We are filtering by color contrast but color contrast is a rule not a ruletype.
				// Remove color_contrast from the rule_type filter.
				$key = array_search( self::RULETYPE_COLOR_CONTRAST, $filter['rule_types'], true );
				if ( false !== $key ) {
					unset( $filter['rule_types'][ $key ] );
				}

				// Then add color_contrast_failure to the rule_slugs filter.
				$key = array_search( 'color_contrast_failure', $filter['rule_slugs'], true );
				if ( ! $key ) {
					$filter['rule_slugs'][] = 'color_contrast_failure';
				}
			}

			if ( ! empty( $filter['rule_types'] ) ) {
				$this->query['filters'] .= ' and ruletype IN (' . Helpers::array_to_sql_safe_list( $filter['rule_types'] ) . ') ';
			}
		}

		if ( array_key_exists( 'rule_slugs', $filter ) && ! empty( $filter['rule_slugs'] ) ) {
			$this->query['filters'] .= ' and rule IN (' . Helpers::array_to_sql_safe_list( $filter['rule_slugs'] ) . ') ';
		}
	}
}
