<?php
/**
 * Generic cleanup scheduler to manage multiple cleanup tasks.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class CleanupScheduler
 *
 * Manages registration and execution of cleanup tasks.
 *
 * @since 1.29.0
 */
class CleanupScheduler {

	/**
	 * Registered cleanup tasks.
	 *
	 * @var CleanupTaskInterface[]
	 */
	private array $tasks = [];

	/**
	 * Cron event prefix.
	 *
	 * @var string
	 */
	const EVENT_PREFIX = 'edac_cleanup_';

	/**
	 * Instance of the scheduler.
	 *
	 * @var CleanupScheduler|null
	 */
	private static ?CleanupScheduler $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.29.0
	 *
	 * @return CleanupScheduler
	 */
	public static function get_instance(): CleanupScheduler {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the scheduler.
	 *
	 * @since 1.29.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'edac_register_cleanup_tasks', [ $this, 'register_default_tasks' ] );
		add_action( 'init', [ $this, 'setup_hooks' ] );
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 1.29.0
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		// Allow other plugins/themes to register cleanup tasks.
		do_action( 'edac_register_cleanup_tasks', $this );

		// Schedule all registered tasks.
		$this->schedule_all_tasks();

		// Setup cron handlers for all tasks.
		foreach ( $this->tasks as $task ) {
			add_action( $this->get_event_name( $task->get_task_id() ), [ $this, 'run_task' ] );
		}
	}

	/**
	 * Register default cleanup tasks.
	 *
	 * @since 1.29.0
	 *
	 * @param CleanupScheduler $scheduler The scheduler instance.
	 * @return void
	 */
	public function register_default_tasks( CleanupScheduler $scheduler ): void {
		// Register the orphaned issues cleanup task.
		$orphaned_cleanup = new OrphanedIssuesCleanupTask();
		$scheduler->register_task( $orphaned_cleanup );
	}

	/**
	 * Register a cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @param CleanupTaskInterface $task The cleanup task to register.
	 * @return bool True if registered successfully, false otherwise.
	 */
	public function register_task( CleanupTaskInterface $task ): bool {
		$task_id = $task->get_task_id();

		if ( isset( $this->tasks[ $task_id ] ) ) {
			return false; // Task already registered.
		}

		$this->tasks[ $task_id ] = $task;
		return true;
	}

	/**
	 * Unregister a cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @param string $task_id The task ID to unregister.
	 * @return bool True if unregistered successfully, false otherwise.
	 */
	public function unregister_task( string $task_id ): bool {
		if ( ! isset( $this->tasks[ $task_id ] ) ) {
			return false;
		}

		// Unschedule the task.
		$this->unschedule_task( $task_id );

		unset( $this->tasks[ $task_id ] );
		return true;
	}

	/**
	 * Get all registered tasks.
	 *
	 * @since 1.29.0
	 *
	 * @return CleanupTaskInterface[]
	 */
	public function get_tasks(): array {
		return $this->tasks;
	}

	/**
	 * Get a specific task by ID.
	 *
	 * @since 1.29.0
	 *
	 * @param string $task_id The task ID.
	 * @return CleanupTaskInterface|null The task or null if not found.
	 */
	public function get_task( string $task_id ): ?CleanupTaskInterface {
		return $this->tasks[ $task_id ] ?? null;
	}

	/**
	 * Get tasks sorted by priority.
	 *
	 * @since 1.29.0
	 *
	 * @return CleanupTaskInterface[]
	 */
	public function get_tasks_by_priority(): array {
		$tasks = $this->tasks;
		uasort(
			$tasks,
			function ( CleanupTaskInterface $a, CleanupTaskInterface $b ) {
				return $a->get_priority() - $b->get_priority();
			} 
		);
		return $tasks;
	}

	/**
	 * Schedule all registered tasks.
	 *
	 * @since 1.29.0
	 *
	 * @return void
	 */
	public function schedule_all_tasks(): void {
		foreach ( $this->tasks as $task ) {
			if ( $task->is_enabled() ) {
				$this->schedule_task( $task->get_task_id() );
			}
		}
	}

	/**
	 * Schedule a specific task.
	 *
	 * @since 1.29.0
	 *
	 * @param string $task_id The task ID to schedule.
	 * @return bool True if scheduled successfully, false otherwise.
	 */
	public function schedule_task( string $task_id ): bool {
		$task = $this->get_task( $task_id );
		if ( ! $task || ! $task->is_enabled() ) {
			return false;
		}

		$event_name = $this->get_event_name( $task_id );
		if ( ! wp_next_scheduled( $event_name ) ) {
			wp_schedule_event( time(), $task->get_default_schedule(), $event_name );
		}

		return true;
	}

	/**
	 * Unschedule a specific task.
	 *
	 * @since 1.29.0
	 *
	 * @param string $task_id The task ID to unschedule.
	 * @return bool True if unscheduled successfully, false otherwise.
	 */
	public function unschedule_task( string $task_id ): bool {
		$event_name = $this->get_event_name( $task_id );
		$timestamp  = wp_next_scheduled( $event_name );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $event_name );
			return true;
		}

		return false;
	}

	/**
	 * Run a specific task.
	 *
	 * @since 1.29.0
	 *
	 * @param string   $task_id The task ID to run.
	 * @param int|null $batch_size Optional batch size override.
	 * @return array The results from the task execution.
	 */
	public function run_task( string $task_id, ?int $batch_size = null ): array {
		$task = $this->get_task( $task_id );
		if ( ! $task || ! $task->is_enabled() ) {
			return [];
		}

		/**
		 * Fires before a cleanup task is executed.
		 *
		 * @since 1.29.0
		 *
		 * @param string               $task_id The task ID.
		 * @param CleanupTaskInterface $task    The task instance.
		 */
		do_action( 'edac_before_cleanup_task', $task_id, $task );

		$results = $task->run_cleanup( $batch_size );

		/**
		 * Fires after a cleanup task is executed.
		 *
		 * @since 1.29.0
		 *
		 * @param string               $task_id The task ID.
		 * @param CleanupTaskInterface $task    The task instance.
		 * @param array                $results The results from the task execution.
		 */
		do_action( 'edac_after_cleanup_task', $task_id, $task, $results );

		return $results;
	}

	/**
	 * Run all enabled tasks in priority order.
	 *
	 * @since 1.29.0
	 *
	 * @return array Results from all tasks, keyed by task ID.
	 */
	public function run_all_tasks(): array {
		$results = [];
		$tasks   = $this->get_tasks_by_priority();

		foreach ( $tasks as $task ) {
			if ( $task->is_enabled() ) {
				$results[ $task->get_task_id() ] = $this->run_task( $task->get_task_id() );
			}
		}

		return $results;
	}

	/**
	 * Get the WordPress cron event name for a task.
	 *
	 * @since 1.29.0
	 *
	 * @param string $task_id The task ID.
	 * @return string The event name.
	 */
	private function get_event_name( string $task_id ): string {
		return self::EVENT_PREFIX . $task_id;
	}

	/**
	 * Unschedule all tasks (for deactivation).
	 *
	 * @since 1.29.0
	 *
	 * @return void
	 */
	public function unschedule_all_tasks(): void {
		foreach ( $this->tasks as $task ) {
			$this->unschedule_task( $task->get_task_id() );
		}
	}
}
