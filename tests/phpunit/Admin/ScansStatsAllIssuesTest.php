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
	 * Get the slugs of the registered rules with a given severity.
	 *
	 * @param int $severity Severity, 1 (critical) to 4 (low).
	 * @return string[]
	 */
	private function slugs_with_severity( int $severity ): array {
		$slugs = [];
		foreach ( edac_register_rules() as $rule ) {
			if ( isset( $rule['slug'], $rule['severity'] ) && $severity === (int) $rule['severity'] ) {
				$slugs[] = $rule['slug'];
			}
		}
		sort( $slugs );
		return $slugs;
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
	 * Rules of the same severity are ordered by issue count, then slug.
	 */
	public function test_orders_rules_of_the_same_severity_by_count_then_slug() {
		$medium = $this->slugs_with_severity( 3 );

		$this->add_issue( $medium[1] );
		$this->add_issue( $medium[0] );
		$this->add_issue( $medium[2] );
		$this->add_issue( $medium[2] );

		$slugs = array_column( $this->get_all(), 'rule_slug' );

		$this->assertSame( [ $medium[2], $medium[0], $medium[1] ], $slugs );
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

	/**
	 * Severity 1 is critical and 4 is low, so the most severe rules come first and
	 * rules without a known severity come last.
	 */
	public function test_orders_most_severe_rules_first() {
		$critical = $this->slugs_with_severity( 1 )[0];
		$high     = $this->slugs_with_severity( 2 )[0];
		$medium   = $this->slugs_with_severity( 3 )[0];
		$low      = $this->slugs_with_severity( 4 )[0];

		$this->add_issue( 'unregistered_rule' );
		$this->add_issue( $low );
		$this->add_issue( $medium );
		$this->add_issue( $high );
		$this->add_issue( $critical );

		$slugs = array_column( $this->get_all(), 'rule_slug' );

		$this->assertSame( [ $critical, $high, $medium, $low, 'unregistered_rule' ], $slugs );
	}

	/**
	 * Critical rules stay in the top ten when more than ten lower severity rules have issues.
	 */
	public function test_top_ten_keeps_critical_rules_when_lower_severity_rules_fill_the_list() {
		$critical = $this->slugs_with_severity( 1 )[0];
		$lower    = array_merge( $this->slugs_with_severity( 3 ), $this->slugs_with_severity( 4 ) );
		$this->assertGreaterThan( 10, count( $lower ) );

		foreach ( $lower as $slug ) {
			$this->add_issue( $slug );
			$this->add_issue( $slug, '<b>' );
		}
		$this->add_issue( $critical );

		$summary = ( new Scans_Stats( 0 ) )->summary( true );

		$this->assertCount( 10, $summary['top_issues_found_on_site'] );
		$this->assertSame( $critical, $summary['top_issues_found_on_site'][0]['rule_slug'] );
		$this->assertSame(
			array_slice( array_column( $summary['all_issues_found_on_site'], 'rule_slug' ), 0, 10 ),
			array_column( $summary['top_issues_found_on_site'], 'rule_slug' )
		);
	}
}
