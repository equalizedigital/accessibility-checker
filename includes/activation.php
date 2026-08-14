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
	// Detect a genuinely first install BEFORE writing edac_activation_date below:
	// an existing site (reactivation, or one that pre-dates the capability system)
	// already has this option, and must not be treated as fresh.
	$edac_is_fresh_install = ! get_option( 'edac_activation_date' );

	// set options.
	update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s' ) );
	update_option( 'edac_post_types', [ 'post', 'page' ] );
	update_option( 'edac_simplified_summary_position', 'after' );

	Accessibility_Statement::add_page();

	// New installs and existing sites that deactivate/reactivate both default to
	// showing the metabox in the block editor.
	add_option( 'edac_show_metabox_in_block_editor', '1' );

	// Seed the capability defaults for a fresh install only. An existing site is
	// left to the version-gated migration (SyncCapability::reconcile()), so it is
	// never handed fresh-install default grants for capabilities its legacy
	// configuration never governed. The legacy edacp_ignore_user_roles option is
	// deliberately NOT seeded here anymore: seeding it to ['administrator'] made
	// every fresh install look like a migrating site and skip its defaults.
	if ( $edac_is_fresh_install && function_exists( 'edac_seed_capability_defaults_on_install' ) ) {
		edac_seed_capability_defaults_on_install();
	}

	// Set transient to trigger redirect to welcome page.
	// This will be checked on admin_init and deleted after redirect.
	set_transient( 'edac_activation_redirect', true, 60 );
}
