<?php
/**
 * Plugin Row Meta class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Plugin Row Meta handling class.
 *
 * @since 1.27.0
 */
class Plugin_Row_Meta {

	/**
	 * Initialize hooks.
	 *
	 * @since 1.27.0
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		if ( defined( 'EDAC_PLUGIN_FILE' ) ) {
			add_filter( 'plugin_row_meta', [ $this, 'add_plugin_row_meta' ], 10, 2 );
		}
	}

	/**
	 * Add plugin row meta.
	 *
	 * Adds row meta links to the plugin list table
	 *
	 * Fired by `plugin_row_meta` filter.
	 *
	 * @since 1.27.0
	 * @access public
	 *
	 * @param array  $plugin_meta An array of the plugin's metadata, including
	 *                            the version, author, author URI, and plugin URI.
	 * @param string $plugin_file Path to the plugin file, relative to the plugins
	 *                            directory.
	 *
	 * @return array An array of plugin row meta links.
	 */
	public function add_plugin_row_meta( $plugin_meta, $plugin_file ): array {
		if ( plugin_basename( EDAC_PLUGIN_FILE ) === $plugin_file ) {
			$row_meta = [
				'docs'    => sprintf(
					'<a href="%1$s" aria-label="%2$s" target="_blank">%3$s</a>',
					esc_url( edac_link_wrapper( 'https://equalizedigital.com/accessibility-checker/documentation/', 'plugin-row-meta', 'documentation', false ) ),
					esc_attr__( 'View Accessibility Checker Documentation (opens in new window)', 'accessibility-checker' ),
					esc_html__( 'Documentation', 'accessibility-checker' )
				),
				'support' => sprintf(
					'<a href="%1$s" aria-label="%2$s" target="_blank">%3$s</a>',
					esc_url( edac_link_wrapper( 'https://equalizedigital.com/support/', 'plugin-row-meta', 'support', false ) ),
					esc_attr__( 'Get support for Accessibility Checker (opens in new window)', 'accessibility-checker' ),
					esc_html__( 'Support', 'accessibility-checker' )
				),
				'rate'    => sprintf(
					'<a href="%1$s" aria-label="%2$s" target="_blank">%3$s</a>',
					esc_url( 'https://wordpress.org/support/plugin/accessibility-checker/reviews/#new-post' ),
					esc_attr__( 'Rate Accessibility Checker plugin (opens in new window)', 'accessibility-checker' ),
					esc_html__( 'Rate plugin', 'accessibility-checker' )
				),
			];

			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}
}
