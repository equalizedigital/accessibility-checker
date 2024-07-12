<?php
/**
 * Mocks the WP_CLI class for testing.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks;

use Exception;
use stdClass;

/**
 * Mocks the WP_CLI class for testing
 *
 * This is a simple mock that only implements the methods needed for testing.
 */
class Mock_WP_CLI {

	/**
	 * Holds the array of commands registered.
	 *
	 * @var array|mixed
	 */
	public static $commands = [];

	/**
	 * Flag to emulate a command being added that throws an exception.
	 *
	 * @var bool
	 */
	private static bool $add_command_should_throw = false;

	/**
	 * Sets the static flag to determine if add_command should throw.
	 *
	 * @param bool $should_throw True if the add_command method should throw.
	 *
	 * @return void
	 */
	public static function set_add_command_should_throw( bool $should_throw = false ): void {
		self::$add_command_should_throw = $should_throw;
	}

	/**
	 * Emulates the WP_CLI::add_command method good enough for testing.
	 *
	 * @param string          $name The command name.
	 * @param callable|string $command_callable The callable for the command.
	 *
	 * @return void
	 * @throws Exception If the add fails (IE if the fail flag is set to true).
	 */
	public static function add_command( string $name, $command_callable ) {
		if ( self::$add_command_should_throw ) {
			throw new Exception( 'add_command should throw' );
		}
		self::$commands[ $name ] = $command_callable;
	}

	/**
	 * Emulates the WP_CLI::get_root_command method good enough for testing.
	 *
	 * @return stdClass
	 */
	public static function get_root_command(): object {
		// Create an object with a get_subcommands method.
		return new class() {

			/**
			 * Get the subcommands.
			 *
			 * @return array
			 */
			public static function get_subcommands(): array {
				return Mock_WP_CLI::$commands;
			}
		};
	}

	/**
	 * Echos a warning.
	 *
	 * @param string $message The message to echo.
	 *
	 * @return void
	 */
	public static function warning( string $message ) {
		echo 'Warning: ' . esc_html( $message );
	}

	/**
	 * Echos a success.
	 *
	 * @param string $message The message to echo.
	 *
	 * @return void
	 */
	public static function success( string $message ) {
		echo 'Success: ' . esc_html( $message );
	}

	/**
	 * Echos an error.
	 *
	 * @param string $message The message to echo.
	 *
	 * @return void
	 */
	public static function error( string $message ) {
		echo 'Error: ' . esc_html( $message );
	}

	/**
	 * Echos a log.
	 *
	 * @param string $message The message to echo.
	 *
	 * @return void
	 */
	public static function log( string $message ) {
		echo 'Log: ' . esc_html( $message );
	}
}
