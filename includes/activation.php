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
	update_option( 'edac_do_activation_redirect', true );

	Accessibility_Statement::add_page();

	// This is an add_option on purpose to not overwrite user settings on update.
	add_option( 'edacp_ignore_user_roles', [ 'administrator' ] );
}

/**
 * Redirect to the welcome page after a single plugin activation.
 *
 * @return void
 */
function edac_redirect_after_activation() {

	// Only redirect right after activation.
	if ( ! get_option( 'edac_do_activation_redirect' ) ) {
		return;
	}

	// Delete the flag so the redirect only runs once.
	delete_option( 'edac_do_activation_redirect' );

	// Do not redirect during bulk activations or other non-admin contexts.
	if ( isset( $_GET['activate-multi'] ) || ! is_admin() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Query string checked for redirects only.
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=accessibility_checker' ) );
	exit;
}

add_action( 'admin_init', 'edac_redirect_after_activation' );
