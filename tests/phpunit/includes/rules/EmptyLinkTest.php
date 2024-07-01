<?php
/**
 * Tests the empty_link rule.
 *
 * @package Accessibility_Checker
 */

/**
 * Some test cases for the empty_link rule.
 *
 * @group rules
 */
class EmptyLinkTest extends WP_UnitTestCase {

	/**
	 * Test that a link with no content, no aria-label, no title, no id, no name, and no alt text throws an error.
	 */
	public function test_empty_link() {

		$expected_error = '<a href="http://example.com"></a>';

		$not_expected_error = '<a href="http://example.com">Some content</a>';


		$dom = new EDAC_Dom();
		$dom->load( $expected_error . PHP_EOL . $not_expected_error );

		$errors = edac_rule_empty_link( [ 'html' => $dom ], null );

		$this->assertContains( $expected_error, $errors );
		$this->assertNotContains( $not_expected_error, $errors );
	}

	/**
	 * Test that a link with no content, no aria-label, no title, no id, no name, and no alt text throws an error.
	 */
	public function test_empty_link_with_img() {
		$expected_errors = [
			'<a href="http://example.com"><img src="http://example.com/image.jpg" alt=""></a>',
			'<a href="http://example.com"><img src="http://example.com/image.jpg" alt=" "></a>',
		];

		$not_expected_error = '<a href="http://example.com"><img src="http://example.com/image.jpg" alt="A filled alt"></a>';

		$dom = new EDAC_Dom();
		$dom->load( implode( PHP_EOL, $expected_errors ) . PHP_EOL . $not_expected_error );

		$errors = edac_rule_empty_link( [ 'html' => $dom ], null );

		foreach ( $expected_errors as $expected_error ) {
			$this->assertContains( $expected_error, $errors );
		}

		$this->assertNotContains( $not_expected_error, $errors );
	}

	/**
	 * Test that a link with no content, no aria-label, no title, no id, no name, and no alt text throws an error.
	 */
	public function test_empty_link_with_input() {

		$expected_errors = [
			'<a href="http://example.com"><input type="text"></a>',
			'<a href="http://example.com"><input type="text" value=""></a>',
			'<a href="http://example.com"><input type="text" value=" "></a>', // whitespace should be stripped, this is still empty.
		];

		$not_expected_error = '<a href="http://example.com"><input type="text" value="Some value"></a>';

		$dom = new EDAC_Dom();
		$dom->load( implode( PHP_EOL, $expected_errors ) . PHP_EOL . $not_expected_error );

		$errors = edac_rule_empty_link( [ 'html' => $dom ], null );

		foreach ( $expected_errors as $expected_error ) {
			$this->assertContains( $expected_error, $errors );
		}
		$this->assertNotContains( $not_expected_error, $errors );
	}

	/**
	 * Test that a link with no content, no aria-label, no title, no id, no name, and no alt text throws an error.
	 */
	public function test_empty_link_with_i() {
		$expected_errors = [
			'<a href="http://example.com"><i></i></a>',
			'<a href="http://example.com"><i title=""></i></a>',
			'<a href="http://example.com"><i aria-label=""></i></a>',
			'<a href="http://example.com"><i title=" "></i></a>', // whitespace should be stripped, this is still empty.
			'<a href="http://example.com"><i aria-label=" "></i></a>', // whitespace should be stripped, this is still empty.
		];

		$not_expected_errors = [
			'<a href="http://example.com"><i title="Some title"></i></a>',
			'<a href="http://example.com"><i aria-label="A label"></i></a>',
			'<a href="http://example.com"><i title="Some title" aria-label="A label"></i></a>',
		];

		$dom = new EDAC_Dom();
		$dom->load( implode( PHP_EOL, $expected_errors ) . PHP_EOL . implode( PHP_EOL, $not_expected_errors ) );

		$errors = edac_rule_empty_link( [ 'html' => $dom ], null );

		foreach ( $expected_errors as $expected_error ) {
			$this->assertContains( $expected_error, $errors );
		}
		foreach ( $not_expected_errors as $not_expected_error ) {
			$this->assertNotContains( $not_expected_error, $errors );
		}
	}
}
