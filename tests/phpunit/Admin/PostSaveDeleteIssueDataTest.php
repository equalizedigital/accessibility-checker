<?php
/**
 * Tests for the issue data purge that runs when a post is trashed.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Post_Save;

/**
 * Tests for Post_Save::delete_issue_data_on_post_trashing().
 *
 * @covers \EDAC\Admin\Post_Save::delete_issue_data_on_post_trashing
 */
class PostSaveDeleteIssueDataTest extends WP_UnitTestCase {

	/**
	 * The post the issue data fixtures are attached to.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Creates the issues table and a post that carries issue data.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'accessibility_checker';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				postid mediumint(9) NOT NULL,
				siteid mediumint(9) NOT NULL,
				type varchar(50) NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;"
		);

		$_POST = [];

		update_option( 'edac_post_types', [ 'post' ] );

		$this->post_id = $this->factory()->post->create();

		update_post_meta( $this->post_id, '_edac_rule', 'issue data' );
		update_post_meta( $this->post_id, '_edacp_rule', 'pro issue data' );
		update_post_meta( $this->post_id, 'unrelated_meta', 'not ours' );
	}

	/**
	 * Drops the issues table and clears globals changed by a test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'accessibility_checker' ); // phpcs:ignore WordPress.DB -- Test cleanup only.

		$_POST = [];

		delete_option( 'edac_post_types' );

		parent::tearDown();
	}

	/**
	 * Tests issue data is purged when a scannable post is trashed.
	 *
	 * @return void
	 */
	public function test_issue_data_is_deleted_when_a_scannable_post_is_trashed(): void {
		$_POST = [ 'action' => 'editpost' ];

		Post_Save::delete_issue_data_on_post_trashing(
			$this->post_id,
			$this->post_with_status( 'trash' ),
			true
		);

		$this->assertSame( '', get_post_meta( $this->post_id, '_edac_rule', true ) );
		$this->assertSame( '', get_post_meta( $this->post_id, '_edacp_rule', true ) );
		$this->assertSame( 'not ours', get_post_meta( $this->post_id, 'unrelated_meta', true ) );
	}

	/**
	 * Tests issue data is kept when the post type is not scannable.
	 *
	 * @return void
	 */
	public function test_issue_data_is_kept_when_the_post_type_is_not_scannable(): void {
		$_POST = [ 'action' => 'editpost' ];

		Post_Save::delete_issue_data_on_post_trashing(
			$this->post_id,
			$this->post_with_status( 'trash', 'page' ),
			true
		);

		$this->assertSame( 'issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
	}

	/**
	 * Tests issue data is kept when no post types are scannable.
	 *
	 * @return void
	 */
	public function test_issue_data_is_kept_when_no_post_types_are_scannable(): void {
		delete_option( 'edac_post_types' );

		$_POST = [ 'action' => 'editpost' ];

		Post_Save::delete_issue_data_on_post_trashing(
			$this->post_id,
			$this->post_with_status( 'trash' ),
			true
		);

		$this->assertSame( 'issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
	}

	/**
	 * Tests issue data is kept for saves that are not form submissions.
	 *
	 * WordPress fires save_post for programmatic saves with an empty $_POST.
	 *
	 * @return void
	 */
	public function test_issue_data_is_kept_when_the_request_has_no_post_data(): void {
		$_POST = [];

		Post_Save::delete_issue_data_on_post_trashing(
			$this->post_id,
			$this->post_with_status( 'trash' ),
			true
		);

		$this->assertSame( 'issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
	}

	/**
	 * Tests issue data is kept when the post is not an update.
	 *
	 * @return void
	 */
	public function test_issue_data_is_kept_when_the_post_is_not_an_update(): void {
		$_POST = [ 'action' => 'editpost' ];

		Post_Save::delete_issue_data_on_post_trashing(
			$this->post_id,
			$this->post_with_status( 'trash' ),
			false
		);

		$this->assertSame( 'issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
	}

	/**
	 * Tests issue data is kept when the post is not (being) trashed.
	 *
	 * @return void
	 */
	public function test_issue_data_is_kept_when_the_post_is_not_being_trashed(): void {
		$_POST = [ 'action' => 'editpost' ];

		Post_Save::delete_issue_data_on_post_trashing(
			$this->post_id,
			$this->post_with_status( 'publish' ),
			true
		);

		$this->assertSame( 'issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
	}

	/**
	 * Tests issue data is kept for revisions.
	 *
	 * The parent post's issue data must survive: a revision never gets its own
	 * issue rows, so purging them for a revision would delete the wrong data.
	 *
	 * @return void
	 */
	public function test_issue_data_is_kept_for_a_revision(): void {
		$revision_id = $this->create_revision( $this->post_id . '-revision-v1' );

		$_POST = [ 'action' => 'editpost' ];

		Post_Save::delete_issue_data_on_post_trashing(
			$revision_id,
			$this->post_with_status( 'trash' ),
			true
		);

		$this->assertSame( $this->post_id, wp_is_post_revision( $revision_id ) );
		$this->assertSame( 'issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
	}

	/**
	 * Tests issue data is kept for autosave revisions.
	 *
	 * Autosaves are stored as revisions, so wp_is_post_revision() in the
	 * revision guard is what returns early here - the autosave guard below it
	 * is unreachable. Asserted anyway so a reordering of the guards cannot
	 * start purging autosave data unnoticed.
	 *
	 * @return void
	 */
	public function test_issue_data_is_kept_for_an_autosave_revision(): void {
		$autosave_id = $this->create_revision( $this->post_id . '-autosave-v1' );

		$_POST = [ 'action' => 'editpost' ];

		Post_Save::delete_issue_data_on_post_trashing(
			$autosave_id,
			$this->post_with_status( 'trash' ),
			true
		);

		$this->assertSame( $this->post_id, wp_is_post_autosave( $autosave_id ) );
		$this->assertSame( 'issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
	}

	/**
	 * Builds a post object with the given type and status.
	 *
	 * A fresh object is built instead of changing the stored post so that the
	 * status of the real post - and the post cache - is left alone.
	 *
	 * @param string $status The post status to report.
	 * @param string $type   The post type to report.
	 * @return WP_Post
	 */
	private function post_with_status( string $status, string $type = 'post' ): WP_Post {
		return new WP_Post(
			(object) [
				'ID'          => $this->post_id,
				'post_type'   => $type,
				'post_status' => $status,
			]
		);
	}

	/**
	 * Creates a revision of the fixture post.
	 *
	 * @param string $post_name The revision name, which is what makes it an autosave.
	 * @return int The revision post ID.
	 */
	private function create_revision( string $post_name ): int {
		return $this->factory()->post->create(
			[
				'post_type'   => 'revision',
				'post_parent' => $this->post_id,
				'post_name'   => $post_name,
			]
		);
	}
}
