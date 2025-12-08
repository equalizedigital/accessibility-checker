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

	Accessibility_Statement::add_page();

	// This is an add_option on purpose to not overwrite user settings on update.
	add_option( 'edacp_ignore_user_roles', [ 'administrator' ] );

	// Set transient for redirect on activation, but only if not in multi-activation.
	if ( ! edac_is_multi_activation() ) {
		set_transient( 'edac_activation_redirect', 1, 30 );
	}
}

/**
 * Check if this is a multi-plugin activation
 *
 * During bulk activation, WordPress doesn't provide a direct way to detect
 * multi-activation. However, we can check if we're in an AJAX context or
 * if multiple plugins are being activated based on the request.
 *
 * @return bool True if multiple plugins are being activated, false otherwise.
 */
function edac_is_multi_activation() {
	// Check if this is an AJAX request (bulk actions use AJAX).
	if ( wp_doing_ajax() ) {
		return true;
	}

	// Check if we're in the plugins.php page with bulk action.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only check for bulk activation detection.
	if ( isset( $_GET['action'] ) && 'activate-selected' === $_GET['action'] ) {
		return true;
	}

	// Check if multiple plugins are being activated.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only check for bulk activation detection.
	if ( isset( $_GET['activate-multi'] ) ) {
		return true;
	}

	return false;
}

/**
 * Redirect to welcome page after activation
 *
 * This function is hooked to admin_init and checks if the activation
 * redirect transient is set. If so, it redirects to the welcome page.
 *
 * @return void
 */
function edac_activation_redirect() {
	// Check if we should redirect.
	if ( ! get_transient( 'edac_activation_redirect' ) ) {
		return;
	}

	// Delete the transient so we don't redirect again.
	delete_transient( 'edac_activation_redirect' );

	// Don't redirect if activating multiple plugins.
	if ( edac_is_multi_activation() ) {
		return;
	}

	// Don't redirect on AJAX requests.
	if ( wp_doing_ajax() ) {
		return;
	}

	// Don't redirect if user doesn't have permission to view the welcome page.
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	// Perform the redirect.
	wp_safe_redirect( admin_url( 'admin.php?page=accessibility_checker' ) );
	exit;
}
add_action( 'admin_init', 'edac_activation_redirect' );
