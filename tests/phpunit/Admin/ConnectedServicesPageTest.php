<?php
/**
 * Tests for Connected Services page license context behavior.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage;

/**
 * Test cases for connected services fallback-aware license handling.
 */
class ConnectedServicesPageTest extends WP_UnitTestCase {

	/**
	 * Connected services page instance.
	 *
	 * @var ConnectedServicesPage
	 */
	private $page;

	/**
	 * Set up test state.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->page = new ConnectedServicesPage( 'manage_options' );

		delete_option( 'edac_license_status' );
		delete_option( 'edacp_license_status' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_license_error' );
		delete_option( 'edacp_license_error' );
		delete_option( 'edac_fallback_active' );
	}

	/**
	 * Clean up test state.
	 */
	public function tearDown(): void {
		delete_option( 'edac_license_status' );
		delete_option( 'edacp_license_status' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_license_error' );
		delete_option( 'edacp_license_error' );
		delete_option( 'edac_fallback_active' );

		parent::tearDown();
	}

	/**
	 * Invoke a private static method on the connected services page.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments   Arguments.
	 * @return mixed
	 * @throws ReflectionException If reflection fails.
	 */
	private function invoke_private_static_method( string $method_name, array $arguments = [] ) {
		$reflection = new ReflectionClass( ConnectedServicesPage::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( null, $arguments );
	}

	/**
	 * Invoke a private method on the connected services page.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments   Arguments.
	 * @return mixed
	 * @throws ReflectionException If reflection fails.
	 */
	private function invoke_private_method( string $method_name, array $arguments = [] ) {
		$reflection = new ReflectionClass( ConnectedServicesPage::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->page, $arguments );
	}

	/**
	 * Ensures valid Pro remains authoritative when it is installed and connected.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_license_context_uses_pro_when_pro_is_valid() {
		$context = $this->invoke_private_static_method(
			'resolve_license_context',
			[ true, 'valid', 'valid', 'site-123', false ]
		);

		$this->assertTrue( $context['has_pro_plugin'] );
		$this->assertTrue( $context['is_pro'] );
		$this->assertSame( 'valid', $context['status'] );
		$this->assertTrue( $context['is_connected'] );
	}

	/**
	 * Ensures fallback to free state when Pro is installed but no longer valid.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_license_context_falls_back_to_free_when_pro_is_invalid() {
		$context = $this->invoke_private_static_method(
			'resolve_license_context',
			[ true, 'expired', 'valid', 'site-123', false ]
		);

		$this->assertTrue( $context['has_pro_plugin'] );
		$this->assertFalse( $context['is_pro'] );
		$this->assertSame( 'valid', $context['status'] );
		$this->assertTrue( $context['is_connected'] );
	}

	/**
	 * Ensures notice context uses free error and reports tab when Pro is not effective.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_error_context_uses_free_when_effective_license_is_not_pro() {
		update_option( 'edac_license_error', 'invalid' );
		update_option( 'edacp_license_error', 'expired' );

		$context = $this->invoke_private_static_method( 'resolve_error_context', [ false ] );

		$this->assertSame( 'invalid', $context['error'] );
		$this->assertSame( 'accessibility-reports', $context['tab'] );
	}

	/**
	 * Ensures notice context uses Pro error and license tab when Pro is effective.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_error_context_uses_pro_when_effective_license_is_pro() {
		update_option( 'edac_license_error', 'invalid' );
		update_option( 'edacp_license_error', 'expired' );

		$context = $this->invoke_private_static_method( 'resolve_error_context', [ true ] );

		$this->assertSame( 'expired', $context['error'] );
		$this->assertSame( 'license', $context['tab'] );
	}

	/**
	 * Ensures dynamic error context follows effective license authority.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_error_context_follows_effective_license_context() {
		update_option( 'edac_license_status', 'valid' );
		update_option( 'edacp_license_status', 'expired' );
		update_option( 'edac_site_id', 'site-123' );
		update_option( 'edac_license_error', 'invalid' );
		update_option( 'edacp_license_error', 'expired' );

		$context = $this->invoke_private_method( 'get_error_context' );

		$this->assertSame( 'invalid', $context['error'] );
		$this->assertSame( 'accessibility-reports', $context['tab'] );
	}

	/**
	 * Ensures fallback marker does not disconnect reports when Free is valid and the site remains connected.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_license_context_keeps_free_fallback_connected_when_site_id_exists() {
		$context = $this->invoke_private_static_method(
			'resolve_license_context',
			[ true, 'expired', 'valid', 'site-123', true ]
		);

		$this->assertFalse( $context['is_pro'] );
		$this->assertSame( 'valid', $context['status'] );
		$this->assertTrue( $context['is_connected'] );
	}

	/**
	 * Ensures degraded notice context is shown when Pro is invalid and Free is valid.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_degraded_notice_context_connected_mode() {
		$context = $this->invoke_private_static_method(
			'resolve_degraded_notice_context',
			[ true, false, 'expired', 'valid', true, false ]
		);

		$this->assertTrue( $context['show'] );
		$this->assertSame( 'connected', $context['mode'] );
	}

	/**
	 * Ensures degraded notice context switches to reconnect mode while fallback is active.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_degraded_notice_context_reconnect_mode_during_fallback() {
		$context = $this->invoke_private_static_method(
			'resolve_degraded_notice_context',
			[ true, false, 'expired', 'valid', false, true ]
		);

		$this->assertTrue( $context['show'] );
		$this->assertSame( 'reconnect', $context['mode'] );
	}

	/**
	 * Ensures degraded notice does not show when Pro is still authoritative.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_degraded_notice_context_hidden_when_pro_is_valid() {
		$context = $this->invoke_private_static_method(
			'resolve_degraded_notice_context',
			[ true, true, 'valid', 'valid', true, false ]
		);

		$this->assertFalse( $context['show'] );
		$this->assertSame( '', $context['mode'] );
	}

	/**
	 * Ensures degraded notice message explains connected degraded mode.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_degraded_notice_message_connected_mode() {
		update_option( 'edacp_license_status', 'expired' );
		update_option( 'edac_fallback_active', false );

		$message = $this->invoke_private_method(
			'get_degraded_notice_message',
			[
				[
					'has_pro_plugin' => true,
					'is_pro'         => false,
					'status'         => 'valid',
					'is_connected'   => true,
				],
			]
		);

		$this->assertIsString( $message );
		$this->assertStringContainsString( 'Free email reports', $message );
	}

	/**
	 * Ensures degraded notice message still appears when Pro is expired, fallback is active,
	 * and the free status is blank but the site remains connected.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_degraded_notice_message_connected_mode_during_fallback_with_blank_free_status() {
		update_option( 'edacp_license_status', 'expired' );
		update_option( 'edac_fallback_active', true );

		$message = $this->invoke_private_method(
			'get_degraded_notice_message',
			[
				[
					'has_pro_plugin' => true,
					'is_pro'         => false,
					'status'         => '',
					'is_connected'   => true,
				],
			]
		);

		$this->assertIsString( $message );
		$this->assertStringContainsString( 'Free email reports', $message );
	}

	/**
	 * Ensures degraded notice message explains reconnect mode during fallback.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_degraded_notice_message_reconnect_mode() {
		update_option( 'edacp_license_status', 'expired' );
		update_option( 'edac_fallback_active', true );

		$message = $this->invoke_private_method(
			'get_degraded_notice_message',
			[
				[
					'has_pro_plugin' => true,
					'is_pro'         => false,
					'status'         => 'valid',
					'is_connected'   => false,
				],
			]
		);

		$this->assertIsString( $message );
		$this->assertStringContainsString( 'not currently connected', $message );
	}

	/**
	 * Ensures degraded-state notice can be injected into the Pro license page hook.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_render_pro_license_degraded_notice_outputs_when_pro_is_invalid_and_free_is_valid() {
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', 'test-pro-version' );
		}

		update_option( 'edacp_license_status', 'expired' );
		update_option( 'edac_license_status', 'valid' );
		update_option( 'edac_site_id', 'site-123' );
		update_option( 'edac_fallback_active', false );

		ob_start();
		$this->page->render_pro_license_degraded_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Free email reports', $output );
		$this->assertStringNotContainsString( 'Pro License Degraded to Free', $output );
	}

	/**
	 * The add_page() method wires only the notice callbacks (the Pro license-page
	 * notice and the notice display at priority 1001, so it runs after the default
	 * admin notices). It no longer registers a settings tab or tab content.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage::add_page
	 */
	public function test_add_page_wires_only_the_license_notices() {
		$this->page->add_page();

		$this->assertFalse( has_filter( 'edac_filter_settings_tab_items', [ $this->page, 'add_connected_services_tab' ] ) );
		$this->assertFalse( has_action( 'edac_settings_tab_content', [ $this->page, 'maybe_render_tab_content' ] ) );
		$this->assertSame( 10, has_action( 'edacp_license_page_before_form', [ $this->page, 'render_pro_license_degraded_notice' ] ) );
		$this->assertSame( 1001, has_action( 'in_admin_header', [ $this->page, 'register_admin_notices' ] ) );
	}

	/**
	 * The register_admin_notices() method hooks the page's own notice display onto
	 * admin_notices, which add_page() only runs once the admin header renders.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage::register_admin_notices
	 */
	public function test_register_admin_notices_registers_the_notice_display() {
		$this->assertFalse( has_action( 'admin_notices', [ $this->page, 'admin_notices' ] ) );

		$this->page->register_admin_notices();

		$this->assertSame( 10, has_action( 'admin_notices', [ $this->page, 'admin_notices' ] ) );
	}

	/**
	 * The license error notice carries a stable id so the admin script can
	 * announce it to screen readers after the license form is submitted.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage::admin_notices
	 */
	public function test_admin_notices_renders_license_error_with_id() {
		update_option( 'edac_license_error', 'invalid' );

		ob_start();
		$this->page->admin_notices();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<div id="edac-license-notice" class="notice notice-error">', $output );
	}

	/**
	 * The license context is derived from the stored option values, so the page
	 * reports what is actually saved: a connected Free license reports valid and
	 * connected, a stored Pro status is not treated as authority without the Pro
	 * plugin, and an empty install reports no status and no connection.
	 *
	 * @param array  $options                 Option name/value pairs to store before reading the context.
	 * @param bool   $expected_is_pro         Expected effective Pro authority.
	 * @param string $expected_status         Expected effective license status.
	 * @param bool   $expected_is_connected   Expected effective connection state.
	 *
	 * @dataProvider provider_stored_license_options
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage::get_license_context
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_license_context_reads_the_stored_license_options( array $options, bool $expected_is_pro, string $expected_status, bool $expected_is_connected ) {
		foreach ( $options as $option_name => $option_value ) {
			update_option( $option_name, $option_value );
		}

		$context = $this->invoke_private_method( 'get_license_context' );

		$this->assertSame( $expected_is_pro, $context['is_pro'] );
		$this->assertSame( $expected_status, $context['status'] );
		$this->assertSame( $expected_is_connected, $context['is_connected'] );
	}

	/**
	 * Stored license option combinations and the context they resolve to on a
	 * site running the free plugin alone.
	 *
	 * @return array[]
	 */
	public function provider_stored_license_options() {
		return [
			'free license connected'        => [
				[
					'edac_license_status' => 'valid',
					'edac_site_id'        => 'site-123',
				],
				false,
				'valid',
				true,
			],
			'pro status stored without pro' => [
				[ 'edacp_license_status' => 'valid' ],
				false,
				'',
				false,
			],
			'nothing stored'                => [ [], false, '', false ],
		];
	}

	/**
	 * A stored licence error is printed as an admin notice so the failure is
	 * visible wherever the admin is, linking back to the tab that can fix it.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage::admin_notices
	 */
	public function test_admin_notices_prints_a_stored_licence_error() {
		update_option( 'edac_license_error', 'missing' );

		$html = $this->render_admin_notices();

		$this->assertStringContainsString( 'notice notice-error', $html );
		$this->assertStringContainsString( 'check your license key', $html );
		$this->assertStringContainsString( 'tab=accessibility-reports', $html );
	}

	/**
	 * With no stored error for the current licence authority there is no notice
	 * at all, so the admin header is not polluted with an empty alert.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\ConnectedServicesPage::admin_notices
	 */
	public function test_admin_notices_is_silent_without_a_stored_error() {
		$this->assertSame( '', $this->render_admin_notices() );
	}

	/**
	 * Render the licence admin notices and capture their markup.
	 *
	 * @return string
	 */
	private function render_admin_notices(): string {
		ob_start();
		$this->page->admin_notices();

		return (string) ob_get_clean();
	}
}
