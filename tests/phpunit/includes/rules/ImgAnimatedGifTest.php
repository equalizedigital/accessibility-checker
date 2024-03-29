<?php
/**
 * Tests for the gif and webp animation rule.
 *
 * @package Accessibility_Checker
 */

/**
 * Test that the animated gif rule detects animated gif and webp and does not count
 * static gif and webp as an error.
 *
 * @group rules
 */
class ImgAnimatedGifTest extends WP_UnitTestCase {

	/**
	 * Test if animated gif is detected.
	 */
	public function testAnimatedGifDetectionWithAnimatedGif() {
		$filename = EDAC_TEST_ASSETS_DIR . 'animated.gif';
		$this->assertTrue( edac_img_gif_is_animated( $filename ) );
	}

	/**
	 * Test that static gif is not detected as animated.
	 */
	public function testAnimatedGifDetectionWithStaticGif() {
		$filename = EDAC_TEST_ASSETS_DIR . 'static.gif';
		$this->assertFalse( edac_img_gif_is_animated( $filename ) );
	}

	/**
	 * Test that animated webp is detected as animated.
	 */
	public function testAnimatedWebpDetectionWithAnimatedWebp() {
		$filename = EDAC_TEST_ASSETS_DIR . 'animated.webp';
		$this->assertTrue( edac_img_webp_is_animated( $filename ) );
	}

	/**
	 * Test that static webp is not detected as animated.
	 */
	public function testAnimatedWebpDetectionWithStaticWebp() {
		$filename = EDAC_TEST_ASSETS_DIR . 'static.webp';
		$this->assertFalse( edac_img_webp_is_animated( $filename ) );
	}

	/**
	 * Test that animated gif is detected when passed through entire rule.
	 */
	public function testRuleWithAnimatedGifInContent() {
		$html    = '<img src="' . EDAC_TEST_ASSETS_DIR . 'animated.gif">';
		$dom     = str_get_html( $html );
		$content = array( 'html' => $dom );
		$post    = new stdClass();
		$errors  = edac_rule_img_animated_gif( $content, $post );
		$this->assertNotEmpty( $errors );
	}

	/**
	 * Test that static gif is not detected as animated when passed through entire rule.
	 */
	public function testRuleWithStaticGifInContent() {
		$html    = '<img src="' . EDAC_TEST_ASSETS_DIR . 'static.gif">';
		$dom     = str_get_html( $html );
		$content = array( 'html' => $dom );
		$post    = new stdClass();
		$errors  = edac_rule_img_animated_gif( $content, $post );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test that animated webp is detected as animated when passed through entire rule.
	 */
	public function testRuleWithAnimatedWebpInContent() {
		$html    = '<img src="' . EDAC_TEST_ASSETS_DIR . 'animated.webp">';
		$dom     = str_get_html( $html );
		$content = array( 'html' => $dom );
		$post    = new stdClass();
		$errors  = edac_rule_img_animated_gif( $content, $post );
		$this->assertNotEmpty( $errors );
	}

	/**
	 * Test that static webp is not detected as animated when passed through entire rule.
	 */
	public function testRuleWithStaticWebpInContent() {

		$html    = '<img src="' . EDAC_TEST_ASSETS_DIR . 'static.webp">';
		$dom     = str_get_html( $html );
		$content = array( 'html' => $dom );
		$post    = new stdClass();
		$errors  = edac_rule_img_animated_gif( $content, $post );
		$this->assertEmpty( $errors );
	}
}
