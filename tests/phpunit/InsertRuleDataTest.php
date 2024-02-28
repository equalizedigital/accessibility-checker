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
	 * Create table to test against.
	 *
	 * @return void
	 */
	public function setUp(): void {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'accessibility_checker';
		$charset_collate  = $wpdb->get_charset_collate();
		$sql              = "CREATE TABLE $this->table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				siteid text NOT NULL,
				type text NOT NULL,
				rule text NOT NULL,
				ruletype text NOT NULL,
				object mediumtext NOT NULL,
				recordcheck mediumint(9) NOT NULL,
				created timestamp NOT NULL default CURRENT_TIMESTAMP,
				user bigint(20) NOT NULL,
				ignre mediumint(9) NOT NULL,
				ignre_global mediumint(9) NOT NULL,
				ignre_user bigint(20) NULL,
				ignre_date timestamp NULL,
				ignre_comment mediumtext NULL,
				UNIQUE KEY id (id),
				KEY postid_index (postid)
			) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Cleans up the table after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS $this->table_name" ); // phpcs:ignore WordPress.DB -- Table name is safe and not caching in a test.
	}

	/**
	 * Tests the insert method would return expected data types.
	 */
	public function testRuleInserterReturnLogic() {
		$post     = $this->factory()->post->create_and_get();
		$rule     = 'rule';
		$ruletype = 'ruletype';
		$rule_obj = 'rule_obj';

		sleep( 2 );
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
}
