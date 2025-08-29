<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Scheduled_Tasks;

/**
 * Deactivation
 *
 * @return void
 */
function edac_deactivation() {
	delete_option( 'edac_activation_date' );

		// Unschedule scheduled tasks.
		Scheduled_Tasks::unschedule_event();
}
