<?php
/**
 * Bootstrap the CLI commands for the Accessibility Checker plugin.
 *
 * @since 1.15.0
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI;

use EqualizeDigital\AccessibilityChecker\WPCLI\Command\CLICommandInterface;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\DeleteStats;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\GetStats;
use Exception;
use WP_CLI;

/**
 * Handles the registration of WP-CLI commands for the Accessibility Checker plugin.
 *
 * @since 1.15.0
 */
class BootstrapCLI {

	/**
	 * The boot method on this class will use this array to register custom WP-CLI commands.
	 *
	 * @since 1.15.0
	 *
	 * @var CLICommandInterface[]
	 */
	protected array $commands = [
		GetStats::class,
		DeleteStats::class,
	];

	/**
	 * Register the WP-CLI commands by looping through the commands array and adding each command.
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public function register() {
		// Bail if not running in WP_CLI.
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		/**
		 * Filter the list of classes that hold the commands to be registered.
		 *
		 * @since 1.15.0
		 *
		 * @param CLICommandInterface[] $commands array of classes to register as commands.
		 */
		$commands = apply_filters( 'edac_filter_command_classes', $this->commands );

		foreach ( $commands as $command ) {
			// All commands must follow the interface.
			if ( ! ( $command instanceof CLICommandInterface ) ) {
				continue;
			}

			try {
				WP_CLI::add_command(
					$command::get_name(),
					$command,
					$command::get_args()
				);
			} catch ( Exception $e ) {
				WP_CLI::warning( sprintf(
					// translators: 1: a php classname, 2: an error message that was thrown about why this failed to register.
					__( 'Failed to register command %1$s because %2$s', 'accessibility-checker' ),
					get_class( $command ),
					$e->getMessage()
				) );
			}
		}
	}
}
