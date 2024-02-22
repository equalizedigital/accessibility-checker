<?php
/**
 * Class EDACSummaryGeneratorTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Summary_Generator;

/**
 * Simplified_Summary test case.
 */
class EDACSummaryGeneratorTest extends WP_UnitTestCase {
	
	/**
	 * Undocumented variable
	 *
	 * @var int $post_id The ID of the post to test with.
	 */
	private $post_id;

	/**
	 * Instance of the SummaryGenerator class.
	 *
	 * @var SummaryGenerator $summary_generator.
	 */
	private $summary_generator;

	/**
	 * Set up the test case.
	 * 
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->post_id           = self::factory()->post->create();
		$this->summary_generator = new Summary_Generator( $this->post_id );

		// Create the wptests_accessibility_checker table.
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'accessibility_checker';

		$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            rule tinytext NOT NULL,
            siteid mediumint(9) NOT NULL,
            postid mediumint(9) NOT NULL,
            ignre tinyint(1) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

		parent::tearDown();
		wp_delete_post( $this->post_id, true );
	}

	/**
	 * Test the constructor.
	 *
	 * @return void
	 */
	public function test_constructor() {
		$this->assertInstanceOf( Summary_Generator::class, $this->summary_generator );
		// Further checks can include verifying post_id and site_id assignment.
	}

	/**
	 * Test the generate_summary method.
	 *
	 * @return void
	 */
	public function test_generate_summary() {
		$summary = $this->summary_generator->generate_summary();
		// Assuming default values or mocks for methods called within generate_summary,
		// assert expected structure and values of $summary.
		$this->assertIsArray( $summary );
		// Add more assertions based on expected summary content.
	}

	/**
	 * Test the calculate_passed_tests method.
	 *
	 * @return void
	 */
	public function test_calculate_passed_tests() {
		// Insert mock data for passed tests into your database table for accessibility checks.
		// This step depends on your database schema and how tests/results are stored.
	
		// Using reflection to make the private method accessible for testing.
		$reflection = new ReflectionClass( $this->summary_generator );
		$method     = $reflection->getMethod( 'calculate_passed_tests' );
		$method->setAccessible( true );
	
		// Assuming edac_register_rules() returns an array of test rules.
		$rules = edac_register_rules(); // This should be adjusted based on your actual implementation.
	
		$passed_tests_percentage = $method->invokeArgs( $this->summary_generator, array( $rules ) );
		
		// Assert the percentage of passed tests based on your mock data.
		// This assertion depends on the mock data you've inserted and the logic in calculate_passed_tests.
		$this->assertIsNumeric( $passed_tests_percentage );
		// Example: if you know the expected percentage is 75 based on your mock setup, assert that.
		$this->assertEquals( 100, $passed_tests_percentage );
	}

	/**
	 * Test the count_errors method.
	 *
	 * @return void
	 */
	public function test_count_errors() {
	
		// Using reflection again to access the private method.
		$reflection = new ReflectionClass( $this->summary_generator );
		$method     = $reflection->getMethod( 'count_errors' );
		$method->setAccessible( true );
	
		$errors_count = $method->invoke( $this->summary_generator );
		
		// Assert the count of errors based on your mock data.
		$this->assertIsNumeric( $errors_count );
		// Assuming you know the mock data should result in 2 errors.
		$this->assertEquals( 0, $errors_count );
	}

	/**
	 * Test the count_warnings method.
	 *
	 * @return void
	 */
	public function test_save_summary_meta_data() {
		$summary = array(
			'passed_tests'       => 80,
			'errors'             => 1,
			'warnings'           => 2,
			'ignored'            => 3,
			'contrast_errors'    => 4,
			'content_grade'      => 1,
			'readability'        => '1st',
			'simplified_summary' => true,
		);
	
		// Directly test the public method that eventually calls save_summary_meta_data.
		// If save_summary_meta_data is private, you'll need to trigger it through a public method like generate_summary.
		// For this example, let's assume it's triggered via generate_summary and reflects in post meta.
		$this->summary_generator->generate_summary(); // This should internally call save_summary_meta_data with $summary.
	
		// Now assert that the post meta was updated as expected.
		$saved_summary = get_post_meta( $this->post_id, '_edac_summary', true );
		$this->assertEquals( $summary, $saved_summary );
		// You can add more assertions for each specific key in the summary array.
	}
}
