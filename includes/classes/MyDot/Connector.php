<?php
/**
 * MyDot Connector Class
 *
 * Provides connection and product information for MyDot license management integration.
 *
 * @package Accessibility_Checker
 * @since 1.xx.x
 */

namespace EqualizeDigital\AccessibilityChecker\MyDot;

/**
 * Class Connector
 *
 * Handles MyDot product and license integration constants and utilities.
 *
 * @since 1.xx.x
 */
class Connector {

	/**
	 * The product name used in MyDot licensing system.
	 *
	 * @since 1.xx.x
	 *
	 * @var string
	 */
	const PRODUCT_NAME = 'Accessibility Checker Free';

	/**
	 * The MyDot API endpoint for license validation.
	 *
	 * @since 1.xx.x
	 *
	 * @var string
	 */
	const API_ENDPOINT = 'http://my.equalizedigital.local';

	/**
	 * The product ID used in MyDot licensing system.
	 *
	 * @since 1.xx.x
	 *
	 * @var int
	 */
	const PRODUCT_ID = 1666;

	/**
	 * Sets up the license page and handlers if EDACP is not active.
	 *
	 * @since 1.xx.x
	 */
	public function init() {
		// set up the license page file if EDACP is not active.
		if ( defined( 'EDACP_VERSION' ) ) {
			return;
		}

		$license = new \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\LicensePage( 'administrator' );
		$license->add_page();

		// Ensure the license options group is registered so options.php allows saves.
		add_action( 'admin_init', [ $this, 'register_license_settings' ] );

		// Admin-post handler for license activate/deactivate.
		add_action( 'admin_post_edac_license', [ $this, 'handle_license_post' ] );

		// Schedule periodic license checks.
		add_action( 'admin_init', [ $this, 'check_license_cron' ] );
		add_action( 'edacp_check_license_hook', [ $this, 'periodic_check_license' ] );
	}

	/**
	 * Register license settings so the edac_license group is allowed by options.php.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function register_license_settings() {
		register_setting(
			'edac_license',
			'edac_license_key',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		register_setting(
			'edac_license',
			'edac_license_status',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		register_setting(
			'edac_license',
			'edac_license_error',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
	}

	/**
	 * Handle license activate/deactivate from admin-post.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function handle_license_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this license.', 'accessibility-checker' ) );
		}

		check_admin_referer( 'edac_license_nonce', 'edac_license_nonce' );

		// Normalize license key from the form.
		if ( isset( $_POST['edac_license_key'] ) ) {
			$license = sanitize_text_field( wp_unslash( $_POST['edac_license_key'] ) );
			update_option( 'edac_license_key', $license );
		}

		if ( isset( $_POST['edac_license_activate'] ) ) {
			$this->activate_license();
		} elseif ( isset( $_POST['edac_license_deactivate'] ) ) {
			$this->deactivate_license();
		}

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url();
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Activate the license via API and store status/error.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	private function activate_license() {
		$license = trim( get_option( 'edac_license_key' ) );
		if ( empty( $license ) ) {
			update_option( 'edac_license_error', 'missing' );
			return;
		}

		$api_params = [
			'edd_action'  => 'activate_license',
			'license'     => $license,
			'item_name'   => rawurlencode( self::PRODUCT_NAME ),
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'wp_version'  => get_bloginfo( 'version' ),
			'php_version' => phpversion(),
		];

		$response = wp_remote_post(
			self::API_ENDPOINT,
			[
				'timeout'   => 15,  // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- accommodation for slow hosting environments.
				'sslverify' => self::verify_ssl(),
				'body'      => $api_params,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = is_wp_error( $response ) ? $response->get_error_message() : esc_html__( 'An error occurred, please try again.', 'accessibility-checker' );
			update_option( 'edac_license_error', $message );
			return;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $license_data->error ) ) {
			update_option( 'edac_license_error', $license_data->error );
			update_option( 'edac_license_status', $license_data->license ?? '' );
			return;
		}

		delete_option( 'edac_license_error' );
		update_option( 'edac_license_status', $license_data->license ?? '' );
	}

	/**
	 * Deactivate the license via API and clear stored values on success.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	private function deactivate_license() {
		$license = trim( get_option( 'edac_license_key' ) );
		if ( empty( $license ) ) {
			return;
		}

		$api_params = [
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => rawurlencode( self::PRODUCT_NAME ),
			'url'        => home_url(),
		];

		$response = wp_remote_post(
			self::API_ENDPOINT,
			[
				'timeout'   => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- accommodation for slow hosting environments.
				'sslverify' => self::verify_ssl(),
				'body'      => $api_params,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $license_data->license ) && 'deactivated' === $license_data->license ) {
			delete_option( 'edac_license_key' );
			delete_option( 'edac_license_status' );
			delete_option( 'edac_license_error' );
		}
	}

	/**
	 * License check
	 *
	 * @return void
	 */
	public function periodic_check_license() {

		if ( ! get_option( 'edac_license_key' ) ) {
			return;
		}

		$license = trim( get_option( 'edac_license_key' ) );

		$api_params = [
			'edd_action'   => 'check_license',
			'license'      => $license,
			'item_id'      => self::PRODUCT_ID,
			'item_name'    => rawurlencode( self::PRODUCT_NAME ),
			'url'          => home_url(),
			'environment'  => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'edac_version' => defined( 'EDAC_VERSION' ) ? EDAC_VERSION : '0.0.0',
			'wp_version'   => get_bloginfo( 'version' ),
			'php_version'  => phpversion(),
		];

		// Call the custom API.
		$response = wp_remote_post(
			EDACP_STORE_URL,
			[
				'timeout'   => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 15 seconds is needed for now.
				'sslverify' => self::verify_ssl(),
				'body'      => $api_params,
			]
		);
		if ( is_wp_error( $response ) ) {
			// this is a silent failure, we should log this or flag it somehow.
			return;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 'valid' !== $license_data->license ) {
			update_option( 'edacp_license_status', $license_data->license );
		}
	}

	/**
	 * License check cron schedule
	 *
	 * @return void
	 */
	public function check_license_cron() {
		if ( ! wp_next_scheduled( 'edacp_check_license_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'edacp_check_license_hook' );
		}
	}

	/**
	 * Determines whether to verify SSL for licensing requests.
	 *
	 * Can be disabled by returning `false` to the `edacp_verify_ssl_for_licensing` filter.
	 *
	 * @since 1.xx.x
	 *
	 * @return bool Whether to verify SSL. Defaults to `true`.
	 */
	public static function verify_ssl() {
		return (bool) apply_filters( 'edac_verify_ssl_for_licensing', true );
	}
}
