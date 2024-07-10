<?php
/**
 * Test the GetStats command.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Update_Database;
use EqualizeDigital\AccessibilityChecker\Tests\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\BootstrapCLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\GetStats;

/**
 * Test cases to verify that the GetStats command can get the stats.
 */
class GetStatsTest extends WP_UnitTestCase {

	/**
	 * Sets up the mock and injects it into the class under test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once dirname( __DIR__, 4 ) . '/Mocks/Mock_WP_CLI.php';
		// since this is a synthetic run on WP-CLI, we need to define WP_CLI.
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}

		$wp_cli = new Mock_WP_CLI();

		( new BootstrapCLI( $wp_cli ) )->register();
		$this->get_stats = new GetStats( $wp_cli );

		// create database tables if they don't exist.
		( new Update_Database() )->edac_update_database();

		parent::setUp();
	}

	/**
	 * Drop the table to clean up after tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->drop_table();

		parent::tearDown();
	}

	/**
	 * Test the get stats command errors if the post doesn't exist/
	 */
	public function test_get_stats_command_errors_when_post_does_not_exist() {

		$non_existent_id = 132456789;

		ob_start();
		$this->get_stats->__invoke( [ $non_existent_id ], [] );
		$stats = ob_get_clean();

		// check we have the expected error.
		$this->assertEquals( 'Error: Post ID ' . $non_existent_id . ' does not exist.', $stats );
	}

	/**
	 * Test the get stats command can complete when no stats exist for a post.
	 */
	public function test_get_stats_command_completes_when_no_stats_exist_for_post() {

		$post_id = $this->factory()->post->create();

		ob_start();
		$this->get_stats->__invoke( [ $post_id ], [] );
		$stats = ob_get_clean();

		$this->assertStringStartsWith( 'Success: Either the post is not yet scanned or all tests passed', $stats );
	}

	/**
	 * Test the get stats command can get stats for a post can get the stats.
	 */
	public function test_get_stats_command_returns_results_when_stats_exist_for_post() {
		$post_id = $this->factory()->post->create();
		$post    = get_post( $post_id );

		$this->insert_issue_to_db( $post );

		ob_start();
		$this->get_stats->__invoke( [ $post_id ], [] );
		$stats = ob_get_clean();

		$this->assertStringStartsWith( 'Success: {', $stats );
	}

	/**
	 * Insert a record to the database for a given post.
	 *
	 * @param WP_Post $post The post to insert the record for.
	 *
	 * @return void
	 */
	private function insert_issue_to_db( WP_Post $post ): void {

		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->insert( // phpcs:ignore WordPress.DB -- using direct query for testing.
			$table_name,
			[
				'postid'        => $post->ID,
				'siteid'        => get_current_blog_id(),
				'type'          => $post->post_type,
				'rule'          => 'empty_paragraph_tag',
				'ruletype'      => 'warning',
				'object'        => '<p></p>',
				'recordcheck'   => 1,
				'user'          => get_current_user_id(),
				'ignre'         => 0,
				'ignre_user'    => null,
				'ignre_date'    => null,
				'ignre_comment' => null,
				'ignre_global'  => 0,
			]
		);
	}

	/**
	 * Drops the table for the plugin if it exists.
	 *
	 * Used for cleanup after tests.
	 *
	 * @return void
	 */
	private function drop_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name ); // phpcs:ignore WordPress.DB -- query for a unit test.
	}
}
