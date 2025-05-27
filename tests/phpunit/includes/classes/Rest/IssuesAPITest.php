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
		$this->backup_wpdb = $wpdb;

		$this->wpdb_mock = $this->getMockBuilder( \wpdb::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_results', 'prepare', 'query', 'get_var', 'esc_like' ] )
			->getMock();
		$GLOBALS['wpdb'] = $this->wpdb_mock;

		// Set prefix for the mock BEFORE it's used by any function that relies on it.
		$this->wpdb_mock->prefix = 'wp_';

		// General expectation for esc_like
		$this->wpdb_mock->expects($this->any())
			->method('esc_like')
			->willReturnCallback(function($text) {
				return str_replace(['%', '_'], ['\%', '\_'], $text);
			});

		// Define $raw_table_name_for_show_tables based on the mock's prefix
		$raw_table_name_for_show_tables = $this->wpdb_mock->prefix . 'accessibility_checker';

		// ---- Expectations for edac_get_valid_table_name() calls ----
		// These are called twice within this setUp method.
		$this->wpdb_mock->expects($this->exactly(2))
			->method('prepare')
			->with(
				$this->callback(function($sql) { return strpos(trim((string)$sql), 'SHOW TABLES LIKE') === 0 && substr_count((string)$sql, '%s') === 1; }), // Changed to the robust callback
				$raw_table_name_for_show_tables
			)
			->willReturn("SHOW TABLES LIKE '{$raw_table_name_for_show_tables}'");

		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_var')
			->with("SHOW TABLES LIKE '{$raw_table_name_for_show_tables}'")
			->willReturn($raw_table_name_for_show_tables);

		// ---- Expectations for 'timezone_string' option query (common WP side-effect) ----
		$timezone_sql_identifier = 'SELECT option_value FROM';
		$prepared_timezone_query_string = "PREPARED_SQL_FOR_TIMEZONE_SETUP"; // Unique placeholder

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
			->willReturn('UTC');

		// ---- Actual operations that will use the above mocks ----
		$this->current_site_id = get_current_blog_id();

		// First call to edac_get_valid_table_name()
		$this->table_name = edac_get_valid_table_name( $this->wpdb_mock->prefix . 'accessibility_checker' );

		// Instantiate Issues_API, which also calls edac_get_valid_table_name()
		$this->issues_api = new Issues_API();

		// Reflection for query_options (table_name reflection is already commented out)
		$reflection = new \ReflectionClass( $this->issues_api );
		$query_options_prop = $reflection->getProperty( 'query_options' );
		$query_options_prop->setAccessible(true);
		$query_options_prop->setValue($this->issues_api, ['siteid' => $this->current_site_id, 'limit' => 500, 'offset' => 0]);
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

		// General prepare mock for this test
		$this->wpdb_mock->expects($this->any())
			->method('prepare')
			->willReturnCallback(function($sql, ...$args) use ($issue_id) {
    // Specific query matches for test_update_issue_success
    if (strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false) {
        return "PREPARED_COUNT_QUERY_SUCCESS_TEST_{$issue_id}";
    }
    if (strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && isset($args[1]) && $args[1] == $issue_id) {
         return "PREPARED_SELECT_QUERY_SUCCESS_TEST_{$issue_id}";
    }
    // Example for UPDATE, adjust arg checks as per actual $args passed to prepare for UPDATE
    // The original mock for UPDATE in this test was: ->with(CALLBACK, rule, ignre, ignre_comment, id)
    // So, $args[0]=rule, $args[1]=ignre, $args[2]=ignre_comment, $args[3]=id (if no SQL string in $args for this callback)
    // But $args for willReturnCallback on prepare is ($sql, ...placeholder_values)
    // So $args[0] is first placeholder value. For "UPDATE table SET col1=%s, col2=%s WHERE id=%d",
    // $args[0] is val_col1, $args[1] is val_col2, $args[2] is id_val.
    // Assuming the UPDATE query in this test has 4 placeholders (rule, ignre, ignre_comment, issue_id for WHERE)
    if (strpos(trim($sql), 'UPDATE') === 0 && count($args) === 4 && $args[3] == $issue_id) {
        return "PREPARED_UPDATE_QUERY_SUCCESS_TEST_{$issue_id}";
    }

    // Standardized Fallback for other prepare calls
    if (is_string($sql) && !empty($args)) {
        $placeholder_count = substr_count($sql, '%');
        $args_padded = $args;
        if (count($args) < $placeholder_count) {
            $args_padded = array_pad($args, $placeholder_count, null);
        }
        $sql_for_vsprintf = str_replace('%i', '%s', $sql); // Handle %i for table names
        // Suppress errors for vsprintf in case of mismatch, return raw SQL then.
        $prepared_sql = @vsprintf($sql_for_vsprintf, $args_padded);
        return ($prepared_sql === false) ? $sql : $prepared_sql;
    }
    return $sql; // Return original SQL if no args or not a string
});

		// Mock get_var for count_all_issues calls
		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_var')
			->with("PREPARED_COUNT_QUERY_SUCCESS_TEST_{$issue_id}")
			->willReturn(1); // Issue exists

		// Mock get_results for do_issues_query calls
		$get_results_call_count_success = 0;
		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_results')
			->with("PREPARED_SELECT_QUERY_SUCCESS_TEST_{$issue_id}")
			->willReturnCallback(function() use (&$get_results_call_count_success, $original_issue, $final_issue_data) {
				$get_results_call_count_success++;
				if ($get_results_call_count_success === 1) {
					return [$original_issue]; // Data for initial existence check
				}
				return [(object)$final_issue_data]; // Data for fetching updated issue
			});

		// Mock query for the UPDATE statement
		$this->wpdb_mock->expects($this->once())
			->method('query')
			->with("PREPARED_UPDATE_QUERY_SUCCESS_TEST_{$issue_id}")
			->willReturn(1); // 1 row affected

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

		// General prepare mock for this test
		$this->wpdb_mock->expects($this->any())
			->method('prepare')
			->willReturnCallback(function($sql, ...$args) use ($non_existent_id) {
				// Check for COUNT query related to this test
				if (strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$non_existent_id})") !== false) {
					return "PREPARED_COUNT_NOT_FOUND_{$non_existent_id}";
				}
				// Check for SELECT * query related to this test
				if (strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && isset($args[1]) && $args[1] == $non_existent_id) {
					return "PREPARED_SELECT_NOT_FOUND_{$non_existent_id}";
				}
				// Fallback for other prepare calls
				if (is_string($sql) && !empty($args)) {
					// Ensure enough arguments for vsprintf if placeholders exist
					$placeholder_count = substr_count($sql, '%');
					// Only pad if there are fewer args than placeholders. Do not pad if args > placeholders.
					if (count($args) < $placeholder_count) {
						$args_padded = array_pad($args, $placeholder_count, null);
					} else {
						$args_padded = $args;
					}
					// Replace WordPress specific %i (table name) with %s for vsprintf compatibility
					$sql_for_vsprintf = str_replace('%i', '%s', $sql);
					return @vsprintf($sql_for_vsprintf, $args_padded);
				}
				return $sql; // Return original SQL if no args or not a string
			});

		// Mock get_var for count_all_issues
		$this->wpdb_mock->expects($this->once())
			->method('get_var')
			->with("PREPARED_COUNT_NOT_FOUND_{$non_existent_id}")
			->willReturn(0); // Issue count is 0

		// Mock get_results for do_issues_query
		$this->wpdb_mock->expects($this->once())
			->method('get_results')
			->with("PREPARED_SELECT_NOT_FOUND_{$non_existent_id}")
			->willReturn([]); // Issue not found

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

		$this->wpdb_mock->expects($this->any())
			->method('prepare')
			->willReturnCallback(function($sql, ...$args) use ($issue_id) {
				if (strpos(trim($sql), 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false) {
					return "PREPARED_COUNT_NO_FIELDS_TEST_{$issue_id}";
				}
				if (strpos(trim($sql), 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && isset($args[1]) && $args[1] == $issue_id) {
					return "PREPARED_SELECT_NO_FIELDS_TEST_{$issue_id}";
				}
				// Fallback for other prepare calls
				if (is_string($sql) && !empty($args)) {
					// Ensure enough arguments for vsprintf if placeholders exist
					$placeholder_count = substr_count($sql, '%');
					// Only pad if there are fewer args than placeholders. Do not pad if args > placeholders.
					if (count($args) < $placeholder_count) {
						$args_padded = array_pad($args, $placeholder_count, null);
					} else {
						$args_padded = $args;
					}
					// Replace WordPress specific %i (table name) with %s for vsprintf compatibility
					$sql_for_vsprintf = str_replace('%i', '%s', $sql);
					return @vsprintf($sql_for_vsprintf, $args_padded);
				}
				return $sql; // Return original SQL if no args or not a string
			});

		$this->wpdb_mock->expects($this->once())
			->method('get_var')
			->with("PREPARED_COUNT_NO_FIELDS_TEST_{$issue_id}")
			->willReturn(1); // Issue exists

		$this->wpdb_mock->expects( $this->exactly(2) )
			->method( 'get_results' )
			->withConsecutive(
				["PREPARED_SELECT_NO_FIELDS_TEST_{$issue_id}"], // Match specific prepared SELECT query
				[$this->stringContains('options')]      // Matcher for the options query SQL from $request->get_params()
			)
			->willReturnOnConsecutiveCalls(
				[ $original_issue ],
				[]
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

		$this->wpdb_mock->expects($this->any())
			->method('prepare')
			->willReturnCallback(function($sql, ...$args) use ($issue_id, $attempted_updates) {
				$trimmed_sql = trim($sql);
				// COUNT query
				if (strpos($trimmed_sql, 'SELECT COUNT(*) FROM') === 0 && strpos($sql, "id IN ({$issue_id})") !== false) {
					return "PREPARED_COUNT_PROTECTED_TEST_{$issue_id}";
				}
				// SELECT * query
				if (strpos($trimmed_sql, 'SELECT * FROM') === 0 && strpos($sql, "id IN (%d)") !== false && isset($args[1]) && $args[1] == $issue_id) {
					return "PREPARED_SELECT_PROTECTED_TEST_{$issue_id}";
				}
				// UPDATE query - specific check for this test
				if (strpos($trimmed_sql, 'UPDATE') === 0 &&
					strpos($sql, "`rule` = %s") !== false && // Check that 'rule' is being set
					strpos($sql, "`siteid`") === false &&    // Check that 'siteid' is NOT in the SET clause
					strpos($sql, "`created`") === false &&  // Check that 'created' is NOT in the SET clause
					count($args) === 2 && // Expecting value for 'rule' and 'id' for WHERE
					isset($args[0]) && $args[0] === $attempted_updates['rule'] &&
					isset($args[1]) && $args[1] === $issue_id
				) {
					return "PREPARED_UPDATE_PROTECTED_TEST_{$issue_id}";
				}
				// Fallback for other prepare calls
				if (is_string($sql) && !empty($args)) {
					// Ensure enough arguments for vsprintf if placeholders exist
					$placeholder_count = substr_count($sql, '%');
					// Only pad if there are fewer args than placeholders. Do not pad if args > placeholders.
					if (count($args) < $placeholder_count) {
						$args_padded = array_pad($args, $placeholder_count, null);
					} else {
						$args_padded = $args;
					}
					// Replace WordPress specific %i (table name) with %s for vsprintf compatibility
					$sql_for_vsprintf = str_replace('%i', '%s', $sql);
					return @vsprintf($sql_for_vsprintf, $args_padded);
				}
				return $sql; // Return original SQL if no args or not a string
			});

		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_var')
			->with("PREPARED_COUNT_PROTECTED_TEST_{$issue_id}")
			->willReturn(1); // Issue exists

		$get_results_call_count_protected = 0; // Local counter for this test
		$this->wpdb_mock->expects($this->exactly(2))
			->method('get_results')
			->with("PREPARED_SELECT_PROTECTED_TEST_{$issue_id}")
			->willReturnCallback(function() use (&$get_results_call_count_protected, $original_issue, $final_issue_data) {
				$get_results_call_count_protected++;
				if ($get_results_call_count_protected === 1) {
					return [$original_issue]; // Data for initial existence check
				}
				return [(object)$final_issue_data]; // Data for fetching updated issue (only rule changed)
			});

		$this->wpdb_mock->expects($this->once())
			->method('query')
			->with("PREPARED_UPDATE_PROTECTED_TEST_{$issue_id}")
			->willReturn(1); // 1 row affected

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
