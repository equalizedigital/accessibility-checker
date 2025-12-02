<?php
/**
 * Tests for the Scans_Stats class.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Scans_Stats;

/**
 * Test class for Scans_Stats summary calculations.
 */
class ScansStatsTest extends WP_UnitTestCase {

        /**
         * Name of the accessibility checker table.
         *
         * @var string
         */
        protected $table_name;

        /**
         * Set up the database table required for the tests.
         */
        public function setUp(): void {
                parent::setUp();

                global $wpdb;

                $this->table_name = $wpdb->prefix . 'accessibility_checker';

                require_once dirname( __DIR__, 3 ) . '/admin/class-update-database.php';

                ( new \EDAC\Admin\Update_Database() )->edac_update_database();

                update_option( 'edac_post_types', [ 'post' ] );
        }

        /**
         * Clean up the custom table after each test.
         */
        public function tearDown(): void {
                global $wpdb;

                $wpdb->query( "DROP TABLE IF EXISTS $this->table_name" ); // phpcs:ignore WordPress.DB -- table name is safe and not caching in tests.

                delete_option( 'edac_post_types' );

                parent::tearDown();
        }

        /**
         * Ensures the average issue density ignores posts where all issues are ignored.
         */
        public function test_average_issue_density_excludes_ignored_posts() {
                global $wpdb;

                $site_id = get_current_blog_id();

                $ignored_post = self::factory()->post->create( [ 'post_type' => 'post', 'post_status' => 'publish' ] );
                $active_post  = self::factory()->post->create( [ 'post_type' => 'post', 'post_status' => 'publish' ] );

                update_post_meta( $ignored_post, '_edac_issue_density', 75 );
                update_post_meta( $active_post, '_edac_issue_density', 50 );

                $this->insert_issue_row(
                        $ignored_post,
                        $site_id,
                        [
                                'ignre' => 1,
                        ]
                );

                $this->insert_issue_row( $active_post, $site_id );

                $stats  = new Scans_Stats( 0 );
                $result = $stats->summary( true );

                $this->assertSame( 50.0, $result['avg_issue_density_percentage'] );

                // Mark the remaining issue as ignored and confirm the average density drops to zero.
                $wpdb->update(
                        $this->table_name,
                        [
                                'ignre'        => 1,
                                'ignre_global' => 0,
                        ],
                        [
                                'postid' => $active_post,
                        ]
                );

                $result = $stats->summary( true );

                $this->assertSame( 0.0, $result['avg_issue_density_percentage'] );
        }

        /**
         * Helper to insert an issue row into the accessibility checker table.
         *
         * @param int   $post_id  Post ID the issue is associated with.
         * @param int   $site_id  Current site ID.
         * @param array $overrides Optional overrides for the default row data.
         */
        private function insert_issue_row( int $post_id, int $site_id, array $overrides = [] ): void {
                global $wpdb;

                $defaults = [
                        'postid'            => $post_id,
                        'siteid'            => (string) $site_id,
                        'type'              => 'post',
                        'landmark'          => null,
                        'landmark_selector' => null,
                        'selector'          => null,
                        'ancestry'          => null,
                        'xpath'             => null,
                        'rule'              => 'test_rule',
                        'ruletype'          => 'error',
                        'object'            => '{}',
                        'recordcheck'       => 1,
                        'user'              => 1,
                        'ignre'             => 0,
                        'ignre_global'      => 0,
                        'ignre_user'        => null,
                        'ignre_date'        => null,
                        'ignre_comment'     => null,
                ];

                $data = array_merge( $defaults, $overrides );

                $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- direct insert is acceptable within tests.
                        $this->table_name,
                        $data
                );
        }
}

