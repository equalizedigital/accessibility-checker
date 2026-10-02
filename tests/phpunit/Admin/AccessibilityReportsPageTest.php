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

		$next_send_date = $this->invoke_private_method( 'get_next_send_estimate_date' );

		$this->assertSame( $expected, $next_send_date );
	}

	/**
	 * Top issues are shown most severe first, then by count, with severity labels.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_orders_by_severity_then_count() {
		$rules  = [
			'low_rule'      => [
				'title'    => 'Low rule',
				'severity' => 4,
			],
			'critical_few'  => [
				'title'    => 'Critical few',
				'severity' => 1,
			],
			'critical_many' => [
				'title'    => 'Critical many',
				'severity' => 1,
			],
			'high_rule'     => [
				'title'    => 'High rule',
				'severity' => 2,
			],
		];
		$issues = [
			[
				'rule_slug'   => 'low_rule',
				'issue_count' => 99,
			],
			[
				'rule_slug'   => 'critical_few',
				'issue_count' => 2,
			],
			[
				'rule_slug'   => 'high_rule',
				'issue_count' => 5,
			],
			[
				'rule_slug'   => 'critical_many',
				'issue_count' => 7,
			],
		];

		$result = $this->invoke_private_method( 'format_top_issues', [ $issues, $rules ] );

		$this->assertSame(
			[ 'Critical many', 'Critical few', 'High rule', 'Low rule' ],
			array_column( $result, 'title' )
		);
		$this->assertSame( [ 7, 2, 5, 99 ], array_column( $result, 'count' ) );
		$this->assertSame( [ 'Critical', 'Critical', 'High', 'Low' ], array_column( $result, 'severity' ) );
		$this->assertArrayNotHasKey( 'rank', $result[0] );
	}

	/**
	 * Only the top five rows are returned, and only the first ten inputs are considered.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_caps_output_at_five_from_first_ten() {
		$rules  = [];
		$issues = [];
		for ( $i = 1; $i <= 12; $i++ ) {
			$rules[ 'rule_' . $i ] = [
				'title'    => 'Rule ' . $i,
				'severity' => 3,
			];
			$issues[]              = [
				'rule_slug'   => 'rule_' . $i,
				'issue_count' => $i,
			];
		}

		$result = $this->invoke_private_method( 'format_top_issues', [ $issues, $rules ] );

		// Rules 11 and 12 are beyond the first ten inputs, so the top five are rules 10 to 6.
		$this->assertSame(
			[ 'Rule 10', 'Rule 9', 'Rule 8', 'Rule 7', 'Rule 6' ],
			array_column( $result, 'title' )
		);
	}

	/**
	 * Rules missing from the index fall back to the slug and an Unknown severity.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_handles_unknown_rules() {
		$result = $this->invoke_private_method(
			'format_top_issues',
			[
				[
					[
						'rule_slug'   => 'mystery_rule',
						'issue_count' => '3',
					],
				],
				[],
			]
		);

		$this->assertSame( 'mystery_rule', $result[0]['title'] );
		$this->assertSame( 'Unknown', $result[0]['severity'] );
		$this->assertSame( 3, $result[0]['count'] );
	}

	/**
	 * No issues gives an empty list.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_empty() {
		$this->assertSame( [], $this->invoke_private_method( 'format_top_issues', [ [], [] ] ) );
	}
}
