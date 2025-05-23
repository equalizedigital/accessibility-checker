<?php

namespace EqualizeDigital\AccessibilityChecker\Tests\phpunit\includes\classes\Rest;

use EqualizeDigital\AccessibilityChecker\Rest\Issues_API;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_Error;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;

// Ensure the class we are testing is loaded
require_once dirname( __FILE__, 6 ) . '/includes/classes/Rest/IssuesAPI.php';
require_once dirname(__FILE__, 6) . '/includes/helper-functions.php';


class IssuesAPITest extends WP_UnitTestCase {

	/**
	 * @var Issues_API
	 */
	protected $issues_api;

	/**
	 * @var \wpdb|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $wpdb_mock;

	/**
	 * @var string
	 */
	protected $table_name;

	/**
	 * @var int
	 */
	protected $current_site_id;

	/**
	 * @var \wpdb|null
	 */
	protected $backup_wpdb;

	public function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->backup_wpdb = $wpdb; // Backup the original (potentially test-framework-provided) $wpdb

		// Mock global $wpdb
		$this->wpdb_mock = $this->getMockBuilder( \wpdb::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_results', 'prepare', 'query', 'get_var', 'esc_like' ] ) // Added 'esc_like'
			->getMock();
		$GLOBALS['wpdb'] = $this->wpdb_mock;

		// General expectation for esc_like, used by esc_sql
		$this->wpdb_mock->expects($this->any())
			->method('esc_like')
			->willReturnCallback(function($text) {
				// Simple passthrough for testing purposes.
				// WordPress's esc_like is more complex: addcslashes($text, '_%\\');
				// If tests require actual LIKE pattern escaping, this might need adjustment.
				return str_replace(['%', '_'], ['\\%', '\\_'], $text);
			});

		$this->current_site_id = get_current_blog_id(); // Or mock if needed: 1;
		$this->table_name      = edac_get_valid_table_name( $this->wpdb_mock->prefix . 'accessibility_checker' );

		$this->issues_api = new Issues_API();

		// Reflection to set the table name in Issues_API if it's not set via constructor or other means
		// In the actual Issues_API, table_name is set in constructor using global $wpdb->prefix
		// So, we need to ensure our mock $wpdb has a prefix or set it manually if problems arise.
		$this->wpdb_mock->prefix = 'wp_'; // Standard WordPress prefix

		$raw_table_name_for_show_tables = $this->wpdb_mock->prefix . 'accessibility_checker';

		// Expectation for the prepare call in edac_get_valid_table_name
		$this->wpdb_mock->expects($this->exactly(2))
			->method('prepare')
			->with(
				$this->equalTo('SHOW TABLES LIKE %s'), // Changed to equalTo for direct match
				$raw_table_name_for_show_tables
			)
			->willReturn("SHOW TABLES LIKE '{$raw_table_name_for_show_tables}'");

		// Expectation for the get_var call in edac_get_valid_table_name
		$this->wpdb_mock->expects($this->exactly(2)) // Changed from any()
			->method('get_var')
			->with("SHOW TABLES LIKE '{$raw_table_name_for_show_tables}'") // Should match what prepare returns
			->willReturn($raw_table_name_for_show_tables); // Return the table name to indicate it exists


		// For 'timezone_string' option query often called by WordPress date functions
		$timezone_sql_identifier = 'SELECT option_value FROM'; // Used to identify this specific prepare call
		$prepared_timezone_query_string = "PREPARED_SQL_FOR_TIMEZONE"; // Placeholder

		$this->wpdb_mock->expects($this->any())
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($timezone_sql_identifier) {
					return strpos(trim($sql), $timezone_sql_identifier) === 0 && strpos($sql, "option_name = %s") !== false;
				}),
				'timezone_string'
			)
			->willReturn($prepared_timezone_query_string);

		$this->wpdb_mock->expects($this->any())
			->method('get_var')
			->with($prepared_timezone_query_string)
			->willReturn('UTC'); // Provide a default timezone value

		$this->table_name = edac_get_valid_table_name( $this->wpdb_mock->prefix . 'accessibility_checker' );

		$reflection = new \ReflectionClass( $this->issues_api );
		// $table_name_prop = $reflection->getProperty( 'table_name' );
		// $table_name_prop->setAccessible( true );
		// $table_name_prop->setValue( $this->issues_api, $this->table_name );

		$query_options_prop = $reflection->getProperty( 'query_options' );
		$query_options_prop->setAccessible(true);
		$query_options_prop->setValue($this->issues_api, ['siteid' => $this->current_site_id, 'limit' => 500, 'offset' => 0]);


		// Mock get_current_blog_id() if it's directly used by the class and not passed.
		// For now, assuming query_options['siteid'] is correctly set via constructor or test setup.

		// It's good practice to install the schema if your tests interact with the actual DB
		// For this specific case, we are mocking $wpdb, but if we were to do real DB interaction:
		// DatabaseHelpers::install_database_schema();
	}

	public function tearDown(): void {
		// DatabaseHelpers::remove_database_schema(); // If we used real DB
		$GLOBALS['wpdb'] = $this->backup_wpdb;
		$this->backup_wpdb = null; // Optional: clear the backup property
		parent::tearDown();
	}

	protected function _create_dummy_issue( array $data = [] ) {
		global $wpdb;
		$wpdb = $this->wpdb_mock; // Ensure global $wpdb is our mock for this helper too

		$defaults = [
			'id'            => rand( 1, 1000 ),
			'postid'        => 1,
			'siteid'        => $this->current_site_id,
			'type'          => 'error',
			'rule'          => 'test_rule',
			'ruletype'      => 'WCAG2AA',
			'object'        => '<button>Test</button>',
			'recordcheck'   => 0,
			'created'       => current_time( 'mysql' ),
			'user'          => 1,
			'ignre'         => 0,
			'ignre_global'  => 0,
			'ignre_user'    => null,
			'ignre_date'    => null,
			'ignre_comment' => null,
		];
		$issue_data = array_merge( $defaults, $data );

		// If we were using DatabaseHelpers for real DB:
		// return DatabaseHelpers::insert_issue($issue_data);

		return (object) $issue_data; // Return as object to mimic $wpdb->get_results
	}

	/**
	 * @test
	 */
	public function test_update_issue_success() {
		$issue_id = 123;
		$original_issue = $this->_create_dummy_issue( [
			'id'            => $issue_id,
			'rule'          => 'old_rule',
			'ignre_comment' => 'old_comment',
			'ignre'         => 0,
		] );

		$updated_fields = [
			'rule'          => 'new_rule',
			'ignre_comment' => 'new_comment',
			'ignre'         => 1,
		];

		$final_issue_data = array_merge( (array) $original_issue, $updated_fields );

		// ---- Mocks for the FIRST call to do_issues_query (existence check) ----
		$expected_sql_count_1 = "SQL_COUNT_EXISTENCE_ID_{$issue_id}"; // Placeholder for prepared COUNT query
		$this->wpdb_mock->expects($this->at(0)) // Order specific for prepare calls
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false;
				}),
				$this->current_site_id
			)
			->willReturn($expected_sql_count_1);

		$expected_sql_select_1 = "SQL_SELECT_EXISTENCE_ID_{$issue_id}"; // Placeholder for prepared SELECT query
		$this->wpdb_mock->expects($this->at(1)) // Second call to prepare
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && strpos($sql, "LIMIT %d OFFSET %d") !== false;
				}),
				$this->current_site_id,
				$issue_id,
				500, // limit from setUp
				0    // offset from setUp
			)
			->willReturn($expected_sql_select_1);

		// ---- Mocks for the UPDATE query ----
		$expected_sql_update = "SQL_UPDATE_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(2)) // Third call to prepare
			->method('prepare')
			->with(
				$this->callback(function($sql) { return strpos(trim($sql), 'UPDATE') === 0; }),
				$updated_fields['rule'], 
				(int)$updated_fields['ignre'],
				$updated_fields['ignre_comment'],
				$issue_id
			)
			->willReturn($expected_sql_update);

		// ---- Mocks for the SECOND call to do_issues_query (fetch updated) ----
		$expected_sql_count_2 = "SQL_COUNT_FETCH_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(3)) // Fourth call to prepare
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) { // Same SQL structure as first COUNT
					return strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false;
				}),
				$this->current_site_id
			)
			->willReturn($expected_sql_count_2);

		$expected_sql_select_2 = "SQL_SELECT_FETCH_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(4)) // Fifth call to prepare
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) { // Same SQL structure as first SELECT
					return strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && strpos($sql, "LIMIT %d OFFSET %d") !== false;
				}),
				$this->current_site_id,
				$issue_id,
				500, // limit
				0    // offset
			)
			->willReturn($expected_sql_select_2);

		// ---- Mocks for get_var (for count_all_issues calls) ----
		$this->wpdb_mock->expects($this->exactly(2)) // Two calls to get_var for the two count_all_issues
			->method('get_var')
			->withConsecutive(
				[$expected_sql_count_1],
				[$expected_sql_count_2]
			)
			->willReturn(1); // Both times, issue is found (count = 1)

		// ---- Mocks for get_results (for do_issues_query calls) ----
		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_results')
			->withConsecutive(
				[$expected_sql_select_1],
				[$expected_sql_select_2]
			)
			->willReturnOnConsecutiveCalls(
				[$original_issue],
				[(object)$final_issue_data]
			);

		// ---- Mock for the $wpdb->query (for the UPDATE) ----
		$this->wpdb_mock->expects($this->once())
			->method('query')
			->with($expected_sql_update) // Match the prepared UPDATE query
			->willReturn(1);

		$request = new WP_REST_Request( 'PUT', "/accessibility-checker/v1/issues/{$issue_id}" );
		$request->set_url_params( [ 'id' => $issue_id ] );
		$request->set_body_params( $updated_fields );

		// Mock methods used by prepare_item_for_response if they cause issues
		// For example, if get_the_title, get_permalink, get_user_by, get_the_author_meta are problematic
		// For now, let's assume they work or are not critical for this specific test's assertions on updated fields.

		$response = $this->issues_api->update_issue( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( $updated_fields['rule'], $data['rule'] );
		$this->assertEquals( $updated_fields['ignre_comment'], $data['ignre_comment'] );
		$this->assertEquals( (bool)$updated_fields['ignre'], $data['ignre'] ); // Cast to bool for comparison
	}

	/**
	 * @test
	 */
	public function test_update_issue_not_found() {
		$non_existent_id = 999;

		// Order of execution by update_issue -> do_issues_query -> count_all_issues:
		// 1. count_all_issues: prepare (SELECT COUNT)
		// 2. count_all_issues: get_var
		// 3. do_issues_query: prepare (SELECT *)
		// 4. do_issues_query: get_results

		// 1. Expectation for the prepare call in count_all_issues
		$this->wpdb_mock->expects($this->once())
			->method('prepare')
			->with(
				$this->stringContains("SELECT COUNT(*) FROM `{$this->table_name}` WHERE siteid = %d AND id IN ({$non_existent_id})"),
				$this->current_site_id
			)
			->willReturn("PREPARED_COUNT_QUERY_FOR_NOT_FOUND");

		// 2. Expectation for the get_var call in count_all_issues
		$this->wpdb_mock->expects($this->once())
			->method('get_var')
			->with("PREPARED_COUNT_QUERY_FOR_NOT_FOUND")
			->willReturn(0); // Issue count is 0

		// 3. Expectation for the prepare call in do_issues_query's SELECT *
		// This is the second prepare call overall in this code path.
		// We need to ensure PHPUnit can distinguish it or use at() if they were identical.
		// Since the SQL strings are different, direct with() should be fine.
		$this->wpdb_mock->expects($this->once())
			->method('prepare')
			->with(
				$this->stringContains("SELECT * FROM `{$this->table_name}` WHERE siteid = %d AND id IN (%d) ORDER BY id DESC LIMIT %d OFFSET %d"),
				$this->current_site_id,
				$non_existent_id,
				500, // from setUp's query_options for Issues_API instance
				0    // from setUp's query_options for Issues_API instance
			)
			->willReturn("PREPARED_SELECT_QUERY_FOR_NOT_FOUND");

		// 4. Expectation for the get_results call in do_issues_query
		$this->wpdb_mock->expects( $this->once() )
			->method( 'get_results' )
			->with("PREPARED_SELECT_QUERY_FOR_NOT_FOUND")
			->willReturn( [] ); // Issue not found

		$request = new WP_REST_Request( 'PUT', "/accessibility-checker/v1/issues/{$non_existent_id}" );
		$request->set_url_params( [ 'id' => $non_existent_id ] );
		$request->set_body_params( [ 'rule' => 'new_rule' ] ); // Some body to make it a valid update attempt

		$response = $this->issues_api->update_issue( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'rest_issue_invalid_id', $response->get_error_code() );
		$this->assertEquals( 404, $response->get_error_data()['status'] );
	}

	/**
	 * @test
	 */
	public function test_update_issue_no_fields_provided() {
		$issue_id = 124;
		$original_issue = $this->_create_dummy_issue( [ 'id' => $issue_id ] );

		// 1. Mock `prepare` for `count_all_issues` (`SELECT COUNT`)
		$expected_sql_count = "SQL_COUNT_NO_FIELDS_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(0)) // First 'prepare' call specific to this test's logic
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false;
				}),
				$this->current_site_id
			)
			->willReturn($expected_sql_count);

		// 2. Mock `get_var` for `count_all_issues`
		$this->wpdb_mock->expects($this->once()) // This is the only get_var call in this specific code path for the test
			->method('get_var')
			->with($expected_sql_count) // Match the specific prepared COUNT query
			->willReturn(1); // Issue exists

		// 3. Mock `prepare` for `do_issues_query` (`SELECT *`)
		$expected_sql_select = "SQL_SELECT_NO_FIELDS_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(1)) // Second 'prepare' call specific to this test's logic
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && strpos($sql, "LIMIT %d OFFSET %d") !== false;
				}),
				$this->current_site_id,
				$issue_id,
				500, // limit from setUp
				0    // offset from setUp
			)
			->willReturn($expected_sql_select);
		
		// 4. Adjust `get_results` mock
		// The first call to get_results is for do_issues_query (existence check)
		// The second call to get_results is for options loading by $request->get_params() (WP Core behavior)
		$this->wpdb_mock->expects( $this->exactly(2) )
			->method( 'get_results' )
			->withConsecutive(
				[$expected_sql_select], // Match specific prepared SELECT query for issue existence
				[$this->stringContains('options')] // Matcher for the options query string
			)
			->willReturnOnConsecutiveCalls(
				[ $original_issue ], // Return for issue existence check
				[] // Return for options query
			);

		$request = new WP_REST_Request( 'PUT', "/accessibility-checker/v1/issues/{$issue_id}" );
		$request->set_url_params( [ 'id' => $issue_id ] );
		$request->set_body_params( [] ); // Empty body

		$response = $this->issues_api->update_issue( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'rest_nothing_to_update', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}


	/**
	 * @test
	 */
	public function test_update_issue_cannot_update_protected_fields() {
		$issue_id = 125;
		$original_siteid = $this->current_site_id; // Should not change
		$original_created = '2023-01-01 10:00:00'; // Should not change

		$original_issue = $this->_create_dummy_issue( [
			'id'      => $issue_id,
			'rule'    => 'old_rule',
			'siteid'  => $original_siteid,
			'created' => $original_created,
		] );

		$attempted_updates = [
			'rule'    => 'new_rule_for_protected_test', // This should be updated
			'siteid'  => $original_siteid + 1, // Attempt to change siteid
			'created' => '2024-01-01 12:00:00', // Attempt to change created date
		];

		// This is what the data should look like after the update call.
		// rule is updated, but siteid and created are NOT.
		$final_issue_data = (array) $original_issue;
		$final_issue_data['rule'] = $attempted_updates['rule'];


		// Mock do_issues_query (existence check and fetch after update)
		// ---- Mocks for the FIRST call to do_issues_query (existence check) ----
		$expected_sql_count_1_protected = "SQL_COUNT_EXISTENCE_PROTECTED_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(0))
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false;
				}),
				$this->current_site_id
			)
			->willReturn($expected_sql_count_1_protected);

		$expected_sql_select_1_protected = "SQL_SELECT_EXISTENCE_PROTECTED_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(1))
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && strpos($sql, "LIMIT %d OFFSET %d") !== false;
				}),
				$this->current_site_id,
				$issue_id,
				500, // limit
				0    // offset
			)
			->willReturn($expected_sql_select_1_protected);

		// ---- Mocks for the UPDATE query (only 'rule' should be updated) ----
		$expected_sql_update_protected = "SQL_UPDATE_PROTECTED_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(2))
			->method('prepare')
			->with(
				$this->callback(function($sql) { 
					return strpos(trim($sql), 'UPDATE') === 0 && strpos($sql, '`rule` = %s') !== false && strpos($sql, 'siteid') === false && strpos($sql, 'created') === false; 
				}),
				$attempted_updates['rule'], // Only rule is updated
				$issue_id
			)
			->willReturn($expected_sql_update_protected);

		// ---- Mocks for the SECOND call to do_issues_query (fetch updated) ----
		$expected_sql_count_2_protected = "SQL_COUNT_FETCH_PROTECTED_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(3))
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false;
				}),
				$this->current_site_id
			)
			->willReturn($expected_sql_count_2_protected);

		$expected_sql_select_2_protected = "SQL_SELECT_FETCH_PROTECTED_ID_{$issue_id}";
		$this->wpdb_mock->expects($this->at(4))
			->method('prepare')
			->with(
				$this->callback(function($sql) use ($issue_id) {
					return strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && strpos($sql, "LIMIT %d OFFSET %d") !== false;
				}),
				$this->current_site_id,
				$issue_id,
				500, // limit
				0    // offset
			)
			->willReturn($expected_sql_select_2_protected);

		// ---- Mocks for get_var ----
		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_var')
			->withConsecutive(
				[$expected_sql_count_1_protected],
				[$expected_sql_count_2_protected]
			)
			->willReturn(1);

		// ---- Mocks for get_results ----
		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_results')
			->withConsecutive(
				[$expected_sql_select_1_protected],
				[$expected_sql_select_2_protected]
			)
			->willReturnOnConsecutiveCalls(
				[$original_issue], // Before update
				[(object)$final_issue_data]  // After update (only rule changed)
			);
		
		// ---- Mock for the $wpdb->query (for the UPDATE) ----
		$this->wpdb_mock->expects($this->once())
			->method('query')
			->with($expected_sql_update_protected)
			->willReturn(1);

		$request = new WP_REST_Request( 'PUT', "/accessibility-checker/v1/issues/{$issue_id}" );
		$request->set_url_params( [ 'id' => $issue_id ] );
		$request->set_body_params( $attempted_updates );

		$response = $this->issues_api->update_issue( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( $attempted_updates['rule'], $data['rule'] ); // Rule should be updated
		$this->assertEquals( $original_siteid, $data['siteid'] );       // siteid should NOT be updated
		$this->assertEquals( $original_created, $data['created'] );     // created should NOT be updated
	}

}
?>
