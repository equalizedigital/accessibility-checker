<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// check if the delte data option is checked. If not, don't delete data.
$delete_data = get_option( 'edac_delete_data' );
if ( true === (bool) $delete_data ) {

	// drop database.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Using direct query for table drop in uninstall script, caching not required for one time operation.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'accessibility_checker' ) );

	// delete options.
	$options = [
		'edac_db_version',
		'edac_activation_date',
		'edac_simplified_summary_position',
		'edac_post_types',
		'edac_add_footer_accessibility_statement',
		'edac_accessibility_policy_page',
		'edac_delete_data',
		'edac_review_notice',
		'edac_authorization_password',
		'edac_authorization_username',
		'edac_gaad_notice_dismiss',
		'edac_black_friday_2023_notice_dismiss',
	];
	if ( $options ) {
		foreach ( $options as $option ) {
			delete_option( $option );
			delete_site_option( $option );
		}
	}
}
