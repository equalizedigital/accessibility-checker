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

		// The admin-post handlers for register/unregister buttons.
		add_action( 'admin_post_edac_jwt_register', [ $this, 'handle_jwt_register_post' ] );
		add_action( 'admin_post_edac_jwt_unregister', [ $this, 'handle_jwt_unregister_post' ] );
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

	/**
	 * Handle admin-post for site registration (button on License page).
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function handle_jwt_register_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to register this site.', 'accessibility-checker' ) );
		}
		check_admin_referer( 'edac_jwt_register', 'edac_jwt_register_nonce' );
		$this->handle_site_registration();
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url();
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle admin-post for site unregistration (button on License page).
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function handle_jwt_unregister_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to unregister this site.', 'accessibility-checker' ) );
		}
		check_admin_referer( 'edac_jwt_unregister', 'edac_jwt_unregister_nonce' );
		$this->handle_site_unregistration();
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url();
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle the site registration process including UI feedback.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	private function handle_site_registration() {
		$license_key = get_option( 'edac_license_key' );
		if ( empty( $license_key ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No license key found. Please activate a license before registering your site.', 'accessibility-checker' ) . '</p></div>';
				}
			);
			return;
		}
		$site_url  = site_url();
		$site_name = get_bloginfo( 'name' );

		$response_data = self::register_site( $license_key, $site_url, $site_name, true, true );
		if ( empty( $response_data['success'] ) ) {
			$error_msg = ! empty( $response_data['message'] ) ? $response_data['message'] : __( 'Unknown error occurred while registering the site.', 'accessibility-checker' );
			add_action(
				'admin_notices',
				function () use ( $error_msg ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_msg ) . '</p></div>';
				}
			);
			return;
		}
		if ( isset( $response_data['data'] ) ) {
			$data = $response_data['data'];
			if ( ! empty( $data['jwt_token'] ) ) {
				update_option( 'edac_jwt_token', $data['jwt_token'] );
			}
			if ( ! empty( $data['jwt_public_key'] ) ) {
				update_option( 'edac_jwt_public_key', $data['jwt_public_key'] );
			}
			if ( ! empty( $data['site_id'] ) ) {
				update_option( 'edac_site_id', $data['site_id'] );
			}
			if ( ! empty( $data['collection_interval_days'] ) ) {
				update_option( 'edac_collection_interval_days', $data['collection_interval_days'] );
			}
			if ( ! empty( $data['next_collection'] ) ) {
				update_option( 'edac_next_collection', $data['next_collection'] );
			}
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Site registered successfully. Your site is now configured to use additional accessibility services.', 'accessibility-checker' ) . '</p></div>';
				}
			);
		} else {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Site registration completed, but the response data was not in the expected format. Some features may not work correctly.', 'accessibility-checker' ) . '</p></div>';
				}
			);
		}
	}

	/**
	 * Handle the site unregistration process including UI feedback.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	private function handle_site_unregistration() {
		$site_id     = get_option( 'edac_site_id' );
		$jwt_token   = get_option( 'edac_jwt_token' );
		$license_key = get_option( 'edac_license_key' );
		if ( empty( $site_id ) || empty( $jwt_token ) || empty( $license_key ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unable to unregister site. Required registration data is missing.', 'accessibility-checker' ) . '</p></div>';
				}
			);
			return;
		}
		$response_data = self::unregister_site( $jwt_token, get_site_url(), $license_key );
		if ( empty( $response_data['success'] ) ) {
			$error_msg = ! empty( $response_data['message'] ) ? $response_data['message'] : __( 'Unknown error occurred while unregistering the site.', 'accessibility-checker' );
			add_action(
				'admin_notices',
				function () use ( $error_msg ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_msg ) . '</p></div>';
				}
			);
			return;
		}
		delete_option( 'edac_jwt_token' );
		delete_option( 'edac_jwt_public_key' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_collection_interval_days' );
		delete_option( 'edac_next_collection' );
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Site unregistered successfully. Your site will no longer receive email reports.', 'accessibility-checker' ) . '</p></div>';
			}
		);
	}

	/**
	 * Register a site with the MyDot API.
	 *
	 * @since 1.xx.x
	 *
	 * @param string $license_key     The license key to register the site with.
	 * @param string $site_url        The URL of the site to register.
	 * @param string $site_name       The name of the site to register.
	 * @param bool   $weekly_reports  Whether to enable weekly reports.
	 * @param bool   $monthly_reports Whether to enable monthly reports.
	 *
	 * @return array The response data from the API.
	 */
	public static function register_site( $license_key, $site_url, $site_name, $weekly_reports = true, $monthly_reports = true ) {
		if ( empty( $license_key ) ) {
			return [
				'success' => false,
				'message' => __( 'No license key provided.', 'accessibility-checker' ),
			];
		}
		$request_data = [
			'site_url'        => $site_url,
			'site_name'       => $site_name,
			'license_key'     => $license_key,
			'weekly_reports'  => $weekly_reports,
			'monthly_reports' => $monthly_reports,
		];
		$response     = wp_remote_post(
			self::API_ENDPOINT . '/wp-json/myed-email-reports/v1/register-site',
			[
				'headers'     => [ 'Content-Type' => 'application/json' ],
				'body'        => wp_json_encode( $request_data ),
				'method'      => 'POST',
				'data_format' => 'body',
				'timeout'     => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- accommodation for slow hosting environments.
				'sslverify'   => self::verify_ssl(),
			]
		);
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );
		if ( 200 !== $response_code || empty( $response_body ) ) {
			return [
				'success' => false,
				'message' => ( ! empty( $response_data['message'] ) ? $response_data['message'] : __( 'Unknown error occurred while registering the site.', 'accessibility-checker' ) ),
			];
		}
		return $response_data;
	}

	/**
	 * Unregister a site from the MyDot API.
	 *
	 * @since 1.xx.x
	 *
	 * @param string $jwt_token   The JWT token for authentication.
	 * @param string $site_url    The URL of the site to unregister.
	 * @param string $license_key The license key associated with the site.
	 *
	 * @return array The response data from the API.
	 */
	public static function unregister_site( $jwt_token, $site_url, $license_key ) {
		if ( empty( $jwt_token ) || empty( $site_url ) || empty( $license_key ) ) {
			return [
				'success' => false,
				'message' => __( 'Missing required parameters for unregistration.', 'accessibility-checker' ),
			];
		}
		$request_data = [
			'site_url'    => $site_url,
			'license_key' => $license_key,
		];
		$response     = wp_remote_post(
			self::API_ENDPOINT . '/wp-json/myed-email-reports/v1/unregister-site',
			[
				'headers'     => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $jwt_token,
				],
				'body'        => wp_json_encode( $request_data ),
				'method'      => 'POST',
				'data_format' => 'body',
				'timeout'     => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- accommodation for slow hosting environments.
				'sslverify'   => self::verify_ssl(),
			]
		);
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );
		if ( 200 !== $response_code || empty( $response_body ) ) {
			return [
				'success' => false,
				'message' => ( ! empty( $response_data['message'] ) ? $response_data['message'] : __( 'Unknown error occurred while unregistering the site.', 'accessibility-checker' ) ),
			];
		}
		return $response_data;
	}

	/**
	 * Validate a JWT token using the stored public key.
	 *
	 * @since 1.xx.x
	 *
	 * @param string $token The JWT token to validate.
	 * @return bool True if the token is valid, false otherwise.
	 */
	public static function validate_jwt_token( $token ) {
		if ( empty( $token ) ) {
			return false;
		}
		$public_key = get_option( 'edac_jwt_public_key' );
		if ( empty( $public_key ) ) {
			return false;
		}
		$parts = explode( '.', $token );
		if ( count( $parts ) !== 3 ) {
			return false;
		}
		list( $header_b64, $payload_b64, $signature_b64 ) = $parts;
		$header  = json_decode( base64_decode( strtr( $header_b64, '-_', '+/' ) ), true );
		$payload = json_decode( base64_decode( strtr( $payload_b64, '-_', '+/' ) ), true );
		if ( ! $header || ! $payload ) {
			return false;
		}
		$message           = $header_b64 . '.' . $payload_b64;
		$signature_decoded = base64_decode( strtr( $signature_b64, '-_', '+/' ) );
		$algo              = $header['alg'] ?? 'RS256';
		if ( 'RS256' !== $algo ) {
			return false;
		}
		$public_key_resource = openssl_pkey_get_public( $public_key );
		if ( ! $public_key_resource ) {
			return false;
		}
		$verify_result = openssl_verify( $message, $signature_decoded, $public_key_resource, OPENSSL_ALGO_SHA256 );
		if ( 1 !== $verify_result ) {
			return false;
		}
		$current_time = time();
		if ( isset( $payload['exp'] ) && $payload['exp'] < $current_time ) {
			return false;
		}
		return true;
	}

	/**
	 * Permission helper for validating JWT token in REST request.
	 *
	 * @since 1.xx.x
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool True if valid JWT token is present, false otherwise.
	 */
	public static function validate_jwt_token_in_request( $request ): bool {
		if ( ! $request instanceof \WP_REST_Request ) {
			return false;
		}
		// If token header provided, validate.
		$auth_header = $request->get_header( 'Authorization' );
		if ( ! empty( $auth_header ) ) {
			$parts = explode( ' ', $auth_header );
			if ( count( $parts ) === 2 && 'Bearer' === $parts[0] ) {
				if ( self::validate_jwt_token( $parts[1] ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
