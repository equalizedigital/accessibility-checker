<?php
/**
 * Class SimplifiedSummaryShortcodeTest
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Shortcodes\SimplifiedSummaryShortcode;

/**
 * SimplifiedSummaryShortcode test case.
 */
class SimplifiedSummaryShortcodeTest extends WP_UnitTestCase {

	/**
	 * Set up the test fixture.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! shortcode_exists( SimplifiedSummaryShortcode::SHORTCODE ) ) {
			( new SimplifiedSummaryShortcode() )->init_hooks();
		}
	}

	/**
	 * Tests that the shortcode renders the summary for the current post.
	 *
	 * @return void
	 */
	public function test_shortcode_renders_summary_for_current_post() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Shortcode summary.' );

		global $post;
		$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up the loop for the test.
		setup_postdata( $post );

		$output = do_shortcode( '[edac_simplified_summary]' );
		$this->assertStringContainsString( 'Shortcode summary.', $output );
		$this->assertStringContainsString( 'edac-simplified-summary', $output );
	}

	/**
	 * Tests that the shortcode accepts an explicit post_id attribute.
	 *
	 * @return void
	 */
	public function test_shortcode_accepts_post_id_attribute() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Attribute summary.' );

		$output = do_shortcode( '[edac_simplified_summary post_id="' . $post_id . '"]' );
		$this->assertStringContainsString( 'Attribute summary.', $output );
	}

	/**
	 * Tests that the shortcode returns an empty string when the post has no summary.
	 *
	 * @return void
	 */
	public function test_shortcode_returns_empty_string_without_summary() {
		$post_id = self::factory()->post->create();

		$output = do_shortcode( '[edac_simplified_summary post_id="' . $post_id . '"]' );
		$this->assertSame( '', $output );
	}

	/**
	 * Tests that the shortcode returns an empty string outside the loop with no post_id.
	 *
	 * @return void
	 */
	public function test_shortcode_returns_empty_string_without_post() {
		global $post;
		$post = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Clearing the loop for the test.

		$output = do_shortcode( '[edac_simplified_summary]' );
		$this->assertSame( '', $output );
	}
}
