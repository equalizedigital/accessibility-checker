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
}
