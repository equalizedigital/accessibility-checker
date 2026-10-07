<?php
/**
 * Tests for the scheduled orphaned issues cleanup.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Orphaned_Issues_Cleanup;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;

/**
 * Tests for Orphaned_Issues_Cleanup.
 *
 * @covers \EDAC\Admin\Orphaned_Issues_Cleanup::init_hooks
 * @covers \EDAC\Admin\Orphaned_Issues_Cleanup::schedule_event
 * @covers \EDAC\Admin\Orphaned_Issues_Cleanup::get_orphaned_post_ids
 * @covers \EDAC\Admin\Orphaned_Issues_Cleanup::run_cleanup
 */
class OrphanedIssuesCleanupTest extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var Orphaned_Issues_Cleanup
	 */
	private $cleanup;

	/**
	 * The plugin issues table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Create the plugin table and the class under test.
	 */
	public function setUp(): void {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'accessibility_checker';
		$this->cleanup    = new Orphaned_Issues_Cleanup();

		// Create the table before the parent setUp so its per-test transaction
		// is not implicitly committed by the table DDL.
		DatabaseHelpers::create_table();

		parent::setUp();
	}

	/**
	 * Remove the fixtures created by these tests.
	 */
	public function tearDown(): void {
		delete_option( 'edac_post_types' );
		wp_clear_scheduled_hook( Orphaned_Issues_Cleanup::EVENT );
		DatabaseHelpers::drop_table();

		parent::tearDown();
	}

	/**
	 * Insert an issue row straight into the plugin table.
	 *
	 * @param int      $post_id The post id the issue is stored against.
	 * @param string   $type    The post type recorded with the issue.
	 * @param int|null $site_id The blog id, defaults to the current blog.
	 */
	private function insert_issue( int $post_id, string $type = 'post', ?int $site_id = null ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- direct insert is the test fixture.
			$this->table_name,
			[
				'postid'        => $post_id,
				'siteid'        => $site_id ?? get_current_blog_id(),
				'type'          => $type,
				'rule'          => 'empty_link',
				'ruletype'      => 'error',
				'object'        => '<a></a>',
				'recordcheck'   => 1,
				'user'          => 0,
				'ignre'         => 0,
				'ignre_user'    => null,
				'ignre_date'    => null,
				'ignre_comment' => null,
				'ignre_global'  => 0,
			]
		);
	}

	/**
	 * Count the issue rows stored for a post.
	 *
	 * @param int $post_id The post id to count rows for.
	 * @return int
	 */
	private function count_issues_for( int $post_id ): int {
		global $wpdb;

		// The count is returned as a string, so cast it for comparisons.
		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- counting the test fixture rows.
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE postid = %d', $this->table_name, $post_id )
		);
	}

	/**
	 * Registering the hooks schedules the event and adds the cleanup callback.
	 */
	public function test_init_hooks_schedules_the_event_and_adds_the_callback(): void {
		wp_clear_scheduled_hook( Orphaned_Issues_Cleanup::EVENT );

		$this->cleanup->init_hooks();

		$this->assertNotFalse( wp_next_scheduled( Orphaned_Issues_Cleanup::EVENT ) );
		$this->assertSame( 10, has_action( Orphaned_Issues_Cleanup::EVENT, [ $this->cleanup, 'run_cleanup' ] ) );
	}

	/**
	 * An event already scheduled with the expected recurrence is left alone.
	 */
	public function test_schedule_event_leaves_an_existing_event_alone(): void {
		wp_clear_scheduled_hook( Orphaned_Issues_Cleanup::EVENT );
		$timestamp = time() + HOUR_IN_SECONDS;
		wp_schedule_event( $timestamp, Orphaned_Issues_Cleanup::RECURRENCE, Orphaned_Issues_Cleanup::EVENT );

		Orphaned_Issues_Cleanup::schedule_event();

		$this->assertSame( $timestamp, wp_next_scheduled( Orphaned_Issues_Cleanup::EVENT ) );
	}

	/**
	 * An event scheduled with a different recurrence is rescheduled.
	 */
	public function test_schedule_event_reschedules_a_stale_recurrence(): void {
		wp_clear_scheduled_hook( Orphaned_Issues_Cleanup::EVENT );
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', Orphaned_Issues_Cleanup::EVENT );

		Orphaned_Issues_Cleanup::schedule_event();

		$event = wp_get_scheduled_event( Orphaned_Issues_Cleanup::EVENT );
		$this->assertNotFalse( $event );
		$this->assertSame( Orphaned_Issues_Cleanup::RECURRENCE, $event->schedule );
	}

	/**
	 * The event is created when nothing is scheduled.
	 */
	public function test_schedule_event_creates_the_event_when_it_is_missing(): void {
		wp_clear_scheduled_hook( Orphaned_Issues_Cleanup::EVENT );

		Orphaned_Issues_Cleanup::schedule_event();

		$event = wp_get_scheduled_event( Orphaned_Issues_Cleanup::EVENT );
		$this->assertNotFalse( $event );
		$this->assertSame( Orphaned_Issues_Cleanup::RECURRENCE, $event->schedule );
	}

	/**
	 * Nothing is deleted when every issue belongs to a scannable post.
	 */
	public function test_run_cleanup_reports_nothing_to_do_for_scannable_posts(): void {
		update_option( 'edac_post_types', [ 'post' ] );
		$post = self::factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		$this->insert_issue( $post->ID );

		$this->assertSame( [], $this->cleanup->run_cleanup() );
		$this->assertSame( 1, $this->count_issues_for( $post->ID ) );
	}

	/**
	 * Issues for posts that no longer exist are deleted.
	 */
	public function test_run_cleanup_deletes_issues_for_missing_posts(): void {
		update_option( 'edac_post_types', [ 'post' ] );
		$post      = self::factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		$orphan_id = 987654;
		$this->insert_issue( $orphan_id );
		$this->insert_issue( $post->ID );

		// The ids come back from the database as strings, so compare the cast values.
		$this->assertSame( [ $orphan_id ], array_map( 'intval', $this->cleanup->run_cleanup() ) );
		$this->assertSame( 0, $this->count_issues_for( $orphan_id ) );
		$this->assertSame( 1, $this->count_issues_for( $post->ID ) );
	}

	/**
	 * Issues for posts whose type is no longer scannable are deleted.
	 */
	public function test_run_cleanup_deletes_issues_for_unscannable_post_types(): void {
		update_option( 'edac_post_types', [ 'post' ] );
		$page = self::factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		$this->insert_issue( $page->ID, 'page' );

		$this->assertSame( [ $page->ID ], array_map( 'intval', $this->cleanup->run_cleanup() ) );
		$this->assertSame( 0, $this->count_issues_for( $page->ID ) );
	}

	/**
	 * Orphan detection is scoped to the current site and honours the batch size.
	 */
	public function test_get_orphaned_post_ids_limits_to_the_site_and_batch_size(): void {
		update_option( 'edac_post_types', [ 'post' ] );
		$this->insert_issue( 987651 );
		$this->insert_issue( 987652 );
		$this->insert_issue( 987653, 'post', get_current_blog_id() + 1 );

		$this->cleanup->set_batch_size( 5 );
		$this->assertEqualsCanonicalizing( [ 987651, 987652 ], array_map( 'intval', $this->cleanup->get_orphaned_post_ids() ) );

		$this->cleanup->set_batch_size( 1 );
		$this->assertCount( 1, $this->cleanup->get_orphaned_post_ids() );
	}

	/**
	 * With no scannable post types configured every issue is treated as orphaned.
	 */
	public function test_get_orphaned_post_ids_falls_back_to_all_issues_without_scannable_post_types(): void {
		delete_option( 'edac_post_types' );
		$post = self::factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		$this->insert_issue( $post->ID );

		$this->assertSame( [ $post->ID ], array_map( 'intval', $this->cleanup->get_orphaned_post_ids() ) );
	}
}
