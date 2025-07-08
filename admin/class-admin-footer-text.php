<?php
/**
 * Filters the admin_footer_text on Accessibility Checker settings pages only.
 *
 * @package Accessibility_Checker\Admin
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Admin_Footer_Text
 *
 * Handles filtering of admin footer text on Accessibility Checker settings pages.
 *
 * @since 1.27.0
 */
class Admin_Footer_Text {
	/**
	 * Initialize the admin footer text filter.
	 *
	 * @since 1.27.0
	 */
	public function init() {
		add_filter( 'admin_footer_text', [ $this, 'filter_footer_text' ] );
	}

	/**
	 * Filter the admin footer text on our settings pages only.
	 *
	 * @since 1.27.0
	 * @param string $footer_text The current footer text.
	 * @return string
	 */
	public function filter_footer_text( $footer_text ) {
		if ( ! $this->is_settings_page() ) {
			return $footer_text;
		}

		if ( $this->is_pro_active() ) {
			return sprintf(
				/* translators: %1$s: opening link tag, %2$s: closing link tag */
				esc_html__( 'Enjoying Accessibility Checker? %1$sPlease leave us a ★★★★★ rating.%2$s We really appreciate your support!', 'accessibility-checker' ),
				'<a href="' . esc_url( 'https://wordpress.org/support/plugin/accessibility-checker/reviews/#new-post' ) . '" target="_blank" aria-label="' . esc_attr__( 'Please leave us a five star rating (opens in new window)', 'accessibility-checker' ) . '">',
				'</a>'
			);
		}

		$pro_link = edac_link_wrapper( 'https://equalizedigital.com/accessibility-checker/pricing/', 'admin-footer', 'admin-footer-text', false );
		return sprintf(
			/* translators: %1$s: opening link tag, %2$s: closing link tag */
			esc_html__( 'Want to do more with Accessibility Checker? %1$sUnlock Pro Features%2$s', 'accessibility-checker' ),
			'<a href="' . esc_url( $pro_link ) . '" target="_blank" aria-label="' . esc_attr__( 'Unlock Pro Features (opens in new window)', 'accessibility-checker' ) . '">',
			'</a>'
		);
	}

	/**
	 * Check if we are on the Accessibility Checker settings page.
	 *
	 * @since 1.27.0
	 * @return bool
	 */
	protected function is_settings_page() {
		// Check if we're on an admin page and have a page parameter.
		if ( ! is_admin() || ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		// Sanitize and check if it's an accessibility checker page.
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return strpos( $page, 'accessibility_checker' ) === 0;
	}

	/**
	 * Check if Pro is active with a valid license.
	 *
	 * @since 1.27.0
	 * @return bool
	 */
	protected function is_pro_active() {
		// Pro is active if EDACP_VERSION is defined and license is valid.
		return defined( 'EDACP_VERSION' ) && defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID;
	}
}
