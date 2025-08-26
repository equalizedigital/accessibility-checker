<?php
/**
 * Test cases for the OrphanedIssuesCleanupTask class.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\OrphanedIssuesCleanupTask;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;
use EDAC\Admin\Orphaned_Issues_Cleanup;

/**
 * Test cases to verify that the OrphanedIssuesCleanupTask operates correctly.
 */
class OrphanedIssuesCleanupTaskTest extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var OrphanedIssuesCleanupTask
	 */
	protected $cleanup_task;

	/**
	 * Set up the class under test and the database.
	 */
	protected function setUp(): void {
		$this->cleanup_task = new OrphanedIssuesCleanupTask();
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
	 * Test the task ID is correct.
	 */
	public function test_get_task_id() {
		$this->assertEquals( 'orphaned_issues', $this->cleanup_task->get_task_id() );
	}

	/**
	 * Test the task name is localized.
	 */
	public function test_get_task_name() {
		$name = $this->cleanup_task->get_task_name();
		$this->assertNotEmpty( $name );
		$this->assertIsString( $name );
	}

	/**
	 * Test the task description is localized.
	 */
	public function test_get_task_description() {
		$description = $this->cleanup_task->get_task_description();
		$this->assertNotEmpty( $description );
		$this->assertIsString( $description );
	}

	/**
	 * Test the default schedule is daily.
	 */
	public function test_get_default_schedule() {
		$this->assertEquals( 'daily', $this->cleanup_task->get_default_schedule() );
	}

	/**
	 * Test the default batch size matches the legacy constant.
	 */
	public function test_get_default_batch_size() {
		$this->assertEquals( Orphaned_Issues_Cleanup::BATCH_LIMIT, $this->cleanup_task->get_default_batch_size() );
	}

	/**
	 * Test the task is enabled by default.
	 */
	public function test_is_enabled_by_default() {
		$this->assertTrue( $this->cleanup_task->is_enabled() );
	}

	/**
	 * Test the task can be disabled via filter.
	 */
	public function test_can_be_disabled_via_filter() {
		add_filter( 'edac_orphaned_issues_cleanup_enabled', '__return_false' );
		$this->assertFalse( $this->cleanup_task->is_enabled() );
		remove_filter( 'edac_orphaned_issues_cleanup_enabled', '__return_false' );
	}

	/**
	 * Test the default priority is 10.
	 */
	public function test_get_default_priority() {
		$this->assertEquals( 10, $this->cleanup_task->get_priority() );
	}

	/**
	 * Test the priority can be changed via filter.
	 */
	public function test_priority_can_be_changed_via_filter() {
		add_filter(
			'edac_orphaned_issues_cleanup_priority',
			function () {
				return 5;
			} 
		);
		$this->assertEquals( 5, $this->cleanup_task->get_priority() );
		remove_all_filters( 'edac_orphaned_issues_cleanup_priority' );
	}

	/**
	 * Test running cleanup with no orphaned issues.
	 */
	public function test_run_cleanup_with_no_orphaned_issues() {
		$results = $this->cleanup_task->run_cleanup();
		$this->assertIsArray( $results );
		$this->assertEmpty( $results );
	}

	/**
	 * Test running cleanup with orphaned issues.
	 */
	public function test_run_cleanup_with_orphaned_issues() {
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

		// Run cleanup.
		$results = $this->cleanup_task->run_cleanup();

		// Verify results contain the cleaned up post ID.
		$this->assertIsArray( $results );
		$this->assertContains( (string) $non_existent_id, $results );

		// Verify the issue was deleted after cleanup.
		$issues_after = $wpdb->get_results( "SELECT * FROM $table_name WHERE postid = $non_existent_id" ); // phpcs:ignore WordPress.DB -- Querying for testing purposes.
		$this->assertEmpty( $issues_after );
	}

	/**
	 * Test running cleanup with custom batch size.
	 */
	public function test_run_cleanup_with_custom_batch_size() {
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

		// Run with batch size of 1.
		$results = $this->cleanup_task->run_cleanup( 1 );

		// Should process exactly 1 item.
		$this->assertIsArray( $results );
		$this->assertEquals( 1, count( $results ) );
	}

	/**
	 * Test that the legacy cleanup instance is accessible.
	 */
	public function test_get_legacy_cleanup() {
		$legacy_cleanup = $this->cleanup_task->get_legacy_cleanup();
		$this->assertInstanceOf( Orphaned_Issues_Cleanup::class, $legacy_cleanup );
	}
}
