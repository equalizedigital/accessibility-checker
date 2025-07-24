<?php
/**
 * Tests for the Orphaned_Issues_Cleanup class.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Orphaned_Issues_Cleanup;

/**
 * Class OrphanedIssuesCleanupTest
 */
class OrphanedIssuesCleanupTest extends WP_UnitTestCase {

        /**
         * Table name for custom issues table.
         *
         * @var string
         */
        private $table_name;

        /**
         * Cleanup instance.
         *
         * @var Orphaned_Issues_Cleanup
         */
        private $cleanup;

        /**
         * Setup test environment.
         */
        public function setUp(): void {
                parent::setUp();

                global $wpdb;

                $this->table_name  = $wpdb->prefix . 'accessibility_checker';
                $charset_collate   = $wpdb->get_charset_collate();
                $sql               = "CREATE TABLE {$this->table_name} (
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        postid mediumint(9) NOT NULL,
                        siteid mediumint(9) NOT NULL,
                        PRIMARY KEY  (id)
                ) {$charset_collate};";

                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $sql );

                $this->cleanup = new Orphaned_Issues_Cleanup();
        }

        /**
         * Tear down the environment.
         */
        public function tearDown(): void {
                global $wpdb;
                $wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                parent::tearDown();
        }

        /**
         * Test that orphaned issues are removed.
         */
        public function test_orphaned_issues_are_deleted() {
                global $wpdb;

                $valid_id  = $this->factory()->post->create();
                $orphan_id = 999999;

                $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                        $this->table_name,
                        [
                                'postid' => $valid_id,
                                'siteid' => get_current_blog_id(),
                        ],
                        [
                                '%d',
                                '%d',
                        ]
                );

                $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                        $this->table_name,
                        [
                                'postid' => $orphan_id,
                                'siteid' => get_current_blog_id(),
                        ],
                        [
                                '%d',
                                '%d',
                        ]
                );

                $this->assertEquals( 2, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

                $this->cleanup->run_cleanup();

                $rows_after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $this->assertEquals( 1, $rows_after );

                $remaining_postid = (int) $wpdb->get_var( "SELECT postid FROM {$this->table_name} LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $this->assertEquals( $valid_id, $remaining_postid );
        }

        /**
         * Test that the event is scheduled.
         */
        public function test_schedule_event_registers_cron() {
                Orphaned_Issues_Cleanup::schedule_event();

                $this->assertNotFalse( wp_next_scheduled( Orphaned_Issues_Cleanup::EVENT ) );

                Orphaned_Issues_Cleanup::unschedule_event();
                $this->assertFalse( wp_next_scheduled( Orphaned_Issues_Cleanup::EVENT ) );
        }
}
