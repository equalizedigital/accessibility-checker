<?php
/**
 * Handles plugin activation redirect to welcome page.
 *
 * @package Accessibility_Checker\Admin
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Activation_Redirect
 *
 * Handles redirecting to the welcome page after plugin activation.
 *
 * @since 1.36.0
 */
class Activation_Redirect {

	/**
	 * The page slug for the welcome page.
	 *
	 * @since 1.36.0
	 * @var string
	 */
	const WELCOME_PAGE_SLUG = 'accessibility_checker';

	/**
	 * Initialize the activation redirect.
	 *
	 * @since 1.36.0
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'maybe_redirect_to_welcome' ] );
	}

	/**
	 * Get the welcome page URL.
	 *
	 * @since 1.36.0
	 * @return string The URL to the welcome page.
	 */
	public function get_welcome_page_url(): string {
		return admin_url( 'admin.php?page=' . self::WELCOME_PAGE_SLUG );
	}

	/**
	 * Redirect to welcome page after activation if conditions are met.
	 *
	 * This will only redirect if:
	 * - The activation redirect transient is set
	 * - We're not doing an AJAX request
	 * - We're not in the network admin (multisite)
	 * - We're not activating multiple plugins at once
	 * - User has permission to access the welcome page
	 *
	 * @since 1.36.0
	 * @return void
	 */
	public function maybe_redirect_to_welcome(): void {
		// Check if the activation redirect transient exists.
		if ( ! get_transient( 'edac_activation_redirect' ) ) {
			return;
		}

		// Don't redirect during AJAX requests.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Don't redirect during REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Don't redirect in network admin (multisite).
		if ( is_network_admin() ) {
			return;
		}

		// Don't redirect if multiple plugins are being activated at once.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We're checking $_GET, not processing form data.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Don't redirect if user doesn't have permission to see the welcome page.
		// Uses 'edit_posts' to match the welcome page's capability check (see includes/options-page.php).
		// This allows Authors and above to access the welcome page, as intended by the plugin design.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Don't redirect if we're in a test environment.
		if ( defined( 'WP_TESTS_DOMAIN' ) ) {
			return;
		}

		// Delete the transient to prevent redirect loops.
		delete_transient( 'edac_activation_redirect' );

		// Perform the redirect to the welcome page.
		wp_safe_redirect( $this->get_welcome_page_url() );
		exit;
	}
}
