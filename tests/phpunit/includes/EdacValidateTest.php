<?php
/**
 * Test class for edac_validate function.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for edac_validate function.
 */
class EdacValidateTest extends TestCase {

	/**
	 * Test that the_content filter is properly applied during validation.
	 */
	public function test_validation_applies_the_content_filter() {
		// Setup a test post with content that requires the_content processing.
		$post_id = $this->factory->post->create(
			[
				'post_content' => '<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->',
			]
		);
		$post    = get_post( $post_id );

		// Mock the_content filter to verify it's called.
		add_filter(
			'the_content',
			function ( $content ) {
				$this->content_filter_called = true;
				return $content;
			}
		);

		// Run validation.
		edac_validate( $post_id, $post, 'test' );

		// Assert that the_content filter was called.
		$this->assertTrue( $this->content_filter_called );
	}
}
