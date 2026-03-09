<?php
/**
 * Tests for the Purge Post Data class.
 *
 * @package Accessibility_Checker
 */

use PHPUnit\Framework\TestCase;
use EDAC\Admin\Purge_Post_Data;

/**
 * Class PurgePostDataTest
 */
class PurgePostDataTest extends WP_UnitTestCase {

	/**
	 * The ID of a post that can be used for testing.
	 *
	 * @var int
	 */
	public $valid_post_id;

	/**
	 * Sets up some database tables and a post to test with.
	 *
	 * @var $valid_post_id int
	 */
	public function setUp(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'accessibility_checker';

		$sql = "CREATE TABLE $table_name (
	        id mediumint(9) NOT NULL AUTO_INCREMENT,
	        postid mediumint(9) NOT NULL,
	        siteid mediumint(9) NOT NULL,
	        PRIMARY KEY  (id)
	    ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$this->valid_post_id = $this->factory()->post->create();

		// Insert data into the 'accessibility_checker' table.
		$wpdb->insert( // phpcs:ignore WordPress.DB -- this is just one-time use data for testing.
			$table_name,
			[
				'postid' => $this->valid_post_id,
				'siteid' => 1,
			],
			[
				'%d',
				'%d',
			]
		);
	}

	/**
	 * Removes the database tables and post created in the setUp method.
	 */
	public function tearDown(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB -- Safe variable used for table name, caching not required for one time operation.

		wp_delete_post( $this->valid_post_id, true );
	}

	/**
	 * Test that the method to delete deletes the metadata from the database.
	 */
	public function testDeletePostMetaRemovesMetaFromDatabase() {
		$this->set_edac_post_meta( $this->valid_post_id );

		Purge_Post_Data::delete_post_meta( $this->valid_post_id );

		$this->assertEmpty( get_post_meta( $this->valid_post_id, '_edac', true ) );
		$this->assertEmpty( get_post_meta( $this->valid_post_id, '_edacp', true ) );
		$this->assertNotEmpty( get_post_meta( $this->valid_post_id, 'other', true ) );
	}

	/**
	 * Test that Purge_Post_Data::delete_post() is called when the wp_trash_post action fires.
	 */
	public function testDeletePostIsCalledOnWpTrashPost() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Check that the row exists before trashing the post.
		$row_exists_before = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE postid = %d", $this->valid_post_id ) ); // phpcs:ignore WordPress.DB -- Safe variable used for table name.
		$this->assertEquals( 1, $row_exists_before );

		// The action is now added in the init method the Admin class which is
		// not loaded here in this test class so need to manually hos this in.
		add_action( 'wp_trash_post', [ Purge_Post_Data::class, 'delete_post' ] );

		wp_trash_post( $this->valid_post_id );

		// Check that the row no longer exists after trashing the post.
		$row_exists_after = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE postid = %d", $this->valid_post_id ) ); // phpcs:ignore WordPress.DB -- Safe variable used for table name.
		$this->assertEquals( 0, $row_exists_after );
	}

	/**
	 * Test that Purge_Post_Data::delete_cpt_posts() deletes the post type
	 * entries from the custom table and the post meta.
	 */
	public function testCPTPostsAreDeleted() {

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'accessibility_checker';

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			postid bigint(20) NOT NULL,
			siteid text NOT NULL,
			type text NOT NULL,
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$data = [
			'postid' => $this->valid_post_id,
			'siteid' => get_current_blog_id(),
			'type'   => 'post',
		];

		$format = [
			'%d',
			'%d',
			'%s',
		];

		$wpdb->insert( $table_name, $data, $format ); // phpcs:ignore WordPress.DB -- this is just one-time use data for testing.

		// count the number of rows in the table.
		$rows_before = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ); // phpcs:ignore WordPress.DB -- caching not required in tests
		$this->set_edac_post_meta( $this->valid_post_id );

		Purge_Post_Data::delete_cpt_posts( 'post' );
		// should now have fewer rows.
		$this->assertLessThan( $rows_before, $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) ); // phpcs:ignore WordPress.DB -- caching not required in tests

		// make sure that post meta has been deleted when custom table data
		// is deleted.
		$this->assertEmpty( get_post_meta( $this->valid_post_id, '_edac', true ) );
		$this->assertEmpty( get_post_meta( $this->valid_post_id, '_edacp', true ) );
		$this->assertNotEmpty( get_post_meta( $this->valid_post_id, 'other', true ) );
	}

	/**
	 * Test that the deprecated functions are flagged as deprecated.
	 */
	public function testDeprecatedFunctionsAreDeprecated() {
		// Set up the expectation for the deprecated function.
		$this->expectDeprecated();

		// Add the deprecated function to the list of expected deprecated functions.
		$this->setExpectedDeprecated( 'edac_delete_post' );
		$this->setExpectedDeprecated( 'edac_delete_post_meta' );
		$this->setExpectedDeprecated( 'edac_delete_cpt_posts' );

		// Call the deprecated function.
		edac_delete_post( $this->valid_post_id );
		$another_post = $this->factory()->post->create();
		$this->set_edac_post_meta( $another_post );
		edac_delete_post_meta( $this->valid_post_id );
		edac_delete_cpt_posts( 'some_cpt' );
	}

	/**
	 * Set the plugins post meta keys for the given post ID.
	 *
	 * @param int $post_id The post ID to set the post meta for.
	 */
	private function set_edac_post_meta( $post_id ) {
		$post_meta = [
			'_edac'  => 'value',
			'_edacp' => 'value',
			'other'  => 'value',
		];
		foreach ( $post_meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Test that delete_status_posts() removes custom table rows and postmeta
	 * for posts that have the given post status.
	 */
	public function testDeleteStatusPostsRemovesDataForGivenStatus() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Re-create the table with the full schema needed for the JOIN query.
		$charset_collate = $wpdb->get_charset_collate();
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Safe variable used for table name, caching not required for one time operation.
		$wpdb->query(
			// phpcs:ignore WordPress.DB
			"CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				siteid bigint(20) NOT NULL,
				type text NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;" // phpcs:ignore WordPress.DB
		);

		// Create a draft post and a published post.
		$draft_post_id   = $this->factory()->post->create( [ 'post_status' => 'draft' ] );
		$publish_post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		// Insert rows into the custom table for both posts.
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- this is just one-time use data for testing.
			$table_name,
			[
				'postid' => $draft_post_id,
				'siteid' => get_current_blog_id(),
				'type'   => 'post',
			],
			[ '%d', '%d', '%s' ]
		);
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- this is just one-time use data for testing.
			$table_name,
			[
				'postid' => $publish_post_id,
				'siteid' => get_current_blog_id(),
				'type'   => 'post',
			],
			[ '%d', '%d', '%s' ]
		);

		// Set _edac* postmeta on both posts.
		$this->set_edac_post_meta( $draft_post_id );
		$this->set_edac_post_meta( $publish_post_id );

		$rows_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- caching not required in tests.
		$this->assertEquals( 3, $rows_before, 'Expected 3 rows before purge (setUp row + 2 new rows).' );

		// Purge draft posts only.
		Purge_Post_Data::delete_status_posts( 'draft' );

		// Custom table row for draft should be gone; publish row should remain.
		$draft_row   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE postid = %d", $draft_post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe variable used for table name, caching not required in tests.
		$publish_row = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE postid = %d", $publish_post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe variable used for table name, caching not required in tests.
		$this->assertEquals( 0, $draft_row, 'Draft post row should have been deleted.' );
		$this->assertEquals( 1, $publish_row, 'Publish post row should NOT have been deleted.' );

		// _edac* postmeta for draft should be gone; publish should remain.
		$this->assertEmpty( get_post_meta( $draft_post_id, '_edac', true ), 'Draft _edac meta should be deleted.' );
		$this->assertEmpty( get_post_meta( $draft_post_id, '_edacp', true ), 'Draft _edacp meta should be deleted.' );
		$this->assertNotEmpty( get_post_meta( $publish_post_id, '_edac', true ), 'Publish _edac meta should remain.' );
		$this->assertNotEmpty( get_post_meta( $publish_post_id, '_edacp', true ), 'Publish _edacp meta should remain.' );

		// Clean up.
		wp_delete_post( $draft_post_id, true );
		wp_delete_post( $publish_post_id, true );
	}

	/**
	 * Test that delete_status_posts() is a no-op when given an empty string.
	 */
	public function testDeleteStatusPostsWithEmptyStringDoesNothing() {
		global $wpdb;
		$table_name  = $wpdb->prefix . 'accessibility_checker';
		$rows_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- caching not required in tests.

		Purge_Post_Data::delete_status_posts( '' );

		$rows_after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- caching not required in tests.
		$this->assertEquals( $rows_before, $rows_after, 'No rows should be deleted for an empty status.' );
	}
}
