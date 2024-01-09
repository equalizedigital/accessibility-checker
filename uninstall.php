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

	\EDAC\Admin\Options::delete_all( true );
}
