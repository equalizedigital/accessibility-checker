<?php
/**
 * Class EDACSimplifiedSummaryTest
 *
 * @package Accessibility_Checker
 */

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
		update_post_meta( $post_id, '_edac_simplified_summary', 'This is a simplified summary.' );

		$output = $this->simplified_summary->simplified_summary_markup( $post_id );
		$this->assertStringContainsString( '<div class="edac-simplified-summary">', $output );
		$this->assertStringContainsString( '<h2>Simplified Summary</h2>', $output );
		$this->assertStringContainsString( '<p>This is a simplified summary.</p>', $output );
		$this->assertStringContainsString( '</div>', $output );
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

	/**
	 * Test that init_hooks registers the_content filter.
	 *
	 * @return void
	 */
	public function test_init_hooks_adds_the_content_filter() {
		$this->simplified_summary->init_hooks();

		$this->assertNotFalse(
			has_filter( 'the_content', [ $this->simplified_summary, 'output_simplified_summary' ] )
		);

		remove_filter( 'the_content', [ $this->simplified_summary, 'output_simplified_summary' ] );
	}

	/**
	 * Test that output_simplified_summary returns content unchanged when prompt is 'none'.
	 *
	 * @return void
	 */
	public function test_output_returns_content_unchanged_when_prompt_is_none() {
		update_option( 'edac_simplified_summary_prompt', 'none' );

		$content = '<p>Original content</p>';
		$result  = $this->simplified_summary->output_simplified_summary( $content );

		$this->assertEquals( $content, $result );

		delete_option( 'edac_simplified_summary_prompt' );
	}

	/**
	 * Test that output_simplified_summary prepends summary when position is 'before'.
	 *
	 * @return void
	 */
	public function test_output_prepends_summary_when_position_is_before() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'A test summary.' );
		update_option( 'edac_simplified_summary_prompt', 'enabled' );
		update_option( 'edac_simplified_summary_position', 'before' );

		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		$content = '<p>Original content</p>';
		$result  = $this->simplified_summary->output_simplified_summary( $content );

		$summary_pos = strpos( $result, '<div class="edac-simplified-summary">' );
		$content_pos = strpos( $result, $content );

		$this->assertNotFalse( $summary_pos );
		$this->assertLessThan( $content_pos, $summary_pos );

		wp_reset_postdata();
		delete_option( 'edac_simplified_summary_prompt' );
		delete_option( 'edac_simplified_summary_position' );
	}

	/**
	 * Test that output_simplified_summary appends summary when position is 'after'.
	 *
	 * @return void
	 */
	public function test_output_appends_summary_when_position_is_after() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'A test summary.' );
		update_option( 'edac_simplified_summary_prompt', 'enabled' );
		update_option( 'edac_simplified_summary_position', 'after' );

		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		$content = '<p>Original content</p>';
		$result  = $this->simplified_summary->output_simplified_summary( $content );

		$content_pos = strpos( $result, $content );
		$summary_pos = strpos( $result, '<div class="edac-simplified-summary">' );

		$this->assertNotFalse( $summary_pos );
		$this->assertLessThan( $summary_pos, $content_pos );

		wp_reset_postdata();
		delete_option( 'edac_simplified_summary_prompt' );
		delete_option( 'edac_simplified_summary_position' );
	}
}
