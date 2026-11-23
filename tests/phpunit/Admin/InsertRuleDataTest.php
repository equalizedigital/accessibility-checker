<?php
/**
 * Test file for InsertRuleData
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Insert_Rule_Data;

/**
 * Test class for InsertRuleData
 */
class InsertRuleDataTest extends WP_UnitTestCase {

	/**
	 * Table name for the accessibility checker.
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * Create table to test against.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'accessibility_checker';

		// Use the Update_Database class to create/update the table schema.
		require_once dirname( __DIR__, 3 ) . '/admin/class-update-database.php';
		$update_db = new \EDAC\Admin\Update_Database();
		$update_db->edac_update_database();
	}

	/**
	 * Cleans up the table after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS $this->table_name" ); // phpcs:ignore WordPress.DB -- Table name is safe and not caching in a test.
		parent::tearDown();
	}

	/**
	 * Tests the insert method would return expected data types.
	 */
	public function testRuleInserterReturnLogic() {
		$post     = $this->factory()->post->create_and_get();
		$rule     = 'rule';
		$ruletype = 'ruletype';
		$rule_obj = 'rule_obj';

		global $wpdb;

		$rule_inserter     = new Insert_Rule_Data();
		$initial_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- caching not required for one time operation.

		// call should return int as a successful insert.
		$new_data = $rule_inserter->insert( $post, $rule, $ruletype, $rule_obj );
		$this->assertIsInt( $new_data );
		// second call is a duplicate and should return null.
		$duplicate_data = $rule_inserter->insert( $post, $rule, $ruletype, $rule_obj );
		$this->assertEquals( null, $duplicate_data );

		// check if the row count has increased by 1.
		$current_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- caching not required for one time operation.
		$this->assertEquals( $initial_row_count + 1, $current_row_count );

		// should return null as ruletype is 'revision'.
		$revision_type_return = $rule_inserter->insert( $post, $rule, 'revision', $rule_obj );
		$this->assertEquals( null, $revision_type_return );

		// should throw an exception because of missing parameters.
		$this->expectException( TypeError::class );
		$rule_inserter->insert(); // phpcs:ignore -- intentionally passing something that will cause an exception.

		// check that row count has not increased since last check.
		$current_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- caching not required for one time operation.
		$this->assertEquals( $initial_row_count + 1, $current_row_count );
	}

	/**
	 * Tests that duplicate objects with different selectors are stored separately.
	 * This test verifies the fix for the issue where duplicate code objects
	 * (like two empty paragraphs) were only flagged as one issue.
	 */
	public function testDuplicateObjectsWithDifferentSelectors() {
		$post     = $this->factory()->post->create_and_get();
		$rule     = 'empty_paragraph_tag';
		$ruletype = 'warning';
		$rule_obj = '<p></p>';

		global $wpdb;

		$rule_inserter     = new Insert_Rule_Data();
		$initial_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- caching not required for one time operation.

		// Insert first empty paragraph with selector 1.
		$selectors_1 = [
			'selector' => [ 'div.content > p:nth-child(1)' ],
			'ancestry' => [ 'div.content', 'p' ],
			'xpath'    => [ '/html/body/div/p[1]' ],
		];
		$result_1    = $rule_inserter->insert( $post, $rule, $ruletype, $rule_obj, null, null, $selectors_1 );
		$this->assertIsInt( $result_1 );

		// Insert second empty paragraph with different selector - should be stored as separate issue.
		$selectors_2 = [
			'selector' => [ 'div.content > p:nth-child(5)' ],
			'ancestry' => [ 'div.content', 'p' ],
			'xpath'    => [ '/html/body/div/p[5]' ],
		];
		$result_2    = $rule_inserter->insert( $post, $rule, $ruletype, $rule_obj, null, null, $selectors_2 );
		$this->assertIsInt( $result_2 );

		// Verify two separate records were created.
		$current_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- caching not required for one time operation.
		$this->assertEquals( $initial_row_count + 2, $current_row_count );

		// Verify inserting the same object with same selector is treated as duplicate.
		$result_3 = $rule_inserter->insert( $post, $rule, $ruletype, $rule_obj, null, null, $selectors_1 );
		$this->assertEquals( null, $result_3 );

		// Verify row count hasn't changed.
		$final_row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- caching not required for one time operation.
		$this->assertEquals( $initial_row_count + 2, $final_row_count );
	}

	/**
	 * Tests that a missing post id/type or an empty rule, rule type or object writes nothing.
	 *
	 * @covers \EDAC\Admin\Insert_Rule_Data::insert
	 */
	public function test_insert_returns_null_when_required_arguments_are_missing() {
		$post     = $this->factory()->post->create_and_get();
		$inserter = new Insert_Rule_Data();

		global $wpdb;
		// The table is shared with the rest of the suite, so assert the count does not move
		// rather than assuming it starts empty (the neighbouring cases take the same baseline).
		$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.

		$this->assertNull( $inserter->insert( $post, '', 'error', 'object' ), 'An empty rule should not insert.' );
		$this->assertNull( $inserter->insert( $post, 'rule', '', 'object' ), 'An empty rule type should not insert.' );
		$this->assertNull( $inserter->insert( $post, 'rule', 'error', '' ), 'An empty object should not insert.' );

		// A post-like object without a post_type property.
		$this->assertNull( $inserter->insert( (object) [ 'ID' => 123 ], 'rule', 'error', 'object' ), 'A post without a post_type should not insert.' );

		$this->assertEquals( $before, (int) $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ), 'A rejected insert should not write a row.' ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.
	}

	/**
	 * Tests that data for a revision post type is never stored.
	 *
	 * Revisions are stored as posts with the `revision` post type, so the guard that skips
	 * them is keyed on the post type rather than the rule type that was passed in.
	 *
	 * @covers \EDAC\Admin\Insert_Rule_Data::insert
	 */
	public function test_insert_returns_null_for_a_revision_post() {
		$post_id  = $this->factory()->post->create();
		$revision = wp_save_post_revision( $post_id );
		$this->assertIsInt( $revision, 'The fixture did not create a revision.' );

		$revision_post = get_post( $revision );
		$this->assertEquals( 'revision', $revision_post->post_type, 'The fixture post is not a revision.' );

		global $wpdb;
		// Baseline against the shared table rather than assuming it is empty.
		$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.

		$inserter = new Insert_Rule_Data();
		$this->assertNull( $inserter->insert( $revision_post, 'rule', 'error', 'object' ), 'A revision should not insert.' );

		$this->assertEquals( $before, (int) $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ), 'A revision should not write a row.' ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.
	}

	/**
	 * Tests that re-inserting a row the user has ignored keeps the ignored state.
	 *
	 * A later scan re-inserts every issue it finds, including ones already ignored; doing so
	 * must not silently un-ignore the issue and put it back in front of the user.
	 *
	 * @covers \EDAC\Admin\Insert_Rule_Data::insert
	 */
	public function test_insert_preserves_an_ignored_row() {
		$post      = $this->factory()->post->create_and_get();
		$inserter  = new Insert_Rule_Data();
		$rule      = 'rig_ignored_rule';
		$selectors = [ 'selector' => [ '#target' ] ];

		global $wpdb;
		$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.

		$this->assertIsInt( $inserter->insert( $post, $rule, 'error', 'object', null, null, $selectors ) );

		// Simulate the issue being ignored by a user.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Direct write to a test fixture row.
			$this->table_name,
			[ 'ignre' => 1 ],
			[
				'postid' => $post->ID,
				'rule'   => $rule,
			]
		);
		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( 'SELECT ignre FROM %i WHERE postid = %d AND rule = %s', $this->table_name, $post->ID, $rule ) ), 'The fixture row should be ignored.' ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.

		// The next scan finds the same issue again and re-inserts it.
		$this->assertNull( $inserter->insert( $post, $rule, 'error', 'object', null, null, $selectors ), 'A duplicate issue should update rather than insert.' );

		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( 'SELECT ignre FROM %i WHERE postid = %d AND rule = %s', $this->table_name, $post->ID, $rule ) ), 'Re-inserting an ignored issue should keep it ignored.' ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.
		$this->assertEquals( $before + 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_name" ), 'A duplicate issue should update the existing row rather than add one.' ); // phpcs:ignore WordPress.DB -- caching is not required for a one time operation.
	}

	/**
	 * Tests that an ignore comment supplied through the insert filter is sanitised.
	 *
	 * The comment is filterable, so its markup is restricted to the allowed tags and then
	 * escaped before it reaches the database.
	 *
	 * @covers \EDAC\Admin\Insert_Rule_Data::insert
	 */
	public function test_insert_sanitises_a_filtered_ignore_comment() {
		$post     = $this->factory()->post->create_and_get();
		$inserter = new Insert_Rule_Data();

		$add_comment = static function ( $rule_data ) {
			$rule_data['ignre_comment'] = '<strong>Why it was ignored</strong><script>alert(1)</script>';
			return $rule_data;
		};
		add_filter( 'edac_filter_insert_rule_data', $add_comment );

		$this->assertIsInt( $inserter->insert( $post, 'rule', 'error', 'object' ) );

		remove_filter( 'edac_filter_insert_rule_data', $add_comment );

		global $wpdb;
		$stored = $wpdb->get_var( $wpdb->prepare( 'SELECT ignre_comment FROM %i WHERE postid = %d', $this->table_name, $post->ID ) ); // phpcs:ignore WordPress.DB -- Table name is safe and caching is not required for a one time operation.

		$this->assertStringContainsString( 'Why it was ignored', $stored, 'The comment text should be kept.' );
		$this->assertStringContainsString( '&lt;strong&gt;', $stored, 'An allowed tag should be stored escaped.' );
		$this->assertStringNotContainsString( '<strong>', $stored, 'An allowed tag should not be stored as live markup.' );
		$this->assertStringNotContainsString( '<script', $stored, 'A disallowed tag should be stripped.' );
	}
}
