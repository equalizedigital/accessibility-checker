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
	 * Ensures fallback marker prevents stale connected state during pro->free handoff.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_resolve_license_context_treats_fallback_as_temporarily_disconnected() {
		$context = $this->invoke_private_static_method(
			'resolve_license_context',
			[ true, 'expired', 'valid', 'site-123', true ]
		);

		$this->assertFalse( $context['is_pro'] );
		$this->assertSame( 'valid', $context['status'] );
		$this->assertFalse( $context['is_connected'] );
	}
}
