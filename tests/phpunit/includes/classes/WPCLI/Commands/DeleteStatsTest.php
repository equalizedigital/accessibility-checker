<?php
/**
 * Test cases for the delete-stats command.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\DeleteStats;

/**
 * Test cases to verify that the delete-stats command can delete the stats.
 */
class DeleteStatsTest extends WP_UnitTestCase {

	/**
	 * Set up the class under test and the database.
	 */
	protected function setUp(): void {
		$this->delete_stats = new DeleteStats( new Mock_WP_CLI() );
		DatabaseHelpers::create_table();
		parent::setUp();
	}

	/**
	 * Drop the table to clean up after tests.
	 */
	protected function tearDown(): void {
		DatabaseHelpers::drop_table();
		parent::tearDown();
	}

	/**
	 * Test the delete stats command errors if the post doesn't exist.
	 */
	public function test_delete_stats_command_errors_when_post_does_not_exist() {
		$non_existent_id = 132456789;

		ob_start();
		$this->delete_stats->__invoke( [ $non_existent_id ], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Error: Post ID ' . $non_existent_id . ' does not exist', $output );
	}

	/**
	 * Test the delete stats command errors if no post ID is passed.
	 */
	public function test_delete_stats_command_errors_when_no_id_is_passed() {
		ob_start();
		$this->delete_stats->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Error: No Post ID provided.', $output );
	}

	/**
	 * Test the delete stats command deletes the stats.
	 */
	public function test_delete_stats_command_deletes_stats() {
		$post_id = $this->factory()->post->create();
		DatabaseHelpers::insert_test_issue_to_db( get_post( $post_id ) );

		global $wpdb;
		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$stats_before = $wpdb->get_results( "SELECT * FROM $table_name WHERE postid = $post_id" ); // phpcs:ignore WordPress.DB -- Querying for testing purposes.
		$this->assertEquals( 1, count( $stats_before ) );

		ob_start();
		$this->delete_stats->__invoke( [ $post_id ], [] );
		$output = ob_get_clean();

		$this->assertStringStartsWith( 'Success: Stats of ' . $post_id . ' deleted', $output );

		// make sure the issue is actually deleted from the database.
		$stats_after = $wpdb->get_results( "SELECT * FROM $table_name WHERE postid = $post_id" ); // phpcs:ignore WordPress.DB -- Querying for testing purposes.
		$this->assertEmpty( $stats_after );
	}
}
