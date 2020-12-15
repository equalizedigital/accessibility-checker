<?php

function edac_activation(){
	global $wpdb;
	$plugin_name_db_version = '1.0';
	$table_name = $wpdb->prefix . "accessibility_checker"; 
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
			  id bigint(20) NOT NULL AUTO_INCREMENT,
			  postid bigint(20) NOT NULL,
			  siteid text NOT NULL,
			  type text NOT NULL,
			  rule text NOT NULL,
			  ruletype text NOT NULL,
			  object mediumtext NOT NULL,
			  recordcheck mediumint(9) NOT NULL,
			  created timestamp NOT NULL default CURRENT_TIMESTAMP,
			  user bigint(20) NOT NULL,
			  ignre mediumint(9) NOT NULL,
			  ignre_user bigint(20) NULL,
			  ignre_date timestamp NULL,
			  ignre_comment mediumtext NULL,
			  UNIQUE KEY id (id)
			) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	
	// set options
	add_option( 'edac_db_version', $plugin_name_db_version );
	add_option( 'edac_activation_date', date('Y-m-d H:i:s') );
	add_option( 'edac_post_types', ['post','page']);
	add_option( 'edac_simplified_summary_position', 'after');
	

	// Redirect: Don't do redirects when multiple plugins are bulk activated
	if (
		( isset( $_REQUEST['action'] ) && 'activate-selected' === $_REQUEST['action'] ) &&
		( isset( $_POST['checked'] ) && count( $_POST['checked'] ) > 1 ) ) {
		return;
	}
	
}