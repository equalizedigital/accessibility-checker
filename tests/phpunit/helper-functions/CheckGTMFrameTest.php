<?php
/**
 * Test that the function properly checks and detects GTM iframes.
 *
 * @package Accessibility_Checker
 */

use simple_html_dom;

/**
 * Class CheckGTMFrameTest
 */
class CheckGTMFrameTest extends WP_UnitTestCase {

	/**
	 * Test that the function returns true for a hidden iframe.
	 */
	public function testHiddenIframe() {
		$iframe = new simple_html_dom();
		$iframe->load( '<iframe style="display: none; visibility: hidden;"></iframe>' );
		$this->assertTrue( edac_check_gtm_frame( $iframe->find( 'iframe', 0 ) ) );
		// Most of the time the style is minified, dropping spaces and the final semi-colon.
		$iframe->load( '<iframe style="display:none;visibility:hidden"></iframe>' );
		$this->assertTrue( edac_check_gtm_frame( $iframe->find( 'iframe', 0 ) ) );
	}

	/**
	 * Test that the function returns true for a GTM iframe.
	 */
	public function testGtmIframe() {
		$iframe = new simple_html_dom();
		$iframe->load( '<iframe src="https://www.googletagmanager.com"></iframe>' );
		$this->assertTrue( edac_check_gtm_frame( $iframe->find( 'iframe', 0 ) ) );
	}

	/**
	 * Test that the function returns false for a visible iframe.
	 */
	public function testVisibleIframe() {
		$iframe = new simple_html_dom();
		$iframe->load( '<iframe style="display: block;"></iframe>' );
		$this->assertFalse( edac_check_gtm_frame( $iframe->find( 'iframe', 0 ) ) );
	}

	/**
	 * Test that the function returns false for a non-GTM iframe with no styles.
	 */
	public function testNonGtmIframeNoStyles() {
		$iframe = new simple_html_dom();
		$iframe->load( '<iframe src="https://example.com"></iframe>' );
		$this->assertFalse( edac_check_gtm_frame( $iframe->find( 'iframe', 0 ) ) );
	}
}
