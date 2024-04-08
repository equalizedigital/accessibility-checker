<?php
/**
 * Class EDACSimplifiedSummaryTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Data\Post_Meta\Scan_Summary;
use EDAC\Inc\Simplified_Summary;

/**
 * Simplified_Summary test case.
 */
class SimplifiedSummaryTest extends WP_UnitTestCase {

	/**
	 * Instance of the Simplified_Summary class.
	 *
	 * Holds an instance of the Simplified_Summary class
	 * which is used to test its methods.
	 *
	 * @var Admin_Notices $simplified_summary.
	 */
	private $simplified_summary;

	/**
	 * Set up the test fixture.
	 *
	 * Initializes the testing environment before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->simplified_summary = new Simplified_Summary();
	}

	/**
	 * Tests output of simplified_summary_markup with a summary.
	 *
	 * Verifies that the correct HTML markup is returned when a post
	 * has a simplified summary.
	 *
	 * @return void
	 */
	public function test_simplified_summary_markup_with_summary() {
		$post_id = self::factory()->post->create();
		( new Scan_Summary( $post_id ) )->save( 'This is a simplified summary.', 'simplified_summary_text' );
		update_post_meta( $post_id, '_edac_simplified_summary', 'This is a simplified summary.' );

		$output = $this->simplified_summary->simplified_summary_markup( $post_id );
		$this->assertStringContainsString( '<div class="edac-simplified-summary">', $output );
		$this->assertStringContainsString( '<h2>Simplified Summary</h2>', $output );
		$this->assertStringContainsString( '<p>This is a simplified summary.</p>', $output );
		$this->assertStringContainsString( '</div>', $output );
	}

	/**
	 * Tests output of simplified_summary_markup with a summary that passes through back compat methods.
	 *
	 * Verifies that the correct HTML markup is returned when a post
	 * has a simplified summary.
	 *
	 * @return void
	 */
	public function test_simplified_summary_markup_with_summary_back_compat() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'This is a simplified summary.' );

		$output = $this->simplified_summary->simplified_summary_markup( $post_id );
		$this->assertStringContainsString( '<div class="edac-simplified-summary">', $output );
	}

	/**
	 * Tests output of simplified_summary_markup without a summary.
	 *
	 * Ensures that an empty string is returned when a post does not
	 * have a simplified summary.
	 *
	 * @return void
	 */
	public function test_simplified_summary_markup_without_summary() {
		$post_id = self::factory()->post->create();

		$output = $this->simplified_summary->simplified_summary_markup( $post_id );
		$this->assertEmpty( $output );
	}
}
