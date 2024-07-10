<?php
/**
 * Test the BootstrapCLI command loader.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Tests\Mocks\Mock_WP_CLI as WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\BootstrapCLI;

/**
 * Test cases to verify that the BootstrapCLI command loader can register the commands.
 */
class BootstrapCLITest extends WP_UnitTestCase {


	/**
	 * Set up the test environment.
	 *
	 * Makes the mock available and sets the constant like WP_CLI would in a real environment.
	 */
	protected function setUp(): void {
		require_once dirname( __DIR__, 3 ) . '/Mocks/Mock_WP_CLI.php';
		// since this is a synthetic run on WP-CLI, we need to define WP_CLI.
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}
		parent::setUp();
	}

	/**
	 * Test the bootstrap CLI command.
	 */
	public function test_bootstrap_cli_command() {
		$commands      = WP_CLI::get_root_command();
		$command_count = count( $commands->get_subcommands() );

		$bootstrap_cli = new BootstrapCLI( new WP_CLI() );
		$bootstrap_cli->register();

		$commands            = WP_CLI::get_root_command();
		$command_count_after = count( $commands->get_subcommands() );

		// check if the number of commands has increased after register is called.
		$this->assertGreaterThan( $command_count, $command_count_after );
	}

	/**
	 * Test the bootstrap CLI command with a mock that throws an exception when
	 * adding commands.
	 */
	public function test_bootstrap_cli_command_with_exception() {
		WP_CLI::set_add_command_should_throw( true );

		$bootstrap_cli = new BootstrapCLI( new WP_CLI() );

		ob_start();
		$bootstrap_cli->register();
		$output = ob_get_clean();

		// check if the output contains the expected exception message.
		$this->assertStringStartsWith( 'Warning: Failed to register command', $output );
	}
}
