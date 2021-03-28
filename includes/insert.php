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

	$postid = $post->ID;
	$ignre = 0; // This will need a function to check if should be ignored
	$siteid = get_current_blog_id();
	$type = $post->post_type;
	$user = get_current_user_id();
	$object = esc_attr($object);

	if($type == 'revision'){
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . "accessibility_checker";
	
	// Check if exists
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT postid, ignre FROM '.$table_name.' where type = %s and postid = %d and rule = %s and object = %s and siteid = %d', $type, $postid, $rule, $object, $siteid), ARRAY_A );	

	// Loop existing records
	if($results){
		foreach ($results as $row){

			// if being ignored, don't overwrite value
			if($row['ignre'] == 1)  $ignre = 1;

			// update existing record
			$wpdb->query( $wpdb->prepare( 'UPDATE '.$table_name.' SET recordcheck = %d, ignre = %d  WHERE siteid = %d and postid = %d and rule = %s and object = %s and type = %s', 1, $ignre, $siteid, $postid, $rule, $object, $type) );

		}
	}

	// Insert new records
	if(!$results){
		$wpdb->insert($table_name, array(
			'postid' => $postid,
			'ignre' => $ignre,
			'siteid' => $siteid,
			'type' => $type,
			'rule' => $rule,
			'ruletype' => $ruletype,
			'object' => $object,
			'recordcheck' => 1,
			'user' => $user,
		));

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

	if($action == 'enable'){

		$wpdb->query( $wpdb->prepare( 'UPDATE '.$table_name.' SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s WHERE siteid = %d and id = %d', 1, $ignre_user, $ignre_date, $ignre_comment, $siteid, $id) );

	}elseif($action == 'disable'){

		$wpdb->query( $wpdb->prepare( 'UPDATE '.$table_name.' SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s WHERE siteid = %d and id = %d', 0, NULL, NULL, NULL, $siteid, $id) );

	}

	$data = ['id' => $id, 'action' => $action, 'type' => $type, 'user' => $ignre_username, 'date' => $ignre_date_formatted];

	if( !$data ){

		$error = new WP_Error( '-2', 'No ignore data to return' );
		wp_send_json_error( $error );
	
	}
	
	wp_send_json_success( json_encode($data) );

}