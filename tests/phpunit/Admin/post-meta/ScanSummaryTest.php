<?php
/**
 * Tests for the Scan Summary class.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Data\Post_Meta\Scan_Summary;

/**
 * Test cases for the Scan Summary class.
 */
class ScanSummaryTest extends WP_UnitTestCase {

	/**
	 * Hold an instance of the Scan_Summary class.
	 *
	 * @var Scan_Summary $scan_summary
	 */
	private Scan_Summary $scan_summary;

	/**
	 * Set up the scan summary property before each test and the post id.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->post_id      = $this->factory()->post->create();
		$this->scan_summary = new Scan_Summary( $this->post_id );
	}

	/**
	 * Remove the post after each test.
	 */
	protected function tearDown(): void {
		wp_delete_post( $this->post_id, true );
		parent::tearDown();
	}

	/**
	 * Test that before any scan data is saved it returns empty array.
	 */
	public function test_returns_empty_array_when_no_data_is_saved(): void {
		$this->assertEquals( array(), $this->scan_summary->get() );
	}

	/**
	 * Test that it returns saved data for single keys when requested.
	 */
	public function test_returns_saved_data_when_requested_by_key(): void {
		$passed_tests            = 5;
		$simplified_summary_text = '<p>Simplified summary</p>';

		$this->scan_summary->save(
			array(
				'passed_tests'            => $passed_tests,
				'simplified_summary_text' => $simplified_summary_text,
			)
		);

		$this->assertEquals(
			$passed_tests,
			$this->scan_summary->get( 'passed_tests' )
		);
		$this->assertEquals(
			$simplified_summary_text,
			$this->scan_summary->get( 'simplified_summary_text' )
		);
	}

	/**
	 * Test that it can save data for a single key.
	 */
	public function test_can_save_by_key(): void {
		$passed_tests = 5;
		$this->scan_summary->save( $passed_tests, 'passed_tests' );
		$this->assertEquals( 5, $this->scan_summary->get( 'passed_tests' ) );
	}

	/**
	 * Test that a single key can be saved and it gets merged rather than
	 * overwriting the entire array.
	 */
	public function test_single_key_save_doesnt_override_entire_array(): void {

		$key                 = 'passed_tests';
		$valid_summary_array = $this->get_a_valid_summary();
		$passed_tests_before = $valid_summary_array[ $key ];

		$this->scan_summary->save( $valid_summary_array );
		$summary_before           = $this->scan_summary->get();
		$keys_count_before_update = count( $summary_before );
		// should be the same as the valid summary array.
		$this->assertEquals( $passed_tests_before, $valid_summary_array[ $key ] );

		$passed_tests = 9;
		$this->scan_summary->save( $passed_tests, $key );
		$summary_after = $this->scan_summary->get();
		// should return same number of keys before and after saving single item.
		$this->assertEquals( $keys_count_before_update, count( $summary_after ) );
		// but with an updated value for the passed_tests key.
		$this->assertEquals( $passed_tests, $summary_after[ $key ] );
	}

	/**
	 * Test that it can delete a single key, making it the default value
	 * without deleting the entire array.
	 */
	public function test_delete_single_key_removes_data_for_that_key(): void {
		$passed_tests = 5;
		$key          = 'passed_tests';
		$this->scan_summary->save( $passed_tests, $key );
		$summary_before = $this->scan_summary->get();
		$this->assertEquals( $passed_tests, $summary_before[ $key ] );

		$this->scan_summary->delete( $key );
		$summary_after = $this->scan_summary->get();
		// data will have changed.
		$this->assertNotEquals( $passed_tests, $summary_after[ $key ] );

		// should have the same number of keys.
		$this->assertEquals( count( $summary_before ), count( $summary_after ) );
	}

	/**
	 * Test that the sanitizer converts data from all strings to the expected types.
	 */
	public function test_sanitize_summary_returns_expected_types(): void {
		$summary = array(
			'passed_tests'            => '5',
			'errors'                  => '3',
			'warnings'                => '2',
			'ignored'                 => '1',
			'contrast_errors'         => '4',
			'content_grade'           => '90',
			'readability'             => '9th Grade',
			'simplified_summary'      => 'true',
			'simplified_summary_text' => '<p>Test</p>',
		);

		$expected = $this->get_a_valid_summary();

		$this->assertEquals( $expected, $this->scan_summary->sanitize_summary( $summary ) );
	}

	/**
	 * The valid summary data used to compare against in some tests.
	 */
	private function get_a_valid_summary(): array {
		return array(
			'passed_tests'            => 5,
			'errors'                  => 3,
			'warnings'                => 2,
			'ignored'                 => 1,
			'contrast_errors'         => 4,
			'content_grade'           => 90,
			'readability'             => '9th Grade',
			'simplified_summary'      => true,
			'simplified_summary_text' => '<p>Test</p>',
		);
	}
}
