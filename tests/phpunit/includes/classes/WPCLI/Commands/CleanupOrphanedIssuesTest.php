<?php
/**
 * Test cases for the cleanup-orphaned-issues command.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\CleanupOrphanedIssues;

/**
 * Test cases to verify that the cleanup-orphaned-issues command operates correctly in various situations.
 */
class CleanupOrphanedIssuesTest extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var CleanupOrphanedIssues
	 */
	protected $cleanup_orphaned_issues;

	/**
	 * Set up the class under test and the database.
	 */
	protected function setUp(): void {
		$this->cleanup_orphaned_issues = new CleanupOrphanedIssues( new Mock_WP_CLI() );
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
	 * Test the get_name method returns the correct command name.
	 */
	public function test_get_name_returns_correct_command_name() {
		$expected_name = 'accessibility-checker cleanup-orphaned-issues';
		$actual_name   = CleanupOrphanedIssues::get_name();

		$this->assertEquals( $expected_name, $actual_name );
	}

	/**
	 * Test the get_args method returns the correct arguments structure.
	 */
	public function test_get_args_returns_correct_arguments() {
		$args = CleanupOrphanedIssues::get_args();

		$this->assertIsArray( $args );
		$this->assertArrayHasKey( 'batch', $args );
		$this->assertArrayHasKey( 'sleep', $args );

		// Check batch argument structure.
		$this->assertArrayHasKey( 'type', $args['batch'] );
		$this->assertArrayHasKey( 'description', $args['batch'] );
		$this->assertArrayHasKey( 'optional', $args['batch'] );
		$this->assertArrayHasKey( 'default', $args['batch'] );
		$this->assertEquals( 'assoc', $args['batch']['type'] );
		$this->assertTrue( $args['batch']['optional'] );

		// Check sleep argument structure.
		$this->assertArrayHasKey( 'type', $args['sleep'] );
		$this->assertArrayHasKey( 'description', $args['sleep'] );
		$this->assertArrayHasKey( 'optional', $args['sleep'] );
		$this->assertArrayHasKey( 'default', $args['sleep'] );
		$this->assertEquals( 'assoc', $args['sleep']['type'] );
		$this->assertTrue( $args['sleep']['optional'] );
		$this->assertEquals( 0, $args['sleep']['default'] );
	}

	/**
	 * Test the cleanup command succeeds when no orphaned issues are found.
	 */
	public function test_cleanup_command_succeeds_when_no_orphaned_issues_found() {
		ob_start();
		$this->cleanup_orphaned_issues->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: No orphaned issues found.', $output );
	}

	/**
	 * Test the cleanup command processes orphaned issues when they exist.
	 */
	public function test_cleanup_command_processes_orphaned_issues_when_they_exist() {
		// Create an issue for a non-existent post ID to simulate orphaned data.
		global $wpdb;
		$table_name      = $wpdb->prefix . 'accessibility_checker';
		$non_existent_id = 999999;

		$wpdb->insert( // phpcs:ignore WordPress.DB -- using direct query for testing.
			$table_name,
			[
				'postid'        => $non_existent_id,
				'siteid'        => get_current_blog_id(),
				'type'          => 'post',
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

		// Verify the issue exists before cleanup.
		$issues_before = $wpdb->get_results( "SELECT * FROM $table_name WHERE postid = $non_existent_id" ); // phpcs:ignore WordPress.DB -- Querying for testing purposes.
		$this->assertEquals( 1, count( $issues_before ) );

		ob_start();
		$this->cleanup_orphaned_issues->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Log: Found 1 orphaned post IDs', $output );
		$this->assertStringContainsString( "Log:  - Deleting issues for post ID: $non_existent_id", $output );
		$this->assertStringContainsString( 'Success: Orphaned issues cleanup complete. 1 post(s) processed.', $output );

		// Verify the issue was deleted after cleanup.
		$issues_after = $wpdb->get_results( "SELECT * FROM $table_name WHERE postid = $non_existent_id" ); // phpcs:ignore WordPress.DB -- Querying for testing purposes.
		$this->assertEmpty( $issues_after );
	}

	/**
	 * Test the cleanup command handles custom batch size parameter.
	 */
	public function test_cleanup_command_handles_custom_batch_size() {
		// Create multiple orphaned issues.
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$post_ids   = [ 999998, 999999 ];

		foreach ( $post_ids as $post_id ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB -- using direct query for testing.
				$table_name,
				[
					'postid'        => $post_id,
					'siteid'        => get_current_blog_id(),
					'type'          => 'post',
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

		// Test with batch size of 1.
		ob_start();
		$this->cleanup_orphaned_issues->__invoke( [], [ 'batch' => '1' ] );
		$output = ob_get_clean();

		// Should find and process at least 1 orphaned post ID.
		$this->assertStringContainsString( 'Log: Found 1 orphaned post IDs', $output );
		$this->assertStringContainsString( 'Success: Orphaned issues cleanup complete. 1 post(s) processed.', $output );
	}

	/**
	 * Test the cleanup command handles sleep parameter without errors.
	 */
	public function test_cleanup_command_handles_sleep_parameter() {
		// Create an orphaned issue.
		global $wpdb;
		$table_name      = $wpdb->prefix . 'accessibility_checker';
		$non_existent_id = 999997;

		$wpdb->insert( // phpcs:ignore WordPress.DB -- using direct query for testing.
			$table_name,
			[
				'postid'        => $non_existent_id,
				'siteid'        => get_current_blog_id(),
				'type'          => 'post',
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

		// Test with a very small sleep value to avoid slowing down tests.
		ob_start();
		$this->cleanup_orphaned_issues->__invoke( [], [ 'sleep' => '0.001' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Log: Found 1 orphaned post IDs', $output );
		$this->assertStringContainsString( "Log:  - Deleting issues for post ID: $non_existent_id", $output );
		$this->assertStringContainsString( 'Success: Orphaned issues cleanup complete. 1 post(s) processed.', $output );
	}

	/**
	 * Test the cleanup command handles invalid batch parameter gracefully.
	 */
	public function test_cleanup_command_handles_invalid_batch_parameter() {
		ob_start();
		$this->cleanup_orphaned_issues->__invoke( [], [ 'batch' => 'invalid' ] );
		$output = ob_get_clean();

		// Should still complete successfully (invalid batch is ignored).
		$this->assertStringContainsString( 'Success: No orphaned issues found.', $output );
	}

	/**
	 * Test the cleanup command handles invalid sleep parameter gracefully.
	 */
	public function test_cleanup_command_handles_invalid_sleep_parameter() {
		ob_start();
		$this->cleanup_orphaned_issues->__invoke( [], [ 'sleep' => 'invalid' ] );
		$output = ob_get_clean();

		// Should still complete successfully (invalid sleep defaults to 0).
		$this->assertStringContainsString( 'Success: No orphaned issues found.', $output );
	}
}
