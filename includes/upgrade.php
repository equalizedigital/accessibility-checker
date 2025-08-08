<?php
/**
 * Upgrade routines for Accessibility Checker.
 *
 * @package Accessibility_Checker
 */

/**
 * Run upgrade routines when plugin version changes.
 *
 * @return void
 */
function edac_upgrade() {
	$installed_version = get_option( 'edac_version', '0' );

	if ( version_compare( $installed_version, EDAC_VERSION, '<' ) ) {
		// Unschedule legacy orphaned issues cleanup event.
		$timestamp = wp_next_scheduled( 'edac_cleanup_orphaned_issues' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'edac_cleanup_orphaned_issues' );
		}

		update_option( 'edac_version', EDAC_VERSION );
	}
}
add_action( 'plugins_loaded', 'edac_upgrade' );
