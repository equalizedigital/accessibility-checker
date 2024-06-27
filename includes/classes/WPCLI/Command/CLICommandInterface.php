<?php /* phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- WP_CLI doesn't follow WP method name rules */
/**
 * Interface CLICommandInterface
 *
 * @since 1.15.0
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

/**
 * Interface defining the methods required for a WP-CLI command to be bootstrapped by this plugin.
 */
interface CLICommandInterface {

	/**
	 * Get the name of the command
	 *
	 * @return string
	 */
	public static function get_name(): string;

	/**
	 * Get the arguments for the command
	 *
	 * @return array
	 */
	public static function get_args(): array;

	/**
	 * Run the command
	 *
	 * @param array $options Positional args passed to the command.
	 * @param array $arguments Associative args passed to the command.
	 *
	 * @return mixed
	 */
	public function __invoke( array $options = [], array $arguments = [] );
}
