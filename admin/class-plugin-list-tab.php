<?php
/**
 * Plugin List Tab class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers an "Equalize Digital" tab on the WordPress Plugins screen.
 *
 * The tab appears when 2 or more Equalize Digital plugins are installed,
 * using the plugins_list and plugins_list_status_text filters added in WP 7.0.
 *
 * @since 1.43.0
 */
class Plugin_List_Tab {

	/**
	 * Minimum number of Equalize Digital plugins required to show the tab.
	 *
	 * @var int
	 */
	const MINIMUM_FOR_TAB = 2;

	/**
	 * The status slug used for the Equalize Digital tab.
	 *
	 * @var string
	 */
	const STATUS_SLUG = 'equalize-digital';

	/**
	 * Initialize hooks.
	 *
	 * @since 1.43.0
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		global $wp_version;

		if ( \version_compare( $wp_version, '7.0-alpha0', '<' ) ) {
			return;
		}

		add_filter( 'plugins_list', [ $this, 'filter_plugins_list' ] );
		add_filter( 'plugins_list_status_text', [ $this, 'get_status_text' ], 10, 3 );
	}

	/**
	 * Filters the plugins list to add an Equalize Digital group.
	 *
	 * Only adds the group when 2 or more Equalize Digital plugins are installed.
	 *
	 * @since 1.43.0
	 *
	 * @param array<string, array<string, array<string, string>>> $plugins The plugins list keyed by status.
	 *
	 * @return array<string, array<string, array<string, string>>> The filtered plugins list.
	 */
	public function filter_plugins_list( $plugins ) {
		if ( ! \is_array( $plugins ) || ! isset( $plugins['all'] ) || ! \is_array( $plugins['all'] ) ) {
			return $plugins;
		}

		$equalize_digital_plugins = [];
		foreach ( $plugins['all'] as $plugin_file => $plugin_data ) {
			if ( $this->is_equalize_digital_plugin( $plugin_data ) ) {
				$equalize_digital_plugins[ $plugin_file ] = $plugin_data;
			}
		}

		if ( \count( $equalize_digital_plugins ) < self::MINIMUM_FOR_TAB ) {
			return $plugins;
		}

		$plugins[ self::STATUS_SLUG ] = $equalize_digital_plugins;

		return $plugins;
	}

	/**
	 * Returns the label text for the Equalize Digital tab.
	 *
	 * @since 1.43.0
	 *
	 * @param string $text  The current status text.
	 * @param int    $count The number of plugins with this status.
	 * @param string $type  The status type slug.
	 *
	 * @return string The status text.
	 */
	public function get_status_text( $text, $count, $type ) {
		if ( self::STATUS_SLUG !== $type ) {
			return $text;
		}

		return \_nx( 'Equalize Digital', 'Equalize Digital', $count, 'plugin status', 'accessibility-checker' );
	}

	/**
	 * Determines whether a plugin belongs to Equalize Digital.
	 *
	 * @since 1.43.0
	 *
	 * @param array<string, string> $plugin_data The plugin header data.
	 *
	 * @return bool
	 */
	private function is_equalize_digital_plugin( $plugin_data ): bool {
		if ( ! \is_array( $plugin_data ) || empty( $plugin_data['AuthorURI'] ) ) {
			return false;
		}

		return false !== \stripos( $plugin_data['AuthorURI'], 'equalizedigital.com' );
	}
}
