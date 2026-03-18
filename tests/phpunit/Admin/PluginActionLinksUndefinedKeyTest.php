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
			 * @param string $base_url      The URL to wrap.
			 * @param string $campaign      The campaign parameter.
			 * @param string $content       The content parameter.
			 * @param bool   $directly_echo Unused in this mock.
			 * @return string The wrapped URL.
			 */
			function edac_link_wrapper( $base_url, $campaign, $content, $directly_echo ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				$params = [
					'utm_campaign' => $campaign,
					'utm_content'  => $content,
				];
				return $base_url . '?' . http_build_query( array_filter( $params ) );
			}
		}

		$plugin_action_links = new Plugin_Action_Links();
		$input_links         = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
		$result              = $plugin_action_links->add_plugin_action_links( $input_links );

		$this->assertArrayHasKey( 'go_pro', $result, 'Pro link should be present when EDAC_KEY_VALID is undefined' );
	}
}
