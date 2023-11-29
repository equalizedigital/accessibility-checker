<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Insert rule date into database
 *
 * @param object $post the post object.
 * @param string $rule the rule.
 * @param string $ruletype the rule type.
 * @param string $object the object.
 * @return void
 */
function edac_insert_rule_data( $post, $rule, $ruletype, $object ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound -- Object is a valid parameter name in this context.

	global $wpdb;
	$table_name = $wpdb->prefix . 'accessibility_checker';

	// set up rule data array.
	$rule_data = array(
		'postid'        => $post->ID,
		'siteid'        => get_current_blog_id(),
		'type'          => $post->post_type,
		'rule'          => $rule,
		'ruletype'      => $ruletype,
		'object'        => esc_attr( $object ),
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
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Safe variable used for table name.
			'SELECT postid, ignre FROM ' . $table_name . ' where type = %s and postid = %d and rule = %s and object = %s and siteid = %d',
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
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Safe variable used for table name.
					'UPDATE ' . $table_name . ' SET recordcheck = %d, ignre = %d  WHERE siteid = %d and postid = %d and rule = %s and object = %s and type = %s',
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
		if ( has_filter( 'edac_filter_insert_rule_data' ) ) {
			$rule_data = apply_filters( 'edac_filter_insert_rule_data', $rule_data );
		}

		// insert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using direct query for adding data to database.
		$wpdb->insert( $table_name, $rule_data );

		// Return insert id or error.
		return $wpdb->insert_id;
	}
}

/**
 * Insert ignore data into database
 *
 * @return void
 *
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that there isn't any ignore data to return
 */
function edac_insert_ignore_data() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	global $wpdb;
	$table_name           = $wpdb->prefix . 'accessibility_checker';
	$raw_ids              = isset( $_REQUEST['ids'] ) ? $_REQUEST['ids'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled below.
	$ids                  = array_map( 'intval', $raw_ids ); // Sanitizing array elements to integers.
	$action               = isset( $_REQUEST['ignore_action'] ) ? sanitize_text_field( $_REQUEST['ignore_action'] ) : '';
	$type                 = isset( $_REQUEST['ignore_type'] ) ? sanitize_text_field( $_REQUEST['ignore_type'] ) : '';
	$siteid               = get_current_blog_id();
	$ignre                = ( 'enable' === $action ) ? 1 : 0;
	$ignre_user           = ( 'enable' === $action ) ? get_current_user_id() : null;
	$ignre_user_info      = ( 'enable' === $action ) ? get_userdata( $ignre_user ) : '';
	$ignre_username       = ( 'enable' === $action ) ? $ignre_user_info->user_login : '';
	$ignre_date           = ( 'enable' === $action ) ? gmdate( 'Y-m-d H:i:s' ) : null;
	$ignre_date_formatted = ( 'enable' === $action ) ? gmdate( 'F j, Y g:i a', strtotime( $ignre_date ) ) : '';
	$ignre_comment        = ( 'enable' === $action && isset( $_REQUEST['comment'] ) ) ? sanitize_textarea_field( $_REQUEST['comment'] ) : null;
	$ignore_global        = ( 'enable' === $action && isset( $_REQUEST['ignore_global'] ) ) ? sanitize_textarea_field( $_REQUEST['ignore_global'] ) : 0;

	foreach ( $ids as $id ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . $table_name . ' SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d and id = %d', $ignre, $ignre_user, $ignre_date, $ignre_comment, $ignore_global, $siteid, $id ) );
	}

	$data = array(
		'ids'    => $ids,
		'action' => $action,
		'type'   => $type,
		'user'   => $ignre_username,
		'date'   => $ignre_date_formatted,
	);

	if ( ! $data ) {

		$error = new WP_Error( '-2', 'No ignore data to return' );
		wp_send_json_error( $error );

	}
	wp_send_json_success( wp_json_encode( $data ) );
}
