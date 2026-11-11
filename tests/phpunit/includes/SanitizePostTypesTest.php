<?php
/**
 * Tests for the post types sanitizer used by the settings page.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Scans_Stats;

/**
 * Tests for edac_sanitize_post_types().
 *
 * @covers ::edac_sanitize_post_types
 */
class SanitizePostTypesTest extends WP_UnitTestCase {

	/**
	 * A post holding issue data for the `post` post type.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * A page holding issue data for the `page` post type.
	 *
	 * @var int
	 */
	private $page_id;

	/**
	 * Creates the issues table and issue data for a post and a page.
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

		update_option( 'edac_post_types', [ 'post', 'page' ] );

		$this->post_id = $this->factory()->post->create();
		$this->page_id = $this->factory()->post->create( [ 'post_type' => 'page' ] );

		update_post_meta( $this->post_id, '_edac_rule', 'post issue data' );
		update_post_meta( $this->page_id, '_edac_rule', 'page issue data' );

		// Other test classes create this same table and can leave rows behind
		// (FrontendHighlightAjaxTest does), so start from a known-empty table.
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Resetting a test-owned table.
			$wpdb->prepare( 'DELETE FROM %i', $table_name )
		);

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture on a test-owned table.
			$table_name,
			[
				'postid' => $this->post_id,
				'siteid' => get_current_blog_id(),
				'type'   => 'post',
			]
		);
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture on a test-owned table.
			$table_name,
			[
				'postid' => $this->page_id,
				'siteid' => get_current_blog_id(),
				'type'   => 'page',
			]
		);
	}

	/**
	 * Drops the issues table and clears globals changed by a test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'accessibility_checker' ); // phpcs:ignore WordPress.DB -- Test cleanup only.

		delete_option( 'edac_post_types' );

		parent::tearDown();
	}

	/**
	 * Tests unknown post types are dropped and known ones kept.
	 *
	 * @return void
	 */
	public function test_returns_only_known_post_types(): void {
		$this->assertSame(
			[ 'post', 'page' ],
			edac_sanitize_post_types( [ 'post', 'page', 'not_a_post_type' ] )
		);
	}

	/**
	 * Tests the submitted order is preserved.
	 *
	 * @return void
	 */
	public function test_preserves_the_submitted_order(): void {
		$this->assertSame( [ 'page', 'post' ], edac_sanitize_post_types( [ 'page', 'post' ] ) );
	}

	/**
	 * Tests selecting nothing returns nothing.
	 *
	 * @return void
	 */
	public function test_returns_an_empty_array_when_nothing_is_selected(): void {
		$this->assertSame( [], edac_sanitize_post_types( [] ) );
	}

	/**
	 * Tests a selection of only unknown post types returns nothing.
	 *
	 * @return void
	 */
	public function test_returns_an_empty_array_when_only_unknown_types_are_selected(): void {
		$this->assertSame( [], edac_sanitize_post_types( [ 'not_a_post_type' ] ) );
	}

	/**
	 * Tests issue data for deselected post types is purged.
	 *
	 * Only `post` is submitted, so the stored issues and the `_edac` meta of
	 * every `page` should be removed while the post is left alone and the page
	 * itself is not deleted.
	 *
	 * @return void
	 */
	public function test_drops_issue_data_for_post_types_that_are_no_longer_selected(): void {
		$this->assertSame( [ 'post' ], edac_sanitize_post_types( [ 'post' ] ) );

		$this->assertSame( 'post issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
		$this->assertSame( '', get_post_meta( $this->page_id, '_edac_rule', true ) );
		$this->assertInstanceOf( WP_Post::class, get_post( $this->page_id ) );
		$this->assertSame( [ 'post' ], $this->stored_issue_types() );
	}

	/**
	 * Tests issue data survives when the selection is unchanged.
	 *
	 * @return void
	 */
	public function test_keeps_issue_data_when_the_selection_is_unchanged(): void {
		$this->assertSame( [ 'post', 'page' ], edac_sanitize_post_types( [ 'post', 'page' ] ) );

		$this->assertSame( 'post issue data', get_post_meta( $this->post_id, '_edac_rule', true ) );
		$this->assertSame( 'page issue data', get_post_meta( $this->page_id, '_edac_rule', true ) );
		$this->assertSame( [ 'page', 'post' ], $this->stored_issue_types() );
	}

	/**
	 * Tests the cached scan stats are cleared when the selection changes.
	 *
	 * @return void
	 */
	public function test_clears_cached_scan_stats_when_the_selection_changes(): void {
		set_transient( $this->stats_transient_name(), 'cached stats' );

		edac_sanitize_post_types( [ 'post' ] );

		$this->assertFalse( get_transient( $this->stats_transient_name() ) );
	}

	/**
	 * Tests the cached scan stats survive a reordered but identical selection.
	 *
	 * @return void
	 */
	public function test_keeps_cached_scan_stats_when_the_selection_is_unchanged(): void {
		set_transient( $this->stats_transient_name(), 'cached stats' );

		edac_sanitize_post_types( [ 'page', 'post' ] );

		$this->assertSame( 'cached stats', get_transient( $this->stats_transient_name() ) );
	}

	/**
	 * Returns the post types of the issue rows still stored.
	 *
	 * @return string[]
	 */
	private function stored_issue_types(): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'accessibility_checker';

		return $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test-only read of a test-owned table.
			$wpdb->prepare( 'SELECT type FROM %i ORDER BY type ASC', $table_name )
		);
	}

	/**
	 * Returns the transient name Scans_Stats caches its summary under.
	 *
	 * @return string
	 */
	private function stats_transient_name(): string {
		$stats = new Scans_Stats();

		$prefix = new ReflectionProperty( Scans_Stats::class, 'cache_name_prefix' );
		$prefix->setAccessible( true );

		return $prefix->getValue( $stats ) . '_summary';
	}
}
