<?php
/**
 * Tests for the MyDot Connector license metadata behavior.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\MyDot\Connector;

/**
 * Test cases for Connector license metadata storage.
 */
class ConnectorTest extends WP_UnitTestCase {

	/**
	 * Set up test state.
	 */
	public function set_up() {
		parent::set_up();

		delete_option( 'edac_license_metadata' );
		delete_option( 'edac_license_status' );
		delete_option( 'edac_license_error' );
		delete_option( 'edacp_license_status' );
		delete_option( 'edacp_license_error' );
		delete_option( 'edac_jwt_public_key' );
		delete_option( 'edacp_license_key' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_collection_interval_days' );
		delete_option( 'edac_next_collection' );
		delete_option( 'edac_fallback_active' );
	}

	/**
	 * Tear down test state.
	 */
	public function tear_down() {
		delete_option( 'edac_license_metadata' );
		delete_option( 'edac_license_status' );
		delete_option( 'edac_license_error' );
		delete_option( 'edacp_license_status' );
		delete_option( 'edacp_license_error' );
		delete_option( 'edac_jwt_public_key' );
		delete_option( 'edacp_license_key' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_collection_interval_days' );
		delete_option( 'edac_next_collection' );
		delete_option( 'edac_fallback_active' );
		remove_all_filters( 'edac_pro_product_id' );
		remove_all_filters( 'pre_http_request' );

		parent::tear_down();
	}

	/**
	 * Invoke a private static Connector method.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments Arguments to pass.
	 * @return mixed
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	private function invoke_private_static_method( $method_name, array $arguments = [] ) {
		$reflection = new ReflectionClass( Connector::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( null, $arguments );
	}

	/**
	 * Build a mocked HTTP response payload for pre_http_request.
	 *
	 * @param array $body Response body data.
	 * @return Closure
	 */
	private function mock_http_response( array $body ) {
		return function () use ( $body ) {
			return [
				'headers'  => [],
				'body'     => wp_json_encode( $body ),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
	}

	/**
	 * Ensures the free product ID is exposed correctly.
	 */
	public function test_get_free_product_id_returns_expected_id() {
		$this->assertSame( 1666, Connector::get_free_product_id() );
	}

	/**
	 * Ensures connector notices include a stable CSS class for a11y announcement hooks.
	 */
	public function test_display_admin_notices_outputs_connector_notice_class() {
		$connector = new Connector();

		set_transient(
			'edac_connector_notice_' . get_current_user_id(),
			[
				'type'    => 'success',
				'message' => 'Reports enabled successfully.',
			],
			60
		);

		ob_start();
		$connector->display_admin_notices();
		$output = ob_get_clean();

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'edac-connector-notice', $output );
		$this->assertStringContainsString( 'notice-message', $output );
	}

	/**
	 * Ensures metadata is stored as a single array option for free licenses.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_store_license_metadata_saves_single_array_option_for_free_license() {
		$license_data = (object) [
			'item_id'          => 1666,
			'item_name'        => 'Accessibility Checker Free',
			'license_limit'    => 1,
			'expires'          => '2026-12-31 00:00:00',
			'site_count'       => '1',
			'activations_left' => '0',
		];

		$this->invoke_private_static_method( 'store_license_metadata_from_response', [ $license_data, 'free' ] );

		$metadata = get_option( 'edac_license_metadata' );

		$this->assertIsArray( $metadata );
		$this->assertSame( 'free', $metadata['type'] );
		$this->assertSame( 'single-site', $metadata['level'] );
		$this->assertSame( 1666, $metadata['item_id'] );
		$this->assertSame( 'Accessibility Checker Free', $metadata['item_name'] );
		$this->assertSame( '2026-12-31 00:00:00', $metadata['expires'] );
		$this->assertSame( '1', $metadata['license_limit'] );
		$this->assertSame( '1', $metadata['site_count'] );
		$this->assertSame( '0', $metadata['activations_left'] );
		$this->assertIsInt( $metadata['last_response_at'] );
		// Verify individual keys are NOT scattered across separate wp_options.
		$this->assertFalse( get_option( 'edac_license_type', false ) );
		$this->assertFalse( get_option( 'edac_license_item_id', false ) );
	}

	/**
	 * Ensures EDD license_limit of 0 (no activation cap) maps to 'unlimited', not 'single-site'.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_store_license_metadata_treats_zero_limit_as_unlimited() {
		$license_data = (object) [
			'item_id'       => 1666,
			'item_name'     => 'Accessibility Checker Free',
			'license_limit' => 0, // EDD uses 0 to mean no limit.
		];

		$this->invoke_private_static_method( 'store_license_metadata_from_response', [ $license_data, 'free' ] );

		$metadata = get_option( 'edac_license_metadata' );

		$this->assertSame( 'unlimited', $metadata['level'] );
	}

	/**
	 * Ensures multi-site level is inferred correctly for limits > 1.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_store_license_metadata_treats_limit_above_one_as_multi_site() {
		$license_data = (object) [
			'item_id'       => 1666,
			'item_name'     => 'Accessibility Checker Free',
			'license_limit' => 5,
		];

		$this->invoke_private_static_method( 'store_license_metadata_from_response', [ $license_data, 'free' ] );

		$metadata = get_option( 'edac_license_metadata' );

		$this->assertSame( 'multi-site', $metadata['level'] );
	}

	/**
	 * Ensures Pro item IDs can still be inferred from the filtered product ID.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_store_license_metadata_infers_pro_type_from_filtered_product_id() {
		add_filter(
			'edac_pro_product_id',
			static function () {
				return 24;
			}
		);

		$license_data = (object) [
			'item_id'       => 24,
			'item_name'     => 'Accessibility Checker',
			'license_limit' => '5',
		];

		$this->invoke_private_static_method( 'store_license_metadata_from_response', [ $license_data, 'free' ] );

		$metadata = get_option( 'edac_license_metadata' );

		$this->assertSame( 'pro', $metadata['type'] );
		$this->assertSame( 'multi-site', $metadata['level'] );
		$this->assertSame( 24, $metadata['item_id'] );
	}

	/**
	 * Ensures fallback inference still works when only item name identifies the license.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_store_license_metadata_falls_back_to_item_name_and_lifetime_level() {
		$license_data = (object) [
			'item_id'       => 0,
			'item_name'     => 'Accessibility Checker Pro',
			'license_limit' => 'lifetime',
		];

		$this->invoke_private_static_method( 'store_license_metadata_from_response', [ $license_data, 'free' ] );

		$metadata = get_option( 'edac_license_metadata' );

		$this->assertSame( 'pro', $metadata['type'] );
		$this->assertSame( 'lifetime', $metadata['level'] );
	}

	/**
	 * Ensures clearing metadata removes the single option.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_clear_stored_license_metadata_deletes_single_option() {
		update_option(
			'edac_license_metadata',
			[
				'type' => 'free',
			]
		);

		$this->invoke_private_static_method( 'clear_stored_license_metadata' );

		$this->assertFalse( get_option( 'edac_license_metadata', false ) );
	}

	/**
	 * Ensures the public periodic license check stores metadata in the single option.
	 */
	public function test_periodic_check_license_updates_status_and_metadata_option() {
		$connector = new Connector();
		$filter    = $this->mock_http_response(
			[
				'license'          => 'valid',
				'item_id'          => 1666,
				'item_name'        => 'Accessibility Checker Free',
				'license_limit'    => '1',
				'expires'          => '2026-12-31 00:00:00',
				'site_count'       => '1',
				'activations_left' => '0',
			]
		);

		update_option( 'edacp_license_key', 'free-license-key' );
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$connector->periodic_check_license();

		$metadata = get_option( 'edac_license_metadata' );

		$this->assertSame( 'valid', get_option( 'edac_license_status' ) );
		$this->assertIsArray( $metadata );
		$this->assertSame( 'free', $metadata['type'] );
		$this->assertSame( 1666, $metadata['item_id'] );
		$this->assertSame( 'single-site', $metadata['level'] );
		$this->assertFalse( get_option( 'edac_fallback_active', false ) );
	}

	/**
	 * Ensures free plugin's activate_license() does not run when Pro is installed with valid license.
	 *
	 * This prevents the free activation form from overwriting Pro license state.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_activate_license_bails_when_pro_is_active_with_valid_license() {
		// Simulate Pro plugin installed with valid license.
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.19.0' );
		}
		update_option( 'edacp_license_status', 'valid' );
		update_option( 'edacp_license_key', 'free-license-key' );

		// Create a new Connector instance and call activate_license via reflection.
		$connector  = new Connector();
		$reflection = new ReflectionClass( Connector::class );
		$method     = $reflection->getMethod( 'activate_license' );
		$method->setAccessible( true );

		// Invoke activate_license.
		$method->invoke( $connector );

		// Verify error was set and no HTTP request was attempted.
		$error = get_option( 'edac_license_error' );
		$this->assertNotEmpty( $error );
		$this->assertStringContainsString( 'Pro license is active', $error );

		// Verify metadata was NOT updated (no HTTP call happened).
		$this->assertFalse( get_option( 'edac_license_metadata', false ) );
	}

	/**
	 * Ensures free plugin's periodic_check_license resumes when Pro is defined but license is not valid.
	 *
	 * This enables automatic fallback from Pro to free when Pro's license expires or is disabled.
	 */
	public function test_periodic_check_license_resumes_when_pro_license_becomes_invalid() {
		// Simulate Pro plugin installed but with expired license.
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.19.0' );
		}
		update_option( 'edacp_license_status', 'expired' ); // Pro license is no longer valid.
		update_option( 'edacp_license_key', 'free-license-key' );

		$connector = new Connector();
		$filter    = $this->mock_http_response(
			[
				'license'          => 'valid',
				'item_id'          => 1666,
				'item_name'        => 'Accessibility Checker Free',
				'license_limit'    => 1,
				'expires'          => '2026-12-31 00:00:00',
				'site_count'       => '1',
				'activations_left' => '0',
			]
		);

		add_filter( 'pre_http_request', $filter, 10, 3 );

		// Call periodic_check_license — it should NOT bail even though Pro is defined.
		$connector->periodic_check_license();

		$metadata = get_option( 'edac_license_metadata' );

		// Verify free plugin checked and stored metadata (type: 'free', not Pro).
		$this->assertIsArray( $metadata );
		$this->assertSame( 'free', $metadata['type'] );
		$this->assertSame( 1666, $metadata['item_id'] );
		$this->assertSame( 'valid', get_option( 'edac_license_status' ) );
	}

	/**
	 * Ensures free plugin's deactivate_license clears all expected options.
	 *
	 * @throws ReflectionException If the method cannot be reflected.
	 */
	public function test_deactivate_license_clears_all_options() {
		// Set up initial state with all license-related options.
		update_option( 'edacp_license_key', 'test-key' );
		update_option( 'edacp_license_status', 'valid' );
		update_option( 'edacp_license_error', 'test-pro-error' );
		update_option( 'edac_license_status', 'valid' );
		update_option( 'edac_license_error', 'test-error' );
		update_option( 'edac_license_metadata', [ 'type' => 'free' ] );
		update_option( 'edac_site_id', 'test-site-id' );
		update_option( 'edac_jwt_public_key', 'test-public-key' );
		update_option( 'edac_collection_interval_days', '7' );
		update_option( 'edac_next_collection', '2026-04-22' );
		update_option( 'edac_fallback_active', 1 );

		// Deactivate via reflection.
		$connector  = new Connector();
		$reflection = new ReflectionClass( Connector::class );
		$method     = $reflection->getMethod( 'deactivate_license' );
		$method->setAccessible( true );
		$method->invoke( $connector );

		// Verify all options are cleared.
		$this->assertFalse( get_option( 'edacp_license_key', false ) );
		$this->assertFalse( get_option( 'edacp_license_status', false ) );
		$this->assertFalse( get_option( 'edacp_license_error', false ) );
		$this->assertFalse( get_option( 'edac_license_status', false ) );
		$this->assertFalse( get_option( 'edac_license_error', false ) );
		$this->assertFalse( get_option( 'edac_license_metadata', false ) );
		$this->assertFalse( get_option( 'edac_site_id', false ) );
		$this->assertFalse( get_option( 'edac_jwt_public_key', false ) );
		$this->assertFalse( get_option( 'edac_collection_interval_days', false ) );
		$this->assertFalse( get_option( 'edac_next_collection', false ) );
		$this->assertFalse( get_option( 'edac_fallback_active', false ) );
	}

	/**
	 * Ensures that when Pro license expires and free resumes, no automatic enrollment occurs.
	 *
	 * The free plugin should resume checking the license, but NOT automatically
	 * register the site for email reports without explicit user action.
	 */
	public function test_periodic_check_license_fallback_does_not_auto_enroll() {
		// Simulate Pro installed but expired.
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.19.0' );
		}
		update_option( 'edacp_license_status', 'expired' );
		update_option( 'edacp_license_key', 'free-license-key' );

		// Mock HTTP response.
		$filter = $this->mock_http_response(
			[
				'license'          => 'valid',
				'item_id'          => 1666,
				'item_name'        => 'Accessibility Checker Free',
				'license_limit'    => 1,
				'expires'          => '2026-12-31 00:00:00',
				'site_count'       => '1',
				'activations_left' => '0',
			]
		);

		add_filter( 'pre_http_request', $filter, 10, 3 );

		$connector = new Connector();


		$connector->periodic_check_license();

		// Verify metadata was stored (license check succeeded).
		$metadata = get_option( 'edac_license_metadata' );
		$this->assertIsArray( $metadata );
		$this->assertSame( 'free', $metadata['type'] );

		// Verify that site ID is still empty (no enrollment happened).
		$site_id = get_option( 'edac_site_id', false );
		$this->assertFalse( $site_id, 'Site should not be auto-registered during fallback' );
	}

	/**
	 * Ensures fallback marker is cleared when free periodic check validates successfully.
	 */
	public function test_periodic_check_license_clears_fallback_marker_when_free_becomes_valid() {
		update_option( 'edac_fallback_active', 1 );
		update_option( 'edacp_license_key', 'free-license-key' );

		$filter = $this->mock_http_response(
			[
				'license'          => 'valid',
				'item_id'          => 1666,
				'item_name'        => 'Accessibility Checker Free',
				'license_limit'    => '1',
				'expires'          => '2026-12-31 00:00:00',
				'site_count'       => '1',
				'activations_left' => '0',
			]
		);
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$connector = new Connector();
		$connector->periodic_check_license();

		$this->assertFalse( get_option( 'edac_fallback_active', false ) );
	}

	/**
	 * Ensures Pro activation hook refreshes existing registration context.
	 */
	public function test_handle_pro_license_activation_refreshes_registration_when_already_connected() {
		update_option( 'edac_site_id', 'existing-site-id' );
		update_option( 'edacp_license_key', 'pro-license-key' );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( false === strpos( $url, '/wp-json/myed-email-reports/v1/register-site' ) ) {
					return $preempt;
				}

				$body = json_decode( $args['body'], true );
				if ( empty( $body['license_key'] ) || 'pro-license-key' !== $body['license_key'] ) {
					return [
						'headers'  => [],
						'body'     => wp_json_encode(
							[
								'success' => false,
								'message' => 'Unexpected request body',
							]
						),
						'response' => [
							'code'    => 200,
							'message' => 'OK',
						],
					];
				}

				return [
					'headers'  => [],
					'body'     => wp_json_encode(
						[
							'success' => true,
							'data'    => [
								'site_id'                  => 'pro-site-id',
								'jwt_public_key'           => 'pro-public-key',
								'collection_interval_days' => '7',
								'next_collection'          => '2030-01-01',
							],
						]
					),
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
				];
			},
			10,
			3
		);

		$connector = new Connector();
		$connector->handle_pro_license_activation( 'pro-license-key', home_url(), (object) [ 'license' => 'valid' ] );

		$this->assertSame( 'pro-site-id', get_option( 'edac_site_id' ) );
		$this->assertSame( 'pro-public-key', get_option( 'edac_jwt_public_key' ) );
		$this->assertSame( '7', get_option( 'edac_collection_interval_days' ) );
		$this->assertSame( '2030-01-01', get_option( 'edac_next_collection' ) );
	}

	/**
	 * Ensures missing-data unregistration preserves active Pro license state.
	 */
	public function test_handle_site_unregistration_preserves_active_pro_license_when_registration_data_is_missing() {
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.19.0' );
		}

		update_option( 'edacp_license_key', 'test-key' );
		update_option( 'edacp_license_status', 'valid' );
		update_option( 'edacp_license_error', 'test-pro-error' );
		update_option( 'edac_license_status', 'valid' );
		update_option( 'edac_site_id', '' );

		$connector = new Connector();
		$connector->handle_site_unregistration();

		$this->assertSame( 'test-key', get_option( 'edacp_license_key' ) );
		$this->assertSame( 'valid', get_option( 'edacp_license_status' ) );
		$this->assertSame( 'test-pro-error', get_option( 'edacp_license_error' ) );
		$this->assertFalse( get_option( 'edac_site_id', false ) );
	}

	/**
	 * Ensures free-authority unregistration clears the shared key even when data is missing.
	 */
	public function test_handle_site_unregistration_clears_free_license_state_when_registration_data_is_missing() {
		update_option( 'edacp_license_key', 'free-key' );
		update_option( 'edac_license_status', 'valid' );
		update_option( 'edac_site_id', '' );

		$connector = new Connector();
		$connector->handle_site_unregistration();

		$this->assertFalse( get_option( 'edacp_license_key', false ) );
		$this->assertFalse( get_option( 'edac_license_status', false ) );
	}

	/**
	 * Ensures active Pro license is preserved even when remote unregistration fails.
	 */
	public function test_handle_site_unregistration_preserves_active_pro_license_on_remote_failure() {
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.19.0' );
		}

		update_option( 'edacp_license_key', 'pro-key' );
		update_option( 'edacp_license_status', 'valid' );
		update_option( 'edac_site_id', 'existing-site-id' );
		update_option( 'edac_jwt_public_key', 'public-key' );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( false === strpos( $url, '/wp-json/myed-email-reports/v1/unregister-site' ) ) {
					return $preempt;
				}

				return [
					'headers'  => [],
					'body'     => wp_json_encode(
						[
							'success' => false,
							'message' => 'Remote failure',
						]
					),
					'response' => [
						'code'    => 500,
						'message' => 'Server Error',
					],
				];
			},
			10,
			3
		);

		$connector = new Connector();
		$connector->handle_site_unregistration();

		$this->assertSame( 'pro-key', get_option( 'edacp_license_key' ) );
		$this->assertSame( 'valid', get_option( 'edacp_license_status' ) );
		$this->assertFalse( get_option( 'edac_site_id', false ) );
		$this->assertFalse( get_option( 'edac_jwt_public_key', false ) );
	}

	/**
	 * Ensures hook-provided license key is used for unregistration when option key is missing.
	 */
	public function test_handle_site_unregistration_uses_hook_license_when_stored_key_missing() {
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.19.0' );
		}

		update_option( 'edacp_license_status', 'valid' );
		update_option( 'edac_site_id', 'existing-site-id' );

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( false === strpos( $url, '/wp-json/myed-email-reports/v1/unregister-site' ) ) {
					return $preempt;
				}

				$body = json_decode( $args['body'], true );
				if ( empty( $body['license_key'] ) || 'hook-key' !== $body['license_key'] ) {
					return [
						'headers'  => [],
						'body'     => wp_json_encode(
							[
								'success' => false,
								'message' => 'Unexpected license key',
							]
						),
						'response' => [
							'code'    => 200,
							'message' => 'OK',
						],
					];
				}

				return [
					'headers'  => [],
					'body'     => wp_json_encode(
						[
							'success' => true,
							'data'    => [],
						]
					),
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
				];
			},
			10,
			3
		);

		$connector = new Connector();
		$connector->handle_site_unregistration( 'hook-key', home_url(), (object) [ 'license' => 'deactivated' ] );

		$this->assertFalse( get_option( 'edac_site_id', false ) );
	}
}
