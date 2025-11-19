<?php
/**
 * Plugin Action Links class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Plugin Action Links handling class.
 *
 * @since 1.27.0
 */
class Plugin_Action_Links {

	/**
	 * Initialize hooks.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		if ( defined( 'EDAC_PLUGIN_FILE' ) ) {
			add_filter( 'plugin_action_links_' . plugin_basename( EDAC_PLUGIN_FILE ), [ $this, 'add_plugin_action_links' ] );
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * Adds action links to the plugin list table
	 *
	 * Fired by `plugin_action_links` filter.
	 *
	 * @since 1.27.0
	 * @access public
	 *
	 * @param array $links An array of plugin action links.
	 *
	 * @return array An array of plugin action links.
	 */
	public function add_plugin_action_links( $links ): array {
		$settings_link = sprintf( 
			'<a href="%1$s">%2$s</a>', 
			esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ), 
			esc_html__( 'Settings', 'accessibility-checker' ) 
		);

		array_unshift( $links, $settings_link );

		// Add Pro link if not already pro version.
		if ( ! defined( 'EDACP_VERSION' ) || ! EDAC_KEY_VALID ) {
			$go_pro_text = esc_html__( 'Get Pro', 'accessibility-checker' );
			
			$links['go_pro'] = sprintf( 
				'<a href="%1$s" target="_blank" class="edac-plugin-action-links__go-pro" aria-label="%2$s">%3$s</a>', 
				esc_url( edac_link_wrapper( 'https://equalizedigital.com/accessibility-checker/pricing/', 'plugin-action-links', 'get-pro-link', false ) ), 
				esc_attr__( 'Get Accessibility Checker Pro, opens in new window', 'accessibility-checker' ),
				$go_pro_text 
			);
		}

		return $links;
	}
}
