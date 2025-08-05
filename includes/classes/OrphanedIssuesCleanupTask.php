<?php
/**
 * Orphaned issues cleanup task implementation.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker;

use EDAC\Admin\Orphaned_Issues_Cleanup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class OrphanedIssuesCleanupTask
 *
 * Implements the cleanup task interface for orphaned issues cleanup.
 *
 * @since 1.29.0
 */
class OrphanedIssuesCleanupTask implements CleanupTaskInterface {

	/**
	 * The legacy cleanup instance.
	 *
	 * @var Orphaned_Issues_Cleanup
	 */
	private Orphaned_Issues_Cleanup $legacy_cleanup;

	/**
	 * Constructor.
	 *
	 * @since 1.29.0
	 */
	public function __construct() {
		$this->legacy_cleanup = new Orphaned_Issues_Cleanup();
	}

	/**
	 * Get the unique identifier for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The task identifier.
	 */
	public function get_task_id(): string {
		return 'orphaned_issues';
	}

	/**
	 * Get the human-readable name for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The task name.
	 */
	public function get_task_name(): string {
		return __( 'Orphaned Issues Cleanup', 'accessibility-checker' );
	}

	/**
	 * Get the description for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The task description.
	 */
	public function get_task_description(): string {
		return __( 'Removes accessibility issues for posts that no longer exist or are not scannable.', 'accessibility-checker' );
	}

	/**
	 * Get the default schedule for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The WordPress cron schedule.
	 */
	public function get_default_schedule(): string {
		return 'daily';
	}

	/**
	 * Get the default batch size for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return int The default batch size.
	 */
	public function get_default_batch_size(): int {
		return Orphaned_Issues_Cleanup::BATCH_LIMIT;
	}

	/**
	 * Run the cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @param int|null $batch_size Optional batch size override.
	 * @return array Array of processed orphaned post IDs.
	 */
	public function run_cleanup( ?int $batch_size = null ): array {
		if ( null !== $batch_size ) {
			$this->legacy_cleanup->set_batch_size( $batch_size );
		}

		return $this->legacy_cleanup->run_cleanup();
	}

	/**
	 * Check if the task is enabled.
	 *
	 * @since 1.29.0
	 *
	 * @return bool True if the task is enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		/**
		 * Filter whether the orphaned issues cleanup task is enabled.
		 *
		 * @since 1.29.0
		 *
		 * @param bool $enabled Whether the task is enabled. Default true.
		 */
		return apply_filters( 'edac_orphaned_issues_cleanup_enabled', true );
	}

	/**
	 * Get task priority for execution order.
	 * Lower numbers run first.
	 *
	 * @since 1.29.0
	 *
	 * @return int The task priority.
	 */
	public function get_priority(): int {
		/**
		 * Filter the priority of the orphaned issues cleanup task.
		 *
		 * @since 1.29.0
		 *
		 * @param int $priority The task priority. Default 10.
		 */
		return apply_filters( 'edac_orphaned_issues_cleanup_priority', 10 );
	}

	/**
	 * Get the legacy cleanup instance for backward compatibility.
	 *
	 * @since 1.29.0
	 *
	 * @return Orphaned_Issues_Cleanup
	 */
	public function get_legacy_cleanup(): Orphaned_Issues_Cleanup {
		return $this->legacy_cleanup;
	}
}
