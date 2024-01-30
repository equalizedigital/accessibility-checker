<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Insert rule date into database
 *
 * @param object $post     The post object.
 * @param string $rule     The rule.
 * @param string $ruletype The rule type.
 * @param string $rule_obj The object.
 * @return void
 */
function edac_insert_rule_data( $post, $rule, $ruletype, $rule_obj ) {

	global $wpdb;
	$table_name = $wpdb->prefix . 'accessibility_checker';

	// set up rule data array.
	$rule_data = array(
		'postid'        => $post->ID,
		'siteid'        => get_current_blog_id(),
		'type'          => $post->post_type,
		'rule'          => $rule,
		'ruletype'      => $ruletype,
		'object'        => esc_attr( $rule_obj ),
		'recordcheck'   => 1,
		'user'          => get_current_user_id(),
		'ignre'         => 0,
		'ignre_user'    => null,
		'ignre_date'    => null,
		'ignre_comment' => null,
		'ignre_global'  => 0,
	);

	// return if revision.
	if ( 'revision' === $rule_data['type'] ) {
		return;
	}

	// Check if exists.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
	$results = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT postid, ignre FROM %i where type = %s and postid = %d and rule = %s and object = %s and siteid = %d',
			$table_name,
			$rule_data['type'],
			$rule_data['postid'],
			$rule_data['rule'],
			$rule_data['object'],
			$rule_data['siteid']
		),
		ARRAY_A
	);

	// Loop existing records.
	if ( $results ) {
		foreach ( $results as $row ) {

			// if being ignored, don't overwrite value.
			if ( true === (bool) $row['ignre'] ) {
				$rule_data['ignre'] = 1;
			}

			// update existing record.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE %i SET recordcheck = %d, ignre = %d  WHERE siteid = %d and postid = %d and rule = %s and object = %s and type = %s',
					$table_name,
					1,
					$rule_data['ignre'],
					$rule_data['siteid'],
					$rule_data['postid'],
					$rule_data['rule'],
					$rule_data['object'],
					$rule_data['type']
				)
			);

		}
	}

	// Insert new records.
	if ( ! $results ) {

		// filter post types.
		$rule_data = apply_filters( 'edac_filter_insert_rule_data', $rule_data );

		// insert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using direct query for adding data to database.
		$wpdb->insert( $table_name, $rule_data );

		// Return insert id or error.
		return $wpdb->insert_id;
	}
}
