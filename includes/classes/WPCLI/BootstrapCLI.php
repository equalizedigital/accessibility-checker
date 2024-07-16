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
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\GetSiteStats;
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
	 * The WP-CLI instance.
	 *
	 * This allows injecting a mock WP-CLI instance for testing.
	 *
	 * @since 1.15.0
	 *
	 * @var WP_CLI
	 */
	private $wp_cli;

	/**
	 * The boot method on this class will use this array to register custom WP-CLI commands.
	 *
	 * @since 1.15.0
	 *
	 * @var CLICommandInterface[]
	 */
	protected array $commands = [
		DeleteStats::class,
		GetSiteStats::class,
		GetStats::class,
	];

	/**
	 * Set up the internal wp_cli property.
	 *
	 * @since 1.15.0
	 *
	 * @param WP_CLI|null $wp_cli The WP-CLI instance.
	 */
	public function __construct( $wp_cli = null ) {
		$this->wp_cli = $wp_cli ? $wp_cli : new WP_CLI();
	}

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
			if ( ! is_subclass_of( $command, CLICommandInterface::class, true ) ) {
				continue;
			}

			try {
				$this->wp_cli::add_command(
					$command::get_name(),
					$command,
					$command::get_args()
				);
			} catch ( Exception $e ) {
				$this->wp_cli::warning(
					sprintf(
						// translators: 1: a php classname, 2: an error message that was thrown about why this failed to register.
						esc_html__( 'Failed to register command %1$s because %2$s', 'accessibility-checker' ),
						$command,
						$e->getMessage()
					)
				);
			}
		}
	}
}
