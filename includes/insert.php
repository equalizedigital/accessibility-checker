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
 * 
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that there isn't any ignore data to return
 */
function edac_insert_ignore_data(){

	// nonce security
	if ( !isset( $_REQUEST['nonce'] ) || !wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
			
		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . "accessibility_checker";
	$ids = $_REQUEST['ids'];
	$action = esc_html($_REQUEST['ignore_action']);
	$type = esc_html($_REQUEST['ignore_type']);
	$siteid = get_current_blog_id();
	$ignre = ($action == 'enable') ? 1 : 0 ;
	$ignre_user = ($action == 'enable') ? get_current_user_id() : NULL;
	$ignre_user_info = ($action == 'enable') ? get_userdata($ignre_user) : '';
	$ignre_username = ($action == 'enable') ? $ignre_user_info->user_login : '';
	$ignre_date = ($action == 'enable') ? date('Y-m-d H:i:s') : NULL;
	$ignre_date_formatted = ($action == 'enable') ? date("F j, Y g:i a", strtotime($ignre_date)) : '';
	$ignre_comment = ($action == 'enable') ? esc_html($_REQUEST['comment']) : NULL;
	$ignore_global = ($action == 'enable' && isset($_REQUEST['ignore_global'])) ? esc_html($_REQUEST['ignore_global']) : 0;

	foreach ($ids as $id) {
		$wpdb->query( $wpdb->prepare( 'UPDATE '.$table_name.' SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d and id = %d', $ignre, $ignre_user, $ignre_date, $ignre_comment, $ignore_global, $siteid, $id) );
	}

	$data = ['ids' => $ids, 'action' => $action, 'type' => $type, 'user' => $ignre_username, 'date' => $ignre_date_formatted];

	if( !$data ){

		$error = new WP_Error( '-2', 'No ignore data to return' );
		wp_send_json_error( $error );
	
	}
	
	wp_send_json_success( json_encode($data) );

}