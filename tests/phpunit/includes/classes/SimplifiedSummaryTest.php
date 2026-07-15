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
	 * Creates a post with a summary and sets it up as the current global post.
	 *
	 * @param string $content The post content.
	 * @return int The post ID.
	 */
	private function create_post_in_loop( $content = 'Post content.' ) {
		$post_id = self::factory()->post->create( [ 'post_content' => $content ] );
		update_post_meta( $post_id, '_edac_simplified_summary', 'This is a simplified summary.' );
		update_option( 'edac_simplified_summary_prompt', 'always' );
		update_option( 'edac_simplified_summary_position', 'after' );

		global $post;
		$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up the loop for the test.
		setup_postdata( $post );

		return $post_id;
	}

	/**
	 * Tests that the summary is auto-inserted when not manually placed.
	 *
	 * @return void
	 */
	public function test_output_simplified_summary_auto_inserts_when_not_manually_placed() {
		$this->create_post_in_loop();

		$output = $this->simplified_summary->output_simplified_summary( 'Post content.' );
		$this->assertStringContainsString( 'edac-simplified-summary', $output );
	}

	/**
	 * Tests that auto-insertion is suppressed when the block is in the post content.
	 *
	 * @return void
	 */
	public function test_output_simplified_summary_suppressed_when_block_in_content() {
		$this->create_post_in_loop( '<!-- wp:edac/simplified-summary /--><!-- wp:paragraph --><p>Post content.</p><!-- /wp:paragraph -->' );

		$output = $this->simplified_summary->output_simplified_summary( 'Post content.' );
		$this->assertStringNotContainsString( 'edac-simplified-summary', $output );
	}

	/**
	 * Tests that auto-insertion is suppressed when the shortcode is in the post content.
	 *
	 * @return void
	 */
	public function test_output_simplified_summary_suppressed_when_shortcode_in_content() {
		$this->create_post_in_loop( 'Post content. [edac_simplified_summary]' );

		$output = $this->simplified_summary->output_simplified_summary( 'Post content.' );
		$this->assertStringNotContainsString( 'edac-simplified-summary', $output );
	}

	/**
	 * Tests that auto-insertion is suppressed when the block is in the current block theme template.
	 *
	 * @return void
	 */
	public function test_output_simplified_summary_suppressed_when_block_in_template() {
		$this->create_post_in_loop();

		global $_wp_current_template_content;
		$_wp_current_template_content = '<!-- wp:edac/simplified-summary /--><!-- wp:post-content /-->';

		$output = $this->simplified_summary->output_simplified_summary( 'Post content.' );

		$_wp_current_template_content = null;

		$this->assertStringNotContainsString( 'edac-simplified-summary', $output );
	}

	/**
	 * Tests that the manually placed filter can suppress auto-insertion.
	 *
	 * @return void
	 */
	public function test_output_simplified_summary_suppressed_by_filter() {
		$this->create_post_in_loop();

		add_filter( 'edac_filter_simplified_summary_is_manually_placed', '__return_true' );
		$output = $this->simplified_summary->output_simplified_summary( 'Post content.' );
		remove_filter( 'edac_filter_simplified_summary_is_manually_placed', '__return_true' );

		$this->assertStringNotContainsString( 'edac-simplified-summary', $output );
	}
}
