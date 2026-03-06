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
	 * Secondary table used for caching regression test.
	 *
	 * @var string
	 */
	private $alt_table;

	/**
	 * Sets up tables for testing.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$main_table      = $wpdb->prefix . 'accessibility_checker';
		$this->alt_table = $wpdb->posts;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$db_schema = "CREATE TABLE %s (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( sprintf( $db_schema, $main_table ) );
	}

	/**
	 * Ensures the helper validates different tables without returning cached results.
	 */
	public function test_returns_requested_table_name_each_time() {
		global $wpdb;

		$main_table = $wpdb->prefix . 'accessibility_checker';

		$this->assertSame( $main_table, edac_get_valid_table_name( $main_table ) );
		$this->assertSame( $this->alt_table, edac_get_valid_table_name( $this->alt_table ) );
	}
}
