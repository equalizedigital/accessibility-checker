<?php
/**
 * Scheduled summary update for posts with ignored issues.
 *
 * NOTE: This entire class should ideally be hooked into a refactored and
 * generalized cleanup routine when that is merged in.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Inc\Summary_Generator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Summary_Update_Scheduler
 *
 * Handles scheduled updates of post summaries for posts that have recently
 * had issues ignored or unignored.
 *
 * @since 1.34.0
 */
class Summary_Update_Scheduler {

	/**
	 * Cron event name.
	 *
	 * @var string
	 */
	const EVENT = 'edac_update_post_summaries';

	/**
	 * Option name for storing post IDs.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'edac_last_ignored_ids';

	/**
	 * Register hooks.
	 *
	 * @since 1.34.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		self::schedule_event();
		add_action( self::EVENT, [ $this, 'run_summary_updates' ] );
	}

	/**
	 * Schedule the summary update event.
	 *
	 * @since 1.34.0
	 *
	 * @return void
	 */
	public static function schedule_event() {
		if ( ! wp_next_scheduled( self::EVENT ) ) {
			wp_schedule_event( time(), 'hourly', self::EVENT );
		}
	}

	/**
	 * Unschedule the summary update event.
	 *
	 * @since 1.34.0
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
	 * Get post IDs from the option.
	 *
	 * @since 1.34.0
	 *
	 * @return int[] Array of post IDs.
	 */
	protected function get_post_ids(): array {
		$post_ids = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $post_ids ) ) {
			return [];
		}

		// Ensure all values are integers.
		return array_map( 'intval', $post_ids );
	}

	/**
	 * Clear the post IDs option.
	 *
	 * @since 1.34.0
	 *
	 * @return void
	 */
	protected function clear_post_ids() {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Update summary for a single post.
	 *
	 * @since 1.34.0
	 *
	 * @param int $post_id The post ID.
	 * @return bool Whether the update was successful.
	 */
	protected function update_post_summary( int $post_id ): bool {
		// Verify the post exists.
		if ( ! get_post( $post_id ) ) {
			return false;
		}

		try {
			$summary_generator = new Summary_Generator( $post_id );
			$summary_generator->generate_summary();
			return true;
		} catch ( \Exception $e ) {
			// Log the error if WP_DEBUG is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logs in debug mode.
				error_log( 'EDAC Summary Update Error for Post ' . $post_id . ': ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Run summary updates for all pending posts.
	 *
	 * @since 1.34.0
	 *
	 * @return array {
	 *     Summary of the update operation.
	 *
	 *     @type int[] $processed  Array of successfully processed post IDs.
	 *     @type int[] $failed     Array of post IDs that failed to process.
	 *     @type int   $total      Total number of posts attempted.
	 * }
	 */
	public function run_summary_updates(): array {
		$post_ids = $this->get_post_ids();

		if ( empty( $post_ids ) ) {
			return [
				'processed' => [],
				'failed'    => [],
				'total'     => 0,
			];
		}

		$processed = [];
		$failed    = [];

		// Track start time to prevent timeouts.
		$start_time       = time();
		$max_execution    = (int) ini_get( 'max_execution_time' );
		$safety_threshold = 0.3; // Stop at 30% of max execution time.
		$time_limit       = $max_execution > 0 ? ( $max_execution * $safety_threshold ) : 50;
		$elapsed          = 0;

		// Split the array into chunks of 20 to avoid timeouts on large updates.
		$post_id_chunks = array_chunk( $post_ids, 20 );

		// Process each chunk separately.
		foreach ( $post_id_chunks as $chunk ) {
			// Check if we're approaching max execution time before processing next chunk.
			$elapsed = time() - $start_time;
			if ( $elapsed >= $time_limit ) {
				// Time limit reached, save remaining posts back to the option for next run.
				$remaining = array_merge( $chunk, ...array_slice( $post_id_chunks, array_search( $chunk, $post_id_chunks, true ) + 1 ) );
				$remaining = array_merge( $remaining, $failed ); // Include failed posts for retry.
				update_option( self::OPTION_NAME, array_unique( $remaining ) );
				break;
			}

			foreach ( $chunk as $post_id ) {
				if ( $this->update_post_summary( $post_id ) ) {
					$processed[] = $post_id;
				} else {
					$failed[] = $post_id;
				}
			}
		}

		// Only clear the option if all posts were processed successfully.
		if ( empty( $failed ) && $elapsed < $time_limit ) {
			$this->clear_post_ids();
		}

		return [
			'processed' => $processed,
			'failed'    => $failed,
			'total'     => count( $post_ids ),
		];
	}
}
