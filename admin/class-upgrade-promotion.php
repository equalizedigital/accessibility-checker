<?php
/**
 * Simple upgrade promotion menu item.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

/**
 * Class that handles adding an upgrade promotion menu item.
 *
 * @since 1.27.0
 */
class Upgrade_Promotion {

	/**
	 * The upgrade URL for the pricing page.
	 *
	 * @since 1.27.0
	 */
	private const UPGRADE_URL = 'https://equalizedigital.com/accessibility-checker/pricing/';

	/**
	 * Initialize the upgrade promotion.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_item' ], 999 );
		add_action( 'admin_head', [ $this, 'add_menu_styling' ] );
		add_action( 'admin_init', [ $this, 'maybe_handle_redirect' ] );
	}

	/**
	 * Add the upgrade menu item.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function add_menu_item(): void {
		$required_capability = apply_filters( 'edac_filter_settings_capability', 'manage_options' );
		
		// Only show to users who can manage options and don't have pro version.
		if ( ! current_user_can( $required_capability ) ) {
			return;
		}

		// Check if pro version is active.
		if ( $this->is_pro_active() ) {
			return;
		}

		// Determine menu label based on sale status.
		$menu_label = __( 'Upgrade to Pro', 'accessibility-checker' );
		if ( $this->is_sale_time() ) {
			$menu_label = __( 'Upgrade Sale Now', 'accessibility-checker' );
		}

		add_submenu_page(
			'accessibility_checker',
			'',
			$menu_label,
			$required_capability,
			'accessibility_checker_upgrade',
			[ $this, 'dummy_page_callback' ]
		);
	}

	/**
	 * Dummy page callback (redirect happens in admin_init).
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function dummy_page_callback(): void {
		// This should never be reached due to redirect in admin_init.
		wp_die( esc_html__( 'Unable to redirect to upgrade page.', 'accessibility-checker' ) );
	}

	/**
	 * Check if we should handle redirect and do it early.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function maybe_handle_redirect(): void {
		// Check if we're on the upgrade page.
		if ( ! isset( $_GET['page'] ) || 'accessibility_checker_upgrade' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a simple page check for redirect, no form processing.
			return;
		}

		$required_capability = apply_filters( 'edac_filter_settings_capability', 'manage_options' );
		
		// Only redirect if user has capability and pro is not active.
		if ( ! current_user_can( $required_capability ) || $this->is_pro_active() ) {
			return;
		}

		$upgrade_url = edac_link_wrapper( 
			self::UPGRADE_URL,
			'admin-menu-promotion',
			'menu-upgrade-to-pro',
			false
		);
		
		// Add the domain to allowed hosts before redirecting.
		add_filter( 'allowed_redirect_hosts', [ $this, 'allow_redirect_host' ] );
		if ( wp_safe_redirect( $upgrade_url ) ) {
			exit;
		}
	}

	/**
	 * Allow redirects to the Equalize Digital domain.
	 *
	 * @since 1.27.0
	 *
	 * @param array $hosts Array of allowed hosts.
	 * @return array Modified array of allowed hosts.
	 */
	public function allow_redirect_host( array $hosts ): array {
		$hosts[] = 'equalizedigital.com';
		return $hosts;
	}

	/**
	 * Add CSS styling to make the upgrade menu item stand out.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function add_menu_styling(): void {
		// Only add styling on accessibility checker admin pages.
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'accessibility_checker' ) ) {
			return;
		}

		$required_capability = apply_filters( 'edac_filter_settings_capability', 'manage_options' );
		
		// Only add styling if user can see the menu and pro is not active.
		if ( ! current_user_can( $required_capability ) ) {
			return;
		}

		if ( $this->is_pro_active() ) {
			return;
		}

		?>
		<style type="text/css">
			/* Style the upgrade promotion menu item - WCAG AAA compliant colors */
			#adminmenu .wp-submenu a[href$="accessibility_checker_upgrade"] {
				color: #f3cd1e !important;
				font-weight: 600 !important;
			}
			
			#adminmenu .wp-submenu a[href$="accessibility_checker_upgrade"]:hover,
			#adminmenu .wp-submenu a[href$="accessibility_checker_upgrade"]:focus {
				color: #f3cd1e !important;
				background-color: rgba(243, 205, 30, 0.15) !important;
			}
		</style>
		<?php
	}

	/**
	 * Check if the pro version is active.
	 *
	 * @since 1.27.0
	 *
	 * @return bool
	 */
	private function is_pro_active(): bool {
		return defined( 'EDACP_VERSION' ) && defined( 'EDAC_KEY_VALID' ) && (bool) EDAC_KEY_VALID;
	}

	/**
	 * Check if it's currently sale time.
	 *
	 * @since 1.27.0
	 *
	 * @return bool
	 */
	private function is_sale_time(): bool {
		/**
		 * Filter whether it's currently sale time for upgrade promotions.
		 *
		 * This filter allows you to control when the upgrade menu item displays
		 * "Upgrade Sale Now" instead of "Upgrade to Pro".
		 *
		 * @since 1.27.0
		 *
		 * @param bool $is_sale_time Whether it's currently sale time. Default false.
		 */
		return apply_filters( 'edac_is_sale_time', false );
	}
}
