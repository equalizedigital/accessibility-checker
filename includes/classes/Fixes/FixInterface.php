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
	 * Run the fix.
	 *
	 * This will be called in admin only, frontend only or everywhere depending on the fix type.
	 */
	public function run();
}
