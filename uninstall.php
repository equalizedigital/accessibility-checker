<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// check if the delte data option is checked. If not, don't delete data.
$delete_data = get_option('edac_delete_data');
if($delete_data == true){

    // drop database
    global $wpdb;
    $table_name = $wpdb->prefix . "accessibility_checker";
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

    // delete options
    $options = [
        'edac_db_version',
        'edac_activation_date',
        'edac_simplified_summary_position',
        'edac_post_types',
        'edac_add_footer_accessibility_statement',
        'edac_accessibility_policy_page',
        'edac_delete_data',
        'edac_anww_update_post_meta',
        'edac_review_notice'
    ];
    if($options){
        foreach ($options as $option){
            delete_option($option);
            delete_site_option($option);
        }
    }

}