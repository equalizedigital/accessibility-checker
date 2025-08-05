<?php
/**
 * Tests for the Site Health Checks class.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Tests\Admin\SiteHealth;

use EDAC\Admin\SiteHealth\Checks;
use EDAC\Admin\Scans_Stats;
use EDAC\Admin\Settings;
use WP_UnitTestCase;

/**
 * Test case for Site Health Checks functionality.
 *
 * @since 1.29.0
 */
class ChecksTest extends WP_UnitTestCase {

	/**
	 * Instance of the Checks class.
	 *
	 * @var Checks
	 */
	private $checks;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->checks = new Checks();
	}

	/**
	 * Test that init_hooks adds the correct filter.
	 */
	public function test_init_hooks_adds_filter() {
		$this->checks->init_hooks();
		$this->assertTrue( has_filter( 'site_status_tests', [ $this->checks, 'register_tests' ] ) !== false );
	}

	/**
	 * Test that register_tests adds all expected tests.
	 */
	public function test_register_tests_adds_all_tests() {
		$tests  = [];
		$result = $this->checks->register_tests( $tests );

		$this->assertArrayHasKey( 'direct', $result );
		$this->assertArrayHasKey( 'edac_issues', $result['direct'] );
		$this->assertArrayHasKey( 'edac_scanned', $result['direct'] );
		$this->assertArrayHasKey( 'edac_post_types', $result['direct'] );

		// Test structure of each test.
		$this->assertEquals( 'Accessibility issues', $result['direct']['edac_issues']['label'] );
		$this->assertEquals( [ $this->checks, 'test_for_issues' ], $result['direct']['edac_issues']['test'] );

		$this->assertEquals( 'Content checked for accessibility', $result['direct']['edac_scanned']['label'] );
		$this->assertEquals( [ $this->checks, 'test_content_scanned' ], $result['direct']['edac_scanned']['test'] );

		$this->assertEquals( 'Post types configured for accessibility checks', $result['direct']['edac_post_types']['label'] );
		$this->assertEquals( [ $this->checks, 'test_post_types_configured' ], $result['direct']['edac_post_types']['test'] );
	}

	/**
	 * Test that register_tests preserves existing tests.
	 */
	public function test_register_tests_preserves_existing_tests() {
		$existing_tests = [
			'direct' => [
				'existing_test' => [
					'label' => 'Existing Test',
					'test'  => 'some_callback',
				],
			],
		];

		$result = $this->checks->register_tests( $existing_tests );

		$this->assertArrayHasKey( 'existing_test', $result['direct'] );
		$this->assertEquals( 'Existing Test', $result['direct']['existing_test']['label'] );
	}

	/**
	 * Test test_for_issues with no issues.
	 */
	public function test_test_for_issues_with_no_issues() {
		// Mock Scans_Stats to return no issues.
		$mock_stats = $this->getMockBuilder( Scans_Stats::class )
			->disableOriginalConstructor()
			->getMock();
		
		$mock_stats->method( 'summary' )
			->willReturn(
				[
					'errors'   => 0,
					'warnings' => 0,
				] 
			);

		// Use reflection to set the private stats property.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'errors'   => 0,
				'warnings' => 0,
			] 
		);

		$result = $this->checks->test_for_issues();

		$this->assertEquals( 'good', $result['status'] );
		$this->assertEquals( 'No accessibility issues detected', $result['label'] );
		$this->assertEquals( 'Accessibility Checker has not found any issues in scanned content.', $result['description'] );
		$this->assertEquals( 'edac_issues', $result['test'] );
		$this->assertArrayHasKey( 'badge', $result );
		$this->assertEquals( 'Accessibility', $result['badge']['label'] );
		$this->assertEquals( 'blue', $result['badge']['color'] );
	}

	/**
	 * Test test_for_issues with issues detected.
	 */
	public function test_test_for_issues_with_issues_detected() {
		// Mock stats with issues.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'errors'   => 5,
				'warnings' => 3,
			] 
		);

		$result = $this->checks->test_for_issues();

		$this->assertEquals( 'recommended', $result['status'] );
		$this->assertEquals( 'Accessibility issues detected', $result['label'] );
		$this->assertStringContainsString( '5 errors and 3 warnings', $result['description'] );
		$this->assertEquals( 'edac_issues', $result['test'] );
		$this->assertArrayHasKey( 'actions', $result );
		$this->assertStringContainsString( 'button-primary', $result['actions'] );
	}

	/**
	 * Test test_for_issues with Pro version available.
	 */
	public function test_test_for_issues_with_pro_version() {
		// Only run if constants aren't already defined.
		$skip_test = defined( 'EDACP_VERSION' ) || defined( 'EDAC_KEY_VALID' );
		
		if ( ! $skip_test ) {
			if ( ! defined( 'EDACP_VERSION' ) ) {
				define( 'EDACP_VERSION', '1.0.0' );
			}
			if ( ! defined( 'EDAC_KEY_VALID' ) ) {
				define( 'EDAC_KEY_VALID', true );
			}
		}

		// Mock stats with issues.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'errors'   => 2,
				'warnings' => 1,
			] 
		);

		$result = $this->checks->test_for_issues();

		if ( $skip_test || ! ( defined( 'EDACP_VERSION' ) && defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID ) ) {
			// For free version, expect the basic accessibility_checker URL.
			$this->assertStringContainsString( 'accessibility_checker', $result['actions'] );
			$this->assertStringContainsString( 'View Accessibility Checker', $result['actions'] );
		} else {
			// For Pro version, expect the issues-specific URL.
			$this->assertStringContainsString( 'accessibility_checker_issues', $result['actions'] );
			$this->assertStringContainsString( 'View Issues', $result['actions'] );
		}
	}

	/**
	 * Test test_content_scanned with no scanned content.
	 */
	public function test_test_content_scanned_with_no_content() {
		// Mock stats with no scanned posts.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'posts_scanned' => 0,
			] 
		);

		$result = $this->checks->test_content_scanned();

		$this->assertEquals( 'recommended', $result['status'] );
		$this->assertEquals( 'Content has not been checked', $result['label'] );
		$this->assertEquals( 'No posts have been scanned yet. Run a full site scan to begin checking your content for accessibility issues.', $result['description'] );
		$this->assertEquals( 'edac_scanned', $result['test'] );
		$this->assertStringContainsString( 'accessibility_checker_full_site_scan', $result['actions'] );
		$this->assertStringContainsString( 'Start full site scan', $result['actions'] );
	}

	/**
	 * Test test_content_scanned with scanned content (singular).
	 */
	public function test_test_content_scanned_with_single_post() {
		// Mock stats with one scanned post.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'posts_scanned' => 1,
			] 
		);

		$result = $this->checks->test_content_scanned();

		$this->assertEquals( 'good', $result['status'] );
		$this->assertEquals( 'Content is being checked for accessibility', $result['label'] );
		$this->assertStringContainsString( 'has scanned 1 post for', $result['description'] );
		$this->assertEquals( 'edac_scanned', $result['test'] );
	}

	/**
	 * Test test_content_scanned with multiple scanned posts.
	 */
	public function test_test_content_scanned_with_multiple_posts() {
		// Mock stats with multiple scanned posts.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'posts_scanned' => 150,
			] 
		);

		$result = $this->checks->test_content_scanned();

		$this->assertEquals( 'good', $result['status'] );
		$this->assertEquals( 'Content is being checked for accessibility', $result['label'] );
		$this->assertStringContainsString( 'has scanned 150 posts for', $result['description'] );
		$this->assertEquals( 'edac_scanned', $result['test'] );
	}

	/**
	 * Test test_post_types_configured with no post types configured.
	 */
	public function test_test_post_types_configured_with_no_types() {
		// Clear the edac_post_types option to simulate no post types configured.
		update_option( 'edac_post_types', [] );

		$result = $this->checks->test_post_types_configured();

		$this->assertEquals( 'critical', $result['status'] );
		$this->assertEquals( 'No post types selected for accessibility checking', $result['label'] );
		$this->assertEquals( 'Accessibility Checker cannot scan any content because no post types have been selected. Without configured post types, no accessibility issues will be detected.', $result['description'] );
		$this->assertEquals( 'edac_post_types', $result['test'] );
		$this->assertEquals( 'red', $result['badge']['color'] );
		$this->assertStringContainsString( 'accessibility_checker_settings', $result['actions'] );
		$this->assertStringContainsString( 'Configure post types', $result['actions'] );
	}

	/**
	 * Test test_post_types_configured with single post type.
	 */
	public function test_test_post_types_configured_with_single_type() {
		// Set option to have single post type.
		update_option( 'edac_post_types', [ 'post' ] );

		$result = $this->checks->test_post_types_configured();

		$this->assertEquals( 'good', $result['status'] );
		$this->assertEquals( 'Post types are configured for accessibility checking', $result['label'] );
		$this->assertStringContainsString( 'configured to scan 1 post type: post', $result['description'] );
		$this->assertEquals( 'edac_post_types', $result['test'] );
		$this->assertEquals( 'blue', $result['badge']['color'] );
	}

	/**
	 * Test test_post_types_configured with multiple post types.
	 */
	public function test_test_post_types_configured_with_multiple_types() {
		// Set option to have multiple post types (only use built-in WordPress post types).
		update_option( 'edac_post_types', [ 'post', 'page' ] );

		$result = $this->checks->test_post_types_configured();

		$this->assertEquals( 'good', $result['status'] );
		$this->assertEquals( 'Post types are configured for accessibility checking', $result['label'] );
		$this->assertStringContainsString( 'configured to scan 2 post types: post, page', $result['description'] );
		$this->assertEquals( 'edac_post_types', $result['test'] );
		$this->assertEquals( 'blue', $result['badge']['color'] );
	}

	/**
	 * Test get_accessibility_badge with default color.
	 */
	public function test_get_accessibility_badge_default_color() {
		$reflection = new \ReflectionClass( $this->checks );
		$method     = $reflection->getMethod( 'get_accessibility_badge' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->checks );

		$this->assertEquals( 'Accessibility', $result['label'] );
		$this->assertEquals( 'blue', $result['color'] );
	}

	/**
	 * Test get_accessibility_badge with custom color.
	 */
	public function test_get_accessibility_badge_custom_color() {
		$reflection = new \ReflectionClass( $this->checks );
		$method     = $reflection->getMethod( 'get_accessibility_badge' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->checks, 'red' );

		$this->assertEquals( 'Accessibility', $result['label'] );
		$this->assertEquals( 'red', $result['color'] );
	}

	/**
	 * Test get_issues_link for free version.
	 */
	public function test_get_issues_link_free_version() {
		$reflection = new \ReflectionClass( $this->checks );
		$method     = $reflection->getMethod( 'get_issues_link' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->checks );

		$this->assertStringContainsString( 'accessibility_checker', $result['url'] );
		$this->assertEquals( 'View Accessibility Checker', $result['text'] );
	}

	/**
	 * Test number formatting in issues description.
	 */
	public function test_number_formatting_in_issues() {
		// Mock stats with large numbers.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'errors'   => 1234,
				'warnings' => 5678,
			] 
		);

		$result = $this->checks->test_for_issues();

		// WordPress number formatting should add commas.
		$this->assertStringContainsString( '1,234', $result['description'] );
		$this->assertStringContainsString( '5,678', $result['description'] );
	}

	/**
	 * Test number formatting in content scanned description.
	 */
	public function test_number_formatting_in_content_scanned() {
		// Mock stats with large number.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'posts_scanned' => 12345,
			] 
		);

		$result = $this->checks->test_content_scanned();

		// WordPress number formatting should add commas.
		$this->assertStringContainsString( '12,345', $result['description'] );
	}

	/**
	 * Test that all test methods return required array keys.
	 */
	public function test_all_tests_return_required_keys() {
		// Mock required data.
		$reflection     = new \ReflectionClass( $this->checks );
		$stats_property = $reflection->getProperty( 'stats' );
		$stats_property->setAccessible( true );
		$stats_property->setValue(
			$this->checks,
			[
				'errors'        => 1,
				'warnings'      => 1,
				'posts_scanned' => 1,
			] 
		);

		// Set up post types option.
		update_option( 'edac_post_types', [ 'post' ] );

		$required_keys = [ 'status', 'label', 'description', 'test', 'badge' ];

		// Test test_for_issues.
		$result = $this->checks->test_for_issues();
		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $result, "test_for_issues missing key: $key" );
		}

		// Test test_content_scanned.
		$result = $this->checks->test_content_scanned();
		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $result, "test_content_scanned missing key: $key" );
		}

		// Test test_post_types_configured.
		$result = $this->checks->test_post_types_configured();
		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $result, "test_post_types_configured missing key: $key" );
		}
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_post_types' );
		parent::tearDown();
	}
}
