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

	// New installs and existing sites that deactivate/reactivate both default to
	// showing the metabox in the block editor.
	add_option( 'edac_show_metabox_in_block_editor', '1' );

	// Set transient to trigger redirect to welcome page.
	// This will be checked on admin_init and deleted after redirect.
	set_transient( 'edac_activation_redirect', true, 60 );

	// Grant manual issues capabilities to administrator and editor.
	$manual_caps = [
		'edac_create_manual_issues',
		'edac_edit_manual_issues',
		'edac_delete_manual_issues',
	];
	foreach ( [ 'administrator', 'editor' ] as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $manual_caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}
}
