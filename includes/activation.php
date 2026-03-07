<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Accessibility_Statement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	Accessibility_Statement::add_page();

	// This is an add_option on purpose to not overwrite user settings on update.
	add_option( 'edacp_ignore_user_roles', [ 'administrator' ] );

	// New installs default to hiding the legacy metabox in the block editor.
	// Existing sites that deactivate/reactivate preserve the visible (legacy) behavior
	// by defaulting to '1', since they may never have had this option written.
	$show_metabox_default = get_option( 'edac_db_version' ) ? '1' : '0';
	add_option( 'edac_show_metabox_in_block_editor', $show_metabox_default );

	// Set transient to trigger redirect to welcome page.
	// This will be checked on admin_init and deleted after redirect.
	set_transient( 'edac_activation_redirect', true, 60 );
}
