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
 *
 * @since 1.29.0
 */
class Orphaned_Issues_Cleanup {

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
		 * @since 1.29.0
		 *
		 * @return void
		 */
	public function init_hooks() {
			add_action( Scheduled_Tasks::EVENT, [ $this, 'run_cleanup' ] );
	}

	/**
	 * Set a custom batch size for this cleanup instance.
	 *
	 * @since 1.29.0
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
	 * @since 1.29.0
	 *
	 * @return int[] Array of orphaned post IDs.
	 */
	public function get_orphaned_post_ids(): array {
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		if ( ! $table_name ) {
			return [];
		}
		
		$scannable_post_types = Settings::get_scannable_post_types();
		if ( empty( $scannable_post_types ) ) {
			// No scannable post types: treat all issues as orphaned for this site.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_col(
				$wpdb->prepare(
					'SELECT DISTINCT postid FROM %i WHERE siteid = %d LIMIT %d',
					$table_name,
					get_current_blog_id(),
					$this->get_batch_size()
				)
			);
		}

		// Build placeholders for each post type.
		$placeholders = implode( ',', array_fill( 0, count( $scannable_post_types ), '%s' ) );
		$sql          = "SELECT DISTINCT t.postid
				FROM %i AS t
				LEFT JOIN {$wpdb->posts} AS p ON t.postid = p.ID
				WHERE t.siteid = %d
				AND (p.ID IS NULL OR p.post_type NOT IN ($placeholders))
				LIMIT %d";

		// Build arguments for prepare: table name, site id, ...post types, batch size.
		$args = array_merge(
			[ $sql, $table_name, get_current_blog_id() ],
			$scannable_post_types,
			[ $this->get_batch_size() ]
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic placeholders for IN() are required for security and cannot be avoided in this context.
		return $wpdb->get_col( call_user_func_array( [ $wpdb, 'prepare' ], $args ) );
	}

	/**
	 * Delete all issues for a given orphaned post ID.
	 *
	 * @since 1.29.0
	 *
	 * @param int $post_id The orphaned post ID.
	 */
	public function delete_orphaned_post( int $post_id ) {
		Purge_Post_Data::delete_post( $post_id );
	}

	/**
	 * Cleanup orphaned issues (batch process).
	 *
	 * @since 1.29.0
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
