<?php

/**
 * Insert rule date into database
 *
 * @param array $post
 * @param string $rule
 * @param string $ruletype
 * @param string $object
 * @return void
 */
function edac_insert_rule_data($post, $rule, $ruletype, $object){

	global $wpdb;
	$table_name = $wpdb->prefix . "accessibility_checker";

	// set up rule data array
	$rule_data = [
		'postid' => $post->ID,
		'siteid' => get_current_blog_id(),
		'type' => $post->post_type,
		'rule' => $rule,
		'ruletype' => $ruletype,
		'object' => esc_attr($object),
		'recordcheck' => 1,
		'user' => get_current_user_id(),
		'ignre' => 0,
		'ignre_user' => null,
		'ignre_date' => null,
		'ignre_comment' => null,
		'ignre_global' => 0,
	];

	// return if revision
	if($rule_data['type'] == 'revision'){
		return;
	}
	
	// Check if exists
	$results = $wpdb->get_results( 
		$wpdb->prepare( 
			'SELECT postid, ignre FROM '.$table_name.' where type = %s and postid = %d and rule = %s and object = %s and siteid = %d', $rule_data['type'], $rule_data['postid'], $rule_data['rule'], $rule_data['object'], $rule_data['siteid']
		), ARRAY_A 
	);	

	// Loop existing records
	if($results){
		foreach ($results as $row){

			// if being ignored, don't overwrite value
			if($row['ignre'] == 1) $rule_data['ignre'] = 1;

			// update existing record
			$wpdb->query( 
				$wpdb->prepare( 
					'UPDATE '.$table_name.' SET recordcheck = %d, ignre = %d  WHERE siteid = %d and postid = %d and rule = %s and object = %s and type = %s', 1, $rule_data['ignre'], $rule_data['siteid'], $rule_data['postid'], $rule_data['rule'], $rule_data['object'], $rule_data['type']
				) 
			);

		}
	}

	// Insert new records
	if(!$results){

		// filter post types
		if(has_filter('edac_filter_insert_rule_data')) {
			$rule_data = apply_filters('edac_filter_insert_rule_data', $rule_data);
		}

		// insert
		$wpdb->insert($table_name, $rule_data);

		// Return insert id or error
		return $wpdb->insert_id;
	}

}

/**
 * Insert ignore data into database
 *
 * @return void
 */
function edac_insert_ignore_data(){
	
	global $wpdb;
	$table_name = $wpdb->prefix . "accessibility_checker";
	$id = intval($_REQUEST['id']);
	$action = esc_html($_REQUEST['ignore_action']);
	$type = esc_html($_REQUEST['ignore_type']);
	$siteid = get_current_blog_id();
	$ignre_user = get_current_user_id();
	$ignre_user_info = ($action == 'enable') ? get_userdata($ignre_user) : '';
	$ignre_username = ($action == 'enable') ? $ignre_user_info->user_login : '';
	$ignre_date = ($action == 'enable') ? date('Y-m-d H:i:s') : '';
	$ignre_date_formatted = ($action == 'enable') ? date("F j, Y g:i a", strtotime($ignre_date)) : '';
	$ignre_comment = $_REQUEST['comment'] ? esc_html($_REQUEST['comment']) : '';
	$ignore_global = isset($_REQUEST['ignore_global']) ? esc_html($_REQUEST['ignore_global']) : 0;

	if($action == 'enable'){

		$wpdb->query( $wpdb->prepare( 'UPDATE '.$table_name.' SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d and id = %d', 1, $ignre_user, $ignre_date, $ignre_comment, $ignore_global, $siteid, $id) );

	}elseif($action == 'disable'){

		$wpdb->query( $wpdb->prepare( 'UPDATE '.$table_name.' SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d and id = %d', 0, NULL, NULL, NULL, 0, $siteid, $id) );

	}

	print json_encode(['id' => $id, 'action' => $action, 'type' => $type, 'user' => $ignre_username, 'date' => $ignre_date_formatted, 'ignore_global' => $ignore_global]);
	die();

}