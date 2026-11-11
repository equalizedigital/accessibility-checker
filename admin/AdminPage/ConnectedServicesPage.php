<?php
/**
 * Connected Services Page and Notices for Accessibility Checker
 *
 * Handles the rendering and functionality of the license/connected services settings page
 * in the WordPress admin, including form submission, error notices, and
 * license status management.
 *
 * @package Accessibility_Checker
 * @since 1.40.0
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

use EqualizeDigital\AccessibilityChecker\MyDot\Connector;

/**
 * Class ConnectedServicesPage
 *
 * Manages connected services (license) page display and functionality within the admin settings area.
 *
 * @since 1.40.0
 */
class ConnectedServicesPage implements PageInterface {

	/**
	 * The capability required to access the settings page.
	 *
	 * @since 1.40.0
	 *
	 * @var string
	 */
	private string $settings_capability;

	/**
	 * Constructor
	 *
	 * @since 1.40.0
	 *
	 * @param string $settings_capability The capability required to view/edit license settings.
	 */
	public function __construct( $settings_capability = 'manage_options' ) {
		$this->settings_capability = $settings_capability;
	}

	/**
	 * Register hooks for license-related notices.
	 *
	 * @since 1.40.0
	 *
	 * @return void
	 */
	public function add_page() {
		// Inject degraded-state messaging above the Pro license form when Pro renders the license tab.
		add_action(
			'edacp_license_page_before_form',
			[ $this, 'render_pro_license_degraded_notice' ]
		);

		// Register admin notice display with higher priority than default.
		add_action(
			'in_admin_header',
			[ $this, 'register_admin_notices' ],
			1001
		);
	}

	/**
	 * Register the admin notices function to display license-related messages.
	 *
	 * @since 1.40.0
	 *
	 * @return void
	 */
	public function register_admin_notices() {
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	/**
	 * Render degraded-state notice inside the Pro license page.
	 *
	 * @return void
	 */
	public function render_pro_license_degraded_notice(): void {
		$license_context = $this->get_license_context();
		$degraded_notice = $this->get_degraded_notice_message( $license_context );

		if ( empty( $degraded_notice ) ) {
			return;
		}
		?>
		<div class="notice notice-warning inline"><p><?php echo esc_html( $degraded_notice ); ?></p></div>
		<?php
	}

	/**
	 * Build degraded notice state from the current effective license context.
	 *
	 * @param array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool} $license_context Effective license context.
	 * @return array{show:bool,mode:string}
	 */
	private function get_degraded_notice_context( array $license_context ): array {
		$pro_status      = (string) get_option( 'edacp_license_status', '' );
		$fallback_active = (bool) get_option( 'edac_fallback_active', false );

		return self::resolve_degraded_notice_context(
			$license_context['has_pro_plugin'],
			$license_context['is_pro'],
			$pro_status,
			$license_context['status'],
			$license_context['is_connected'],
			$fallback_active
		);
	}

	/**
	 * Resolve the effective license context for connected services.
	 *
	 * @return array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool}
	 */
	private function get_license_context(): array {
		$has_pro_plugin  = defined( 'EDACP_VERSION' );
		$pro_status      = (string) get_option( 'edacp_license_status', '' );
		$free_status     = (string) get_option( 'edac_license_status', '' );
		$site_id         = (string) get_option( 'edac_site_id', '' );
		$fallback_active = (bool) get_option( 'edac_fallback_active', false );

		return self::resolve_license_context( $has_pro_plugin, $pro_status, $free_status, $site_id, $fallback_active );
	}

	/**
	 * Resolve effective license context from current status values.
	 *
	 * @param bool   $has_pro_plugin  Whether the Pro plugin is installed.
	 * @param string $pro_status      Current Pro license status.
	 * @param string $free_status     Current free license status.
	 * @param string $site_id         Current connected site ID.
	 * @param bool   $fallback_active Whether a fallback from Pro to Free is currently active.
	 * @return array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool,is_registered:bool,fallback_active:bool}
	 */
	private static function resolve_license_context( bool $has_pro_plugin, string $pro_status, string $free_status, string $site_id, bool $fallback_active = false ): array {
		$is_pro = $has_pro_plugin && 'valid' === $pro_status && ! $fallback_active;
		$status = $is_pro ? $pro_status : $free_status;
		// is_connected is true when:
		// 1. License status is valid AND site_id exists, OR
		// 2. Pro exists but is expired/degraded (fallback_active), AND site_id exists (reports still flowing).
		$is_connected  = ( 'valid' === $status && '' !== $site_id ) || ( $has_pro_plugin && $fallback_active && '' !== $site_id );
		$is_registered = '' !== $site_id;

		return [
			'has_pro_plugin'  => $has_pro_plugin,
			'is_pro'          => $is_pro,
			'status'          => $status,
			'is_connected'    => $is_connected,
			'is_registered'   => $is_registered,
			'fallback_active' => $fallback_active,
		];
	}

	/**
	 * Resolve which error option and settings tab should be used for notices.
	 *
	 * @return array{error:string,tab:string}
	 */
	private function get_error_context(): array {
		$license_context = $this->get_license_context();

		return self::resolve_error_context( $license_context['is_pro'] );
	}

	/**
	 * Resolve notice context from effective license authority.
	 *
	 * @param bool $is_pro Whether Pro is currently authoritative.
	 * @return array{error:string,tab:string}
	 */
	private static function resolve_error_context( bool $is_pro ): array {
		return [
			'error' => (string) get_option( $is_pro ? 'edacp_license_error' : 'edac_license_error', '' ),
			'tab'   => $is_pro ? 'license' : 'accessibility-reports',
		];
	}

	/**
	 * Resolve whether to show a Pro-to-Free degraded-state notice.
	 *
	 * @param bool   $has_pro_plugin    Whether Pro plugin is installed.
	 * @param bool   $is_pro            Whether Pro is currently authoritative.
	 * @param string $pro_status        Current Pro status option.
	 * @param string $effective_status  Effective authority status.
	 * @param bool   $is_connected      Whether UI currently considers site connected.
	 * @param bool   $fallback_active   Whether Pro has degraded to fallback mode.
	 * @return array{show:bool,mode:string}
	 */
	private static function resolve_degraded_notice_context( bool $has_pro_plugin, bool $is_pro, string $pro_status, string $effective_status, bool $is_connected, bool $fallback_active = false ): array {
		// Show degraded notice when:
		// - Pro plugin exists AND
		// - Pro is not currently active as authority AND
		// - Pro status is set but not valid AND
		// - Either: Free is valid OR Pro fallback mode is active.
		$show = $has_pro_plugin
			&& ! $is_pro
			&& '' !== $pro_status
			&& 'valid' !== $pro_status
			&& ( 'valid' === $effective_status || $fallback_active );

		if ( ! $show ) {
			return [
				'show' => false,
				'mode' => '',
			];
		}

		return [
			'show' => true,
			'mode' => $is_connected ? 'connected' : 'reconnect',
		];
	}

	/**
	 * Get degraded-state notice message when Pro is invalid and Free is active.
	 *
	 * @param array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool} $license_context Effective license context.
	 * @return string|null
	 */
	private function get_degraded_notice_message( array $license_context ): ?string {
		$notice_context = $this->get_degraded_notice_context( $license_context );

		if ( ! $notice_context['show'] ) {
			return null;
		}

		if ( 'connected' === $notice_context['mode'] ) {
			return __( 'Your Pro license is no longer valid. Email reports remain connected as Free email reports. Renew your Pro license to restore Pro-only features.', 'accessibility-checker' );
		}

		return __( 'Your Pro license is no longer valid. This site is using a valid Free license, but email reports are not currently connected. Connect this site from the Free License section to resume Free email reports.', 'accessibility-checker' );
	}

	/**
	 * Display admin notices when the license added is invalid or expired.
	 *
	 * @since 1.40.0
	 *
	 * @return void
	 */
	public function admin_notices() {
		$error_context = $this->get_error_context();
		$error         = $error_context['error'];
		$license_url   = admin_url( 'admin.php?page=accessibility_checker_settings&tab=' . $error_context['tab'] );
		$message       = null;

		if ( $error ) {
			$message = $this->get_error_message( $error, $license_url );
		}

		if ( isset( $message ) ) {
			printf(
				'<div id="edac-license-notice" class="notice notice-error"><p>%s</p></div>',
				wp_kses_post( $message )
			);
		}
	}

	/**
	 * Get appropriate error message based on the license error code.
	 *
	 * @since 1.40.0
	 *
	 * @param string $error_code The license error code.
	 * @param string $license_url URL to the license settings page.
	 *
	 * @return string The formatted error message.
	 */
	private function get_error_message( $error_code, $license_url ) {
		switch ( $error_code ) {
			case 'expired':
				$renew_link = '<a href="' . esc_url( \edac_link_wrapper( 'https://my.equalizedigital.com/', 'connected-services', 'renew', false ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Please renew your license', 'accessibility-checker' ) . '</a>';
				return sprintf(
				/* translators: %1$s: item name, %2$s: item name, %3$s: renew license link */
					__( 'Your %1$s license has expired. %3$s to continue accessing additional features and services for %2$s.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME,
					Connector::PRODUCT_NAME,
					wp_kses_post( $renew_link )
				);

			case 'disabled':
			case 'revoked':
				return sprintf(
				/* translators: %s: item name */
					__( 'Your %s license key has been disabled. Please contact our support team if you believe this is an error or would like to continue accessing additional features.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME
				);

			case 'missing':
				return sprintf(
				/* translators: %1$s: item name, %2$s: license url, %3$s: item name */
					__( 'The license key for %1$s appears to be invalid. Please <a href="%2$s">check your license key</a> to access additional accessibility features and services for %3$s.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME,
					$license_url,
					Connector::PRODUCT_NAME
				);

			case 'invalid':
			case 'site_inactive':
				return sprintf(
				/* translators: %s: item name */
					__( 'This license key for %s is not active for this site. Please activate it to access additional features on this website.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME
				);

			case 'item_name_mismatch':
				return sprintf(
				/* translators: %s: the plugins item name */
					__( 'This license key does not appear to be valid for %s. Please verify you\'ve entered the correct license key.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME
				);

			case 'no_activations_left':
				return sprintf(
				/* translators: %s: the plugins item name */
					__( 'Your %s license key has reached its site activation limit. You can upgrade your license or deactivate it on another site to use the additional features here.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME
				);

			default:
				return sprintf(
				/* translators: %s: the plugins item name */
					__( 'There was an issue validating your %s license key. Please try again or contact our support team if the problem persists.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME
				);
		}
	}
}
