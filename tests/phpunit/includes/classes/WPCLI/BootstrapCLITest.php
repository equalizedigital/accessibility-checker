<?php
/**
 * Test the BootstrapCLI command loader.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI as WP_CLI;
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
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}
		parent::setUp();
	}

	/**
	 * Set the should_throw flag back to false after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		WP_CLI::set_add_command_should_throw( false );
		parent::tearDown();
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

	/**
	 * Ensure non-array filter output does not trigger warnings during registration.
	 */
	public function test_register_handles_non_array_filter_output() {
		$filter = static function () {
			return 'not-an-array';
		};

		add_filter( 'edac_filter_command_classes', $filter );

		$bootstrap_cli = new BootstrapCLI( new WP_CLI() );

		$error_handler = static function () {
			throw new Exception( 'PHP warning raised during register().' );
		};

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into test failures for this regression case.
		set_error_handler( $error_handler );

		try {
			$bootstrap_cli->register();
			$this->addToAssertionCount( 1 ); // If we reach this point without an exception, the test has passed.
		} catch ( Exception $exception ) {
			$this->fail( 'Register should ignore non-array filter output.' );
		} finally {
			restore_error_handler();
			remove_filter( 'edac_filter_command_classes', $filter );
		}
	}
}
