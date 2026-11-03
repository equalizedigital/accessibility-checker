<?php
/**
 * Tests for the issues query builder.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Issues_Query;

/**
 * Tests for \EDAC\Admin\Issues_Query::get_sql() and ::get_query().
 *
 * The SQL is asserted as the whole clause list rather than by running it, so the
 * tests need no rows: the only database dependency is that the issues table exists,
 * because the constructor resolves the table name through edac_get_valid_table_name().
 *
 * @covers \EDAC\Admin\Issues_Query::__construct
 * @covers \EDAC\Admin\Issues_Query::get_sql
 * @covers \EDAC\Admin\Issues_Query::get_query
 * @covers \EDAC\Admin\Issues_Query::count
 * @covers \EDAC\Admin\Issues_Query::distinct_count
 * @covers \EDAC\Admin\Issues_Query::distinct_posts_count
 * @covers \EDAC\Admin\Issues_Query::get_ids
 */
class IssuesQueryTest extends WP_UnitTestCase {

	/**
	 * The issues table the queries are built against.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Creates the issues table and clears the post type setting.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->table_name = $wpdb->prefix . 'accessibility_checker';

		// Use the Update_Database class to create/update the table schema.
		require_once dirname( __DIR__, 3 ) . '/admin/class-update-database.php';
		$update_database = new \EDAC\Admin\Update_Database();
		$update_database->edac_update_database();

		delete_option( 'edac_post_types' );
	}

	/**
	 * Tests the query a bare instance builds.
	 *
	 * @return void
	 */
	public function test_default_query_selects_issues_that_are_not_ignored(): void {
		$query = new Issues_Query();

		$this->assertSame(
			'select count(*) FROM ' . $this->table_name . ' WHERE siteid=' . get_current_blog_id() . ' and ignre=0 and ignre_global=0 LIMIT 100000',
			$this->normalized_sql( $query )
		);
	}

	/**
	 * Tests that no post type filter is applied when nothing is scannable.
	 *
	 * The comment above the guard in admin/class-issues-query.php says the query should
	 * be forced to return nothing with ' and 1!=1' in this case. It is not: the guard is
	 * `false === ( $flags & self::FLAG_INCLUDE_ALL_POST_TYPES )` and a bitwise AND
	 * returns an int, so the strict comparison can never be true and the clause is
	 * unreachable. This asserts what the class does today, not what the comment intends.
	 *
	 * @return void
	 */
	public function test_no_scannable_post_types_does_not_force_an_empty_result(): void {
		$query = new Issues_Query();

		$this->assertStringNotContainsString( '1!=1', $this->normalized_sql( $query ) );
	}

	/**
	 * Tests the where clause built for each ignore flag.
	 *
	 * @dataProvider provider_ignore_flags
	 *
	 * @param int    $flags The flag passed to the query.
	 * @param string $expected_where The expected where clause.
	 *
	 * @return void
	 */
	public function test_ignore_flags_build_the_where_clause( int $flags, string $expected_where ): void {
		$query = new Issues_Query( [], 100000, $flags );

		$this->assertSame( $expected_where, trim( $query->get_query()['where_base'] ) );
	}

	/**
	 * Provides the ignore flags and the where clause each one produces.
	 *
	 * @return array<string, array<int, int|string>>
	 */
	public function provider_ignore_flags(): array {
		$site_id = get_current_blog_id();

		return [
			'default excludes ignored'  => [
				Issues_Query::FLAG_EXCLUDE_IGNORED,
				"WHERE siteid={$site_id} and ignre=0 and ignre_global=0",
			],
			'ignore the ignore columns' => [
				Issues_Query::FLAG_INCLUDE_IGNORED,
				"WHERE siteid={$site_id}",
			],
			'only ignored issues'       => [
				Issues_Query::FLAG_ONLY_IGNORED,
				"WHERE siteid={$site_id} and (ignre=1 or ignre_global=1)",
			],
		];
	}

	/**
	 * Tests that the include-all-post-types flag removes the post type filter entirely.
	 *
	 * @return void
	 */
	public function test_include_all_post_types_flag_drops_the_post_type_filter(): void {
		update_option( 'edac_post_types', [ 'post' ] );
		$query = new Issues_Query( [ 'post_types' => [ 'post' ] ], 100000, Issues_Query::FLAG_INCLUDE_ALL_POST_TYPES );

		$this->assertStringNotContainsString( 'type IN', $this->normalized_sql( $query ) );
	}

	/**
	 * Tests that a requested post type the settings do not scan is left out of the clause.
	 *
	 * @return void
	 */
	public function test_post_types_filter_is_limited_to_the_scannable_post_types(): void {
		update_option( 'edac_post_types', [ 'post', 'page' ] );
		$query = new Issues_Query( [ 'post_types' => [ 'post', 'page', 'attachment' ] ] );
		$sql   = $this->normalized_sql( $query );

		$this->assertStringContainsString( "and type IN ('post','page')", $sql );
		$this->assertStringNotContainsString( 'attachment', $sql );
	}

	/**
	 * Tests that a filter which matches no scannable post type adds no type clause.
	 *
	 * @return void
	 */
	public function test_post_types_filter_that_matches_nothing_scannable_adds_no_type_clause(): void {
		update_option( 'edac_post_types', [ 'post' ] );
		$query = new Issues_Query( [ 'post_types' => [ 'attachment' ] ] );

		$this->assertStringNotContainsString( 'type IN', $this->normalized_sql( $query ) );
	}

	/**
	 * Tests that the filter keys are matched exactly, not case-insensitively.
	 *
	 * @return void
	 */
	public function test_unknown_filter_keys_are_dropped(): void {
		update_option( 'edac_post_types', [ 'post', 'page' ] );
		$query = new Issues_Query( [ 'Post_Types' => [ 'post', 'page' ] ] );

		$this->assertStringNotContainsString( 'type IN', $this->normalized_sql( $query ) );
	}

	/**
	 * Tests the clauses built from the rule filters.
	 *
	 * @dataProvider provider_rule_filters
	 *
	 * @param array  $filter The filter passed to the query.
	 * @param string $expected_fragment The clause expected in the sql.
	 *
	 * @return void
	 */
	public function test_rule_filters_add_the_expected_clauses( array $filter, string $expected_fragment ): void {
		$query = new Issues_Query( $filter );

		$this->assertStringContainsString( $expected_fragment, $this->normalized_sql( $query ) );
	}

	/**
	 * Provides the rule filters and the clause each one produces.
	 *
	 * @return array<string, array<int, array<string, string[]>>|string>
	 */
	public function provider_rule_filters(): array {
		return [
			'rule types'                        => [
				[ 'rule_types' => [ 'error', 'warning' ] ],
				"and ruletype IN ('error','warning')",
			],
			'rule slugs'                        => [
				[ 'rule_slugs' => [ 'color_contrast', 'missing_headings' ] ],
				"and rule IN ('color_contrast','missing_headings')",
			],
			'color contrast is a rule'          => [
				[ 'rule_types' => [ 'color_contrast' ] ],
				"and rule IN ('color_contrast_failure')",
			],
			'color contrast beside a rule type' => [
				[ 'rule_types' => [ 'error', 'color_contrast' ] ],
				"and ruletype IN ('error') and rule IN ('color_contrast_failure')",
			],
		];
	}

	/**
	 * Tests that color_contrast is not used as a rule type in the clause.
	 *
	 * @return void
	 */
	public function test_color_contrast_rule_type_adds_no_rule_type_clause(): void {
		$query = new Issues_Query( [ 'rule_types' => [ 'color_contrast' ] ] );

		$this->assertStringNotContainsString( 'ruletype IN', $this->normalized_sql( $query ) );
	}

	/**
	 * Tests that an already-present color contrast slug is appended a second time.
	 *
	 * The add_filters() method looks the slug up with array_search() and adds it with
	 * `if ( ! $key )`. array_search() returns 0 for the first element, so a slug that is
	 * already the first entry is treated as missing. A duplicate in the IN list does not
	 * change the rows returned, so this is documented rather than fixed here.
	 *
	 * @return void
	 */
	public function test_color_contrast_rule_slug_at_the_first_index_is_duplicated(): void {
		$query = new Issues_Query(
			[
				'rule_types' => [ 'color_contrast' ],
				'rule_slugs' => [ 'color_contrast_failure' ],
			]
		);

		$this->assertStringContainsString(
			"and rule IN ('color_contrast_failure','color_contrast_failure')",
			$this->normalized_sql( $query )
		);
	}

	/**
	 * Tests that the record limit argument becomes the limit clause.
	 *
	 * @return void
	 */
	public function test_record_limit_sets_the_limit_clause(): void {
		$query = new Issues_Query( [], 5 );

		$this->assertSame( 'LIMIT 5', $query->get_query()['limit'] );
	}

	/**
	 * Tests the query parts returned for a query that has not been run.
	 *
	 * @return void
	 */
	public function test_get_query_returns_the_query_parts(): void {
		$query = new Issues_Query();
		$parts = $query->get_query();

		$this->assertSame( [ 'select', 'from', 'where_base', 'filters', 'limit' ], array_keys( $parts ) );
		$this->assertSame( 'select count(*)', $parts['select'] );
		$this->assertSame( ' FROM ' . $this->table_name . ' ', $parts['from'] );
	}

	/**
	 * Tests that each query method replaces the select clause it will run.
	 *
	 * @return void
	 */
	public function test_query_methods_replace_the_select_clause(): void {
		$query = new Issues_Query();
		$query->count();
		$this->assertSame( 'SELECT COUNT(id) ', $query->get_query()['select'] );

		$query = new Issues_Query();
		$query->distinct_count();
		$this->assertSame( 'SELECT COUNT( DISTINCT rule, object ) ', $query->get_query()['select'] );

		$query = new Issues_Query();
		$query->distinct_posts_count();
		$this->assertSame( 'SELECT COUNT( DISTINCT postid ) ', $query->get_query()['select'] );

		$query = new Issues_Query();
		$query->get_ids();
		$this->assertSame( 'SELECT id', $query->get_query()['select'] );
	}

	/**
	 * The sql a query builds, with the clause-joining whitespace collapsed.
	 *
	 * Each clause is added with its own leading and trailing space, so two adjacent
	 * clauses are separated by two spaces in the raw string.
	 *
	 * @param Issues_Query $query The query to read.
	 * @return string
	 */
	private function normalized_sql( Issues_Query $query ): string {
		return trim( preg_replace( '/\s+/', ' ', $query->get_sql() ) );
	}
}
