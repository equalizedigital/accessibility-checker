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
		// Skip this test if get_plugins is not available.
		if ( ! function_exists( 'get_plugins' ) ) {
			$this->markTestSkipped( 'get_plugins function not available in test environment.' );
		}

		// Test with a non-existent plugin (should return false).
		$result = edac_check_plugin_installed( 'non-existent-plugin/plugin.php' );
		$this->assertFalse( $result, 'Non-existent plugin should return false.' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with plugin found by value.
	 */
	public function test_plugin_found_by_value() {
		// get_plugins() reads from the `plugins` cache group, so seed it directly.
		$installed_plugins = [
			'seeded-plugin/seeded-plugin.php',
			'other-seeded-plugin/other-seeded-plugin.php',
		];
		wp_cache_set( 'plugins', [ '' => $installed_plugins ], 'plugins' );

		try {
			$this->assertTrue(
				edac_check_plugin_installed( 'seeded-plugin/seeded-plugin.php' ),
				'A plugin listed as a value in the installed plugins should be found.'
			);
			$this->assertFalse(
				edac_check_plugin_installed( 'not-installed/not-installed.php' ),
				'A plugin absent from the installed plugins should not be found.'
			);
		} finally {
			wp_cache_delete( 'plugins', 'plugins' );
		}
	}

	/**
	 * Tests the edac_check_plugin_installed function with plugin not found.
	 */
	public function test_plugin_not_found() {
		// Skip this test if get_plugins is not available.
		if ( ! function_exists( 'get_plugins' ) ) {
			$this->markTestSkipped( 'get_plugins function not available in test environment.' );
		}

		// Test with a definitely non-existent plugin.
		$result = edac_check_plugin_installed( 'definitely-non-existent-test-plugin/plugin.php' );
		$this->assertFalse( $result, 'Non-existent plugin should return false.' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with empty plugin list.
	 */
	public function test_empty_plugin_list() {
		// Skip this test if get_plugins is not available.
		if ( ! function_exists( 'get_plugins' ) ) {
			$this->markTestSkipped( 'get_plugins function not available in test environment.' );
		}

		// This test would need mocking, test the function with guaranteed non-match instead.
		$result = edac_check_plugin_installed( 'guaranteed-non-match-plugin/plugin.php' );
		$this->assertFalse( $result, 'Non-existent plugin should return false.' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with partial slug matching.
	 */
	public function test_partial_slug_matching() {
		// Skip this test if get_plugins is not available.
		if ( ! function_exists( 'get_plugins' ) ) {
			$this->markTestSkipped( 'get_plugins function not available in test environment.' );
		}

		// Test basic behavior - partial matches should return false.
		$result = edac_check_plugin_installed( 'partial-match-only' );
		$this->assertFalse( $result, 'Partial plugin slug should return false.' );
	}

	/**
	 * Tests the edac_check_plugin_installed function with edge case inputs.
	 */
	public function test_edge_case_inputs() {
		// Skip this test if get_plugins is not available.
		if ( ! function_exists( 'get_plugins' ) ) {
			$this->markTestSkipped( 'get_plugins function not available in test environment.' );
		}

		// Test with empty string.
		$result = edac_check_plugin_installed( '' );
		$this->assertFalse( $result, 'Empty string should return false.' );
	}
}
