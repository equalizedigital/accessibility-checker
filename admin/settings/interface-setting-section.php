<?php
/**
 * Interface file for defining methods that manage settings sections within the plugin.
 *
 * This interface provides methods for initializing class hooks, registering the settings section with WordPress,
 * registering all settings fields associated with this section, and rendering additional content for the section.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

/**
 * Interface for defining a settings section within the plugin.
 */
interface Setting_Section_Interface {

	/**
	 * Initialize class hooks.
	 */
	public function init_hooks();

	/**
	 * Registers the settings section with WordPress.
	 */
	public function register_section();

	/**
	 * Registers all settings fields associated with this section.
	 */
	public function register_fields();

	/**
	 * Callback for rendering additional content for the section.
	 */
	public function callback();
}
