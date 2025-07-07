<?php
/**
 * Class PluginActionLinksTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Plugin_Action_Links;

/**
 * Plugin Action Links test case.
 */
class PluginActionLinksTest extends WP_UnitTestCase {

	/**
	 * Instance of the Plugin_Action_Links class.
	 *
	 * @var Plugin_Action_Links $plugin_action_links.
	 */
	private $plugin_action_links;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		$this->plugin_action_links = new Plugin_Action_Links();
		
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
		
		if ( ! defined( 'EDAC_KEY_VALID' ) ) {
			define( 'EDAC_KEY_VALID', false );
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
			method_exists( $this->plugin_action_links, 'init_hooks' ),
			'Class does not have method init_hooks'
		);
	}

	/**
	 * Test that the add_plugin_action_links method exists.
	 */
	public function test_add_plugin_action_links_method_exists() {
		$this->assertTrue(
			method_exists( $this->plugin_action_links, 'add_plugin_action_links' ),
			'Class does not have method add_plugin_action_links'
		);
	}

	/**
	 * Test that init_hooks registers the filter when EDAC_PLUGIN_FILE is defined.
	 */
	public function test_init_hooks_registers_filter_when_constant_defined() {
		// Clear any existing filters.
		remove_all_filters( 'plugin_action_links_' . plugin_basename( EDAC_PLUGIN_FILE ) );

		// Initialize hooks.
		$this->plugin_action_links->init_hooks();

		// Check that the filter was added.
		$this->assertNotFalse(
			has_filter( 'plugin_action_links_' . plugin_basename( EDAC_PLUGIN_FILE ), [ $this->plugin_action_links, 'add_plugin_action_links' ] ),
			'Filter was not registered'
		);
	}

	/**
	 * Test that add_plugin_action_links returns an array.
	 */
	public function test_add_plugin_action_links_returns_array() {
		$input_links = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
		$result      = $this->plugin_action_links->add_plugin_action_links( $input_links );

		$this->assertIsArray( $result, 'Method should return an array' );
	}

	/**
	 * Test that add_plugin_action_links adds settings link.
	 */
	public function test_add_plugin_action_links_adds_settings_link() {
		$input_links = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
		$result      = $this->plugin_action_links->add_plugin_action_links( $input_links );

		// Settings link should be the first element.
		$first_link = reset( $result );
		$this->assertStringContainsString( 'Settings', $first_link, 'Settings link not found' );
		$this->assertStringContainsString( 'accessibility_checker_settings', $first_link, 'Settings link does not point to correct page' );
	}

	/**
	 * Test that add_plugin_action_links adds pro link when EDAC_KEY_VALID is false.
	 */
	public function test_add_plugin_action_links_adds_pro_link_when_not_pro() {
		$input_links = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
		$result      = $this->plugin_action_links->add_plugin_action_links( $input_links );

		$this->assertArrayHasKey( 'go_pro', $result, 'Pro link not found in result' );
		$this->assertStringContainsString( 'Get Pro', $result['go_pro'], 'Pro link does not contain expected text' );
		$this->assertStringContainsString( 'target="_blank"', $result['go_pro'], 'Pro link does not open in new window' );
		$this->assertStringContainsString( 'edac-plugin-action-links__go-pro', $result['go_pro'], 'Pro link does not have correct CSS class' );
	}

	/**
	 * Test that add_plugin_action_links preserves existing links.
	 */
	public function test_add_plugin_action_links_preserves_existing_links() {
		$input_links = [
			'edit'       => '<a href="#">Edit</a>',
			'deactivate' => '<a href="#">Deactivate</a>',
		];

		$result = $this->plugin_action_links->add_plugin_action_links( $input_links );

		$this->assertArrayHasKey( 'edit', $result, 'Edit link was not preserved' );
		$this->assertArrayHasKey( 'deactivate', $result, 'Deactivate link was not preserved' );
		$this->assertEquals( '<a href="#">Edit</a>', $result['edit'], 'Edit link content was modified' );
		$this->assertEquals( '<a href="#">Deactivate</a>', $result['deactivate'], 'Deactivate link content was modified' );
	}

	/**
	 * Test that add_plugin_action_links properly escapes URLs.
	 */
	public function test_add_plugin_action_links_escapes_urls() {
		$input_links = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
		$result      = $this->plugin_action_links->add_plugin_action_links( $input_links );

		// Check that the settings link is properly escaped.
		$first_link = reset( $result );
		$this->assertStringContainsString( 'href="', $first_link, 'Settings link href is not properly quoted' );
	}

	/**
	 * Test that add_plugin_action_links includes proper accessibility attributes.
	 */
	public function test_add_plugin_action_links_includes_accessibility_attributes() {
		$input_links = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
		$result      = $this->plugin_action_links->add_plugin_action_links( $input_links );

		if ( isset( $result['go_pro'] ) ) {
			$this->assertStringContainsString( 'aria-label=', $result['go_pro'], 'Pro link does not have aria-label attribute' );
			$this->assertStringContainsString( 'opens in new window', $result['go_pro'], 'Pro link aria-label does not indicate new window' );
		}
	}

	/**
	 * Clean up after tests.
	 */
	protected function tearDown(): void {
		// Clean up any filters that were added during tests.
		if ( defined( 'EDAC_PLUGIN_FILE' ) ) {
			remove_all_filters( 'plugin_action_links_' . plugin_basename( EDAC_PLUGIN_FILE ) );
		}
	}
}
