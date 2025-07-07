<?php
/**
 * Class PluginRowMetaTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Plugin_Row_Meta;

/**
 * Plugin Row Meta test case.
 */
class PluginRowMetaTest extends WP_UnitTestCase {

	/**
	 * Instance of the Plugin_Row_Meta class.
	 *
	 * @var Plugin_Row_Meta $plugin_row_meta.
	 */
	private $plugin_row_meta;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		$this->plugin_row_meta = new Plugin_Row_Meta();
		
		// Set up mock function and constants for all tests.
		$this->setupMocks();
	}

	/**
	 * Set up mocks and constants for testing.
	 */
	private function setupMocks(): void {
		// Define constants if not already defined.
		if ( ! defined( 'EDAC_PLUGIN_FILE' ) ) {
			define( 'EDAC_PLUGIN_FILE', __FILE__ );
		}

		// Mock the edac_link_wrapper function if it doesn't exist.
		if ( ! function_exists( 'edac_link_wrapper' ) ) {
			/**
			 * Mock the edac_link_wrapper function.
			 *
			 * @param string $url      The URL to wrap.
			 * @param string $source   The source parameter.
			 * @param string $campaign The campaign parameter.
			 * @param bool   $unused   Unused parameter for compatibility.
			 * @return string The wrapped URL.
			 */
			function edac_link_wrapper( $url, $source, $campaign, $unused ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				return $url . '?utm_source=' . $source . '&utm_campaign=' . $campaign;
			}
		}
	}

	/**
	 * Test that the init_hooks method exists.
	 */
	public function test_init_hooks_method_exists() {
		$this->assertTrue(
			method_exists( $this->plugin_row_meta, 'init_hooks' ),
			'Class does not have method init_hooks'
		);
	}

	/**
	 * Test that the add_plugin_row_meta method exists.
	 */
	public function test_add_plugin_row_meta_method_exists() {
		$this->assertTrue(
			method_exists( $this->plugin_row_meta, 'add_plugin_row_meta' ),
			'Class does not have method add_plugin_row_meta'
		);
	}

	/**
	 * Test that init_hooks registers the filter when EDAC_PLUGIN_FILE is defined.
	 */
	public function test_init_hooks_registers_filter_when_constant_defined() {
		// Clear any existing filters.
		remove_all_filters( 'plugin_row_meta' );

		// Initialize hooks.
		$this->plugin_row_meta->init_hooks();

		// Check that the filter was added.
		$this->assertNotFalse(
			has_filter( 'plugin_row_meta', [ $this->plugin_row_meta, 'add_plugin_row_meta' ] ),
			'Filter was not registered'
		);
	}

	/**
	 * Test that add_plugin_row_meta returns an array.
	 */
	public function test_add_plugin_row_meta_returns_array() {
		$input_meta  = [
			'Version' => '1.0.0',
			'Author'  => 'Test Author',
		];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		$this->assertIsArray( $result, 'Method should return an array' );
	}

	/**
	 * Test that add_plugin_row_meta adds meta links for correct plugin file.
	 */
	public function test_add_plugin_row_meta_adds_links_for_correct_plugin() {
		$input_meta  = [
			'Version' => '1.0.0',
			'Author'  => 'Test Author',
		];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		// Should contain original meta plus new links.
		$this->assertArrayHasKey( 'Version', $result, 'Original meta not preserved' );
		$this->assertArrayHasKey( 'Author', $result, 'Original meta not preserved' );
		$this->assertArrayHasKey( 'docs', $result, 'Documentation link not added' );
		$this->assertArrayHasKey( 'support', $result, 'Support link not added' );
		$this->assertArrayHasKey( 'rate', $result, 'Rate link not added' );
	}

	/**
	 * Test that add_plugin_row_meta does not add links for different plugin file.
	 */
	public function test_add_plugin_row_meta_ignores_different_plugin() {
		$input_meta            = [
			'Version' => '1.0.0',
			'Author'  => 'Test Author',
		];
		$different_plugin_file = 'different-plugin/different-plugin.php';
		$result                = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $different_plugin_file );

		// Should only contain original meta.
		$this->assertArrayHasKey( 'Version', $result, 'Original meta not preserved' );
		$this->assertArrayHasKey( 'Author', $result, 'Original meta not preserved' );
		$this->assertArrayNotHasKey( 'docs', $result, 'Documentation link should not be added for different plugin' );
		$this->assertArrayNotHasKey( 'support', $result, 'Support link should not be added for different plugin' );
		$this->assertArrayNotHasKey( 'rate', $result, 'Rate link should not be added for different plugin' );
	}

	/**
	 * Test that documentation link contains expected content.
	 */
	public function test_documentation_link_content() {
		$input_meta  = [ 'Version' => '1.0.0' ];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		$docs_link = $result['docs'];
		$this->assertStringContainsString( 'Documentation', $docs_link, 'Documentation link text not found' );
		$this->assertStringContainsString( 'target="_blank"', $docs_link, 'Documentation link does not open in new window' );
		$this->assertStringContainsString( 'aria-label=', $docs_link, 'Documentation link does not have aria-label' );
		$this->assertStringContainsString( 'equalizedigital.com', $docs_link, 'Documentation link does not point to correct domain' );
	}

	/**
	 * Test that support link contains expected content.
	 */
	public function test_support_link_content() {
		$input_meta  = [ 'Version' => '1.0.0' ];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		$support_link = $result['support'];
		$this->assertStringContainsString( 'Support', $support_link, 'Support link text not found' );
		$this->assertStringContainsString( 'target="_blank"', $support_link, 'Support link does not open in new window' );
		$this->assertStringContainsString( 'aria-label=', $support_link, 'Support link does not have aria-label' );
		$this->assertStringContainsString( 'equalizedigital.com', $support_link, 'Support link does not point to correct domain' );
	}

	/**
	 * Test that rate link contains expected content.
	 */
	public function test_rate_link_content() {
		$input_meta  = [ 'Version' => '1.0.0' ];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		$rate_link = $result['rate'];
		$this->assertStringContainsString( 'Rate plugin', $rate_link, 'Rate link text not found' );
		$this->assertStringContainsString( 'target="_blank"', $rate_link, 'Rate link does not open in new window' );
		$this->assertStringContainsString( 'aria-label=', $rate_link, 'Rate link does not have aria-label' );
		$this->assertStringContainsString( 'wordpress.org', $rate_link, 'Rate link does not point to WordPress.org' );
	}

	/**
	 * Test that links have proper accessibility attributes.
	 */
	public function test_links_have_accessibility_attributes() {
		$input_meta  = [ 'Version' => '1.0.0' ];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		// Check that all links indicate they open in new window.
		$this->assertStringContainsString( 'opens in new window', $result['docs'], 'Documentation link aria-label does not indicate new window' );
		$this->assertStringContainsString( 'opens in new window', $result['support'], 'Support link aria-label does not indicate new window' );
		$this->assertStringContainsString( 'opens in new window', $result['rate'], 'Rate link aria-label does not indicate new window' );
	}

	/**
	 * Test that tracking links use edac_link_wrapper.
	 */
	public function test_tracking_links_use_wrapper() {
		$input_meta  = [ 'Version' => '1.0.0' ];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		// Documentation and support links should have UTM parameters.
		$this->assertStringContainsString( 'utm_source=', $result['docs'], 'Documentation link does not have tracking parameters' );
		$this->assertStringContainsString( 'utm_campaign=', $result['docs'], 'Documentation link does not have campaign parameter' );
		$this->assertStringContainsString( 'utm_source=', $result['support'], 'Support link does not have tracking parameters' );
		$this->assertStringContainsString( 'utm_campaign=', $result['support'], 'Support link does not have campaign parameter' );

		// Rate link should not have UTM parameters (WordPress.org link).
		$this->assertStringNotContainsString( 'utm_source', $result['rate'], 'Rate link should not have tracking parameters' );
	}

	/**
	 * Test that links are properly escaped.
	 */
	public function test_links_are_properly_escaped() {
		$input_meta  = [ 'Version' => '1.0.0' ];
		$plugin_file = plugin_basename( EDAC_PLUGIN_FILE );
		$result      = $this->plugin_row_meta->add_plugin_row_meta( $input_meta, $plugin_file );

		// Check that all links have properly quoted attributes.
		$this->assertStringContainsString( 'href="', $result['docs'], 'Documentation link href is not properly quoted' );
		$this->assertStringContainsString( 'href="', $result['support'], 'Support link href is not properly quoted' );
		$this->assertStringContainsString( 'href="', $result['rate'], 'Rate link href is not properly quoted' );
	}

	/**
	 * Clean up after tests.
	 */
	protected function tearDown(): void {
		// Clean up any filters that were added during tests.
		remove_all_filters( 'plugin_row_meta' );
	}
}
