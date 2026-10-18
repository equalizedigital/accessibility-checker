<?php
/**
 * Tests for edac_get_simplified_summary() in includes/helper-functions.php.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for the simplified summary output helper.
 *
 * The helper is what the [edac_simplified_summary] shortcode and the
 * edac/simplified-summary block call, so its output is the markup that
 * appears on the front end. It echoes rather than returns, so every test
 * captures the buffered output instead of a return value.
 *
 * @covers ::edac_get_simplified_summary
 */
class GetSimplifiedSummaryTest extends WP_UnitTestCase {

	/**
	 * Removes the heading filter a test may have added.
	 */
	public function tearDown(): void {
		remove_all_filters( 'edac_filter_simplified_summary_heading' );

		parent::tearDown();
	}

	/**
	 * Runs the helper and returns whatever it echoed.
	 *
	 * @param mixed $post Post ID to pass, or null to use the current post.
	 * @return string The captured output.
	 */
	private function capture_output( $post = null ): string {
		ob_start();
		edac_get_simplified_summary( $post );
		return (string) ob_get_clean();
	}

	/**
	 * A post with no stored summary produces no output at all.
	 */
	public function test_outputs_nothing_when_the_post_has_no_summary(): void {
		$post_id = self::factory()->post->create();

		$this->assertSame( '', $this->capture_output( $post_id ) );
	}

	/**
	 * A zero post ID produces no output rather than a PHP notice.
	 */
	public function test_outputs_nothing_for_a_zero_post_id(): void {
		$this->assertSame( '', $this->capture_output( 0 ) );
	}

	/**
	 * A stored summary is echoed inside the simplified summary wrapper markup.
	 */
	public function test_outputs_the_wrapper_markup_for_a_post_with_a_summary(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'This is a simplified summary.' );

		$this->assertSame(
			'<div class="edac-simplified-summary"><h2>Simplified Summary</h2><p>This is a simplified summary.</p></div>',
			$this->capture_output( $post_id )
		);
	}

	/**
	 * With no post passed the helper falls back to the current post.
	 */
	public function test_uses_the_current_post_when_no_post_is_passed(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Current post summary.' );

		$had_post      = isset( $GLOBALS['post'] );
		$previous_post = $had_post ? $GLOBALS['post'] : null;

		$GLOBALS['post'] = get_post( $post_id );

		try {
			$this->assertSame(
				'<div class="edac-simplified-summary"><h2>Simplified Summary</h2><p>Current post summary.</p></div>',
				$this->capture_output()
			);
		} finally {
			if ( $had_post ) {
				$GLOBALS['post'] = $previous_post;
			} else {
				unset( $GLOBALS['post'] );
			}
		}
	}

	/**
	 * The echoed summary is run through wp_kses_post, so markup and attributes
	 * that are not allowed in post content never reach the page.
	 */
	public function test_the_summary_is_kses_filtered_on_output(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', '<img src="x" onerror="alert(1)">Summary text' );

		$output = $this->capture_output( $post_id );

		$this->assertStringContainsString( 'Summary text', $output );
		$this->assertStringNotContainsString( 'onerror', $output );
	}

	/**
	 * The heading before the summary is filterable, and the filtered value
	 * replaces the default rather than being appended to it.
	 */
	public function test_the_heading_is_filterable(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Summary.' );

		add_filter(
			'edac_filter_simplified_summary_heading',
			static function () {
				return 'Custom Heading';
			}
		);

		$output = $this->capture_output( $post_id );

		$this->assertStringContainsString( '<h2>Custom Heading</h2>', $output );
		$this->assertStringNotContainsString( '<h2>Simplified Summary</h2>', $output );
	}
}
