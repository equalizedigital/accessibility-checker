<?php
/**
 * Class GetValidTableNameTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_get_valid_table_name() function.
 */
class GetValidTableNameTest extends WP_UnitTestCase {

	/**
	 * Tests edac_get_valid_table_name with invalid and valid table names.
	 */
	public function test_edac_get_valid_table_name() {
		global $wpdb;

		$this->assertNull( edac_get_valid_table_name( 'wp_posts; DROP TABLE wp_users' ) );

		$missing_table = $wpdb->prefix . 'edac_missing_table';
		$this->assertNull( edac_get_valid_table_name( $missing_table ) );

		$valid_table = $wpdb->posts;
		$this->assertSame( $valid_table, edac_get_valid_table_name( $valid_table ) );
	}
}
