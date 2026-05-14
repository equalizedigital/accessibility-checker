<?php
/**
 * System information helpers.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\SystemInfo;

use WP_Theme;

/**
 * Collects active plugin and theme information.
 */
class SystemInfo {

	/**
	 * Returns active plugins with name, slug, and version.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_active_plugins() {
		$active_plugins = [];

		foreach ( wp_get_active_and_valid_plugins() as $plugin_path ) {
			$plugin_data      = get_plugin_data( $plugin_path, false, false );
			$active_plugins[] = [
				'name'    => $plugin_data['Name'] ?? '',
				'slug'    => self::get_plugin_slug_from_path( $plugin_path ),
				'version' => $plugin_data['Version'] ?? '',
			];
		}

		return $active_plugins;
	}

	/**
	 * Gets the plugin slug from a plugin file path.
	 *
	 * @param string $plugin_path Path to a plugin file.
	 * @return string
	 */
	public static function get_plugin_slug_from_path( $plugin_path ) {
		if ( ! is_string( $plugin_path ) || '' === $plugin_path ) {
			return '';
		}

		$relative = plugin_basename( $plugin_path );
		$dir      = dirname( $relative );

		if ( '.' !== $dir ) {
			return $dir;
		}

		return basename( $relative, '.php' );
	}

	/**
	 * Returns active theme information, including parent data for child themes.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_active_theme() {
		$theme_data = wp_get_theme();

		$active_theme = self::get_theme_data_collection( $theme_data );

		$active_theme['accessibility_ready'] = self::is_theme_accessibility_ready( $theme_data );

		$active_theme['parent_theme'] = [];
		if ( $theme_data->parent() ) {
			$active_theme['parent_theme'] = self::get_theme_data_collection( $theme_data->parent() );
		}

		return $active_theme;
	}

	/**
	 * Gets theme details as a normalized collection.
	 *
	 * @param mixed $theme Theme object.
	 * @return array<string, mixed>
	 */
	public static function get_theme_data_collection( $theme ) {
		if ( ! is_a( $theme, '\\WP_Theme' ) ) {
			return [];
		}

		return [
			'name'    => $theme->get( 'Name' ) ?? '',
			'slug'    => $theme->get_stylesheet() ?? '',
			'version' => $theme->get( 'Version' ) ?? '',
			'tags'    => $theme->get( 'Tags' ) ?? [],
		];
	}

	/**
	 * Checks whether theme or parent theme is tagged accessibility-ready.
	 *
	 * @param WP_Theme $theme_data Theme object to check.
	 * @return bool
	 */
	public static function is_theme_accessibility_ready( WP_Theme $theme_data ) {
		$tags = is_array( $theme_data->get( 'Tags' ) ) ? $theme_data->get( 'Tags' ) : [];

		if ( $theme_data->parent() ) {
			$parent_tags = $theme_data->parent()->get( 'Tags' );
			$tags        = array_merge( $tags, is_array( $parent_tags ) ? $parent_tags : [] );
		}

		return in_array( 'accessibility-ready', $tags, true );
	}

	/**
	 * Gets the current WordPress environment type.
	 *
	 * @return string
	 */
	public static function get_environment_type() {
		return function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	}

	/**
	 * Gets the current WordPress version.
	 *
	 * @return string
	 */
	public static function get_wordpress_version() {
		return (string) get_bloginfo( 'version' );
	}

	/**
	 * Gets the current PHP version.
	 *
	 * @return string
	 */
	public static function get_php_version() {
		return (string) phpversion();
	}

	/**
	 * Gets a payload-ready set of system fields for license API requests.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_license_request_context() {
		$active_plugins = wp_json_encode( self::get_active_plugins() );
		$active_theme   = wp_json_encode( self::get_active_theme() );

		return [
			'environment'    => self::get_environment_type(),
			'wp_version'     => self::get_wordpress_version(),
			'php_version'    => self::get_php_version(),
			'active_plugins' => false !== $active_plugins ? $active_plugins : '[]',
			'active_theme'   => false !== $active_theme ? $active_theme : '{}',
		];
	}
}
