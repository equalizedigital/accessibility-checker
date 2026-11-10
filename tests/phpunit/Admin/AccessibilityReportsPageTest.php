<?php
/**
 * Tests for Accessibility Reports page license context behavior.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage;

/**
 * Test cases for reports page fallback-aware license handling and preview formatting.
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
	 * Ensures the preview payload derives its figures from the raw summary.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_preview_data_derives_figures_from_summary() {
		$preview = $this->invoke_private_method(
			'get_preview_data',
			[
				[
					'errors'                      => 4,
					'warnings'                    => 6,
					'posts_scanned'               => 12,
					'scannable_post_types_count'  => 3,
					'public_post_types_count'     => 7,
					'passed_percentage_formatted' => '91.5%',
					'top_pages_with_issues'       => [ 'a', 'b', 'c', 'd', 'e', 'f' ],
					'top_issues_found_on_site'    => [
						[
							'rule_slug'   => 'not_a_registered_rule',
							'issue_count' => 2,
						],
					],
				],
				false,
			]
		);

		$this->assertSame( 10, $preview['total_issues'] );
		$this->assertSame( 4, $preview['problems'] );
		$this->assertSame( 6, $preview['needs_review'] );
		$this->assertSame( '91.5%', $preview['passed_checks'] );
		$this->assertSame( 12, $preview['urls_scanned'] );
		$this->assertSame( '3/7', $preview['post_types_checked'] );
		$this->assertSame( [ 'a', 'b', 'c', 'd', 'e' ], $preview['top_pages'] );
		$this->assertMatchesRegularExpression( '/^0\/\d+$/', $preview['taxonomies_checked'] );
		$this->assertSame(
			[
				[
					'title'    => 'not_a_registered_rule',
					'severity' => 'Unknown',
					'count'    => 2,
				],
			],
			$preview['top_issues']
		);
	}

	/**
	 * Ensures an empty summary falls back to zeroed figures and the N/A placeholder.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_preview_data_falls_back_to_defaults_for_empty_summary() {
		$preview = $this->invoke_private_method( 'get_preview_data', [ [], false ] );

		$this->assertSame( 0, $preview['total_issues'] );
		$this->assertSame( 0, $preview['problems'] );
		$this->assertSame( 0, $preview['needs_review'] );
		$this->assertSame( 'N/A', $preview['passed_checks'] );
		$this->assertSame( 0, $preview['urls_scanned'] );
		$this->assertSame( '0/0', $preview['post_types_checked'] );
		$this->assertMatchesRegularExpression( '/^0\/\d+$/', $preview['taxonomies_checked'] );
		$this->assertSame( [], $preview['top_pages'] );
		$this->assertSame( [], $preview['top_issues'] );
	}

	/**
	 * Ensures the post type ratio keeps the denominator at least as large as the numerator.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_preview_data_post_type_ratio_uses_the_larger_denominator() {
		$preview = $this->invoke_private_method(
			'get_preview_data',
			[
				[
					'scannable_post_types_count' => 9,
					'public_post_types_count'    => 4,
				],
				false,
			]
		);

		$this->assertSame( '9/9', $preview['post_types_checked'] );
	}

	/**
	 * Ensures taxonomy coverage is only reported as complete for an effective Pro license.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_preview_data_reports_full_taxonomy_coverage_for_pro() {
		update_option( 'edacp_enable_archive_scanning', 1 );

		$preview = $this->invoke_private_method( 'get_preview_data', [ [], true ] );

		list( $checked, $total ) = explode( '/', $preview['taxonomies_checked'] );

		$this->assertGreaterThan( 0, (int) $total );
		$this->assertSame( $total, $checked );
	}

	/**
	 * Ensures top issue rows fall back to the slug and Unknown severity, and that a rule
	 * declaring no severity ranks alongside an explicit severity of 99 (the default).
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_maps_known_and_unknown_rules() {
		$rules = [
			'known'         => [
				'title'    => 'Known rule',
				'severity' => 2,
			],
			'missing_level' => [
				'title' => 'No severity',
			],
			'explicit_99'   => [
				'title'    => 'Explicit 99',
				'severity' => 99,
			],
		];

		$issues = [
			[
				'rule_slug'   => 'missing_level',
				'issue_count' => 5,
			],
			[
				'rule_slug'   => 'explicit_99',
				'issue_count' => 9,
			],
			[
				'rule_slug'   => 'unregistered',
				'issue_count' => 2,
			],
			[
				'rule_slug'   => 'known',
				'issue_count' => 3,
			],
		];

		$formatted = $this->invoke_private_method( 'format_top_issues', [ $issues, $rules ] );

		$this->assertSame(
			[
				[
					'title'    => 'Known rule',
					'severity' => 'High',
					'count'    => 3,
				],
				[
					'title'    => 'Explicit 99',
					'severity' => 'Unknown',
					'count'    => 9,
				],
				[
					'title'    => 'No severity',
					'severity' => 'Unknown',
					'count'    => 5,
				],
				[
					'title'    => 'unregistered',
					'severity' => 'Unknown',
					'count'    => 2,
				],
			],
			$formatted
		);
	}

	/**
	 * Ensures top issue rows sort by severity then count and are capped at five rows.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_sorts_and_limits_rows() {
		$rules = [
			'crit' => [
				'title'    => 'Critical rule',
				'severity' => 1,
			],
			'high' => [
				'title'    => 'High rule',
				'severity' => 2,
			],
			'warn' => [
				'title'    => 'Medium rule',
				'severity' => 3,
			],
		];

		$issues = [
			[
				'rule_slug'   => 'warn',
				'issue_count' => 9,
			],
			[
				'rule_slug'   => 'crit',
				'issue_count' => 1,
			],
			[
				'rule_slug'   => 'high',
				'issue_count' => 5,
			],
			[
				'rule_slug'   => 'u2',
				'issue_count' => 2,
			],
			[
				'rule_slug'   => 'u4',
				'issue_count' => 4,
			],
			[
				'rule_slug'   => 'u8',
				'issue_count' => 8,
			],
			[
				'rule_slug'   => 'u6',
				'issue_count' => 6,
			],
		];

		$formatted = $this->invoke_private_method( 'format_top_issues', [ $issues, $rules ] );

		$this->assertSame(
			[
				[
					'title'    => 'Critical rule',
					'severity' => 'Critical',
					'count'    => 1,
				],
				[
					'title'    => 'High rule',
					'severity' => 'High',
					'count'    => 5,
				],
				[
					'title'    => 'Medium rule',
					'severity' => 'Medium',
					'count'    => 9,
				],
				[
					'title'    => 'u8',
					'severity' => 'Unknown',
					'count'    => 8,
				],
				[
					'title'    => 'u6',
					'severity' => 'Unknown',
					'count'    => 6,
				],
			],
			$formatted
		);
	}

	/**
	 * Only the first ten inputs are considered before the top five are chosen.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_only_considers_the_first_ten_inputs() {
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
	 * No issues gives an empty list.
	 *
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_top_issues_empty() {
		$this->assertSame( [], $this->invoke_private_method( 'format_top_issues', [ [], [] ] ) );
	}

	/**
	 * Ensures every severity number maps to its label, with Unknown as the catch-all.
	 *
	 * @param int    $severity Severity number.
	 * @param string $expected Expected label.
	 *
	 * @dataProvider provider_severities
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_format_severity_maps_numbers_to_labels( int $severity, string $expected ) {
		$this->assertSame( $expected, $this->invoke_private_method( 'format_severity', [ $severity ] ) );
	}

	/**
	 * Severity numbers and their expected labels.
	 *
	 * @return array[]
	 */
	public function provider_severities() {
		return [
			'critical'    => [ 1, 'Critical' ],
			'high'        => [ 2, 'High' ],
			'medium'      => [ 3, 'Medium' ],
			'low'         => [ 4, 'Low' ],
			'zero'        => [ 0, 'Unknown' ],
			'out of band' => [ 5, 'Unknown' ],
			'negative'    => [ -1, 'Unknown' ],
			'unset value' => [ 99, 'Unknown' ],
		];
	}

	/**
	 * Ensures keys of eight characters or fewer are left intact and longer keys are masked.
	 *
	 * @param string $key      License key.
	 * @param string $expected Expected masked value.
	 *
	 * @dataProvider provider_license_keys
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_mask_license_key( string $key, string $expected ) {
		$this->assertSame( $expected, $this->invoke_private_method( 'mask_license_key', [ $key ] ) );
	}

	/**
	 * License keys and their expected masked values.
	 *
	 * @return array[]
	 */
	public function provider_license_keys() {
		return [
			'empty'             => [ '', '' ],
			'short'             => [ 'abc', 'abc' ],
			'exactly eight'     => [ 'abcdefgh', 'abcdefgh' ],
			'nine (star floor)' => [ 'abcdefghi', 'abcd****fghi' ],
			'long'              => [ 'abcdefghijklmnopqrst', 'abcd************qrst' ],
		];
	}

	/**
	 * The add_page() method wires the reports tab onto the settings page: the
	 * filter the settings page builds its tab list from, and the action it fires
	 * to render the active tab's content.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage::add_page
	 */
	public function test_add_page_wires_the_reports_tab_and_content_callback() {
		$this->page->add_page();

		$this->assertNotFalse( has_filter( 'edac_filter_settings_tab_items', [ $this->page, 'add_reports_tab' ] ) );
		$this->assertSame( 10, has_action( 'edac_settings_tab_content', [ $this->page, 'maybe_render_tab_content' ] ) );
	}

	/**
	 * The reports tab is appended to the tabs already registered, carrying the
	 * slug the settings page switches on, the label and badge it renders, and the
	 * capability the constructor was given. Without Pro the plugin owns no license
	 * tab, so the reports tab sorts third (after Fixes).
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage::add_reports_tab
	 */
	public function test_add_reports_tab_appends_the_reports_tab() {
		// The Pro plugin owns EDACP_VERSION; this test needs the free plugin alone,
		// so a leaked constant fails here instead of silently changing the order.
		$this->assertFalse( defined( 'EDACP_VERSION' ) );

		$existing = [
			[
				'slug'  => 'general',
				'label' => 'General',
				'order' => 1,
			],
		];

		$tabs = $this->page->add_reports_tab( $existing );

		$this->assertCount( 2, $tabs );
		$this->assertSame( 'general', $tabs[0]['slug'] );
		$this->assertSame( 'accessibility-reports', $tabs[1]['slug'] );
		$this->assertSame( 'Accessibility Reports', $tabs[1]['label'] );
		$this->assertSame( 'New', $tabs[1]['badge'] );
		$this->assertSame( 3, $tabs[1]['order'] );
		$this->assertSame( 'manage_options', $tabs[1]['capability'] );
	}

	/**
	 * With Pro installed the reports tab sorts fourth, after the Pro license tab,
	 * so installing Pro inserts the license tab rather than reordering the rest.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage::add_reports_tab
	 */
	public function test_add_reports_tab_sorts_fourth_when_pro_is_installed() {
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', 'test-pro-version' );
		}

		$tabs = $this->page->add_reports_tab( [] );

		$this->assertSame( 'accessibility-reports', $tabs[0]['slug'] );
		$this->assertSame( 4, $tabs[0]['order'] );
	}

	/**
	 * The tab content callback renders the reports page on the reports tab only,
	 * so the page markup is not echoed into every other settings tab. The page
	 * render is intercepted so the assertion is about the branch, not the markup.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage::maybe_render_tab_content
	 */
	public function test_maybe_render_tab_content_renders_on_the_reports_tab_only() {
		$probe = new class( 'manage_options' ) extends AccessibilityReportsPage {
			/**
			 * How many times the tab callback reached the page render.
			 *
			 * @var int
			 */
			public $render_calls = 0;

			/**
			 * Record the call instead of rendering the page.
			 *
			 * @return void
			 */
			public function render_page() {
				++$this->render_calls;
			}
		};

		$probe->maybe_render_tab_content( 'general' );
		$probe->maybe_render_tab_content( null );
		$this->assertSame( 0, $probe->render_calls );

		$probe->maybe_render_tab_content( 'accessibility-reports' );
		$this->assertSame( 1, $probe->render_calls );
	}

	/**
	 * The rule index keys every registered rule by its slug, which is what the
	 * preview table looks rules up by when it formats the top issues.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage::get_rule_index
	 * @throws ReflectionException If reflection fails.
	 */
	public function test_get_rule_index_keys_every_registered_rule_by_its_slug() {
		$rules = edac_register_rules();
		$index = $this->invoke_private_method( 'get_rule_index' );

		$this->assertNotEmpty( $index );
		$this->assertCount( count( $rules ), $index );

		foreach ( $index as $slug => $rule ) {
			$this->assertNotSame( '', $slug );
			$this->assertSame( $slug, $rule['slug'] );
		}

		// A rule the preview table renders against resolves to its full definition.
		$this->assertArrayHasKey( 'img_alt_missing', $index );
		$this->assertSame( 'Image Missing Alternative Text', $index['img_alt_missing']['title'] );
	}
}
