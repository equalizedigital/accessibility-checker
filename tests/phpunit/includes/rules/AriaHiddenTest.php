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
	 * Wrapper to produce $dom nodes and run the rule check.
	 *
	 * @param string $html_string HTML string.
	 * @return array
	 */
	private function get_errors_from_rule_check( string $html_string = '' ): array {
		$dom             = str_get_html( $html_string );
		$content['html'] = $dom;
		$post            = $this->factory()->post->create_and_get();

		return edac_rule_aria_hidden( $content, $post );
	}

	/**
	 * Tests that the screen reader text classes are detected.
	 *
	 * @dataProvider screen_reader_test_classes
	 *
	 * @param string $sibling_class class name.
	 * @param bool   $pass pass or fail.
	 */
	public function test_screen_reader_text_classes( string $sibling_class, bool $pass ) {
		$markup   = <<<EOT
			<div class="parent">
				<div aria-hidden="true"></div>
				<div class="$sibling_class">Some text maybe for screenreaders</div>
			</div>;
		EOT;
		$dom      = str_get_html( $markup );
		$siblings = $dom->find( '.parent > *' );
		$this->assertEquals( $pass, edac_rule_aria_hidden_siblings_are_screen_reader_text_elements( $siblings ) );
	}

	/**
	 * Test elements with aria-label that are not links or buttons still error.
	 */
	public function test_parent_with_label_that_is_not_link_or_button_errors() {

		$test_elements = array( 'div', 'span', 'section' );
		foreach ( $test_elements as $element ) {
			$markup = "<$element aria-label='label'><div aria-hidden='true'></div></$element>";
			$dom    = str_get_html( $markup );
			$errors = $this->get_errors_from_rule_check( $dom );
			$this->assertNotEmpty( $errors );
		}
	}

	/**
	 * Test that parents with likely visible text pass.
	 */
	public function test_parent_with_likely_visible_text_passes() {
		$link_dom     = str_get_html( $this->get_test_markup( 'link_with_visible_text' ) );
		$link_element = $link_dom->find( '[aria-hidden="true"]' );
		$link_parent  = $link_element[0]->parent();
		$this->assertNotEmpty( edac_rule_aria_hidden_strip_markup_and_return_text( $link_parent ) );

		$button_dom     = str_get_html( $this->get_test_markup( 'button_with_visible_text' ) );
		$button_element = $button_dom->find( '[aria-hidden="true"]' );
		$button_parent  = $button_element[0]->parent();
		$this->assertNotEmpty( edac_rule_aria_hidden_strip_markup_and_return_text( $button_parent ) );

		// pass the 2 doms through the rule check as well to validate the whole process.
		$this->assertEmpty( $this->get_errors_from_rule_check( $link_dom ) );
		$this->assertEmpty( $this->get_errors_from_rule_check( $button_dom ) );
	}

	/**
	 * Collection of different markup to use for test cases.
	 *
	 * @param string $type a key to the array of markup fragments.
	 * @return string
	 */
	private function get_test_markup( string $type = '' ): string {
		$markup_fragments = array(
			'element_with_aria-hidden'          => '<div aria-hidden="true"></div>',
			'element_with_aria-hidden_false'    => '<div aria-hidden="false"></div>',
			'element_that_is_wp-block-spacer'   => '<div aria-hidden="true" class="wp-block-spacer"></div>',
			'button_with_aria-label'            => <<<EOT
				<button type="button" aria-haspopup="true" aria-label="Open menu" class="components-button wp-block-navigation__responsive-container-open" inert="true">
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				</button>
			EOT,
			'link_with_aria-label'              => <<<EOT
				<a href="http://example.com" aria-label="label">
					    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				</a>
			EOT,
			'link_with_screen_reader_text'      => <<<EOT
				<a href="/about" >
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				<span class="sr-only">About Us</span>
			EOT,
			'button_with_screen_reader_text'    => <<<EOT
				<button type="button" aria-haspopup="true" class="components-button wp-block-navigation__responsive-container-open" inert="true">
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				<span class="sr-only">Open menu</span>
				</button>
			EOT,
			'link_with_visible_text'            => <<<EOT
				<a href="/about" >
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				About Us
				</a>
			EOT,
			'button_with_visible_text'          => <<<EOT
				<button type="button" aria-haspopup="true" class="components-button wp-block-navigation__responsive-container-open" inert="true">
				    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect><rect x="4" y="15" width="16" height="1.5"></rect></svg>
				Menu
				</button>
			EOT,
			'element_with_aria_label_on_parent' => <<<EOT
				<div aria-label="label">
					<div aria-hidden="true"></div>
				</div>
			EOT,
			'image_that_is_presentational'      => '<img src="http://example.com/image.jpg" aria-hidden="true" role="presentation" />',
		);
		return $markup_fragments[ $type ] ?? '';
	}

	/**
	 * Tests that the screen reader text classes are detected.
	 *
	 * @dataProvider screen_reader_test_classes
	 */
	public function screen_reader_test_classes(): array {
		return array(
			array(
				'sibling_class' => 'screen-reader-text',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'sr-only',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'show-for-sr',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'visuallyhidden',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'visually-hidden',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'hidden-visually',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'invisible',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'accessibly-hidden',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'hide',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'hidden',
				'pass'          => true,
			),
			array(
				'sibling_class' => 'not-screen-reader-text',
				'pass'          => false,
			),
			array(
				'sibling_class' => 'hide-for-sr',
				'pass'          => false,
			),
			array(
				'sibling_class' => 'anotherClass anything-else and-anotherClass',
				'pass'          => false,
			),
		);
	}
}
