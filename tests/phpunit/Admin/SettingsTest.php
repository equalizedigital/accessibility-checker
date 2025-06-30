<?php

namespace EDAC\Tests\Admin;

use EDAC\Admin\Settings;
use EDAC\Admin\Helpers;
use WP_UnitTestCase;

// It's generally better to rely on the WP test environment for these functions.
// If specific mocks are needed, they should be handled by WP_Mock or filters if possible.
// Removing global shims as they likely cause conflicts with the CI's WordPress environment.

// Mock Pro classes if they don't exist - this is for local testing if Pro plugin isn't present.
// In CI, these might not be necessary if the Pro plugin files are never loaded,
// or could conflict if they are. Given the class_alias errors, this needs careful handling.
if ( ! class_exists( 'EDACP\Settings' ) ) {
	class MockEDACPSettings {
		public static $returnValue = ['post', 'page', 'custom_pro_type'];
		public static function get_scannable_post_types() {
			return self::$returnValue;
		}
	}
}
if ( ! class_exists( 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings') ) {
	class MockEqualizeDigitalAccessibilityCheckerProAdminSettings {
		public static $returnValue = ['post', 'page', 'new_custom_pro_type'];
		public static function get_scannable_post_types() {
			return self::$returnValue;
		}
	}
}


class SettingsTest extends WP_UnitTestCase {

	// These static variables will be used to control mock behavior for get_post_types
	// via filters, rather than re-declaring the function.
	public static $mock_valid_post_types_filter = null;
	public static $mock_wpdb_get_var_result = 0;

	// Store original options to restore them
	private $original_edac_post_types;

	public function setUp(): void {
		parent::setUp();
		self::$mock_valid_post_types_filter = null;
		self::$mock_wpdb_get_var_result = 0;

		// Store original edac_post_types option
		$this->original_edac_post_types = get_option('edac_post_types');

		// Mock global $wpdb
		global $wpdb;
		$wpdb = $this->getMockBuilder( \wpdb::class )
					 ->disableOriginalConstructor()
					 ->getMock();
		$wpdb->posts = 'wp_posts'; // Set the posts property
		$wpdb->method( 'get_var' )->willReturnCallback( function() {
			return SettingsTest::$mock_wpdb_get_var_result;
		} );
	}

	public function tearDown(): void {
		parent::tearDown();
		// Reset mocks
		self::$mock_valid_post_types_filter = null;
		self::$mock_wpdb_get_var_result = 0;

		// Restore original edac_post_types option
		if (false === $this->original_edac_post_types) {
			delete_option('edac_post_types');
		} else {
			update_option('edac_post_types', $this->original_edac_post_types);
		}

		// Clean up filters
		remove_filter( 'get_post_types', [ $this, 'filter_get_post_types' ] );
		remove_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10 );


		// Unset the mocked class alias if it was set
		// This part needs to be more robust. Relying on @runInSeparateProcess for tests that use aliasing.
		// No reliable way to "unalias" in tearDown.
	}

	// Helper method to filter get_post_types
	public function filter_get_post_types( $post_types, $args = [], $output = 'names', $operator = 'and' ) {
		if (self::$mock_valid_post_types_filter !== null) {
			return self::$mock_valid_post_types_filter;
		}
		return $post_types; // Passthrough if no mock is set for this call
	}

	// Helper method to filter post_type_exists
	public function filter_post_type_exists( $exists, $post_type ) {
		if (self::$mock_valid_post_types_filter !== null) {
			return array_key_exists( $post_type, self::$mock_valid_post_types_filter );
		}
		return $exists; // Passthrough
	}

	public function test_get_scannable_post_statuses() {
		$statuses = Settings::get_scannable_post_statuses();
		$this->assertEquals( [ 'publish', 'future', 'draft', 'pending', 'private' ], $statuses );
	}

	public function test_get_scannable_post_types_free_version_default() {
		update_option( 'edac_post_types', [ 'post', 'page' ] );
		self::$mock_valid_post_types_filter = [ 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' ];
		add_filter( 'get_post_types', [ $this, 'filter_get_post_types' ], 10, 4 );
		add_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10, 2 );

		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [ 'post', 'page' ], $post_types );
	}

	public function test_get_scannable_post_types_free_version_with_invalid_type() {
		update_option( 'edac_post_types', [ 'post', 'page', 'non_existent_type' ] );
		self::$mock_valid_post_types_filter = [ 'post' => 'post', 'page' => 'page' ];
		add_filter( 'get_post_types', [ $this, 'filter_get_post_types' ], 10, 4 );
		add_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10, 2 );

		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [ 'post', 'page' ], $post_types );
	}

	public function test_get_scannable_post_types_free_version_with_attachment_type() {
		// Attachments should be filtered out even if returned by get_post_types
		update_option( 'edac_post_types', [ 'post', 'page', 'attachment' ] );
		self::$mock_valid_post_types_filter = [ 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' ];
		add_filter( 'get_post_types', [ $this, 'filter_get_post_types' ], 10, 4 );
		add_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10, 2 );

		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [ 'post', 'page' ], $post_types );
	}

	public function test_get_scannable_post_types_free_version_no_valid_custom_types_in_db() {
		update_option( 'edac_post_types', [ 'custom_type_not_registered' ] );
		self::$mock_valid_post_types_filter = [ 'post' => 'post', 'page' => 'page' ];
		add_filter( 'get_post_types', [ $this, 'filter_get_post_types' ], 10, 4 );
		add_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10, 2 );

		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [], $post_types );
	}

	public function test_get_scannable_post_types_free_version_option_returns_string() {
		// Simulate get_option returning a string instead of array
		// Helpers::get_option_as_array should handle this
		update_option( 'edac_post_types', 'post,page' ); // Test with string
		self::$mock_valid_post_types_filter = [ 'post' => 'post', 'page' => 'page' ];
		add_filter( 'get_post_types', [ $this, 'filter_get_post_types' ], 10, 4 );
		add_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10, 2 );

		$post_types = Settings::get_scannable_post_types();
		// Helpers::get_option_as_array returns [] if option is not an array.
		// So this test should reflect that behavior.
		// If 'post,page' string was meant to be parsed, Helpers::get_option_as_array would need modification.
		// Based on current Helpers::get_option_as_array, string value results in [].
		$this->assertEquals( [], $post_types );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_scannable_post_types_pro_version_edacp_settings_exists() {
		// Ensure the original EDACP\Settings does not exist or is aliased away
		// This test will run in a separate process, so aliasing should be clean.
		if (!class_exists('EDACP\Settings')) {
			class_alias('EDAC\Tests\Admin\MockEDACPSettings', 'EDACP\Settings');
		} elseif (!is_a('EDACP\Settings', 'EDAC\Tests\Admin\MockEDACPSettings', true)) {
			// If it exists but is not our mock, this is a problem for this test design.
			// This might happen if the Pro plugin is somehow active in the test environment.
			$this->markTestSkipped('Original EDACP\Settings class is present and not the mock. Cannot reliably test this scenario.');
		}
		// Ensure the newer Pro settings class does NOT exist for this test case
		if (class_exists('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings') && !is_a('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings','EDAC\Tests\Admin\MockEqualizeDigitalAccessibilityCheckerProAdminSettings', true )) {
			// This is tricky. We'd need to unload it or ensure it's not loaded.
			// For now, assume @runInSeparateProcess gives a clean slate for class_alias.
			// If EqualizeDigital\AccessibilityCheckerPro\Admin\Settings is the *actual* class, we can't alias over it.
		}


		$expected_types = MockEDACPSettings::$returnValue;
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( $expected_types, $post_types );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_scannable_post_types_pro_version_new_settings_class_exists() {
		// Mock this class to exist
		if (!class_exists('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings')) {
			class_alias('EDAC\Tests\Admin\MockEqualizeDigitalAccessibilityCheckerProAdminSettings', 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings');
		} elseif (!is_a('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings', 'EDAC\Tests\Admin\MockEqualizeDigitalAccessibilityCheckerProAdminSettings', true)) {
			$this->markTestSkipped('Original EqualizeDigital\AccessibilityCheckerPro\Admin\Settings class is present and not the mock.');
		}

		// Also ensure the older EDACP\Settings exists to test precedence
		if (!class_exists('EDACP\Settings')) {
			class_alias('EDAC\Tests\Admin\MockEDACPSettings', 'EDACP\Settings');
		} elseif (!is_a('EDACP\Settings', 'EDAC\Tests\Admin\MockEDACPSettings', true) ){
			// This indicates the actual EDACP\Settings might be loaded.
		}


		$expected_types = MockEqualizeDigitalAccessibilityCheckerProAdminSettings::$returnValue;
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( $expected_types, $post_types );
	}


	public function test_get_scannable_posts_count_no_post_types() {
		update_option( 'edac_post_types', [] ); // No scannable types configured
		$count = Settings::get_scannable_posts_count();
		$this->assertEquals( 0, $count );
	}

	public function test_get_scannable_posts_count_wpdb_returns_value() {
		update_option( 'edac_post_types', [ 'post', 'page' ] );
		self::$mock_valid_post_types_filter = [ 'post' => 'post', 'page' => 'page' ];
		add_filter( 'get_post_types', [ $this, 'filter_get_post_types' ], 10, 4 );
		add_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10, 2 );

		SettingsTest::$mock_wpdb_get_var_result = 15;
		$count = Settings::get_scannable_posts_count();
		$this->assertEquals( 15, $count );
	}

	public function test_get_scannable_posts_count_wpdb_returns_null() {
		update_option( 'edac_post_types', [ 'post' ] );
		self::$mock_valid_post_types_filter = [ 'post' => 'post' ];
		add_filter( 'get_post_types', [ $this, 'filter_get_post_types' ], 10, 4 );
		add_filter( 'post_type_exists', [ $this, 'filter_post_type_exists' ], 10, 2 );

		SettingsTest::$mock_wpdb_get_var_result = null;
		$count = Settings::get_scannable_posts_count();
		$this->assertEquals( null, $count ); // Or 0 depending on desired behavior for null SQL result
	}

	public function test_get_scannable_posts_count_empty_statuses_returns_zero() {
		// This scenario is technically not possible with current get_scannable_post_statuses
		// but good to have if that method changes.
		// We can't directly mock get_scannable_post_statuses easily without DI or more complex mocking.
		// So we rely on the fact it always returns statuses.
		// If it could return empty, this test would be:
		// Settings::$mock_scannable_post_statuses = []; // Hypothetical mock
		// $count = Settings::get_scannable_posts_count();
		// $this->assertEquals(0, $count);
		$this->markTestSkipped('Cannot directly test empty statuses without deeper refactoring or more complex mocking setup for static methods.');
	}
}
