<?php

namespace EqualizeDigital\AccessibilityChecker\Tests\phpunit\includes\classes\Rest;

use EqualizeDigital\AccessibilityChecker\Rest\Issues_API;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_Error;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;

// Ensure the class we are testing is loaded
require_once dirname( __FILE__, 6 ) . '/includes/classes/Rest/IssuesAPI.php';


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
			->onlyMethods( [ 'get_results', 'prepare', 'query', 'get_var' ] ) // Added get_var for count_all_issues
			->getMock();
		$GLOBALS['wpdb'] = $this->wpdb_mock;

		$this->current_site_id = get_current_blog_id(); // Or mock if needed: 1;
		$this->table_name      = edac_get_valid_table_name( $this->wpdb_mock->prefix . 'accessibility_checker' );

		$this->issues_api = new Issues_API();

		// Reflection to set the table name in Issues_API if it's not set via constructor or other means
		// In the actual Issues_API, table_name is set in constructor using global $wpdb->prefix
		// So, we need to ensure our mock $wpdb has a prefix or set it manually if problems arise.
		$this->wpdb_mock->prefix = 'wp_'; // Standard WordPress prefix
		$this->table_name = edac_get_valid_table_name( $this->wpdb_mock->prefix . 'accessibility_checker' );

		$reflection = new \ReflectionClass( $this->issues_api );
		$table_name_prop = $reflection->getProperty( 'table_name' );
		$table_name_prop->setAccessible( true );
		$table_name_prop->setValue( $this->issues_api, $this->table_name );

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

		// Mock do_issues_query (first call for existence check, second for fetching updated)
		$this->wpdb_mock->expects( $this->exactly(2) )
			->method( 'get_results' )
			->willReturnOnConsecutiveCalls(
				[ $original_issue ], // First call in update_issue to check existence
				[ (object) $final_issue_data ]  // Second call in update_issue after update
			);

		// Mock count_all_issues (called by do_issues_query)
		$this->wpdb_mock->expects( $this->any() ) // Could be called multiple times by do_issues_query
			->method('get_var')
			->willReturn(1);


		// Mock $wpdb->prepare for the UPDATE query
		$this->wpdb_mock->expects( $this->once() )
			->method( 'prepare' )
			->with(
				$this->stringContains( "UPDATE `{$this->table_name}` SET" ),
				$updated_fields['rule'], // Make sure order and number of args match $set_sql and $query_values
				(int)$updated_fields['ignre'], // Booleans are cast to int
				$updated_fields['ignre_comment'],
				$issue_id
			)
			->willReturn( "SQL_QUERY_DOES_NOT_MATTER_WHEN_MOCKED" ); // Return a dummy SQL query string.

		// Mock $wpdb->query for the UPDATE execution
		$this->wpdb_mock->expects( $this->once() )
			->method( 'query' )
			// ->with("SQL_QUERY_DOES_NOT_MATTER_WHEN_MOCKED") // This should match what prepare returns
			->willReturn( 1 ); // 1 row affected

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

		// Mock do_issues_query to return empty for non-existent ID
		$this->wpdb_mock->expects( $this->once() )
			->method( 'get_results' )
			->with( $this->stringContains( "AND id IN ({$non_existent_id})" ) )
			->willReturn( [] );

		// Mock count_all_issues (called by do_issues_query)
		$this->wpdb_mock->expects( $this->any() )
			->method('get_var')
			->willReturn(0);


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

		// Mock do_issues_query for existence check
		$this->wpdb_mock->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( [ $original_issue ] );

		// Mock count_all_issues
		$this->wpdb_mock->expects( $this->any() )
			->method('get_var')
			->willReturn(1);

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
		$this->wpdb_mock->expects( $this->exactly(2) )
			->method( 'get_results' )
			->willReturnOnConsecutiveCalls(
				[ $original_issue ],
				[ (object) $final_issue_data ]
			);

		// Mock count_all_issues
		$this->wpdb_mock->expects( $this->any() )
			->method('get_var')
			->willReturn(1);

		// $wpdb->prepare should only be called with 'rule' as it's the only valid updatable field from $attempted_updates
		$this->wpdb_mock->expects( $this->once() )
			->method( 'prepare' )
			->with(
				$this->stringContains( "UPDATE `{$this->table_name}` SET `rule` = %s WHERE `id` = %d" ), // Only rule is updated
				$attempted_updates['rule'],
				$issue_id
			)
			->willReturn( "SQL_QUERY_PROTECTED_FIELDS_TEST" );

		$this->wpdb_mock->expects( $this->once() )
			->method( 'query' )
			->willReturn( 1 ); // 1 row affected (for the 'rule' update)


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
