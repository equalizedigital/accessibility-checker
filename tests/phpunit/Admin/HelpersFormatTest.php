<?php
/**
 * Class HelpersFormatTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Helpers;

/**
 * Test cases for EDAC\Admin\Helpers formatting methods.
 */
class HelpersFormatTest extends WP_UnitTestCase {

	/**
	 * Tests the format_number method.
	 *
	 * @dataProvider format_number_data
	 *
	 * @param mixed $number     The number to format.
	 * @param int   $precision  The precision (decimals).
	 * @param mixed $expected   The expected result.
	 */
	public function test_format_number( $number, $precision, $expected ) {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'get_locale' ) ) {
			$this->markTestSkipped( 'WordPress get_locale function not available in test environment.' );
		}

		$result = Helpers::format_number( $number, $precision );

		// For numeric results, we check if NumberFormatter is available.
		if ( is_numeric( $expected ) && class_exists( 'NumberFormatter' ) ) {
			// When NumberFormatter is available, we expect a formatted string.
			$this->assertIsString( $result );
		} else {
			$this->assertEquals( $expected, $result );
		}
	}

	/**
	 * Data provider for test_format_number.
	 */
	public function format_number_data() {
		return [
			'integer number'                              => [
				'number'    => 1234,
				'precision' => 0,
				'expected'  => 1234, // Will be formatted if NumberFormatter available.
			],
			'float number with precision'                 => [
				'number'    => 1234.567,
				'precision' => 2,
				'expected'  => 1234.567,
			],
			'large number'                                => [
				'number'    => 1234567,
				'precision' => 0,
				'expected'  => 1234567,
			],
			'non-numeric string should be returned as-is' => [
				'number'    => 'not a number',
				'precision' => 0,
				'expected'  => 'not a number',
			],
			'empty string should be returned as-is'       => [
				'number'    => '',
				'precision' => 0,
				'expected'  => '',
			],
			'zero value'                                  => [
				'number'    => 0,
				'precision' => 0,
				'expected'  => 0,
			],
			'negative number'                             => [
				'number'    => -1234,
				'precision' => 0,
				'expected'  => -1234,
			],
		];
	}

	/**
	 * Tests the format_percentage method.
	 *
	 * @dataProvider format_percentage_data
	 *
	 * @param mixed $number     The number to format as percentage.
	 * @param int   $precision  The precision (decimals).
	 * @param mixed $expected   The expected result type or pattern.
	 */
	public function test_format_percentage( $number, $precision, $expected ) {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'get_locale' ) ) {
			$this->markTestSkipped( 'WordPress get_locale function not available in test environment.' );
		}

		$result = Helpers::format_percentage( $number, $precision );

		if ( is_numeric( $number ) ) {
			// For numeric inputs, expect a string result with % symbol.
			$this->assertIsString( $result );
			$this->assertStringContainsString( '%', $result );
		} else {
			// For non-numeric inputs, expect the input returned as-is.
			$this->assertEquals( $expected, $result );
		}
	}

	/**
	 * Data provider for test_format_percentage.
	 */
	public function format_percentage_data() {
		return [
			'decimal percentage (0.5 = 50%)'              => [
				'number'    => 0.5,
				'precision' => 2,
				'expected'  => 'percentage_string',
			],
			'whole number percentage (50 = 50%)'          => [
				'number'    => 50,
				'precision' => 2,
				'expected'  => 'percentage_string',
			],
			'large percentage over 100'                   => [
				'number'    => 150,
				'precision' => 1,
				'expected'  => 'percentage_string',
			],
			'zero percentage'                             => [
				'number'    => 0,
				'precision' => 2,
				'expected'  => 'percentage_string',
			],
			'negative percentage'                         => [
				'number'    => -0.25,
				'precision' => 2,
				'expected'  => 'percentage_string',
			],
			'non-numeric string should be returned as-is' => [
				'number'    => 'not a number',
				'precision' => 2,
				'expected'  => 'not a number',
			],
			'empty string should be returned as-is'       => [
				'number'    => '',
				'precision' => 2,
				'expected'  => '',
			],
		];
	}

	/**
	 * Tests the get_option_as_array method.
	 *
	 * @dataProvider get_option_as_array_data
	 *
	 * @param string $option_name The option name to test.
	 * @param mixed  $option_value The value to set for the option.
	 * @param array  $expected     The expected result.
	 */
	public function test_get_option_as_array( $option_name, $option_value, $expected ) {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Set up the option value.
		update_option( $option_name, $option_value );

		$result = Helpers::get_option_as_array( $option_name );
		$this->assertSame( $expected, $result );

		// Clean up.
		delete_option( $option_name );
	}

	/**
	 * Data provider for test_get_option_as_array.
	 */
	public function get_option_as_array_data() {
		return [
			'valid array option'                       => [
				'option_name'  => 'test_array_option',
				'option_value' => [
					'key1' => 'value1',
					'key2' => 'value2',
				],
				'expected'     => [
					'key1' => 'value1',
					'key2' => 'value2',
				],
			],
			'empty array option'                       => [
				'option_name'  => 'test_empty_array',
				'option_value' => [],
				'expected'     => [],
			],
			'string option should return empty array'  => [
				'option_name'  => 'test_string_option',
				'option_value' => 'string value',
				'expected'     => [],
			],
			'integer option should return empty array' => [
				'option_name'  => 'test_int_option',
				'option_value' => 123,
				'expected'     => [],
			],
			'null option should return empty array'    => [
				'option_name'  => 'test_null_option',
				'option_value' => null,
				'expected'     => [],
			],
			'non-existent option should return empty array' => [
				'option_name'  => 'non_existent_option_' . uniqid(),
				'option_value' => null, // Won't be set.
				'expected'     => [],
			],
		];
	}

	/**
	 * Test the format_date method with basic functionality.
	 */
	public function test_format_date_basic() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'get_locale' ) ) {
			$this->markTestSkipped( 'WordPress get_locale function not available in test environment.' );
		}

		// Test with a standard date string.
		$date_string = '2023-12-25 14:30:00';
		$result      = Helpers::format_date( $date_string );

		// Should return a formatted string.
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test the format_date method with time inclusion.
	 */
	public function test_format_date_with_time() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'get_locale' ) ) {
			$this->markTestSkipped( 'WordPress get_locale function not available in test environment.' );
		}

		$date_string         = '2023-12-25 14:30:00';
		$result_without_time = Helpers::format_date( $date_string, false );
		$result_with_time    = Helpers::format_date( $date_string, true );

		// Both should be strings.
		$this->assertIsString( $result_without_time );
		$this->assertIsString( $result_with_time );

		// Results should be different when including/excluding time.
		$this->assertNotEquals( $result_without_time, $result_with_time );
	}
}
