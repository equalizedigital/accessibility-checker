<?php

function edac_uninstall(){

	// drop database
	global $wpdb;
	$table_name = $wpdb->prefix . "accessibility_checker";
	$sql = "DROP TABLE IF EXISTS $table_name";
	$wpdb->query($sql);

	// delete options
	$options = ['edac_db_version','edac_activation_date','edac_simplified_summary_position','edac_post_types','edac_add_footer_accessibility_statement','edac_accessibility_policy_page'];
	if($options){
		foreach ($options as $option){
			delete_option($option);
			delete_site_option($option);
		}
	}

}