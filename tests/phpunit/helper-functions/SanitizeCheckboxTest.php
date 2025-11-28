<?php
/**
 * Class SanitizeCheckboxTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_sanitize_checkbox() function.
 */
class SanitizeCheckboxTest extends WP_UnitTestCase {

	/**
	 * Test sanitize checkbox with integer 1.
	 */
	public function test_sanitize_checkbox_with_integer_1() {
		$result = edac_sanitize_checkbox( 1 );
		$this->assertSame( 1, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with string '1'.
	 */
	public function test_sanitize_checkbox_with_string_1() {
		$result = edac_sanitize_checkbox( '1' );
		$this->assertSame( 1, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with integer 0.
	 */
	public function test_sanitize_checkbox_with_integer_0() {
		$result = edac_sanitize_checkbox( 0 );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with string '0'.
	 */
	public function test_sanitize_checkbox_with_string_0() {
		$result = edac_sanitize_checkbox( '0' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with empty string.
	 */
	public function test_sanitize_checkbox_with_empty_string() {
		$result = edac_sanitize_checkbox( '' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with null.
	 */
	public function test_sanitize_checkbox_with_null() {
		$result = edac_sanitize_checkbox( null );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with boolean true.
	 */
	public function test_sanitize_checkbox_with_boolean_true() {
		$result = edac_sanitize_checkbox( true );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with boolean false.
	 */
	public function test_sanitize_checkbox_with_boolean_false() {
		$result = edac_sanitize_checkbox( false );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with string 'true'.
	 */
	public function test_sanitize_checkbox_with_string_true() {
		$result = edac_sanitize_checkbox( 'true' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with string 'false'.
	 */
	public function test_sanitize_checkbox_with_string_false() {
		$result = edac_sanitize_checkbox( 'false' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with string 'on' (common checkbox value).
	 */
	public function test_sanitize_checkbox_with_string_on() {
		$result = edac_sanitize_checkbox( 'on' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with string 'yes'.
	 */
	public function test_sanitize_checkbox_with_string_yes() {
		$result = edac_sanitize_checkbox( 'yes' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with integer 2.
	 */
	public function test_sanitize_checkbox_with_integer_2() {
		$result = edac_sanitize_checkbox( 2 );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with string '2'.
	 */
	public function test_sanitize_checkbox_with_string_2() {
		$result = edac_sanitize_checkbox( '2' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with negative integer.
	 */
	public function test_sanitize_checkbox_with_negative_integer() {
		$result = edac_sanitize_checkbox( -1 );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with random string.
	 */
	public function test_sanitize_checkbox_with_random_string() {
		$result = edac_sanitize_checkbox( 'random' );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with array.
	 */
	public function test_sanitize_checkbox_with_array() {
		$result = edac_sanitize_checkbox( array( 1 ) );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize checkbox with object.
	 */
	public function test_sanitize_checkbox_with_object() {
		$obj    = new stdClass();
		$result = edac_sanitize_checkbox( $obj );
		$this->assertSame( 0, $result );
		$this->assertIsInt( $result );
	}
}

