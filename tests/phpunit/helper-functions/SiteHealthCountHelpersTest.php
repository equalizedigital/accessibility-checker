<?php
/**
 * Tests for the count helpers used by the Site Health debug information.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_get_posts_count(), edac_get_error_count(),
 * edac_get_warning_count() and edac_database_table_count().
 *
 * @covers ::edac_get_posts_count
 * @covers ::edac_get_error_count
 * @covers ::edac_get_warning_count
 * @covers ::edac_database_table_count
 */
class SiteHealthCountHelpersTest extends WP_UnitTestCase {

	/**
	 * The plugin's issues table.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Creates an empty issues table and clears the install's own posts.
	 *
	 * The test install ships with a post and a page and both count helpers
	 * report absolute numbers, so the fixtures start from nothing. The
	 * per-test transaction puts the install content back afterwards.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wpdb;

		$this->table_name = $wpdb->prefix . 'accessibility_checker';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $this->table_name ); // phpcs:ignore WordPress.DB -- Test-owned table.

		$charset_collate = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$this->table_name} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				postid mediumint(9) NOT NULL,
				siteid mediumint(9) NOT NULL,
				type varchar(50) NOT NULL,
				ruletype varchar(50) NOT NULL,
				PRIMARY KEY  (id)
			) {$charset_collate};"
		);

		$post_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test fixture cleanup.
			$wpdb->prepare( 'SELECT ID FROM %i WHERE post_type IN ( %s, %s )', $wpdb->posts, 'post', 'page' )
		);
		foreach ( $post_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}

		update_option( 'edac_post_types', [ 'post', 'page' ] );

		wp_cache_flush();
	}

	/**
	 * Drops the issues table and the option the tests change.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $this->table_name ); // phpcs:ignore WordPress.DB -- Test cleanup only.

		delete_option( 'edac_post_types' );

		parent::tearDown();
	}

	/**
	 * Inserts an issue row into the issues table.
	 *
	 * @param string   $rule_type The rule type to store.
	 * @param int|null $site_id   The site the row belongs to, defaults to this site.
	 *
	 * @return void
	 */
	private function insert_issue( string $rule_type, ?int $site_id = null ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture on a test-owned table.
			$this->table_name,
			[
				'postid'   => 1,
				'siteid'   => $site_id ?? get_current_blog_id(),
				'type'     => 'post',
				'ruletype' => $rule_type,
			]
		);
	}

	/**
	 * Tests the post count is false when no post type is scannable.
	 *
	 * @return void
	 */
	public function test_posts_count_is_false_without_scannable_post_types(): void {
		delete_option( 'edac_post_types' );

		$this->assertFalse( edac_get_posts_count() );
	}

	/**
	 * Tests the post count is false when the scannable post types hold no posts.
	 *
	 * @return void
	 */
	public function test_posts_count_is_false_when_there_are_no_posts(): void {
		$this->assertFalse( edac_get_posts_count() );
	}

	/**
	 * Tests each post type is reported with the statuses that hold posts.
	 *
	 * @return void
	 */
	public function test_posts_count_reports_post_types_with_their_statuses(): void {
		$this->factory()->post->create_many( 2, [ 'post_status' => 'publish' ] );
		$this->factory()->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'draft',
			]
		);

		$this->assertSame( 'post: publish = 2, page: draft = 1', edac_get_posts_count() );
	}

	/**
	 * Tests a post type without posts is left out of the count.
	 *
	 * @return void
	 */
	public function test_posts_count_skips_post_types_without_posts(): void {
		$this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->assertSame( 'post: publish = 1', edac_get_posts_count() );
	}

	/**
	 * Tests both issue counts are zero when the table holds no matching rows.
	 *
	 * @return void
	 */
	public function test_issue_counts_are_zero_on_an_empty_table(): void {
		$this->assertSame( 0, edac_get_error_count() );
		$this->assertSame( 0, edac_get_warning_count() );
	}

	/**
	 * Tests each count only includes rows of its own rule type and site.
	 *
	 * @return void
	 */
	public function test_issue_counts_only_matching_rule_type_and_site(): void {
		$this->insert_issue( 'error' );
		$this->insert_issue( 'error' );
		$this->insert_issue( 'warning' );
		$this->insert_issue( 'warning' );
		$this->insert_issue( 'warning' );
		$this->insert_issue( 'error', get_current_blog_id() + 1 );
		$this->insert_issue( 'warning', get_current_blog_id() + 1 );

		$this->assertSame( 2, edac_get_error_count() );
		$this->assertSame( 3, edac_get_warning_count() );
	}

	/**
	 * Tests the error count is cached and only re-queried once it is cleared.
	 *
	 * @return void
	 */
	public function test_error_count_is_cached_until_the_cache_is_cleared(): void {
		$this->insert_issue( 'error' );

		$this->assertSame( 1, edac_get_error_count() );
		$this->assertSame( 1, wp_cache_get( 'edac_errors_' . get_current_blog_id() ) );

		// The stored count is used, so the added row is not seen yet.
		$this->insert_issue( 'error' );
		$this->assertSame( 1, edac_get_error_count() );

		wp_cache_flush();

		$this->assertSame( 2, edac_get_error_count() );
	}

	/**
	 * Tests the two counts read their own cache entries.
	 *
	 * @return void
	 */
	public function test_issue_counts_use_separate_cache_keys(): void {
		wp_cache_set( 'edac_errors_' . get_current_blog_id(), 7 );
		wp_cache_set( 'edac_warnings_' . get_current_blog_id(), 0 );

		$this->assertSame( 7, edac_get_error_count() );
		$this->assertSame( 0, edac_get_warning_count() );
	}

	/**
	 * Tests the table count is the number of rows in the named table.
	 *
	 * @return void
	 */
	public function test_database_table_count_returns_the_row_count(): void {
		// An empty table returns a string zero rather than false, so it is cached too.
		$this->assertSame( 0, (int) edac_database_table_count( 'accessibility_checker' ) );

		$this->insert_issue( 'error' );
		$this->insert_issue( 'warning' );

		// The stored count is used until the cache is cleared.
		wp_cache_flush();

		// $wpdb->get_var() returns a string for COUNT(*), so cast before asserting.
		$this->assertSame( 2, (int) edac_database_table_count( 'accessibility_checker' ) );
	}

	/**
	 * Tests the count is cached under the table name it was asked for.
	 *
	 * @return void
	 */
	public function test_database_table_count_is_cached_per_table_name(): void {
		$this->insert_issue( 'error' );

		wp_cache_set( 'edac_table_count_accessibility_checker', '9' );
		// An entry for a different table must not be used for this one.
		wp_cache_set( 'edac_table_count_some_other_table', '5' );

		$this->assertSame( 9, (int) edac_database_table_count( 'accessibility_checker' ) );

		wp_cache_flush();

		$this->assertSame( 1, (int) edac_database_table_count( 'accessibility_checker' ) );
	}
}
