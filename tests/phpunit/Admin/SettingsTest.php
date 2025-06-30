<?php

namespace EDAC\Tests\Admin;

use EDAC\Admin\Settings;
use EDAC\Admin\Helpers;
use WP_UnitTestCase;

// Mock WordPress functions not available in unit test environment
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		if ( $option === 'edac_post_types' ) {
			return \EDAC\Tests\Admin\SettingsTest::$mock_edac_post_types ?: $default;
		}
		if ( $option === 'edacp_license_status') {
			return 'valid';
		}
		return $default;
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = [], $output = 'names', $operator = 'and' ) {
		return \EDAC\Tests\Admin\SettingsTest::$mock_valid_post_types ?: [ 'post' => 'post', 'page' => 'page' ];
	}
}

if ( ! function_exists( 'post_type_exists' ) ) {
	function post_type_exists( $post_type ) {
		return in_array( $post_type, array_keys(\EDAC\Tests\Admin\SettingsTest::$mock_valid_post_types ?: [ 'post' => 'post', 'page' => 'page' ]) );
	}
}

// Mock Pro classes if they don't exist
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

	public static $mock_edac_post_types = null;
	public static $mock_valid_post_types = null;
	public static $mock_wpdb_get_var_result = 0;

	public function setUp(): void {
		parent::setUp();
		self::$mock_edac_post_types = null;
		self::$mock_valid_post_types = null;
		self::$mock_wpdb_get_var_result = 0;

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
		self::$mock_edac_post_types = null;
		self::$mock_valid_post_types = null;
		self::$mock_wpdb_get_var_result = 0;
		// Unset the mocked class alias if it was set
		if (class_exists('EDACP\Settings', false)) {
			class_alias('EDAC\Tests\Admin\MockEDACPSettings', 'EDACP\Settings_Original');
		}
		if (class_exists('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings', false)) {
			class_alias('EDAC\Tests\Admin\MockEqualizeDigitalAccessibilityCheckerProAdminSettings', 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings_Original');
		}
	}

	public function test_get_scannable_post_statuses() {
		$statuses = Settings::get_scannable_post_statuses();
		$this->assertEquals( [ 'publish', 'future', 'draft', 'pending', 'private' ], $statuses );
	}

	public function test_get_scannable_post_types_free_version_default() {
		self::$mock_edac_post_types = [ 'post', 'page' ];
		self::$mock_valid_post_types = [ 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' ];
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [ 'post', 'page' ], $post_types );
	}

	public function test_get_scannable_post_types_free_version_with_invalid_type() {
		self::$mock_edac_post_types = [ 'post', 'page', 'non_existent_type' ];
		self::$mock_valid_post_types = [ 'post' => 'post', 'page' => 'page' ];
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [ 'post', 'page' ], $post_types );
	}

	public function test_get_scannable_post_types_free_version_with_attachment_type() {
		// Attachments should be filtered out even if returned by get_post_types
		self::$mock_edac_post_types = [ 'post', 'page', 'attachment' ];
		self::$mock_valid_post_types = [ 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' ];
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [ 'post', 'page' ], $post_types );
	}

	public function test_get_scannable_post_types_free_version_no_valid_custom_types_in_db() {
		self::$mock_edac_post_types = [ 'custom_type_not_registered' ];
		self::$mock_valid_post_types = [ 'post' => 'post', 'page' => 'page' ];
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [], $post_types );
	}

	public function test_get_scannable_post_types_free_version_option_returns_string() {
		// Simulate get_option returning a string instead of array
		// Helpers::get_option_as_array should handle this
		\WP_Mock::userFunction( 'get_option', [
			'args' => ['edac_post_types', []],
			'return' => 'post,page', // Comma-separated string
			'times' => 1,
		]);
		self::$mock_valid_post_types = [ 'post' => 'post', 'page' => 'page' ];
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( [ 'post', 'page' ], $post_types );
	}

	public function test_get_scannable_post_types_pro_version_edacp_settings_exists() {
		// Ensure the original EDACP\Settings does not exist or is aliased away
		if (class_exists('EDACP\Settings') && !is_a('EDACP\Settings', 'EDAC\Tests\Admin\MockEDACPSettings', true)) {
			class_alias('EDACP\Settings', 'EDACP\Settings_Original_For_Test_Pro');
		}
		if (!class_exists('EDACP\Settings')) {
			class_alias('EDAC\Tests\Admin\MockEDACPSettings', 'EDACP\Settings');
		}

		$expected_types = MockEDACPSettings::$returnValue;
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( $expected_types, $post_types );

		// Clean up: restore original class if it was aliased
		if (class_exists('EDACP\Settings_Original_For_Test_Pro', false)) {
			class_alias('EDACP\Settings_Original_For_Test_Pro', 'EDACP\Settings');
			// It seems we cannot truly "unalias" so we might need to ensure this test runs in a separate process if issues arise.
		}
	}

	public function test_get_scannable_post_types_pro_version_new_settings_class_exists() {
		// Ensure the original EqualizeDigital\AccessibilityCheckerPro\Admin\Settings does not exist or is aliased away
		if (class_exists('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings') && !is_a('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings', 'EDAC\Tests\Admin\MockEqualizeDigitalAccessibilityCheckerProAdminSettings', true) ) {
			class_alias('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings', 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings_Original_For_Test_New_Pro');
		}
		// Mock this class to exist
		if (!class_exists('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings')) {
			class_alias('EDAC\Tests\Admin\MockEqualizeDigitalAccessibilityCheckerProAdminSettings', 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings');
		}
		// Also ensure the older EDACP\Settings exists to test precedence
		if (class_exists('EDACP\Settings') && !is_a('EDACP\Settings', 'EDAC\Tests\Admin\MockEDACPSettings', true)) {
			class_alias('EDACP\Settings', 'EDACP\Settings_Original_For_Test_New_Pro_Old');
		}
		if (!class_exists('EDACP\Settings')) {
			class_alias('EDAC\Tests\Admin\MockEDACPSettings', 'EDACP\Settings');
		}


		$expected_types = MockEqualizeDigitalAccessibilityCheckerProAdminSettings::$returnValue;
		$post_types = Settings::get_scannable_post_types();
		$this->assertEquals( $expected_types, $post_types );

		// Clean up
		if (class_exists('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings_Original_For_Test_New_Pro', false)) {
			class_alias('EqualizeDigital\AccessibilityCheckerPro\Admin\Settings_Original_For_Test_New_Pro', 'EqualizeDigital\AccessibilityCheckerPro\Admin\Settings');
		}
		if (class_exists('EDACP\Settings_Original_For_Test_New_Pro_Old', false)) {
			class_alias('EDACP\Settings_Original_For_Test_New_Pro_Old', 'EDACP\Settings');
		}
	}


	public function test_get_scannable_posts_count_no_post_types() {
		self::$mock_edac_post_types = []; // No scannable types configured
		$count = Settings::get_scannable_posts_count();
		$this->assertEquals( 0, $count );
	}

	public function test_get_scannable_posts_count_wpdb_returns_value() {
		self::$mock_edac_post_types = [ 'post', 'page' ];
		self::$mock_valid_post_types = [ 'post' => 'post', 'page' => 'page' ];
		SettingsTest::$mock_wpdb_get_var_result = 15;
		$count = Settings::get_scannable_posts_count();
		$this->assertEquals( 15, $count );
	}

	public function test_get_scannable_posts_count_wpdb_returns_null() {
		self::$mock_edac_post_types = [ 'post' ];
		self::$mock_valid_post_types = [ 'post' => 'post' ];
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
