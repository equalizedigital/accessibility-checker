<?php
/**
 * License Page and Notices for Accessibility Checker
 *
 * Handles the rendering and functionality of the license settings page
 * in the WordPress admin, including form submission, error notices, and
 * license status management.
 *
 * @package Accessibility_Checker
 * @since 1.34.0
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

use EqualizeDigital\AccessibilityChecker\MyDot\Connector;

/**
 * Class LicensePage
 *
 * Manages license page display and functionality within the admin settings area.
 *
 * @since 1.xx.x
 */
class LicensePage implements PageInterface {

	/**
	 * The capability required to access the settings page.
	 *
	 * @since 1.xx.x
	 *
	 * @var string
	 */
	private string $settings_capability;

	/**
	 * Constructor
	 *
	 * @since 1.xx.x
	 *
	 * @param string $settings_capability The capability required to view/edit license settings.
	 */
	public function __construct( $settings_capability ) {
		$this->settings_capability = $settings_capability;
	}

	/**
	 * Register hooks to add the license page to the settings tabs
	 * and handle license-related notices.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function add_page() {
		// Add license tab to settings page.
		add_filter(
			'edac_filter_settings_tab_items',
			[ $this, 'add_license_tab' ]
		);

		// Render license tab content when active.
		add_action(
			'edac_settings_tab_content',
			[ $this, 'maybe_render_tab_content' ]
		);

		// Register admin notice display with higher priority than default.
		add_action(
			'in_admin_header',
			[ $this, 'register_admin_notices' ],
			1001
		);
	}

	/**
	 * Add license tab to settings tabs array.
	 *
	 * @since 1.xx.x
	 *
	 * @param array $tabs Array of registered settings tabs.
	 *
	 * @return array Modified tabs array with license tab added.
	 */
	public function add_license_tab( $tabs ) {
		$tabs[] = [
			'slug'       => 'license',
			'label'      => esc_html__( 'License', 'accessibility-checker' ),
			'order'      => 99, // License is always last tab.
			'capability' => $this->settings_capability,
		];
		return $tabs;
	}

	/**
	 * Conditionally render the license tab content if the current tab is 'license'.
	 *
	 * @since 1.xx.x
	 *
	 * @param string $settings_tab The current active settings tab.
	 *
	 * @return void
	 */
	public function maybe_render_tab_content( $settings_tab ) {
		if ( 'license' === $settings_tab ) {
			$this->render_page();
		}
	}

	/**
	 * Register the admin notices function to display license-related messages.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function register_admin_notices() {
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	/**
	 * Render the license settings page content.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function render_page() {
		$license = get_option( 'edac_license_key' );
		$status  = get_option( 'edac_license_status' );
		?>
		<h2><?php esc_html_e( 'License Settings', 'accessibility-checker' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php settings_fields( 'edac_license' ); ?>
			<table class="form-table">
				<tbody>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php esc_html_e( 'License Key', 'accessibility-checker' ); ?>
					</th>
					<td>
						<input
							id="edac_license_key"
							name="edac_license_key"
							type="text"
							class="regular-text"
							value="<?php echo esc_attr( $license ); ?>"
						/>
						<label class="description" for="edac_license_key">
							<?php if ( false !== $status && 'valid' === $status ) : ?>
								<span style="color:green;"> <?php esc_html_e( 'active', 'accessibility-checker' ); ?></span>
							<?php elseif ( false !== $status && 'expired' === $status ) : ?>
								<span style="color:red;"> <?php esc_html_e( 'expired', 'accessibility-checker' ); ?></span>
							<?php else : ?>
								<?php esc_html_e( 'Enter your license key', 'accessibility-checker' ); ?>
							<?php endif; ?>
						</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top"></th>
					<td>
						<input type="hidden" name="action" value="edac_license" />
						<?php wp_nonce_field( 'edac_license_nonce', 'edac_license_nonce' ); ?>
						<?php if ( false !== $status && 'valid' === $status ) : ?>
							<input
								type="submit"
								class="button-primary"
								name="edac_license_deactivate"
								value="<?php esc_attr_e( 'Deactivate License', 'accessibility-checker' ); ?>"
							>
						<?php else : ?>
							<input
								type="submit"
								class="button-primary"
								name="edac_license_activate"
								value="<?php esc_attr_e( 'Activate License', 'accessibility-checker' ); ?>"
							/>
						<?php endif; ?>
					</td>
				</tr>
				</tbody>
			</table>
		</form>
		<?php
	}

	/**
	 * Display admin notices when the license added is invalid or expired.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function admin_notices() {
		$error       = get_option( 'edac_license_error' );
		$license_url = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings&tab=license';
		$message     = null;

		if ( $error ) {
			$message = $this->get_error_message( $error, $license_url );
		}

		if ( isset( $message ) ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				wp_kses_post( $message )
			);
		}
	}

	/**
	 * Get appropriate error message based on the license error code.
	 *
	 * @since 1.xx.x
	 *
	 * @param string $error_code The license error code.
	 * @param string $license_url URL to the license settings page.
	 *
	 * @return string The formatted error message.
	 */
	private function get_error_message( $error_code, $license_url ) {
		switch ( $error_code ) {
			case 'expired':
				return sprintf(
				/* translators: %1$s: item name, %2$s: item name */
					__( 'Your %1$s license has expired. <a href="https://my.equalizedigital.com/?utm_source=wpadmin" target="_blank">Please renew your license</a> to continue accessing additional features and services for %2$s.', 'accessibility-checker' ),
					Connector::PRODUCT_NAME,
					Connector::PRODUCT_NAME
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
