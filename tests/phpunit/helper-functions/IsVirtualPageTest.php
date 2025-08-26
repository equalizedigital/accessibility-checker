<?php
/**
 * Class IsVirtualPageTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_is_virtual_page() function.
 */
class IsVirtualPageTest extends WP_UnitTestCase {

	/**
	 * Test edac_is_virtual_page returns false when pro plugin class doesn't exist.
	 */
	public function test_returns_false_when_pro_class_does_not_exist() {
		// Create a test post of any type.
		$post_id = $this->factory()->post->create( [ 'post_type' => 'post' ] );

		$this->assertFalse( edac_is_virtual_page( $post_id ) );
	}

	/**
	 * Test edac_is_virtual_page with different post types.
	 *
	 * @dataProvider post_types_data
	 *
	 * @param string $post_type The post type to test.
	 * @param bool   $expected  The expected result.
	 */
	public function test_edac_is_virtual_page_with_post_types( $post_type, $expected ) {
		// Create the mock pro plugin class.
		$this->create_mock_pro_class();

		// Register the custom post type for testing.
		if ( 'edac_virtual_item' === $post_type ) {
			register_post_type(
				$post_type,
				[
					'public'   => false,
					'supports' => [ 'title' ],
				]
			);
		}

		// Create post with the specified post type.
		$post_id = $this->factory()->post->create( [ 'post_type' => $post_type ] );

		$this->assertSame( $expected, edac_is_virtual_page( $post_id ) );
	}

	/**
	 * Test edac_is_virtual_page with invalid post IDs.
	 *
	 * @dataProvider invalid_post_ids_data
	 *
	 * @param mixed $post_id The post ID to test.
	 */
	public function test_edac_is_virtual_page_with_invalid_post_ids( $post_id ) {
		$this->assertFalse( edac_is_virtual_page( $post_id ) );
	}

	/**
	 * Data provider for test_edac_is_virtual_page_with_post_types.
	 */
	public function post_types_data() {
		return [
			'virtual item post type' => [ 'edac_virtual_item', true ],
			'regular post'           => [ 'post', false ],
			'page'                   => [ 'page', false ],
		];
	}

	/**
	 * Data provider for test_edac_is_virtual_page_with_invalid_post_ids.
	 */
	public function invalid_post_ids_data() {
		return [
			'non-existent post ID' => [ 999999 ],
			'zero post ID'         => [ 0 ],
			'negative post ID'     => [ -1 ],
			'non-numeric string'   => [ 'not-a-number' ],
		];
	}

	/**
	 * Helper method to create mock pro plugin class.
	 */
	private function create_mock_pro_class() {
		if ( ! class_exists( '\EqualizeDigital\AccessibilityCheckerPro\VirtualContent\PostType\VirtualItemType' ) ) {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
			eval(
				'
				namespace EqualizeDigital\\AccessibilityCheckerPro\\VirtualContent\\PostType;
				class VirtualItemType {
					const POST_TYPE = "edac_virtual_item";
				}
			'
			);
		}
	}
}
