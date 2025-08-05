<?php
/**
 * Interface for cleanup tasks.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface CleanupTaskInterface
 *
 * Defines the contract for cleanup tasks that can be registered with the cleanup scheduler.
 *
 * @since 1.29.0
 */
interface CleanupTaskInterface {

	/**
	 * Get the unique identifier for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The task identifier.
	 */
	public function get_task_id(): string;

	/**
	 * Get the human-readable name for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The task name.
	 */
	public function get_task_name(): string;

	/**
	 * Get the description for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The task description.
	 */
	public function get_task_description(): string;

	/**
	 * Get the default schedule for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return string The WordPress cron schedule (e.g., 'daily', 'weekly').
	 */
	public function get_default_schedule(): string;

	/**
	 * Get the default batch size for this cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @return int The default batch size.
	 */
	public function get_default_batch_size(): int;

	/**
	 * Run the cleanup task.
	 *
	 * @since 1.29.0
	 *
	 * @param int|null $batch_size Optional batch size override.
	 * @return array Array of processed items or results.
	 */
	public function run_cleanup( ?int $batch_size = null ): array;

	/**
	 * Check if the task is enabled.
	 *
	 * @since 1.29.0
	 *
	 * @return bool True if the task is enabled, false otherwise.
	 */
	public function is_enabled(): bool;

	/**
	 * Get task priority for execution order.
	 * Lower numbers run first.
	 *
	 * @since 1.29.0
	 *
	 * @return int The task priority.
	 */
	public function get_priority(): int;
}
