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

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage;

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
	 * TTL for transient-based admin notices (seconds).
	 */
	private const NOTICE_TRANSIENT_TTL = 60;

	/**
	 * Sets up the license page and handlers if EDACP is not active.
	 *
	 * @since 1.xx.x
	 */
	public function init() {
		$connected_services = new ConnectedServicesPage( 'administrator' );
		$connected_services->add_page();

		// Ensure the license options group is registered so options.php allows saves.
		add_action( 'admin_init', [ $this, 'register_license_settings' ] );

		// Admin-post handler for license activate/deactivate.
		add_action( 'admin_post_edac_license', [ $this, 'handle_license_post' ] );

		// Schedule periodic license checks.
		add_action( 'admin_init', [ $this, 'check_license_cron' ] );
		add_action( 'edac_check_license_hook', [ $this, 'periodic_check_license' ] );

		// The admin-post handlers for register/unregister buttons.
		add_action( 'admin_post_edac_jwt_register', [ $this, 'handle_jwt_register_post' ] );
		add_action( 'admin_post_edac_jwt_unregister', [ $this, 'handle_jwt_unregister_post' ] );

		// Display transient-based admin notices after redirects.
		add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
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

		// Automatically register the site after successful license activation.
		if ( 'valid' === ( $license_data->license ?? '' ) ) {
			$this->handle_site_registration();
		}
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

		// Automatically unregister the site before deactivating the license.
		$jwt_token = get_option( 'edac_jwt_token' );
		if ( ! empty( $jwt_token ) ) {
			$this->handle_site_unregistration();
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
	 * Also includes proactive JWT public key verification as part of key rotation strategy.
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
			self::API_ENDPOINT,
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

		if ( isset( $license_data->license ) ) {
			update_option( 'edac_license_status', $license_data->license );
			if ( 'valid' === $license_data->license ) {
				delete_option( 'edac_license_error' );
			}
		}

		// Verify and update JWT public key daily before validation fails.
		// This ensures the site always has the latest key from the issuer without any downtime.
		self::verify_and_update_public_key();
	}

	/**
	 * License check cron schedule
	 *
	 * @return void
	 */
	public function check_license_cron() {
		if ( ! wp_next_scheduled( 'edac_check_license_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'edac_check_license_hook' );
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
			set_transient(
				$this->get_notice_transient_key(),
				[
					'type'    => 'error',
					'message' => __( 'No license key found. Please activate a license before registering your site.', 'accessibility-checker' ),
				],
				self::NOTICE_TRANSIENT_TTL
			);
			return;
		}
		$site_url  = site_url();
		$site_name = get_bloginfo( 'name' );

		$response_data = self::register_site( $license_key, $site_url, $site_name, true, true );
		if ( empty( $response_data['success'] ) ) {
			$error_msg = ! empty( $response_data['message'] ) ? $response_data['message'] : __( 'Unknown error occurred while registering the site.', 'accessibility-checker' );
			set_transient(
				$this->get_notice_transient_key(),
				[
					'type'    => 'error',
					'message' => $error_msg,
				],
				self::NOTICE_TRANSIENT_TTL
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
			set_transient(
				$this->get_notice_transient_key(),
				[
					'type'    => 'success',
					'message' => __( 'Site registered successfully. Your site is now configured to use additional accessibility services.', 'accessibility-checker' ),
				],
				self::NOTICE_TRANSIENT_TTL
			);
		} else {
			set_transient(
				$this->get_notice_transient_key(),
				[
					'type'    => 'warning',
					'message' => __( 'Site registration completed, but the response data was not in the expected format. Some features may not work correctly.', 'accessibility-checker' ),
				],
				self::NOTICE_TRANSIENT_TTL
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
	 * Get the expected issuer for JWT validation (RFC 8725).
	 *
	 * @since 1.xx.x
	 *
	 * @return string The issuer URL/identifier.
	 */
	public static function get_jwt_issuer() {
		// strip the protocol for issuer comparison.
		return apply_filters( 'edac_jwt_issuer', preg_replace( '#^https?://#', '', self::API_ENDPOINT ) );
	}

	/**
	 * Get the expected audience for JWT validation (RFC 8725).
	 *
	 * @since 1.xx.x
	 *
	 * @return string The audience identifier (site URL or API endpoint identifier).
	 */
	public static function get_jwt_audience() {
		// strip the protocol for audience comparison.
		return apply_filters( 'edac_jwt_audience', preg_replace( '#^https?://#', '', home_url() ) );
	}

	/**
	 * Validate a JWT token using the stored public key (RFC 8725 compliant).
	 *
	 * Validates:
	 * - Token structure (3 parts separated by dots)
	 * - Header algorithm (RS256)
	 * - Signature using stored public key
	 * - Token expiration (exp claim)
	 * - Issuer (iss claim) per RFC 8725 to prevent token substitution attacks
	 * - Audience (aud claim) per RFC 8725 to ensure token is for this recipient
	 * - Not Before (nbf claim) if present
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
		// Validate expiration (exp claim) - required by RFC 8725.
		if ( isset( $payload['exp'] ) && $payload['exp'] < $current_time ) {
			return false;
		}
		// RFC 8725: Validate issuer claim to prevent token substitution attacks.
		if ( isset( $payload['iss'] ) ) {
			$expected_iss = self::get_jwt_issuer();
			if ( $payload['iss'] !== $expected_iss ) {
				return false;
			}
		}
		// RFC 8725: Validate audience claim - if issuer issues JWTs for multiple recipients,
		// the JWT must contain an "aud" claim and must be validated.
		if ( isset( $payload['aud'] ) ) {
			$expected_aud = self::get_jwt_audience();
			$token_aud    = $payload['aud'];
			// aud can be a string or an array of strings per RFC 7519.
			$aud_list = is_array( $token_aud ) ? $token_aud : [ $token_aud ];
			if ( ! in_array( $expected_aud, $aud_list, true ) ) {
				return false;
			}
		}
		// RFC 8725: Validate not-before claim (nbf) if present.
		if ( isset( $payload['nbf'] ) && $payload['nbf'] > $current_time ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate JWT token with reactive fallback.
	 *
	 * If validation fails, attempt to refresh the public key from the issuer and retry.
	 * This handles cases where the issuer rotated keys but the site's cron hasn't run yet.
	 *
	 * @since 1.xx.x
	 *
	 * @param string $token The JWT token to validate.
	 * @return bool True if valid (either on first try or after key refresh), false otherwise.
	 */
	public static function validate_jwt_token_with_fallback( $token ) {
		// Try initial validation.
		if ( self::validate_jwt_token( $token ) ) {
			return true;
		}

		// Validation failed. Try to refresh the public key from the issuer.
		if ( self::refresh_public_key_from_issuer() ) {
			// Key was refreshed, retry validation with the new key.
			return self::validate_jwt_token( $token );
		}

		// Still invalid after refresh attempt.
		return false;
	}

	/**
	 * Permission helper for validating JWT token in REST request with fallback (Option 2 + 3).
	 *
	 * @since 1.xx.x
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool True if valid JWT token is present, false otherwise.
	 */
	public static function validate_jwt_token_in_request_with_fallback( $request ) {
		if ( ! $request instanceof \WP_REST_Request ) {
			return false;
		}

		// Extract the JWT token from the Authorization header.
		$auth_header = $request->get_header( 'Authorization' );
		$parts       = null !== $auth_header ? explode( ' ', $auth_header ) : [];
		if ( ! empty( $auth_header ) ) {
			if ( count( $parts ) === 2 && 'Bearer' === $parts[0] ) {
				// Use the fallback validator which will refresh key if needed.
				return self::validate_jwt_token_with_fallback( $parts[1] );
			}
		}

		// No valid Bearer token found.
		return false;
	}

	/**
	 * Check if stored JWT public key needs to be updated from a fresh registration.
	 *
	 * Called after successful site registration to verify the stored key is current.
	 * If the stored key doesn't match what the issuer sent, it's already been rotated.
	 *
	 * Uses a simple GET request since public keys don't require authentication.
	 *
	 * @since 1.xx.x
	 *
	 * @return bool True if public key was updated or is current, false on error.
	 */
	public static function verify_and_update_public_key() {
		$stored_key = get_option( 'edac_jwt_public_key' );

		if ( empty( $stored_key ) ) {
			return false;
		}

		// Make a lightweight GET request for the current public key.
		$response = self::safe_remote_get( self::API_ENDPOINT . '/wp-json/myed-email-reports/v1/public-key' );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// If issuer returned a new public key, store it immediately.
		if ( ! empty( $data['jwt_public_key'] ) && $data['jwt_public_key'] !== $stored_key ) {
			update_option( 'edac_jwt_public_key', $data['jwt_public_key'] );
			return true; // Key was updated.
		}

		return true; // Key is current.
	}

	/**
	 * Attempt to update the public key from API on failed JWT validation.
	 *
	 * If JWT validation fails, this optional step re-requests the public key
	 * from the issuer. Useful if the issuer rotated keys but the site hasn't
	 * refreshed them yet.
	 *
	 * This is called AFTER a JWT fails validation, so only use as a fallback
	 * to avoid constant API calls.
	 *
	 * Uses a simple GET request since public keys don't require authentication.
	 *
	 * @since 1.xx.x
	 *
	 * @return bool True if key was retrieved and stored, false otherwise.
	 */
	public static function refresh_public_key_from_issuer() {
		$response = self::safe_remote_get( self::API_ENDPOINT . '/wp-json/myed-email-reports/v1/get-public-key' );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $data['public_key'] ) ) {
			update_option( 'edac_jwt_public_key', $data['public_key'] );
			return true;
		}

		return false;
	}

	/**
	 * Perform a safe GET request compatible with VIP and non-VIP environments.
	 *
	 * Uses vip_safe_wp_remote_get() if available, otherwise falls back to wp_remote_get().
	 *
	 * @param string $url  The URL to request.
	 * @param array  $args Optional request args.
	 * @return array|\WP_Error Response array or WP_Error on failure.
	 */
	private static function safe_remote_get( string $url, array $args = [] ) {
		$defaults = [
			'timeout'   => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- accommodation for slow hosting environments.
			'sslverify' => self::verify_ssl(),
		];
		$args     = wp_parse_args( $args, $defaults );

		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$timeout     = isset( $args['timeout'] ) ? (int) $args['timeout'] : 15;
			$retry_count = isset( $args['retry'] ) ? (int) $args['retry'] : 3;
			return vip_safe_wp_remote_get( $url, '', $timeout, $retry_count, $args );
		}

		return wp_remote_get( $url, $args ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- fallback for non-VIP environments.
	}

	/**
	 * Display transient-based admin notices for the current user.
	 */
	public function display_admin_notices() {
		$key    = $this->get_notice_transient_key();
		$notice = get_transient( $key );

		if ( empty( $notice['type'] ) || empty( $notice['message'] ) ) {
			return;
		}

		delete_transient( $key );

		$allowed_types = [ 'success', 'error', 'warning', 'info' ];
		$type          = in_array( $notice['type'], $allowed_types, true ) ? $notice['type'] : 'info';
		$message       = is_string( $notice['message'] ) ? $notice['message'] : '';

		if ( '' === $message ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	/**
	 * Build the transient key for connector notices.
	 *
	 * @param int|null $user_id Optional user ID; defaults to current user.
	 *
	 * @return string
	 */
	private function get_notice_transient_key( $user_id = null ) {
		$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;

		return 'edac_connector_notice_' . absint( $user_id );
	}
}
