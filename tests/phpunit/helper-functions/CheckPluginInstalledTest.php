<?php
/**
 * Class CheckPluginInstalledTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_check_plugin_installed() function.
 */
class CheckPluginInstalledTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_check_plugin_installed function with plugin found by key.
	 */
	public function test_plugin_found_by_key() {
		// Mock the get_plugins function to return test data.
		add_filter( 'pre_get_plugins', function() {
			return [
				'test-plugin/test-plugin.php' => [
					'Name' => 'Test Plugin',
					'Version' => '1.0.0',
				],
				'another-plugin/another.php' => [
					'Name' => 'Another Plugin',
					'Version' => '2.0.0',
				],
			];
		});

		$result = edac_check_plugin_installed( 'test-plugin/test-plugin.php' );
		$this->assertTrue( $result );

		// Clean up the filter.
		remove_all_filters( 'pre_get_plugins' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with plugin found by value.
	 */
	public function test_plugin_found_by_value() {
		// Mock the get_plugins function to return test data.
		add_filter( 'pre_get_plugins', function() {
			return [
				'plugin-one/main.php' => [
					'Name' => 'Plugin One',
					'Version' => '1.0.0',
				],
				'plugin-two/init.php' => [
					'Name' => 'Plugin Two',
					'Version' => '1.5.0', 
				],
			];
		});

		$result = edac_check_plugin_installed( 'plugin-two/init.php' );
		$this->assertTrue( $result );

		// Clean up the filter.
		remove_all_filters( 'pre_get_plugins' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with plugin not found.
	 */
	public function test_plugin_not_found() {
		// Mock the get_plugins function to return test data.
		add_filter( 'pre_get_plugins', function() {
			return [
				'existing-plugin/plugin.php' => [
					'Name' => 'Existing Plugin',
					'Version' => '1.0.0',
				],
			];
		});

		$result = edac_check_plugin_installed( 'non-existent-plugin/plugin.php' );
		$this->assertFalse( $result );

		// Clean up the filter.
		remove_all_filters( 'pre_get_plugins' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with empty plugin list.
	 */
	public function test_empty_plugin_list() {
		// Mock the get_plugins function to return empty array.
		add_filter( 'pre_get_plugins', function() {
			return [];
		});

		$result = edac_check_plugin_installed( 'any-plugin/plugin.php' );
		$this->assertFalse( $result );

		// Clean up the filter.
		remove_all_filters( 'pre_get_plugins' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with partial slug matching.
	 */
	public function test_partial_slug_matching() {
		// Mock the get_plugins function to return test data.
		add_filter( 'pre_get_plugins', function() {
			return [
				'woocommerce/woocommerce.php' => [
					'Name' => 'WooCommerce',
					'Version' => '5.0.0',
				],
				'jetpack/jetpack.php' => [
					'Name' => 'Jetpack',
					'Version' => '10.0.0',
				],
			];
		});

		// Should find by exact match.
		$this->assertTrue( edac_check_plugin_installed( 'woocommerce/woocommerce.php' ) );
		
		// Should not find by partial match.
		$this->assertFalse( edac_check_plugin_installed( 'woocommerce' ) );

		// Clean up the filter.
		remove_all_filters( 'pre_get_plugins' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with edge case inputs.
	 */
	public function test_edge_case_inputs() {
		// Mock the get_plugins function to return test data.
		add_filter( 'pre_get_plugins', function() {
			return [
				'test-plugin/test.php' => [
					'Name' => 'Test Plugin',
					'Version' => '1.0.0',
				],
			];
		});

		// Test with empty string.
		$this->assertFalse( edac_check_plugin_installed( '' ) );

		// Clean up the filter.
		remove_all_filters( 'pre_get_plugins' );
	}
}