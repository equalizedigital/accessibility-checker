<?php
/**
 * Interface file for defining methods that manage plugin settings.
 *
 * This interface provides methods for registering setting fields with WordPress,
 * rendering the setting field's HTML interface, and sanitizing the setting's input.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

/**
 * Interface for plugin setting classes.
 */
interface Setting_Interface {

	/**
	 * Registers a setting field with WordPress.
	 */
	public function add_settings_field();

	/**
	 * Renders the setting field's HTML interface.
	 */
	public function callback();

	/**
	 * Sanitizes the setting's input before saving to the database.
	 *
	 * @param mixed $input The input value to sanitize.
	 *
	 * @return mixed Sanitized input value.
	 */
	public function sanitize( $input );
}
