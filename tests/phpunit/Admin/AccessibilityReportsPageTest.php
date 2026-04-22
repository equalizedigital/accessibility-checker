<?php
/**
 * Tests for Accessibility Reports page license context behavior.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage;

/**
 * Test cases for reports page fallback-aware license handling.
 */
class AccessibilityReportsPageTest extends WP_UnitTestCase {

	/**
	 * Reports page instance.
	 *
	 * @var AccessibilityReportsPage
	 */
	private $page;

	/**
	 * Set up test state.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->page = new AccessibilityReportsPage( 'manage_options' );

		delete_option( 'edac_license_status' );
		delete_option( 'edacp_license_status' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_fallback_active' );
		delete_option( 'edacp_enable_archive_scanning' );
		delete_option( 'edac_next_collection' );
	}

	/**
	 * Clean up test state.
	 */
	public function tearDown(): void {
		delete_option( 'edac_license_status' );
		delete_option( 'edacp_license_status' );
		delete_option( 'edac_site_id' );
		delete_option( 'edac_fallback_active' );
		delete_option( 'edacp_enable_archive_scanning' );
		delete_option( 'edac_next_collection' );

		parent::tearDown();
	}

	/**
	 * Invoke a private method on the reports page.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments   Arguments.
	 * @return mixed
	 * @throws ReflectionException If reflection fails.
	 */
	private function invoke_private_method( string $method_name, array $arguments = [] ) {
		$reflection = new ReflectionClass( AccessibilityReportsPage::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->page, $arguments );
	}

	/**
	 * Invoke a private static method on the reports page.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments   Arguments.
	 * @return mixed
	 * @throws ReflectionException If reflection fails.
	 */
	private function invoke_private_static_method( string $method_name, array $arguments = [] ) {
		$reflection = new ReflectionClass( AccessibilityReportsPage::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( null, $arguments );
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
	 * Ensures the reports page falls back to free state when Pro is installed but no longer valid.
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
	 * Ensures taxonomy coverage only shows full coverage when the effective license is Pro.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_taxonomy_coverage_counts_requires_effective_pro_license() {
		update_option( 'edacp_enable_archive_scanning', 1 );

		$free_counts = $this->invoke_private_method( 'get_taxonomy_coverage_counts', [ false ] );
		$pro_counts  = $this->invoke_private_method( 'get_taxonomy_coverage_counts', [ true ] );

		$this->assertSame( 0, $free_counts['checked'] );
		$this->assertGreaterThanOrEqual( $free_counts['checked'], $free_counts['total'] );
		$this->assertSame( $pro_counts['total'], $pro_counts['checked'] );
	}

	/**
	 * Ensures fallback marker does not disconnect reports when Free is valid and the site remains enrolled.
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
	 * Ensures the next send estimate returns today when it is Monday, otherwise next Monday.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_next_send_estimate_date_returns_today_or_next_monday() {
		$today = new DateTime( 'now', wp_timezone() );

		if ( '1' === $today->format( 'N' ) ) {
			$expected = $today->format( 'Y-m-d' );
		} else {
			$expected = ( new DateTime( 'next monday', wp_timezone() ) )->format( 'Y-m-d' );
		}

		$next_collection = $this->invoke_private_method( 'get_next_send_estimate_date' );

		$this->assertSame( $expected, $next_collection );
	}
}
