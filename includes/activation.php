<?php

function edac_activation(){
	
	// set options
	add_option( 'edac_db_version', EDAC_DB_VERSION );
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