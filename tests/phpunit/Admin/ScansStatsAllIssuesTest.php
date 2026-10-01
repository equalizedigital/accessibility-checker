<?php
/**
 * Tests for the per-issue-type breakdown in Scans_Stats.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Scans_Stats;

/**
 * Class ScansStatsAllIssuesTest
 *
 * @group scans-stats
 */
class ScansStatsAllIssuesTest extends WP_UnitTestCase {

	/**
	 * Name of the issues table.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Create an issues table to test against.
	 */
	public function setUp(): void {
		global $wpdb;
		parent::setUp();

		$this->table = $wpdb->prefix . 'accessibility_checker';

		// phpcs:disable WordPress.DB -- Test-only table setup.
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table}" );
		$wpdb->query(
			"CREATE TABLE {$this->table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				siteid bigint(20) NOT NULL,
				rule text NOT NULL,
				ruletype text NOT NULL,
				object mediumtext NOT NULL,
				ignre mediumint(9) NOT NULL DEFAULT 0,
				ignre_global mediumint(9) NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
			)"
		);
		// phpcs:enable WordPress.DB
	}

	/**
	 * Drop the test table.
	 */
	public function tearDown(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB -- Test-only table teardown.
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table}" );
		parent::tearDown();
	}

	/**
	 * Insert an issue row.
	 *
	 * @param string $rule         Rule slug.
	 * @param string $markup       Object markup.
	 * @param array  $overrides    Column overrides.
	 * @return void
	 */
	private function add_issue( string $rule, string $markup = '<a>', array $overrides = [] ): void {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB -- Test data.
			$this->table,
			array_merge(
				[
					'postid'       => 1,
					'siteid'       => get_current_blog_id(),
					'rule'         => $rule,
					'ruletype'     => 'error',
					'object'       => $markup,
					'ignre'        => 0,
					'ignre_global' => 0,
				],
				$overrides
			)
		);
	}

	/**
	 * Call the private breakdown method.
	 *
	 * @return array
	 */
	private function get_all(): array {
		$method = new ReflectionMethod( Scans_Stats::class, 'get_all_issues_found_on_site' );
		$method->setAccessible( true );
		return $method->invoke( new Scans_Stats( 0 ) );
	}

	/**
	 * Index a breakdown by rule slug.
	 *
	 * @param array $rows Breakdown rows.
	 * @return array
	 */
	private function by_slug( array $rows ): array {
		return array_column( $rows, null, 'rule_slug' );
	}

	/**
	 * Empty table gives an empty breakdown.
	 */
	public function test_returns_empty_array_without_issues() {
		$this->assertSame( [], $this->get_all() );
	}

	/**
	 * More than ten rule types are all returned.
	 */
	public function test_returns_more_than_ten_rule_types() {
		for ( $i = 1; $i <= 14; $i++ ) {
			$this->add_issue( 'rule_' . $i );
		}

		$this->assertCount( 14, $this->get_all() );
	}

	/**
	 * Ignored and globally ignored rows are excluded.
	 */
	public function test_excludes_ignored_issues() {
		$this->add_issue( 'active_rule' );
		$this->add_issue( 'active_rule', '<b>', [ 'ignre' => 1 ] );
		$this->add_issue( 'active_rule', '<i>', [ 'ignre_global' => 1 ] );
		$this->add_issue( 'only_ignored', '<a>', [ 'ignre' => 1 ] );

		$rows = $this->by_slug( $this->get_all() );

		$this->assertArrayNotHasKey( 'only_ignored', $rows );
		$this->assertSame( 1, $rows['active_rule']['issue_count'] );
	}

	/**
	 * Issues from other sites are excluded.
	 */
	public function test_excludes_other_sites() {
		$this->add_issue( 'mine' );
		$this->add_issue( 'mine', '<a>', [ 'siteid' => get_current_blog_id() + 100 ] );
		$this->add_issue( 'theirs', '<a>', [ 'siteid' => get_current_blog_id() + 100 ] );

		$rows = $this->by_slug( $this->get_all() );

		$this->assertArrayNotHasKey( 'theirs', $rows );
		$this->assertSame( 1, $rows['mine']['issue_count'] );
	}

	/**
	 * Distinct count counts distinct objects, not posts.
	 */
	public function test_distinct_count_counts_distinct_objects() {
		// Same post, same object twice, plus a different object on another post.
		$this->add_issue( 'dupe_rule', '<a>', [ 'postid' => 1 ] );
		$this->add_issue( 'dupe_rule', '<a>', [ 'postid' => 1 ] );
		$this->add_issue( 'dupe_rule', '<b>', [ 'postid' => 2 ] );

		$row = $this->by_slug( $this->get_all() )['dupe_rule'];

		$this->assertSame( 3, $row['issue_count'] );
		$this->assertSame( 2, $row['distinct_count'] );
	}

	/**
	 * Results are ordered by severity, issue count, then slug.
	 */
	public function test_orders_by_severity_then_count_then_slug() {
		$this->add_issue( 'b_rule' );
		$this->add_issue( 'a_rule' );
		$this->add_issue( 'c_rule' );
		$this->add_issue( 'c_rule' );

		$slugs = array_column( $this->get_all(), 'rule_slug' );

		$this->assertSame( [ 'c_rule', 'a_rule', 'b_rule' ], $slugs );
	}

	/**
	 * Summary exposes the full breakdown and a capped top 10 derived from it.
	 */
	public function test_summary_payload_includes_full_and_top_ten() {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		for ( $i = 1; $i <= 12; $i++ ) {
			$this->add_issue( 'rule_' . $i, '<a>', [ 'postid' => $post_id ] );
		}

		$summary = ( new Scans_Stats( 0 ) )->summary( true );

		$this->assertCount( 12, $summary['all_issues_found_on_site'] );
		$this->assertCount( 10, $summary['top_issues_found_on_site'] );
		$this->assertArrayNotHasKey( 'distinct_count', $summary['top_issues_found_on_site'][0] );
		$this->assertArrayHasKey( 'distinct_count', $summary['all_issues_found_on_site'][0] );
	}
}
