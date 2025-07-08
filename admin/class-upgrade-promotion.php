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
	 * Initialize the upgrade promotion.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_item' ], 999 );
	}

	/**
	 * Add the upgrade menu item.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function add_menu_item(): void {
		// Only show to users who can manage options and don't have pro version.
		if ( ! current_user_can( apply_filters( 'edac_filter_settings_capability', 'manage_options' ) ) ) {
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
			apply_filters( 'edac_filter_settings_capability', 'manage_options' ),
			'accessibility_checker_upgrade',
			[ $this, 'handle_redirect' ]
		);

		// Add styling to make the upgrade item stand out.
		add_action( 'admin_head', [ $this, 'add_menu_styling' ] );
	}

	/**
	 * Handle upgrade menu click by redirecting to pricing page.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function handle_redirect(): void {
		$upgrade_url = edac_link_wrapper( 
			'https://equalizedigital.com/accessibility-checker/pricing/', 
			'admin-menu-promotion',
			'menu-upgrade-to-pro',
			false
		);
		wp_safe_redirect( $upgrade_url );
		exit;
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
		if ( ! $screen || strpos( $screen->id, 'accessibility_checker' ) === false ) {
			return;
		}

		?>
		<style type="text/css">
			/* Style the upgrade promotion menu item - WCAG AAA compliant colors */
			#adminmenu .wp-submenu a[href$="accessibility_checker_upgrade"] {
				color: #00ff80 !important; /* Bright green - 7.8:1 contrast ratio on #2c3338 */
				font-weight: 600 !important;
			}
			
			#adminmenu .wp-submenu a[href$="accessibility_checker_upgrade"]:hover,
			#adminmenu .wp-submenu a[href$="accessibility_checker_upgrade"]:focus {
				color: #00ff80 !important; /* Same bright green - 7.8:1 contrast ratio */
				background-color: rgba(0, 255, 128, 0.15) !important;
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
		return defined( 'EDACP_VERSION' ) && defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID;
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
