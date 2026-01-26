<?php
/**
 * Activity Log Logger class.
 *
 * @package Accessibility_Checker
 * @since 1.36.0
 */

namespace EqualizeDigital\AccessibilityChecker\ActivityLog;

/**
 * Logger class for recording activity in the plugin.
 *
 * @since 1.36.0
 */
class Logger {

	/**
	 * Log an activity.
	 *
	 * @since 1.36.0
	 *
	 * @param string $action      The action type (e.g., 'post_scan', 'ignore_issue', 'clear_issues').
	 * @param string $message     Plain text message describing the activity.
	 * @param array  $metadata    {
	 *     Optional. Additional metadata for the log entry.
	 *
	 *     @type int    $post_id     Optional. Post ID related to the action.
	 *     @type int    $issue_id    Optional. Issue ID related to the action.
	 *     @type string $rule_type   Optional. Rule type (e.g., 'error', 'warning').
	 * }
	 * @return int|false The log entry ID on success, false on failure.
	 */
	public static function log( string $action, string $message, array $metadata = [] ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'accessibility_checker_activity_log';
		$user_id    = get_current_user_id();
		$siteid     = get_current_blog_id();

		// Prepare data for insertion.
		$data = [
			'user_id'   => $user_id,
			'created'   => current_time( 'mysql', true ),
			'action'    => sanitize_text_field( $action ),
			'message'   => sanitize_text_field( $message ),
			'siteid'    => $siteid,
			'post_id'   => isset( $metadata['post_id'] ) ? absint( $metadata['post_id'] ) : null,
			'issue_id'  => isset( $metadata['issue_id'] ) ? absint( $metadata['issue_id'] ) : null,
			'rule_type' => isset( $metadata['rule_type'] ) ? sanitize_text_field( $metadata['rule_type'] ) : null,
		];

		$format = [
			'%d', // user_id.
			'%s', // created.
			'%s', // action.
			'%s', // message.
			'%d', // siteid.
			'%d', // post_id.
			'%d', // issue_id.
			'%s', // rule_type.
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Logging to custom table.
		$result = $wpdb->insert( $table_name, $data, $format );

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get activity log entries.
	 *
	 * @since 1.36.0
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type int    $limit      Maximum number of entries to retrieve. Default 100.
	 *     @type int    $offset     Number of entries to skip. Default 0.
	 *     @type int    $user_id    Filter by user ID.
	 *     @type string $action     Filter by action type.
	 *     @type int    $post_id    Filter by post ID.
	 *     @type string $order      Sort order (ASC or DESC). Default DESC.
	 * }
	 * @return array Array of log entries.
	 */
	public static function get_logs( array $args = [] ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'accessibility_checker_activity_log';
		$defaults   = [
			'limit'  => 100,
			'offset' => 0,
			'order'  => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = [];
		$where_values  = [];

		// Add siteid filter.
		$where_clauses[] = 'siteid = %d';
		$where_values[]  = get_current_blog_id();

		// Filter by user_id.
		if ( isset( $args['user_id'] ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[]  = absint( $args['user_id'] );
		}

		// Filter by action.
		if ( isset( $args['action'] ) ) {
			$where_clauses[] = 'action = %s';
			$where_values[]  = sanitize_text_field( $args['action'] );
		}

		// Filter by post_id.
		if ( isset( $args['post_id'] ) ) {
			$where_clauses[] = 'post_id = %d';
			$where_values[]  = absint( $args['post_id'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$order     = in_array( strtoupper( $args['order'] ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $args['order'] ) : 'DESC';
		$limit     = absint( $args['limit'] );
		$offset    = absint( $args['offset'] );

		// Prepare the query with all values.
		$prepare_args = array_merge(
			[ $table_name ],
			$where_values,
			[ $limit, $offset ]
		);

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Using spread operator for dynamic number of replacements.
		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifier is safe, WHERE clause is prepared.
			"SELECT * FROM %i WHERE {$where_sql} ORDER BY created {$order} LIMIT %d OFFSET %d",
			...$prepare_args
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Custom table with prepared query.
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ? $results : [];
	}
}
