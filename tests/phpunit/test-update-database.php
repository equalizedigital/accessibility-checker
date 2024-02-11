<?php
/**
 * Class EDACSimplifiedSummaryTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Update_Database;

/**
 * Update_Database test case.
 */
class EDACUpdateDatabaseTest extends WP_UnitTestCase {

	/**
	 * Holds the instance of the Update_Database class for testing.
	 *
	 * @var Update_Database Instance of the Update_Database class. 
	 * This is the object that we will be testing.
	 */
	private $update_database;

	/**
	 * Mocked global WordPress database object for testing.
	 *
	 * @var wpdb Mocked global WordPress database object. 
	 * This is a mock of the global $wpdb variable that we will use to test the Update_Database class.
	 */
	private $wpdb;

	/**
	 * Sets up the environment for each test.
	 * 
	 * This method is called before the execution of each test method in the class.
	 * It's used to set up any state that's shared across multiple tests.
	 * In this case, it's creating a new instance of the Update_Database class and a mock of the $wpdb global variable.
	 * 
	 * @return void
	 */
	protected function setUp(): void {
		$this->update_database = new Update_Database();

		global $wpdb;
		$this->wpdb = $wpdb; // Save the original value to restore it in tearDown.
		$wpdb       = $this->createMock( wpdb::class );
	}

	/**
	 * Tests the edac_update_database method of the Update_Database class.
	 *
	 * This test checks that the edac_update_database method correctly calls the
	 * query method of the $wpdb global variable with the expected SQL query.
	 * 
	 * @return void
	 */
	public function test_edac_update_database() {
		// Arrange: Prepare the environment for the test.
		global $wpdb;
		$wpdb->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'ALTER TABLE' ) );

		// Act: Call the method that you're testing.
		$this->update_database->edac_update_database();

		// Assert: Check that the method behaved as expected.
		// This is done in the mock expectation above.
	}

	/**
	 * Restores global state after each test.
	 *
	 * Called after each test execution, this method resets the global `$wpdb` 
	 * to its original state, preventing interference with subsequent tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Restore the original $wpdb global variable.
		global $wpdb;
		$wpdb = $this->wpdb;
	}
}
