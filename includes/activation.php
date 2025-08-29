<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Accessibility_Statement;

/**
 * Activation
 *
 * @return void
 */
function edac_activation() {
	// set options.
	update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s' ) );
	update_option( 'edac_post_types', [ 'post', 'page' ] );
	update_option( 'edac_simplified_summary_position', 'after' );
	update_option( 'edac_version', EDAC_VERSION );

	Accessibility_Statement::add_page();

	// This is an add_option on purpose to not overwrite user settings on update.
	add_option( 'edacp_ignore_user_roles', [ 'administrator' ] );
}
