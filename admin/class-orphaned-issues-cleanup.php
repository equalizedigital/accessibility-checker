<?php
/**
 * Scheduled cleanup of orphaned issues.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Orphaned_Issues_Cleanup
 */
class Orphaned_Issues_Cleanup {

	/**
	 * Cron event name.
	 *
	 * @var string
	 */
	const EVENT = 'edac_cleanup_orphaned_issues';

	/**
	 * Max number of posts to cleanup per run.
	 *
	 * @var int
	 */
	const BATCH_LIMIT = 50;

	/**
	 * Batch size for cleanup (overrides BATCH_LIMIT if set).
	 *
	 * @var int|null
	 */
	private ?int $batch_size = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		self::schedule_event();
		add_action( self::EVENT, [ $this, 'run_cleanup' ] );
	}

	/**
	 * Schedule the cleanup event.
	 *
	 * @return void
	 */
	public static function schedule_event() {
		if ( ! wp_next_scheduled( self::EVENT ) ) {
			wp_schedule_event( time(), 'daily', self::EVENT );
		}
	}

	/**
	 * Unschedule the cleanup event.
	 *
	 * @return void
	 */
	public static function unschedule_event() {
		$timestamp = wp_next_scheduled( self::EVENT );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::EVENT );
		}
	}

	/**
	 * Set a custom batch size for this cleanup instance.
	 *
	 * @param int $batch_size A custom size of batch to process.
	 * @return void
	 */
	public function set_batch_size( int $batch_size ): void {
		$this->batch_size = $batch_size;
	}

	/**
	 * Get the batch size to use.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		return $this->batch_size ?? self::BATCH_LIMIT;
	}

	/**
	 * Get orphaned post IDs (posts in issues table but not in posts table).
	 *
	 * @return int[] Array of orphaned post IDs.
	 */
	public function get_orphaned_post_ids(): array {
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		if ( ! $table_name ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT t.postid FROM %i AS t LEFT JOIN {$wpdb->posts} AS p ON t.postid = p.ID WHERE t.siteid = %d AND p.ID IS NULL LIMIT %d",
				$table_name,
				get_current_blog_id(),
				$this->get_batch_size()
			)
		);
	}

	/**
	 * Delete all issues for a given orphaned post ID.
	 *
	 * @param int $post_id The orphaned post ID.
	 */
	public function delete_orphaned_post( int $post_id ) {
		Purge_Post_Data::delete_post( $post_id );
	}

	/**
	 * Cleanup orphaned issues (batch process).
	 *
	 * @return int[] Array of deleted orphaned post IDs.
	 */
	public function run_cleanup(): array {
		$orphaned = $this->get_orphaned_post_ids();
		if ( empty( $orphaned ) ) {
			return [];
		}
		foreach ( $orphaned as $post_id ) {
			$this->delete_orphaned_post( (int) $post_id );
		}
		return $orphaned;
	}
}
