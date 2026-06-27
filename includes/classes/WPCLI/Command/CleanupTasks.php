<?php
/**
 * WP-CLI command to run cleanup tasks.
 *
 * @since 1.29.0
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

use EqualizeDigital\AccessibilityChecker\CleanupScheduler;
use WP_CLI;

/**
 * Runs cleanup tasks managed by the cleanup scheduler.
 *
 * @since 1.29.0
 */
class CleanupTasks implements CLICommandInterface {
	/**
	 * The WP-CLI instance.
	 *
	 * @var mixed|WP_CLI
	 */
	private $wp_cli;

	/**
	 * Constructor.
	 *
	 * @since 1.29.0
	 *
	 * @param mixed|WP_CLI $wp_cli The WP-CLI instance.
	 */
	public function __construct( $wp_cli = null ) {
		$this->wp_cli = $wp_cli ?? new WP_CLI();
	}

	/**
	 * Get the name of the command.
	 *
	 * @since 1.29.0
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'accessibility-checker cleanup';
	}

	/**
	 * Get the arguments for the command.
	 *
	 * @since 1.29.0
	 *
	 * @return array
	 */
	public static function get_args(): array {
		return [
			'task'  => [
				'type'        => 'assoc',
				'description' => 'Specific task to run. Use "list" to see available tasks.',
				'optional'    => true,
				'default'     => null,
			],
			'batch' => [
				'type'        => 'assoc',
				'description' => 'Number of items to process in one batch.',
				'optional'    => true,
				'default'     => null,
			],
			'sleep' => [
				'type'        => 'assoc',
				'description' => 'Seconds to sleep between operations (default: 0).',
				'optional'    => true,
				'default'     => 0,
			],
		];
	}

	/**
	 * Run cleanup tasks with feedback.
	 *
	 * ## OPTIONS
	 *
	 * [--task=<task_id>]
	 * : Specific task to run. Use "list" to see available tasks.
	 *
	 * [--batch=<number>]
	 * : Number of items to process in one batch.
	 *
	 * [--sleep=<seconds>]
	 * : Seconds to sleep between operations (default: 0).
	 *
	 * ## EXAMPLES
	 *
	 *     # List all available cleanup tasks
	 *     wp accessibility-checker cleanup --task=list
	 *
	 *     # Run all enabled cleanup tasks
	 *     wp accessibility-checker cleanup
	 *
	 *     # Run a specific cleanup task
	 *     wp accessibility-checker cleanup --task=orphaned_issues
	 *
	 *     # Run with custom batch size
	 *     wp accessibility-checker cleanup --task=orphaned_issues --batch=25
	 *
	 * @since 1.29.0
	 *
	 * @param array $options    Positional args passed to the command.
	 * @param array $arguments  Associative args passed to the command.
	 *
	 * @return void
	 */
	public function __invoke( array $options = [], array $arguments = [] ) {
		$scheduler = CleanupScheduler::get_instance();
		$task_id   = $arguments['task'] ?? null;
		$batch     = isset( $arguments['batch'] ) && is_numeric( $arguments['batch'] ) && (int) $arguments['batch'] > 0 ? (int) $arguments['batch'] : null;
		$sleep     = isset( $arguments['sleep'] ) && is_numeric( $arguments['sleep'] ) && $arguments['sleep'] >= 0 ? (float) $arguments['sleep'] : 0.0;

		// Handle list command.
		if ( 'list' === $task_id ) {
			$this->list_tasks( $scheduler );
			return;
		}

		// Run specific task.
		if ( $task_id ) {
			$this->run_specific_task( $scheduler, $task_id, $batch, $sleep );
			return;
		}

		// Run all enabled tasks.
		$this->run_all_tasks( $scheduler, $sleep );
	}

	/**
	 * List all available cleanup tasks.
	 *
	 * @since 1.29.0
	 *
	 * @param CleanupScheduler $scheduler The cleanup scheduler instance.
	 * @return void
	 */
	private function list_tasks( CleanupScheduler $scheduler ): void {
		$tasks = $scheduler->get_tasks();

		if ( empty( $tasks ) ) {
			$this->wp_cli::warning( 'No cleanup tasks are registered.' );
			return;
		}

		$this->wp_cli::log( 'Available cleanup tasks:' );
		$this->wp_cli::log( '' );

		foreach ( $tasks as $task ) {
			$status = $task->is_enabled() ? 'Enabled' : 'Disabled';
			$this->wp_cli::log(
				sprintf(
					'  %s: %s (%s)',
					$task->get_task_id(),
					$task->get_task_name(),
					$status
				) 
			);
			$this->wp_cli::log( sprintf( '    %s', $task->get_task_description() ) );
			$this->wp_cli::log(
				sprintf(
					'    Schedule: %s, Batch Size: %d, Priority: %d',
					$task->get_default_schedule(),
					$task->get_default_batch_size(),
					$task->get_priority()
				) 
			);
			$this->wp_cli::log( '' );
		}
	}

	/**
	 * Run a specific cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @param CleanupScheduler $scheduler The cleanup scheduler instance.
	 * @param string           $task_id   The task ID to run.
	 * @param int|null         $batch     Optional batch size override.
	 * @param float            $sleep     Sleep time between operations.
	 * @return void
	 */
	private function run_specific_task( CleanupScheduler $scheduler, string $task_id, ?int $batch, float $sleep ): void {
		$task = $scheduler->get_task( $task_id );

		if ( ! $task ) {
			$this->wp_cli::error( sprintf( 'Unknown task: %s. Use --task=list to see available tasks.', $task_id ) );
			return;
		}

		if ( ! $task->is_enabled() ) {
			$this->wp_cli::warning( sprintf( 'Task "%s" is disabled.', $task_id ) );
			return;
		}

		$this->wp_cli::log( sprintf( 'Running cleanup task: %s', $task->get_task_name() ) );

		if ( $sleep > 0 ) {
			$this->wp_cli::log( 'Waiting 2 seconds before starting...' );
			sleep( 2 );
		}

		$results = $scheduler->run_task( $task_id, $batch );

		if ( empty( $results ) ) {
			$this->wp_cli::success( sprintf( 'Task "%s" completed with no items to process.', $task_id ) );
		} else {
			$this->wp_cli::success( sprintf( 'Task "%s" completed. %d item(s) processed.', $task_id, count( $results ) ) );
		}

		if ( $sleep > 0 ) {
			usleep( (int) ( $sleep * 1000000 ) );
		}
	}

	/**
	 * Run all enabled cleanup tasks.
	 *
	 * @since 1.29.0
	 *
	 * @param CleanupScheduler $scheduler The cleanup scheduler instance.
	 * @param float            $sleep     Sleep time between tasks.
	 * @return void
	 */
	private function run_all_tasks( CleanupScheduler $scheduler, float $sleep ): void {
		$tasks         = $scheduler->get_tasks_by_priority();
		$enabled_tasks = array_filter(
			$tasks,
			function ( $task ) {
				return $task->is_enabled();
			} 
		);

		if ( empty( $enabled_tasks ) ) {
			$this->wp_cli::warning( 'No enabled cleanup tasks found.' );
			return;
		}

		$this->wp_cli::log( sprintf( 'Running %d enabled cleanup task(s)...', count( $enabled_tasks ) ) );

		if ( $sleep > 0 ) {
			$this->wp_cli::log( 'Waiting 2 seconds before starting...' );
			sleep( 2 );
		}

		$total_processed = 0;

		foreach ( $enabled_tasks as $task ) {
			$this->wp_cli::log( sprintf( '  Running: %s', $task->get_task_name() ) );
			$results = $scheduler->run_task( $task->get_task_id() );

			if ( empty( $results ) ) {
				$this->wp_cli::log( '    No items to process.' );
			} else {
				$this->wp_cli::log( sprintf( '    Processed %d item(s).', count( $results ) ) );
				$total_processed += count( $results );
			}

			if ( $sleep > 0 ) {
				usleep( (int) ( $sleep * 1000000 ) );
			}
		}

		$this->wp_cli::success( sprintf( 'All cleanup tasks completed. %d total item(s) processed.', $total_processed ) );
	}
}
