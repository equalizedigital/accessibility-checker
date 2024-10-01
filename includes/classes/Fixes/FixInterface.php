<?php
/**
 * Interface for creating fixes for the Accessibility Checker.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes;

/**
 * Interface for creating fixes for the Accessibility Checker.
 *
 * @since 1.16.0
 */
interface FixInterface {

	/**
	 * Get the slug for the fix.
	 *
	 * @return string This is the key that fixes are registered with. It must be unique.
	 */
	public static function get_slug(): string;

	/**
	 * Get the nicename for the fix.
	 *
	 * @return string The name of the fix.
	 */
	public static function get_nicename(): string;

	/**
	 * Get the type for the fix.
	 *
	 * @return string The type of fix. Either 'backend', 'frontend' or everywhere.
	 */
	public static function get_type(): string;

	/**
	 * Register anything needed for the fix.
	 *
	 * Fixes are responsible for implementing their own 'run' method and binding it in here
	 * if they are not just simple hooks.
	 */
	public function register(): void;

	/**
	 * Get the settings fields for the fix.
	 *
	 * This can be called directly when you need a single fix but is also run through filter
	 * 'edac_filter_fixes_settings_fields' to get all fields for all fixes so it must accept
	 * an array of fields and return an array of fields.
	 *
	 * @param array $fields The fields to add to the settings page.
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array;

	/**
	 * Run the fix.
	 *
	 * This will be called in admin only, frontend only or everywhere depending on the fix type.
	 */
	public function run();
}
