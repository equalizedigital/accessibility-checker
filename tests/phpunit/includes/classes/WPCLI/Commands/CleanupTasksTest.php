<?php
/**
 * Test cases for the CleanupTasks command.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\CleanupTasks;
use EqualizeDigital\AccessibilityChecker\CleanupScheduler;
use EqualizeDigital\AccessibilityChecker\CleanupTaskInterface;

/**
 * Test cases to verify that the CleanupTasks command operates correctly.
 */
class CleanupTasksTest extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var CleanupTasks
	 */
	protected $cleanup_tasks_command;

	/**
	 * The cleanup scheduler.
	 *
	 * @var CleanupScheduler
	 */
	protected $scheduler;

	/**
	 * Mock task for testing.
	 *
	 * @var CleanupTaskInterface
	 */
	protected $mock_task;

	/**
	 * Set up the class under test.
	 */
	protected function setUp(): void {
		$this->cleanup_tasks_command = new CleanupTasks( new Mock_WP_CLI() );
		$this->scheduler             = CleanupScheduler::get_instance();
		$this->mock_task             = $this->createMockTask();
		parent::setUp();
	}

	/**
	 * Clean up after tests.
	 */
	protected function tearDown(): void {
		// Unregister all tasks to clean up.
		$tasks = $this->scheduler->get_tasks();
		foreach ( $tasks as $task ) {
			$this->scheduler->unregister_task( $task->get_task_id() );
		}
		parent::tearDown();
	}

	/**
	 * Create a mock cleanup task for testing.
	 *
	 * @return CleanupTaskInterface
	 */
	private function createMockTask(): CleanupTaskInterface {
		return new class() implements CleanupTaskInterface {
			public function get_task_id(): string {
				return 'test_task';
			}

			public function get_task_name(): string {
				return 'Test Task';
			}

			public function get_task_description(): string {
				return 'A test cleanup task';
			}

			public function get_default_schedule(): string {
				return 'daily';
			}

			public function get_default_batch_size(): int {
				return 10;
			}

			public function run_cleanup( ?int $batch_size = null ): array {
				return [ 'test_item_1', 'test_item_2' ];
			}

			public function is_enabled(): bool {
				return true;
			}

			public function get_priority(): int {
				return 10;
			}
		};
	}

	/**
	 * Test the get_name method returns the correct command name.
	 */
	public function test_get_name_returns_correct_command_name() {
		$expected_name = 'accessibility-checker cleanup';
		$actual_name   = CleanupTasks::get_name();

		$this->assertEquals( $expected_name, $actual_name );
	}

	/**
	 * Test the get_args method returns the correct arguments structure.
	 */
	public function test_get_args_returns_correct_arguments() {
		$args = CleanupTasks::get_args();

		$this->assertIsArray( $args );
		$this->assertArrayHasKey( 'task', $args );
		$this->assertArrayHasKey( 'batch', $args );
		$this->assertArrayHasKey( 'sleep', $args );

		// Check task argument structure.
		$this->assertArrayHasKey( 'type', $args['task'] );
		$this->assertArrayHasKey( 'description', $args['task'] );
		$this->assertArrayHasKey( 'optional', $args['task'] );
		$this->assertArrayHasKey( 'default', $args['task'] );
		$this->assertEquals( 'assoc', $args['task']['type'] );
		$this->assertTrue( $args['task']['optional'] );

		// Check batch argument structure.
		$this->assertArrayHasKey( 'type', $args['batch'] );
		$this->assertArrayHasKey( 'description', $args['batch'] );
		$this->assertArrayHasKey( 'optional', $args['batch'] );
		$this->assertArrayHasKey( 'default', $args['batch'] );
		$this->assertEquals( 'assoc', $args['batch']['type'] );
		$this->assertTrue( $args['batch']['optional'] );

		// Check sleep argument structure.
		$this->assertArrayHasKey( 'type', $args['sleep'] );
		$this->assertArrayHasKey( 'description', $args['sleep'] );
		$this->assertArrayHasKey( 'optional', $args['sleep'] );
		$this->assertArrayHasKey( 'default', $args['sleep'] );
		$this->assertEquals( 'assoc', $args['sleep']['type'] );
		$this->assertTrue( $args['sleep']['optional'] );
		$this->assertEquals( 0, $args['sleep']['default'] );
	}

	/**
	 * Test the list command shows available tasks.
	 */
	public function test_list_command_shows_available_tasks() {
		$this->scheduler->register_task( $this->mock_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [ 'task' => 'list' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Available cleanup tasks:', $output );
		$this->assertStringContainsString( 'test_task: Test Task', $output );
		$this->assertStringContainsString( 'A test cleanup task', $output );
	}

	/**
	 * Test the list command with no tasks shows warning.
	 */
	public function test_list_command_with_no_tasks_shows_warning() {
		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [ 'task' => 'list' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Warning: No cleanup tasks are registered.', $output );
	}

	/**
	 * Test running a specific task.
	 */
	public function test_run_specific_task() {
		$this->scheduler->register_task( $this->mock_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [ 'task' => 'test_task' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Running cleanup task: Test Task', $output );
		$this->assertStringContainsString( 'Success: Task &quot;test_task&quot; completed. 2 item(s) processed.', $output );
	}

	/**
	 * Test running a non-existent task shows error.
	 */
	public function test_run_nonexistent_task_shows_error() {
		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [ 'task' => 'nonexistent' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Error: Unknown task: nonexistent', $output );
	}

	/**
	 * Test running a disabled task shows warning.
	 */
	public function test_run_disabled_task_shows_warning() {
		$disabled_task = new class() implements CleanupTaskInterface {
			public function get_task_id(): string {
				return 'disabled_task';
			}

			public function get_task_name(): string {
				return 'Disabled Task';
			}

			public function get_task_description(): string {
				return 'A disabled task';
			}

			public function get_default_schedule(): string {
				return 'daily';
			}

			public function get_default_batch_size(): int {
				return 10;
			}

			public function run_cleanup( ?int $batch_size = null ): array {
				return [];
			}

			public function is_enabled(): bool {
				return false; // Disabled.
			}

			public function get_priority(): int {
				return 10;
			}
		};

		$this->scheduler->register_task( $disabled_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [ 'task' => 'disabled_task' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Warning: Task &quot;disabled_task&quot; is disabled.', $output );
	}

	/**
	 * Test running all tasks.
	 */
	public function test_run_all_tasks() {
		$this->scheduler->register_task( $this->mock_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Running 1 enabled cleanup task(s)', $output );
		$this->assertStringContainsString( 'Running: Test Task', $output );
		$this->assertStringContainsString( 'Processed 2 item(s)', $output );
		$this->assertStringContainsString( 'Success: All cleanup tasks completed. 2 total item(s) processed.', $output );
	}

	/**
	 * Test running all tasks with no enabled tasks shows warning.
	 */
	public function test_run_all_tasks_with_no_enabled_tasks_shows_warning() {
		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Warning: No enabled cleanup tasks found.', $output );
	}

	/**
	 * Test running with custom batch size parameter.
	 */
	public function test_run_with_custom_batch_size() {
		$this->scheduler->register_task( $this->mock_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke(
			[],
			[
				'task'  => 'test_task',
				'batch' => '5',
			] 
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: Task &quot;test_task&quot; completed. 2 item(s) processed.', $output );
	}

	/**
	 * Test running with invalid batch parameter.
	 */
	public function test_run_with_invalid_batch_parameter() {
		$this->scheduler->register_task( $this->mock_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke(
			[],
			[
				'task'  => 'test_task',
				'batch' => 'invalid',
			] 
		);
		$output = ob_get_clean();

		// Should still complete successfully (invalid batch is ignored).
		$this->assertStringContainsString( 'Success: Task &quot;test_task&quot; completed. 2 item(s) processed.', $output );
	}

	/**
	 * Test running with sleep parameter.
	 */
	public function test_run_with_sleep_parameter() {
		$this->scheduler->register_task( $this->mock_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke(
			[],
			[
				'task'  => 'test_task',
				'sleep' => '0.001',
			] 
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: Task &quot;test_task&quot; completed. 2 item(s) processed.', $output );
	}

	/**
	 * Test that a task with no results shows appropriate message.
	 */
	public function test_task_with_no_results_shows_appropriate_message() {
		$empty_task = new class() implements CleanupTaskInterface {
			public function get_task_id(): string {
				return 'empty_task';
			}

			public function get_task_name(): string {
				return 'Empty Task';
			}

			public function get_task_description(): string {
				return 'An empty task';
			}

			public function get_default_schedule(): string {
				return 'daily';
			}

			public function get_default_batch_size(): int {
				return 10;
			}

			public function run_cleanup( ?int $batch_size = null ): array {
				return []; // No results.
			}

			public function is_enabled(): bool {
				return true;
			}

			public function get_priority(): int {
				return 10;
			}
		};

		$this->scheduler->register_task( $empty_task );

		ob_start();
		$this->cleanup_tasks_command->__invoke( [], [ 'task' => 'empty_task' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: Task &quot;empty_task&quot; completed with no items to process.', $output );
	}
}
