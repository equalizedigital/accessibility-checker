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
class PurgePostDataTest extends TestCase {

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

		$this->valid_post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test Content',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			)
		);

		// Insert data into the 'accessibility_checker' table.
		$wpdb->insert( // phpcs:ignore WordPress.DB -- this is just one-time use data for testing.
			$table_name,
			array(
				'postid' => $this->valid_post_id,
				'siteid' => 1,
			),
			array(
				'%d',
				'%d',
			)
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
		$post_meta = array(
			'_edac'  => 'value',
			'_edacp' => 'value',
			'other'  => 'value',
		);
		foreach ( $post_meta as $key => $value ) {
			update_post_meta( $this->valid_post_id, $key, $value );
		}

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
		add_action( 'wp_trash_post', array( Purge_Post_Data::class, 'delete_post' ) );

		wp_trash_post( $this->valid_post_id );

		// Check that the row no longer exists after trashing the post.
		$row_exists_after = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE postid = %d", $this->valid_post_id ) ); // phpcs:ignore WordPress.DB -- Safe variable used for table name.
		$this->assertEquals( 0, $row_exists_after );
	}
}
