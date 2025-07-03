<?php
/**
 * Plugin Action Links class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Plugin Action Links handling class.
 */
class Plugin_Action_Links {

	/**
	 * Class constructor.
	 *
	 * Initializes the hooks for the plugin action links.
	 */
	public function __construct() {
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		add_filter( 'plugin_action_links_' . plugin_basename( EDAC_PLUGIN_FILE ), [ $this, 'plugin_action_links' ] );
	}

	/**
	 * Plugin action links.
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
	public function plugin_action_links( $links ): array {
		$settings_link = sprintf( 
			'<a href="%1$s">%2$s</a>', 
			admin_url( 'admin.php?page=accessibility_checker_settings' ), 
			esc_html__( 'Settings', 'accessibility-checker' ) 
		);

		array_unshift( $links, $settings_link );

		// Add Pro link if not already pro version.
		if ( ! EDAC_KEY_VALID ) {
			$go_pro_text = esc_html__( 'Get Pro', 'accessibility-checker' );
			
			$links['go_pro'] = sprintf( 
				'<a href="%1$s" target="_blank" class="edac-plugin-action-links__go-pro" aria-label="%2$s">%3$s</a>', 
				edac_link_wrapper( 'https://equalizedigital.com/accessibility-checker/pricing/', 'plugin-action-links', 'get-pro-link', false ), 
				esc_attr__( 'Get Accessibility Checker Pro, opens in new window', 'accessibility-checker' ),
				$go_pro_text 
			);
		}

		return $links;
	}
}
