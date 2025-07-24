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
         * Register hooks.
         *
         * @return void
         */
        public function init_hooks() {
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
         * Cleanup orphaned issues.
         *
         * @return void
         */
        public function run_cleanup() {
                global $wpdb;

                $table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );

                if ( ! $table_name ) {
                        return;
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $post_ids = $wpdb->get_col(
                        $wpdb->prepare(
                                "SELECT DISTINCT t.postid FROM %i AS t LEFT JOIN {$wpdb->posts} AS p ON t.postid = p.ID WHERE t.siteid = %d AND p.ID IS NULL LIMIT %d",
                                $table_name,
                                get_current_blog_id(),
                                self::BATCH_LIMIT
                        )
                );

                foreach ( $post_ids as $post_id ) {
                        Purge_Post_Data::delete_post( (int) $post_id );
                }
        }
}
