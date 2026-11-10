<?php
/**
 * Tests for the edac_remove_corrected_posts helper.
 *
 * @package Accessibility_Checker
 * @since 1.51.0
 */

use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;

/**
 * Tests for edac_remove_corrected_posts.
 *
 * The helper is the only thing that clears issue rows a rescan or an edit has
 * made stale, and the REST API calls it twice per scan: once before the
 * validation rules run (stage 1, which marks the rows unchecked) and once after
 * (stage 2, which deletes the rows nothing corrected). Getting the WHERE clause
 * of either stage wrong deletes issues that are still current, or leaves the
 * deleted ones on the reports screen, so both stages are asserted against the
 * table rather than against the arguments they were handed.
 *
 * @covers ::edac_remove_corrected_posts
 * @since 1.51.0
 */
class RemoveCorrectedPostsTest extends WP_UnitTestCase {

	/**
	 * The plugin issues table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Create the plugin table and start from a clean one.
	 */
	public function setUp(): void {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'accessibility_checker';

		// Create the table before the parent setUp so the DDL is not swallowed
		// by the per-test transaction, then clear the rows this class shares
		// with every other test that uses the table.
		DatabaseHelpers::create_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- clearing prior fixtures for this test class.
		$wpdb->delete( $this->table_name, [ 'siteid' => get_current_blog_id() ], [ '%d' ] );

		parent::setUp();
	}

	/**
	 * Drop the fixture table and the rows these tests left behind.
	 */
	public function tearDown(): void {
		DatabaseHelpers::drop_table();

		parent::tearDown();
	}

	/**
	 * Insert an issue row for a post, optionally left unchecked.
	 *
	 * @param WP_Post $post         The post the issue belongs to.
	 * @param int     $recordcheck  The recordcheck flag to store.
	 */
	private function insert_issue( WP_Post $post, int $recordcheck = 1 ): void {
		global $wpdb;

		DatabaseHelpers::insert_test_issue_to_db( $post );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- setting the fixture's recordcheck flag.
		$wpdb->update( $this->table_name, [ 'recordcheck' => $recordcheck ], [ 'postid' => $post->ID ] );
	}

	/**
	 * Read the stored row count and recordcheck flag for one post and post type.
	 *
	 * The values are cast because $wpdb hands them back as strings.
	 *
	 * @param int    $post_id The post id the issues are stored against.
	 * @param string $type    The post type recorded with the issues.
	 * @return array{count: int, recordcheck: int} The row count and the stored flag.
	 */
	private function rows( int $post_id, string $type ): array {
		global $wpdb;

		$sql = 'SELECT COUNT(*) AS row_count, MAX(recordcheck) AS recordcheck FROM ' . $this->table_name . ' WHERE postid = %d AND type = %s';

		// The table name is this test's fixture table and both values are placeholders.
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $post_id, $type ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- Reading back fixture rows.

		return [
			'count'       => (int) ( $row['row_count'] ?? 0 ),
			'recordcheck' => (int) ( $row['recordcheck'] ?? 0 ),
		];
	}

	/**
	 * Tests that the default stage keeps the row and marks it unchecked.
	 */
	public function test_default_stage_marks_the_row_unchecked(): void {
		$post = self::factory()->post->create_and_get();

		$this->insert_issue( $post );

		edac_remove_corrected_posts( $post->ID, 'post' );

		$this->assertSame(
			[
				'count'       => 1,
				'recordcheck' => 0,
			],
			$this->rows( $post->ID, 'post' )
		);
	}

	/**
	 * Tests that the second stage deletes an unchecked row and keeps a checked one.
	 */
	public function test_second_stage_deletes_only_unchecked_rows(): void {
		$unchecked = self::factory()->post->create_and_get();
		$checked   = self::factory()->post->create_and_get();

		$this->insert_issue( $unchecked, 0 );
		$this->insert_issue( $checked );

		edac_remove_corrected_posts( $unchecked->ID, 'post', 2 );
		edac_remove_corrected_posts( $checked->ID, 'post', 2 );

		$this->assertSame( 0, $this->rows( $unchecked->ID, 'post' )['count'] );
		$this->assertSame(
			[
				'count'       => 1,
				'recordcheck' => 1,
			],
			$this->rows( $checked->ID, 'post' )
		);
	}

	/**
	 * Tests that only the given post and post type are touched.
	 */
	public function test_only_the_given_post_and_post_type_are_affected(): void {
		$scanned = self::factory()->post->create_and_get();
		$page    = self::factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		$other   = self::factory()->post->create_and_get();

		$this->insert_issue( $scanned );
		$this->insert_issue( $page );
		$this->insert_issue( $other );

		edac_remove_corrected_posts( $scanned->ID, 'post' );

		$this->assertSame( 0, $this->rows( $scanned->ID, 'post' )['recordcheck'] );
		$this->assertSame( 1, $this->rows( $page->ID, 'page' )['recordcheck'] );
		$this->assertSame( 1, $this->rows( $other->ID, 'post' )['recordcheck'] );
	}
}
