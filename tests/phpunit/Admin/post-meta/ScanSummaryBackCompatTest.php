<?php
/**
 * Tests for the back compat Scan Summary class.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Data\Post_Meta\Scan_Summary_Back_Compat;

/**
 * Test cases for the class added as an overlay to handle back compat
 * for the Scan Summary class that fetches the summary data.
 *
 * The bulk of the functionality is in the parent class, Scan_Summary
 * which has tests covering most of the functionality.
 */
class ScanSummaryBackCompatTest extends WP_UnitTestCase {

	/**
	 * Hold an instance of the Scan_Summary_Back_Compat class.
	 *
	 * @var Scan_Summary_Back_Compat $scan_summary_back_compat
	 */
	private Scan_Summary_Back_Compat $scan_summary_back_compat;

	/**
	 * Set up the scan summary property before each test and the post id.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->post_id                  = $this->factory()->post->create();
		$this->scan_summary_back_compat = new Scan_Summary_Back_Compat( $this->post_id );
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
		$this->assertEquals( array(), $this->scan_summary_back_compat->get() );
	}

	/**
	 * Test that it returns saved data for old key from dedicated post meta.
	 */
	public function test_returns_saved_data_when_requested_by_the_old_key(): void {
		$simplified_summary_text = '<p>Simplified summary</p>';
		$old_key                 = '_edac_simplified_summary';
		update_post_meta( $this->post_id, $old_key, $simplified_summary_text );

		$this->assertEquals( $simplified_summary_text, $this->scan_summary_back_compat->get( 'simplified_summary_text' ) );
	}

	/**
	 * Test that when a key is saved it deletes the old key.
	 */
	public function test_deletes_old_key_when_new_key_is_saved(): void {
		$simplified_summary_text = '<p>Simplified summary</p>';
		$old_key                 = '_edac_simplified_summary';
		$new_key                 = 'simplified_summary_text';
		update_post_meta( $this->post_id, $old_key, $simplified_summary_text );
		$this->assertEquals( $simplified_summary_text, $this->scan_summary_back_compat->get( $old_key ) );

		$this->scan_summary_back_compat->save( $simplified_summary_text, $new_key );

		$this->assertEquals( $simplified_summary_text, $this->scan_summary_back_compat->get( $new_key ) );
		// meta should be deleted after saving the new key.
		$this->assertEquals( '', get_post_meta( $this->post_id, $old_key, true ) );
	}
}
