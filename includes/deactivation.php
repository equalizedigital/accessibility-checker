<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Orphaned_Issues_Cleanup;
use EDAC\Admin\Summary_Update_Scheduler;

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

	// Unschedule summary updates.
	Summary_Update_Scheduler::unschedule_event();
}
