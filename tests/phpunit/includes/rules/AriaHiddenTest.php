<?php
/**
 * Tests the aria_hidden rule.
 *
 * @package Accessibility_Checker
 */

/**
 * Various different test cases and situations to be run against the aria-hidden rule.
 */
class AriaHiddenTest extends WP_UnitTestCase {

	/**
	 * Collection of different markup to use for test cases.
	 *
	 * @param string $type a key to the array of markup fragments.
	 * @return string
	 */
	private function get_test_markup( string $type = '' ): string {
		$markup_fragments = array(
			'element_with_aria-hidden'        => '<div aria-hidden="true"></div>',
			'element_with_aria-hidden_false'  => '<div aria-hidden="false"></div>',
			'element_that_is_wp-block-spacer' => '<div aria-hidden="true" class="wp-block-spacer"></div>',
			'button_with_aria-label'          => <<<EOT
				<button type="button" aria-haspopup="true" aria-label="Open menu" class="components-button wp-block-navigation__responsive-container-open" inert="true">
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				</button>
			EOT,
			'link_with_aria-label'            => <<<EOT
				<a href="http://example.com" aria-label="label">
					    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				</a>
			EOT,
			'link_with_screen_reader_text'    => <<<EOT
				<a href="/about" >
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				<span class="sr-only">About Us</span>
			EOT,
			'button_with_screen_reader_text'  => <<<EOT
				<button type="button" aria-haspopup="true" class="components-button wp-block-navigation__responsive-container-open" inert="true">
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				<span class="sr-only">Open menu</span>
				</button>
			EOT,
			'link_with_visible_text'          => <<<EOT
				<a href="/about" >
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				About Us
				</a>
			EOT,
			'button_with_visible_text'        => <<<EOT
				<button type="button" aria-haspopup="true" class="components-button wp-block-navigation__responsive-container-open" inert="true">
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				Menu
				</button>
			EOT,
			'image_that_is_presentational'    => '<img src="http://example.com/image.jpg" aria-hidden="true" role="presentation" />',
		);
		return $markup_fragments[ $type ] ?? '';
	}
}
