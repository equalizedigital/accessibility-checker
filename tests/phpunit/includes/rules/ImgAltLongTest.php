<?php
/**
 * Class EDACRuleImgAltLongTest
 *
 * @package Accessibility_Checker
 */

namespace rules;

use WP_UnitTestCase;

/**
 * Admin Notices test case.
 */
class ImgAltLongTest extends WP_UnitTestCase {

	/**
	 * Test that edac_rule_img_alt_long function returns an empty array when all images have alt text within the allowed length.
	 *
	 * @return void
	 */
	public function test_empty_array_when_alt_text_is_within_allowed_length() {
		$html    = '<img src="test.jpg" alt="This is a test image">';
		$dom     = str_get_html( $html );
		$content = array(
			'html' => $dom,
		);
		$post    = null;
		$errors  = edac_rule_img_alt_long( $content, $post );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test that edac_rule_img_alt_long function returns an array with errors when images have alt text longer than the allowed length.
	 *
	 * @return void
	 */
	public function test_returns_errors_when_alt_text_is_longer_than_allowed_length() {
		$html    = '<img src="test.jpg" alt="' . str_repeat( 'a', 301 ) . '">';
		$dom     = str_get_html( $html );
		$content = array(
			'html' => $dom,
		);
		$post    = null;
		$errors  = edac_rule_img_alt_long( $content, $post );
		$this->assertNotEmpty( $errors );
	}

	/**
	 * Test that the edac_max_alt_length filter modifies the maximum allowed alt text length.
	 *
	 * @return void
	 */
	public function test_edac_max_alt_length_filter_modifies_max_alt_length() {
		// Add a filter to modify the maximum alt text length.
		add_filter(
			'edac_max_alt_length',
			function () {
				return 10;
			}
		);

		$html    = '<img src="test.jpg" alt="This is a long alt text">';
		$dom     = str_get_html( $html );
		$content = array(
			'html' => $dom,
		);
		$post    = null;
		$errors  = edac_rule_img_alt_long( $content, $post );

		// Remove the filter after the test.
		remove_filter( 'edac_max_alt_length', '__return_true' );

		$this->assertNotEmpty( $errors );
	}
}
