<?php
/**
 * Plugin Action Links undefined key constant test.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Plugin_Action_Links;

/**
 * Plugin Action Links undefined key constant test case.
 */
class PluginActionLinksUndefinedKeyTest extends WP_UnitTestCase {

	/**
	 * Ensure pro link is added when EDAC_KEY_VALID is not defined.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_add_plugin_action_links_adds_pro_link_when_key_constant_missing() {
		if ( ! defined( 'EDAC_PLUGIN_FILE' ) ) {
			define( 'EDAC_PLUGIN_FILE', __FILE__ );
		}

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

		$plugin_action_links = new Plugin_Action_Links();
		$input_links         = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
		$result              = $plugin_action_links->add_plugin_action_links( $input_links );

		$this->assertArrayHasKey( 'go_pro', $result, 'Pro link should be present when EDAC_KEY_VALID is undefined' );
	}
}
