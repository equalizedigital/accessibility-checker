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
	 * The default MyDot API endpoint for license validation.
	 *
	 * @since 1.xx.x
	 *
	 * @var string
	 */
	const API_ENDPOINT = 'https://my.equalizedigital.com';

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
	 * License metadata option: stores inferred license state from EDD responses.
	 *
	 * This is not raw response data; it's processed/inferred state that combines:
	 * - License type inference (free vs pro) based on product_id, item_name, or source context
	 * - License level inference (single-site, multi-site, unlimited, lifetime) from license_limit
	 * - Formatted/sanitized response fields (expires, site_count, activations_left, etc.)
	 *
	 * Single option (not scattered across multiple wp_options for better atomicity).
	 *
	 * @var string
	 */
	private const LICENSE_METADATA_OPTION = 'edac_license_metadata';

	/**
	 * License status constants.
	 */
	private const LICENSE_STATUS_VALID   = 'valid';
	private const LICENSE_STATUS_EXPIRED = 'expired';
	private const LICENSE_STATUS_UNKNOWN = 'unknown';

	/**
	 * License type constants.
	 */
	private const LICENSE_TYPE_FREE    = 'free';
	private const LICENSE_TYPE_PRO     = 'pro';
	private const LICENSE_TYPE_UNKNOWN = 'unknown';

	/**
	 * License level constants.
	 */
	private const LICENSE_LEVEL_SINGLE_SITE = 'single-site';
	private const LICENSE_LEVEL_MULTI_SITE  = 'multi-site';
	private const LICENSE_LEVEL_UNLIMITED   = 'unlimited';
	private const LICENSE_LEVEL_LIFETIME    = 'lifetime';
	private const LICENSE_LEVEL_UNKNOWN     = 'unknown';

	/**
	 * Determine whether enrollment should use the filtered product ID.
	 *
	 * We only use Pro product context when Pro is active and licensed.
	 *
	 * @return bool
	 */
	private static function should_use_filtered_product_id_for_enrollment(): bool {
		return defined( 'EDACP_VERSION' ) && 'valid' === get_option( 'edacp_license_status' );
	}

	/**
	 * Expose the free product ID via a filter so other plugins (e.g. Pro) can
	 * read it when inferring license type from API response product IDs.
	 *
	 * @return int
	 */
	public static function get_free_product_id(): int {
		return self::PRODUCT_ID;
	}

	/**
	 * Sets up the license page and handlers.
	 *
	 * @since 1.xx.x
	 */
	public function init() {
		$connected_services = new ConnectedServicesPage( 'manage_options' );
		$connected_services->add_page();

		// Expose the free product ID so the Pro plugin can infer license type from API response product IDs.
		add_filter( 'edac_free_product_id', [ __CLASS__, 'get_free_product_id' ] );

		// Ensure the license options group is registered so options.php allows saves.
		add_action( 'admin_init', [ $this, 'register_license_settings' ] );

		// Admin-post handler for license activate/deactivate.
		add_action( 'admin_post_edac_license', [ $this, 'handle_license_post' ] );

		// Schedule periodic license checks.
		add_action( 'init', [ $this, 'check_license_cron' ] );
		add_action( 'edac_check_license_hook', [ $this, 'periodic_check_license' ] );

		// The admin-post handlers for register/unregister buttons.
		add_action( 'admin_post_edac_jwt_register', [ $this, 'handle_jwt_register_post' ] );
		add_action( 'admin_post_edac_jwt_unregister', [ $this, 'handle_jwt_unregister_post' ] );

		// When the pro license is deactivated, unregister the site to avoid orphaned registrations.
		add_action( 'edacp_license_deactivated', [ $this, 'handle_site_unregistration' ], 10, 3 );

		// When a Pro license is activated on an already-connected site, refresh registration
		// so enrollment context is updated for Pro.
		add_action( 'edacp_license_activated', [ $this, 'handle_pro_license_activation' ], 10, 3 );

		add_action(
			'in_admin_header',
			function () {
				// Display transient-backed admin notices after redirects.
				add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
			},
			1000
		);
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
			'edacp_license_key',
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
		if ( isset( $_POST['edacp_license_key'] ) ) {
			$license = sanitize_text_field( wp_unslash( $_POST['edacp_license_key'] ) );
			update_option( 'edacp_license_key', $license );
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
		// If pro plugin is enabled with a valid license, it takes precedence.
		// Do not allow free license activation to overwrite pro state.
		if ( defined( 'EDACP_VERSION' ) && 'valid' === get_option( 'edacp_license_status' ) ) {
			update_option( 'edac_license_error', __( 'Pro license is active. Please deactivate the Pro license first if you want to use a free license.', 'accessibility-checker' ) );
			return;
		}

		$license = trim( get_option( 'edacp_license_key' ) );
		if ( empty( $license ) ) {
			update_option( 'edac_license_error', 'missing' );
			return;
		}

		$api_params = [
			'edd_action'  => 'activate_license',
			'license'     => $license,
			'item_id'     => self::PRODUCT_ID,
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'wp_version'  => get_bloginfo( 'version' ),
			'php_version' => phpversion(),
		];

		$response = wp_remote_post(
			self::get_api_endpoint(),
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
		self::store_license_metadata_from_response( $license_data, 'free' );

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
			// Always clear fallback marker when license recovers to valid state,
			// regardless of whether registration succeeded. License validity and
			// registration status are independent concerns (license = key validity,
			// registration = reports feature). The registration can be retried separately.
			delete_option( 'edac_fallback_active' );
		}
	}

	/**
	 * Clear all license and enrollment state.
	 *
	 * Called when deactivating or removing a license to ensure a clean slate.
	 *
	 * @return void
	 */
	private static function clear_all_license_state(): void {
		delete_option( 'edacp_license_key' );
		delete_option( 'edacp_license_status' );
		delete_option( 'edacp_license_error' );
		delete_option( 'edac_license_status' );
		delete_option( 'edac_license_error' );
		self::clear_stored_license_metadata();
		self::clear_report_connection_state();
		delete_option( 'edac_fallback_active' );
	}

	/**
	 * Clear report-connection specific state only.
	 *
	 * @return void
	 */
	private static function clear_report_connection_state(): void {
		delete_option( 'edac_jwt_public_key' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_collection_interval_days' );
		delete_option( 'edac_next_collection' );
	}

	/**
	 * Clear free-authority license state while preserving active Pro status.
	 *
	 * @return void
	 */
	private static function clear_free_disconnect_license_state(): void {
		delete_option( 'edacp_license_key' );
		delete_option( 'edac_license_status' );
		delete_option( 'edac_license_error' );
		self::clear_stored_license_metadata();
		delete_option( 'edac_fallback_active' );
	}

	/**
	 * Determine whether an unregister action should preserve current license state.
	 *
	 * Active Pro licenses should remain active when only disabling reports.
	 *
	 * @return bool
	 */
	private static function should_preserve_license_on_unregistration(): bool {
		return defined( 'EDACP_VERSION' ) && self::LICENSE_STATUS_VALID === get_option( 'edacp_license_status' );
	}

	/**
	 * Deactivate the license via API and always clear local stored values.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	private function deactivate_license() {
		$license = trim( get_option( 'edacp_license_key' ) );
		if ( empty( $license ) ) {
			self::clear_all_license_state();
			return;
		}

		// Best effort unregister: do not block local disconnect on remote failures.
		$site_id = (string) get_option( 'edac_site_id' );
		if ( '' !== $site_id ) {
			self::unregister_site( $site_id, get_site_url(), $license );
		}

		$api_params = [
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => rawurlencode( self::PRODUCT_NAME ),
			'url'        => home_url(),
		];

		wp_remote_post(
			self::get_api_endpoint(),
			[
				'timeout'   => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- accommodation for slow hosting environments.
				'sslverify' => self::verify_ssl(),
				'body'      => $api_params,
			]
		);

		// Remote deactivation is a best effort. Intentionally clear local
		// state regardless of API response so users can always disconnect.
		self::clear_all_license_state();
	}

	/**
	 * License check
	 *
	 * Also includes proactive JWT public key verification as part of key rotation strategy.
	 *
	 * Bails early if the pro plugin (EDACP) is enabled to let it handle license checking.
	 *
	 * @return void
	 */
	public function periodic_check_license() {
		// Guard: Only bail if Pro is active with VALID license.
		// This allows fallback when Pro license becomes invalid (expired, disabled, etc).
		//
		// Safe from race conditions:
		// - Once Pro's license status changes from 'valid' to anything else, this guard
		// stops bailing and free plugin resumes checking.
		// - Both plugins check the same 'edacp_license_status' option atomically
		// - Concurrent reads of the same option value are thread-safe in WordPress.
		if ( defined( 'EDACP_VERSION' ) && self::LICENSE_STATUS_VALID === get_option( 'edacp_license_status' ) ) {
			return;
		}

		$license = trim( get_option( 'edacp_license_key' ) );
		if ( ! $license ) {
			return;
		}

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
			self::get_api_endpoint(),
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

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// this is a silent failure, we should log this or flag it somehow.
			return;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		self::store_license_metadata_from_response( $license_data, 'free' );

		if ( isset( $license_data->license ) ) {
			update_option( 'edac_license_status', $license_data->license );
			if ( 'valid' === $license_data->license ) {
				// License has recovered to valid, so clear error notices and fallback marker.
				delete_option( 'edac_license_error' );
				delete_option( 'edacp_license_error' );
				// Free revalidated successfully after fallback; remove the temporary
				// fallback marker so UI can reflect connected state again.
				delete_option( 'edac_fallback_active' );
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
	 * Can be disabled by returning `false` to the `edac_verify_ssl_for_licensing` filter.
	 *
	 * @since 1.xx.x
	 *
	 * @return bool Whether to verify SSL. Defaults to `true`.
	 */
	public static function verify_ssl() {
		return (bool) apply_filters( 'edac_verify_ssl_for_licensing', true );
	}

	/**
	 * Get the MyDot API endpoint.
	 *
	 * Can be overridden by filtering the value with the `edac_mydot_api_endpoint` filter.
	 *
	 * @since 1.xx.x
	 *
	 * @return string The API endpoint URL (with protocol). Defaults to `https://my.equalizedigital.com`.
	 */
	public static function get_api_endpoint() {
		/**
		 * Filters the MyDot API endpoint URL.
		 *
		 * @since 1.xx.x
		 *
		 * @param string $default The default or environment-overridden API endpoint URL.
		 */
		return apply_filters( 'edac_mydot_api_endpoint', self::API_ENDPOINT );
	}

	/**
	 * Get the MyDot product ID.
	 *
	 * Can be overridden by filtering the value with the `edac_mydot_product_id` filter.
	 *
	 * @since 1.xx.x
	 *
	 * @return int The product ID. Defaults to 1666.
	 */
	public static function get_product_id(): int {
		/**
		 * Filters the MyDot product ID.
		 *
		 * @since 1.xx.x
		 *
		 * @param int $default The default product ID.
		 */
		return (int) apply_filters( 'edac_mydot_product_id', self::PRODUCT_ID );
	}

	/**
	 * Get the active license key.
	 *
	 * Both free and pro plugins store their license key in the same option 'edacp_license_key'.
	 * The actual product type (free vs pro) is determined by the EDD response item_id at activation
	 * time and stored in the metadata. This function simply retrieves the key itself.
	 *
	 * @return string The license key or empty string if none stored.
	 *
	 * @since 1.xx.x
	 */
	public static function get_license_key(): string {
		return (string) get_option( 'edacp_license_key', '' );
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
	 * @return bool True when registration succeeded and state was saved.
	 */
	private function handle_site_registration(): bool {
		$license_key = self::get_license_key();
		if ( empty( $license_key ) ) {
			set_transient(
				$this->get_notice_transient_key(),
				[
					'type'    => 'error',
					'message' => __( 'No license key found. Please activate a license before registering your site.', 'accessibility-checker' ),
				],
				self::NOTICE_TRANSIENT_TTL
			);
			return false;
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
			return false;
		}
		if ( isset( $response_data['data'] ) ) {
			$data = $response_data['data'];
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
			return true;
		} else {
			set_transient(
				$this->get_notice_transient_key(),
				[
					'type'    => 'warning',
					'message' => __( 'Site registration completed, but the response data was not in the expected format. Some features may not work correctly.', 'accessibility-checker' ),
				],
				self::NOTICE_TRANSIENT_TTL
			);
			return false;
		}
	}

	/**
	 * Refresh enrollment after Pro activation when the site is already connected.
	 *
	 * This keeps backend enrollment context aligned on free->pro upgrades without
	 * requiring users to manually disconnect/reconnect reports.
	 *
	 * @param string      $license      Activated license key.
	 * @param string      $url          Site URL from activation hook.
	 * @param object|null $license_data Activation response payload.
	 * @return void
	 */
	public function handle_pro_license_activation( $license = '', $url = '', $license_data = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Hook signature intentionally accepts action args for compatibility.
		$site_id = (string) get_option( 'edac_site_id', '' );
		if ( '' === $site_id ) {
			return;
		}

		$license_key = self::get_license_key();
		if ( '' === $license_key ) {
			return;
		}

		$response_data = self::register_site( $license_key, site_url(), get_bloginfo( 'name' ), true, true );
		if ( empty( $response_data['success'] ) || empty( $response_data['data'] ) ) {
			return;
		}

		$data = $response_data['data'];
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
	}

	/**
	 * Handle the site unregistration process including UI feedback.
	 *
	 * @since 1.xx.x
	 *
	 * @param string      $license      Optional license key passed from deactivation hooks.
	 * @param string      $url          Optional site URL from deactivation hooks.
	 * @param object|null $license_data Optional license payload from deactivation hooks.
	 *
	 * @return void
	 */
	public function handle_site_unregistration( $license = '', $url = '', $license_data = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Hook signature intentionally accepts action args for compatibility.
		$preserve_license = self::should_preserve_license_on_unregistration();
		$site_id          = get_option( 'edac_site_id' );
		$license_key      = '' !== (string) $license ? (string) $license : self::get_license_key();
		if ( empty( $site_id ) || empty( $license_key ) ) {
			// Clear local report connection state even when required data is missing.
			self::clear_report_connection_state();
			if ( ! $preserve_license ) {
				// Free disconnect keeps the historical behavior of clearing the key.
				self::clear_free_disconnect_license_state();
			}
			set_transient(
				$this->get_notice_transient_key(),
				[
					'type'    => 'error',
					'message' => __( 'Unable to unregister site. Required registration data is missing.', 'accessibility-checker' ),
				],
				self::NOTICE_TRANSIENT_TTL
			);
			return;
		}
		$response_data = self::unregister_site( $site_id, get_site_url(), $license_key );

		// Always clear local report state so reports are disabled immediately.
		self::clear_report_connection_state();
		if ( ! $preserve_license ) {
			// Free disconnect keeps the historical behavior of clearing the key,
			// even when the API response is an error.
			self::clear_free_disconnect_license_state();
		}

		if ( empty( $response_data['success'] ) ) {
			$error_msg = ! empty( $response_data['message'] ) ? $response_data['message'] : __( 'Unknown error occurred while unregistering the site.', 'accessibility-checker' );
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
		set_transient(
			$this->get_notice_transient_key(),
			[
				'type'    => 'success',
				'message' => __( 'Site unregistered successfully. Your site will no longer receive email reports.', 'accessibility-checker' ),
			],
			self::NOTICE_TRANSIENT_TTL
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

		if ( self::should_use_filtered_product_id_for_enrollment() ) {
			$request_data['product_id'] = self::get_product_id();
		}
		$response = wp_remote_post(
			self::get_api_endpoint() . '/wp-json/myed-email-reports/v1/register-site',
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
	 * @param string $site_id     The site ID for the registered site.
	 * @param string $site_url    The URL of the site to unregister.
	 * @param string $license_key The license key associated with the site.
	 *
	 * @return array The response data from the API.
	 */
	public static function unregister_site( $site_id, $site_url, $license_key ) {
		if ( empty( $site_id ) || empty( $site_url ) || empty( $license_key ) ) {
			return [
				'success' => false,
				'message' => __( 'Missing required parameters for unregistration.', 'accessibility-checker' ),
			];
		}
		$request_data = [
			'site_id'     => $site_id,
			'site_url'    => $site_url,
			'license_key' => $license_key,
		];

		if ( self::should_use_filtered_product_id_for_enrollment() ) {
			$request_data['product_id'] = self::get_product_id();
		}
		$response = wp_remote_post(
			self::get_api_endpoint() . '/wp-json/myed-email-reports/v1/unregister-site',
			[
				'headers'     => [
					'Content-Type' => 'application/json',
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
		return apply_filters( 'edac_jwt_issuer', preg_replace( '#^https?://#', '', self::get_api_endpoint() ) );
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

		$header_json  = self::base64url_decode_strict( $header_b64 );
		$payload_json = self::base64url_decode_strict( $payload_b64 );
		if ( false === $header_json || false === $payload_json ) {
			return false;
		}

		$header  = json_decode( $header_json, true );
		$payload = json_decode( $payload_json, true );
		if ( ! $header || ! $payload ) {
			return false;
		}

		// Require that aud, iss and exp all exist.
		if ( ! isset( $payload['aud'], $payload['iss'], $payload['exp'] ) ) {
			return false;
		}
		// The exp should be numeric and an int.
		if ( ! is_numeric( $payload['exp'] ) ) {
			return false;
		}
		$exp = (int) $payload['exp'];

		$message           = $header_b64 . '.' . $payload_b64;
		$signature_decoded = self::base64url_decode_strict( $signature_b64 );
		if ( false === $signature_decoded ) {
			return false;
		}

		$algo = $header['alg'] ?? 'RS256';
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
		if ( $exp < $current_time ) {
			return false;
		}

		// RFC 8725: Validate issuer claim to prevent token substitution attacks.
		$expected_iss = self::get_jwt_issuer();
		if ( $payload['iss'] !== $expected_iss ) {
			return false;
		}

		// RFC 8725: Validate audience claim - if issuer issues JWTs for multiple recipients,
		// the JWT must contain an "aud" claim and must be validated.
		$expected_aud = self::get_jwt_audience();
		$token_aud    = $payload['aud'];
		// aud can be a string or an array of strings per RFC 7519.
		$aud_list = is_array( $token_aud ) ? $token_aud : [ $token_aud ];
		if ( ! in_array( $expected_aud, $aud_list, true ) ) {
			return false;
		}

		// RFC 8725: Validate not-before claim (nbf) if present.
		if ( isset( $payload['nbf'] ) ) {
			if ( ! is_numeric( $payload['nbf'] ) ) {
				return false;
			}
			if ( (int) $payload['nbf'] > $current_time ) {
				return false;
			}
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
				// Check if the site is registered currently before attempting validation.
				$site_id = (string) get_option( 'edac_site_id', '' );
				if ( '' === $site_id ) {
					return false;
				}

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
		$response = self::safe_remote_get( self::get_api_endpoint() . '/wp-json/myed-email-reports/v1/public-key' );

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
		$response = self::safe_remote_get( self::get_api_endpoint() . '/wp-json/myed-email-reports/v1/get-public-key' );

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
			$timeout     = isset( $args['timeout'] ) ? max( 1, min( 5, (int) $args['timeout'] ) ) : 5;
			$retry_count = isset( $args['retry'] ) ? (int) $args['retry'] : 10;
			return vip_safe_wp_remote_get( $url, '', 3, $timeout, $retry_count, $args );
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

	/**
	 * Strict Base64URL decode that returns false on invalid input.
	 *
	 * @since 1.xx.x
	 *
	 * @param string $b64url The Base64URL encoded string.
	 * @return string|false The decoded string, or false on failure.
	 */
	private static function base64url_decode_strict( string $b64url ) {
		$b64 = strtr( $b64url, '-_', '+/' );
		$pad = strlen( $b64 ) % 4;
		if ( $pad ) {
			$b64 .= str_repeat( '=', 4 - $pad );
		}
		return base64_decode( $b64, true );
	}

	/**
	 * Infer license metadata from an EDD response.
	 *
	 * Determines the license type (free/pro), level (single-site/multi-site/unlimited/lifetime),
	 * and formats response fields for storage.
	 *
	 * Primary inference uses product ID (most reliable when free/pro have distinct IDs).
	 * Secondary fallback uses item_name string matching.
	 * Tertiary fallback uses the source context (e.g., 'free', 'pro').
	 *
	 * @param object|array $license_data EDD response payload.
	 * @param string       $source       Activation/check source context ('free' or 'pro').
	 * @return array Inferred metadata with keys: type, level, item_id, item_name, expires, license_limit, site_count, activations_left, last_response_at.
	 *
	 * @since 1.xx.x
	 */
	public static function infer_license_metadata_from_response( $license_data, string $source ): array {
		if ( ! is_object( $license_data ) && ! is_array( $license_data ) ) {
			return [
				'type'             => self::LICENSE_TYPE_UNKNOWN,
				'level'            => self::LICENSE_LEVEL_UNKNOWN,
				'item_id'          => 0,
				'item_name'        => '',
				'expires'          => '',
				'license_limit'    => '',
				'site_count'       => '',
				'activations_left' => '',
				'last_response_at' => time(),
			];
		}

		$data = is_object( $license_data ) ? get_object_vars( $license_data ) : $license_data;

		// Validate response has at least basic structure (item_id or item_name).
		if ( empty( $data['item_id'] ) && empty( $data['item_name'] ) ) {
			// Incomplete response; use source as last resort.
			return [
				'type'             => in_array( $source, [ self::LICENSE_TYPE_FREE, self::LICENSE_TYPE_PRO ], true ) ? $source : self::LICENSE_TYPE_UNKNOWN,
				'level'            => self::LICENSE_LEVEL_UNKNOWN,
				'item_id'          => 0,
				'item_name'        => '',
				'expires'          => '',
				'license_limit'    => '',
				'site_count'       => '',
				'activations_left' => '',
				'last_response_at' => time(),
			];
		}

		$item_name = sanitize_text_field( (string) ( $data['item_name'] ?? '' ) );
		$item_id   = absint( $data['item_id'] ?? 0 );
		$limit_raw = $data['license_limit'] ?? '';

		// Primary: infer from product ID in response — most reliable when free/pro have distinct IDs.
		$type           = self::LICENSE_TYPE_UNKNOWN;
		$pro_product_id = (int) apply_filters( 'edac_pro_product_id', 0 );
		if ( $item_id > 0 ) {
			if ( self::PRODUCT_ID === $item_id ) {
				$type = self::LICENSE_TYPE_FREE;
			} elseif ( $pro_product_id > 0 && $pro_product_id === $item_id ) {
				// Inferred as Pro because Pro's product ID filter matched.
				$type = self::LICENSE_TYPE_PRO;
			}
		}

		// Secondary: infer from item_name string match.
		if ( self::LICENSE_TYPE_UNKNOWN === $type && '' !== $item_name ) {
			$item_name_normalized = strtolower( $item_name );
			if ( false !== strpos( $item_name_normalized, 'pro' ) ) {
				$type = self::LICENSE_TYPE_PRO;
			} elseif ( false !== strpos( $item_name_normalized, 'free' ) ) {
				$type = self::LICENSE_TYPE_FREE;
			}
		}

		// Fallback: use source context.
		if ( self::LICENSE_TYPE_UNKNOWN === $type ) {
			$type = in_array( $source, [ self::LICENSE_TYPE_FREE, self::LICENSE_TYPE_PRO ], true ) ? $source : self::LICENSE_TYPE_UNKNOWN;
		}

		$level = self::LICENSE_LEVEL_UNKNOWN;
		if ( is_numeric( $limit_raw ) ) {
			$limit = (int) $limit_raw;
			if ( 0 === $limit ) {
				$level = self::LICENSE_LEVEL_UNLIMITED; // EDD uses 0 to mean no activation limit.
			} elseif ( 1 === $limit ) {
				$level = self::LICENSE_LEVEL_SINGLE_SITE;
			} elseif ( $limit > 1 ) {
				$level = self::LICENSE_LEVEL_MULTI_SITE;
			}
		} elseif ( is_string( $limit_raw ) ) {
			$limit_normalized = strtolower( trim( $limit_raw ) );
			if ( in_array( $limit_normalized, [ self::LICENSE_LEVEL_LIFETIME, self::LICENSE_LEVEL_UNLIMITED ], true ) ) {
				$level = $limit_normalized;
			}
		}

		return [
			'type'             => $type,
			'level'            => $level,
			'item_id'          => $item_id,
			'item_name'        => $item_name,
			'expires'          => sanitize_text_field( (string) ( $data['expires'] ?? '' ) ),
			'license_limit'    => sanitize_text_field( (string) $limit_raw ),
			'site_count'       => sanitize_text_field( (string) ( $data['site_count'] ?? '' ) ),
			'activations_left' => sanitize_text_field( (string) ( $data['activations_left'] ?? '' ) ),
			'last_response_at' => time(),
		];
	}

	/**
	 * Persist inferred license metadata from an EDD response.
	 *
	 * @param object|array|null $license_data EDD response payload.
	 * @param string            $source       Activation/check source context.
	 * @return void
	 */
	private static function store_license_metadata_from_response( $license_data, string $source ): void {
		$metadata = self::infer_license_metadata_from_response( $license_data, $source );
		update_option( self::LICENSE_METADATA_OPTION, $metadata );
	}

	/**
	 * Clear stored inferred license metadata.
	 *
	 * @return void
	 */
	private static function clear_stored_license_metadata(): void {
		delete_option( self::LICENSE_METADATA_OPTION );
	}
}
