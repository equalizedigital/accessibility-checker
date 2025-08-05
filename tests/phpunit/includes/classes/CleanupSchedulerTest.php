<?php
/**
 * Test cases for the CleanupScheduler class.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\CleanupScheduler;
use EqualizeDigital\AccessibilityChecker\CleanupTaskInterface;

/**
 * Test cases to verify that the CleanupScheduler operates correctly.
 */
class CleanupSchedulerTest extends WP_UnitTestCase {

	/**
	 * The class under test.
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
		$this->scheduler = CleanupScheduler::get_instance();
		$this->mock_task = $this->createMockTask();
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
	 * Test that the scheduler is a singleton.
	 */
	public function test_scheduler_is_singleton() {
		$scheduler1 = CleanupScheduler::get_instance();
		$scheduler2 = CleanupScheduler::get_instance();

		$this->assertSame( $scheduler1, $scheduler2 );
	}

	/**
	 * Test registering a cleanup task.
	 */
	public function test_register_task() {
		$result = $this->scheduler->register_task( $this->mock_task );

		$this->assertTrue( $result );
		$this->assertArrayHasKey( 'test_task', $this->scheduler->get_tasks() );
	}

	/**
	 * Test that registering the same task twice fails.
	 */
	public function test_register_duplicate_task_fails() {
		$this->scheduler->register_task( $this->mock_task );
		$result = $this->scheduler->register_task( $this->mock_task );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregistering a cleanup task.
	 */
	public function test_unregister_task() {
		$this->scheduler->register_task( $this->mock_task );
		$result = $this->scheduler->unregister_task( 'test_task' );

		$this->assertTrue( $result );
		$this->assertArrayNotHasKey( 'test_task', $this->scheduler->get_tasks() );
	}

	/**
	 * Test unregistering a non-existent task fails.
	 */
	public function test_unregister_nonexistent_task_fails() {
		$result = $this->scheduler->unregister_task( 'nonexistent_task' );

		$this->assertFalse( $result );
	}

	/**
	 * Test getting a specific task.
	 */
	public function test_get_task() {
		$this->scheduler->register_task( $this->mock_task );
		$retrieved_task = $this->scheduler->get_task( 'test_task' );

		$this->assertSame( $this->mock_task, $retrieved_task );
	}

	/**
	 * Test getting a non-existent task returns null.
	 */
	public function test_get_nonexistent_task_returns_null() {
		$retrieved_task = $this->scheduler->get_task( 'nonexistent_task' );

		$this->assertNull( $retrieved_task );
	}

	/**
	 * Test getting tasks sorted by priority.
	 */
	public function test_get_tasks_by_priority() {
		// Create tasks with different priorities.
		$high_priority_task = new class() implements CleanupTaskInterface {
			public function get_task_id(): string {
				return 'high_priority';
			}

			public function get_task_name(): string {
				return 'High Priority Task';
			}

			public function get_task_description(): string {
				return 'A high priority task';
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
				return true;
			}

			public function get_priority(): int {
				return 5; // Higher priority (lower number).
			}
		};

		$low_priority_task = new class() implements CleanupTaskInterface {
			public function get_task_id(): string {
				return 'low_priority';
			}

			public function get_task_name(): string {
				return 'Low Priority Task';
			}

			public function get_task_description(): string {
				return 'A low priority task';
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
				return true;
			}

			public function get_priority(): int {
				return 20; // Lower priority (higher number).
			}
		};

		$this->scheduler->register_task( $low_priority_task );
		$this->scheduler->register_task( $high_priority_task );

		$sorted_tasks = $this->scheduler->get_tasks_by_priority();
		$task_ids     = array_keys( $sorted_tasks );

		$this->assertEquals( [ 'high_priority', 'low_priority' ], $task_ids );
	}

	/**
	 * Test running a specific task.
	 */
	public function test_run_task() {
		$this->scheduler->register_task( $this->mock_task );
		$results = $this->scheduler->run_task( 'test_task' );

		$this->assertEquals( [ 'test_item_1', 'test_item_2' ], $results );
	}

	/**
	 * Test running a non-existent task returns empty array.
	 */
	public function test_run_nonexistent_task_returns_empty_array() {
		$results = $this->scheduler->run_task( 'nonexistent_task' );

		$this->assertEquals( [], $results );
	}

	/**
	 * Test running a disabled task returns empty array.
	 */
	public function test_run_disabled_task_returns_empty_array() {
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
				return [ 'should_not_run' ];
			}

			public function is_enabled(): bool {
				return false; // Disabled.
			}

			public function get_priority(): int {
				return 10;
			}
		};

		$this->scheduler->register_task( $disabled_task );
		$results = $this->scheduler->run_task( 'disabled_task' );

		$this->assertEquals( [], $results );
	}

	/**
	 * Test running all tasks.
	 */
	public function test_run_all_tasks() {
		$this->scheduler->register_task( $this->mock_task );
		$results = $this->scheduler->run_all_tasks();

		$this->assertArrayHasKey( 'test_task', $results );
		$this->assertEquals( [ 'test_item_1', 'test_item_2' ], $results['test_task'] );
	}

	/**
	 * Test that cleanup task hooks fire correctly.
	 */
	public function test_cleanup_task_hooks_fire() {
		$before_hook_fired = false;
		$after_hook_fired  = false;

		add_action(
			'edac_before_cleanup_task',
			function ( $task_id, $task ) use ( &$before_hook_fired ) {
				if ( 'test_task' === $task_id ) {
					$before_hook_fired = true;
				}
			},
			10,
			2
		);

		add_action(
			'edac_after_cleanup_task',
			function ( $task_id, $task, $results ) use ( &$after_hook_fired ) {
				if ( 'test_task' === $task_id ) {
					$after_hook_fired = true;
				}
			},
			10,
			3
		);

		$this->scheduler->register_task( $this->mock_task );
		$this->scheduler->run_task( 'test_task' );

		$this->assertTrue( $before_hook_fired );
		$this->assertTrue( $after_hook_fired );
	}
}
