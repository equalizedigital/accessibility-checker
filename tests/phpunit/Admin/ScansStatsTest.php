<?php
/**
 * Tests for Scans_Stats top pages, post type summaries and caching.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Scans_Stats;

/**
 * Class ScansStatsTest
 *
 * @group scans-stats
 */
class ScansStatsTest extends WP_UnitTestCase {

	/**
	 * Name of the issues table.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Create the issues table once, outside any test transaction.
	 *
	 * The stats queries self-join this table, which MySQL disallows on the
	 * temporary tables WP_UnitTestCase would otherwise create. A real table is
	 * used and emptied before each test instead.
	 */
	public static function set_up_before_class(): void {
		global $wpdb;
		parent::set_up_before_class();

		$table = $wpdb->prefix . 'accessibility_checker';

		// phpcs:disable WordPress.DB -- Test-only table setup.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		$wpdb->query(
			"CREATE TABLE {$table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				siteid bigint(20) NOT NULL,
				type varchar(20) NOT NULL DEFAULT 'post',
				ruletype text NOT NULL,
				rule text NOT NULL,
				object mediumtext NOT NULL,
				ignre mediumint(9) NOT NULL DEFAULT 0,
				ignre_global mediumint(9) NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
			)"
		);
		// phpcs:enable WordPress.DB
	}

	/**
	 * Drop the issues table.
	 */
	public static function tear_down_after_class(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB -- Test-only table teardown.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}accessibility_checker" );
		parent::tear_down_after_class();
	}

	/**
	 * Start every test with an empty issues table and default options.
	 */
	public function setUp(): void {
		global $wpdb;
		parent::setUp();

		$this->table = $wpdb->prefix . 'accessibility_checker';

		// DELETE rather than TRUNCATE so no implicit commit breaks test rollback.
		$wpdb->query( "DELETE FROM {$this->table}" ); // phpcs:ignore WordPress.DB -- Test-only cleanup.

		update_option( 'edac_post_types', [ 'post', 'page' ] );
		delete_option( 'edacp_fullscan_completed_at' );
		( new Scans_Stats() )->clear_cache();
	}

	/**
	 * Clear caches.
	 */
	public function tearDown(): void {
		( new Scans_Stats() )->clear_cache();
		parent::tearDown();
	}

	/**
	 * Insert issue rows.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $count   Number of rows.
	 * @param string $type    Rule type.
	 * @param array  $columns Column overrides.
	 * @return void
	 */
	private function add_issues( int $post_id, int $count = 1, string $type = 'error', array $columns = [] ): void {
		global $wpdb;
		// Posts count as scanned once they have an issue density meta value.
		update_post_meta( $post_id, '_edac_issue_density', 1 );
		$post_type = get_post_type( $post_id );
		$rule      = 'color_contrast' === $type ? 'color_contrast_failure' : 'rule_' . $type;
		$type      = 'color_contrast' === $type ? 'error' : $type;

		for ( $i = 0; $i < $count; $i++ ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB -- Test data.
				$this->table,
				array_merge(
					[
						'postid'       => $post_id,
						'siteid'       => get_current_blog_id(),
						'rule'         => $rule,
						'type'         => $post_type,
						'ruletype'     => $type,
						'object'       => '<a ' . wp_generate_password( 8, false ) . '>',
						'ignre'        => 0,
						'ignre_global' => 0,
					],
					$columns
				)
			);
		}
	}

	/**
	 * Call the private top pages method.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	private function top_pages( int $limit = 5 ): array {
		$method = new ReflectionMethod( Scans_Stats::class, 'get_top_pages_with_issues' );
		$method->setAccessible( true );
		return $method->invoke( new Scans_Stats( 0 ), $limit );
	}

	/**
	 * Top pages are ordered by issue count and capped at the limit.
	 */
	public function test_top_pages_ordered_by_count_and_limited() {
		$few  = self::factory()->post->create( [ 'post_title' => 'Few' ] );
		$many = self::factory()->post->create( [ 'post_title' => 'Many' ] );
		$mid  = self::factory()->post->create( [ 'post_title' => 'Mid' ] );
		$this->add_issues( $few, 1 );
		$this->add_issues( $many, 5 );
		$this->add_issues( $mid, 3 );

		$pages = $this->top_pages( 2 );

		$this->assertSame( [ 'Many', 'Mid' ], array_column( $pages, 'post_title' ) );
		$this->assertSame( [ 5, 3 ], array_column( $pages, 'issue_count' ) );
		$this->assertSame( get_permalink( $many ), $pages[0]['post_url'] );
	}

	/**
	 * A limit of zero returns nothing.
	 */
	public function test_top_pages_zero_limit_returns_empty() {
		$this->add_issues( self::factory()->post->create() );

		$this->assertSame( [], $this->top_pages( 0 ) );
	}

	/**
	 * Ignored issues, other sites, other post types and unpublished posts don't count.
	 */
	public function test_top_pages_excludes_ignored_foreign_and_unscannable() {
		$kept = self::factory()->post->create( [ 'post_title' => 'Kept' ] );
		$this->add_issues( $kept, 1 );
		$this->add_issues( $kept, 1, 'error', [ 'ignre' => 1 ] );
		$this->add_issues( $kept, 1, 'error', [ 'ignre_global' => 1 ] );
		$this->add_issues( $kept, 1, 'error', [ 'siteid' => get_current_blog_id() + 100 ] );

		$this->add_issues( self::factory()->post->create( [ 'post_type' => 'attachment' ] ), 4 );
		$this->add_issues( self::factory()->post->create( [ 'post_status' => 'trash' ] ), 4 );

		$pages = $this->top_pages();

		$this->assertCount( 1, $pages );
		$this->assertSame( 'Kept', $pages[0]['post_title'] );
		$this->assertSame( 1, $pages[0]['issue_count'] );
	}

	/**
	 * Post type summary splits errors, warnings and contrast.
	 */
	public function test_issues_summary_by_post_type_splits_rule_types() {
		$post_id = self::factory()->post->create();
		$this->add_issues( $post_id, 3, 'error' );
		$this->add_issues( $post_id, 2, 'warning' );
		$this->add_issues( $post_id, 1, 'color_contrast' );
		$this->add_issues( $post_id, 1, 'error', [ 'ignre' => 1 ] );

		$summary = ( new Scans_Stats( 0 ) )->issues_summary_by_post_type( 'post' );

		$this->assertSame( 2, (int) $summary['warnings'] );
		$this->assertSame( 4, (int) $summary['errors'] );
		$this->assertSame( 1, (int) $summary['contrast_errors'] );
		$this->assertSame( 3, $summary['errors_without_contrast'] );
		$this->assertFalse( $summary['cache_hit'] );
	}

	/**
	 * Post type summaries are scoped to the requested post type.
	 */
	public function test_issues_summary_by_post_type_ignores_other_post_types() {
		$this->add_issues( self::factory()->post->create( [ 'post_type' => 'page' ] ), 4, 'warning' );

		$summary = ( new Scans_Stats( 0 ) )->issues_summary_by_post_type( 'post' );

		$this->assertSame( 0, (int) $summary['warnings'] );
	}

	/**
	 * Post type summaries are served from cache on the second call.
	 */
	public function test_issues_summary_by_post_type_uses_cache() {
		$this->add_issues( self::factory()->post->create(), 1, 'warning' );
		$stats = new Scans_Stats( 3600 );

		$first  = $stats->issues_summary_by_post_type( 'post' );
		$second = $stats->issues_summary_by_post_type( 'post' );

		$this->assertFalse( $first['cache_hit'] );
		$this->assertTrue( $second['cache_hit'] );
	}

	/**
	 * Summary is cached, and skip_cache bypasses it.
	 */
	public function test_summary_caches_and_skip_cache_bypasses() {
		$this->add_issues( self::factory()->post->create(), 2 );
		$stats = new Scans_Stats( 3600 );

		$first  = $stats->summary();
		$second = $stats->summary();
		$third  = $stats->summary( true );

		$this->assertFalse( $first['cache_hit'] );
		$this->assertTrue( $second['cache_hit'] );
		$this->assertFalse( $third['cache_hit'] );
	}

	/**
	 * A completed full scan newer than the cache invalidates it.
	 */
	public function test_summary_cache_invalidated_by_newer_full_scan() {
		$this->add_issues( self::factory()->post->create() );
		$stats = new Scans_Stats( 3600 );
		$stats->summary();

		update_option( 'edacp_fullscan_completed_at', time() + 60 );

		$this->assertFalse( $stats->summary()['cache_hit'] );
	}

	/**
	 * Nothing is cached while no posts have been scanned.
	 */
	public function test_summary_not_cached_without_scanned_posts() {
		$stats = new Scans_Stats( 3600 );

		$stats->summary();

		$this->assertFalse( $stats->summary()['cache_hit'] );
	}

	/**
	 * Clear cache removes cached summary and post type stats.
	 */
	public function test_clear_cache_removes_cached_stats() {
		$this->add_issues( self::factory()->post->create() );
		$stats = new Scans_Stats( 3600 );
		$stats->summary();
		$stats->issues_summary_by_post_type( 'post' );

		$stats->clear_cache();

		$this->assertFalse( $stats->summary()['cache_hit'] );
		$this->assertFalse( $stats->issues_summary_by_post_type( 'post' )['cache_hit'] );
	}

	/**
	 * Load cache warms the summary and scannable post types.
	 */
	public function test_load_cache_warms_summary_and_post_types() {
		$this->add_issues( self::factory()->post->create() );
		$stats = new Scans_Stats( 3600 );

		$stats->load_cache();

		$this->assertTrue( $stats->summary()['cache_hit'] );
		$this->assertTrue( $stats->issues_summary_by_post_type( 'post' )['cache_hit'] );
		$this->assertTrue( $stats->issues_summary_by_post_type( 'page' )['cache_hit'] );
	}
}
