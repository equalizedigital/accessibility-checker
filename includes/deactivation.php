<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Orphaned_Issues_Cleanup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation
 *
 * @return void
 */
function edac_deactivation() {
	delete_option( 'edac_activation_date' );

	// Unschedule cleanup of orphaned issues.
	Orphaned_Issues_Cleanup::unschedule_event();

	// Unschedule the daily license check cron event.
	wp_clear_scheduled_hook( 'edac_check_license_hook' );

	// Remove manual issues capabilities added on activation.
	$manual_caps = [
		'edac_create_manual_issues',
		'edac_edit_manual_issues',
		'edac_delete_manual_issues',
	];
	foreach ( [ 'administrator', 'editor' ] as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $manual_caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
