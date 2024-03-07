<?php
/**
 * Tests the aria_hidden rule.
 *
 * @package Accessibility_Checker
 */

/**
 * Various different test cases and situations to be run against the aria-hidden rule.
 *
 * @group rules
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

	/**
	 * Tests the edac_rule_aria_hidden function detects aria-hidden="true".
	 */
	public function test_edac_rule_aria_hidden_finds_hidden() {

		$errors = $this->get_errors_from_rule_check( $this->get_test_markup( 'element_with_aria-hidden' ) );
		$this->assertNotEmpty( $errors );

		// can handle single quotes.
		$errors = $this->get_errors_from_rule_check( str_replace( '"', "'", $this->get_test_markup( 'element_with_aria-hidden' ) ) );
		$this->assertNotEmpty( $errors );
	}

	/**
	 * Tests the edac_rule_aria_hidden function doesn't detect an issue when aria-hidden="false".
	 */
	public function test_edac_rule_aria_hidden_skips_hidden_false() {

		$this->assertEmpty(
			$this->get_errors_from_rule_check(
				$this->get_test_markup( 'element_with_aria-hidden_false' )
			)
		);
	}

	/**
	 * Tests that aria-hidden="true" is ignored when the element is a spacer block.
	 */
	public function test_edac_rule_aria_hidden_skips_spacer_block() {

		$this->assertEmpty(
			$this->get_errors_from_rule_check(
				$this->get_test_markup( 'element_that_is_wp-block-spacer' )
			)
		);
	}
	/**
	 * Tests that aria-hidden="true" is ignored when the element is flagged presentational.
	 */
	public function test_edac_rule_aria_hidden_skips_presentational() {

		$this->assertEmpty(
			$this->get_errors_from_rule_check(
				$this->get_test_markup( 'image_that_is_presentational' )
			)
		);
	}

	/**
	 * Tests that aria-hidden="true" is allowed when the parent has an aria-label that is not empty.
	 */
	public function test_edac_rule_aria_hidden_allows_hidden_with_parent_that_has_label() {

		$button_with_aria_label    = $this->get_test_markup( 'button_with_aria-label' );
		$button_without_aria_label = preg_replace( '/aria-label="[^"]+"/', '', $button_with_aria_label );

		$this->assertEmpty( $this->get_errors_from_rule_check( $button_with_aria_label ) );
		$this->assertNotEmpty( $this->get_errors_from_rule_check( $button_without_aria_label ) );

		$link_with_aria_label    = $this->get_test_markup( 'link_with_aria-label' );
		$link_without_aria_label = preg_replace( '/aria-label="[^"]+"/', '', $button_with_aria_label );

		$this->assertEmpty( $this->get_errors_from_rule_check( $link_with_aria_label ) );
		$this->assertNotEmpty( $this->get_errors_from_rule_check( $link_without_aria_label ) );
	}

	/**
	 * Tests that aria-hidden="true" is allowed when the parent has a screen reader text.
	 */
	public function test_edac_rule_aria_hidden_allows_hidden_with_parent_that_has_screen_reader_text() {

		$this->assertEmpty(
			$this->get_errors_from_rule_check(
				$this->get_test_markup( 'button_with_screen_reader_text' )
			)
		);

		$this->assertEmpty(
			$this->get_errors_from_rule_check(
				$this->get_test_markup( 'link_with_screen_reader_text' )
			)
		);
	}

	/**
	 * Tests that aria-hidden="true" is allowed when the parent has visible text.
	 */
	public function test_edac_rule_aria_hidden_allows_hidden_with_parent_that_has_visible_text() {

		$this->assertEmpty(
			$this->get_errors_from_rule_check(
				$this->get_test_markup( 'button_with_visible_text' )
			)
		);

		$this->assertEmpty(
			$this->get_errors_from_rule_check(
				$this->get_test_markup( 'link_with_visible_text' )
			)
		);
	}

	/**
	 * Wrapper to generate dom objects that match the shape of the object in the plugin.
	 *
	 * @param string $html_string HTML string.
	 * @return EDAC_Dom
	 */
	private function get_DOM( string $html_string = '' ) {
		$lowercase         = true;
		$force_tags_closed = true;
		$target_charset    = DEFAULT_TARGET_CHARSET;
		$strip_rn          = true;
		$default_br_text   = DEFAULT_BR_TEXT;
		$default_span_text = DEFAULT_SPAN_TEXT;

		$dom = new EDAC_Dom(
			$html_string,
			$lowercase,
			$force_tags_closed,
			$target_charset,
			$strip_rn,
			$default_br_text,
			$default_span_text
		);
		return $dom;
	}

	/**
	 * Wrapper to produce $dom nodes and run the rule check.
	 *
	 * @param string $html_string HTML string.
	 * @return array
	 */
	private function get_errors_from_rule_check( string $html_string = '' ) {
		$dom             = $this->get_DOM( $html_string );
		$content['html'] = $dom;
		$post            = $this->factory()->post->create_and_get();

		return edac_rule_aria_hidden( $content, $post );
	}
}
