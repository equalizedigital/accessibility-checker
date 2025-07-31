<?php
/**
 * Class HelpersArrayToSqlTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Helpers;

/**
 * Test cases for EDAC\Admin\Helpers::array_to_sql_safe_list() method.
 */
class HelpersArrayToSqlTest extends WP_UnitTestCase {

	/**
	 * Tests the array_to_sql_safe_list method with various inputs.
	 *
	 * @dataProvider array_to_sql_safe_list_data
	 *
	 * @param array  $items    The array of items to convert.
	 * @param string $expected The expected result pattern.
	 */
	public function test_array_to_sql_safe_list( $items, $expected ) {
		$result = Helpers::array_to_sql_safe_list( $items );

		// The result should be a string.
		$this->assertIsString( $result );

		// Check specific patterns based on expected type.
		switch ( $expected ) {
			case 'empty':
				$this->assertEmpty( $result );
				break;
			case 'single_quoted':
				$this->assertStringStartsWith( "'", $result );
				$this->assertStringEndsWith( "'", $result );
				$this->assertEquals( 1, substr_count( $result, ',' ) + 1 ); // No commas = 1 item.
				break;
			case 'multiple_quoted':
				$this->assertStringContainsString( ',', $result );
				$this->assertStringStartsWith( "'", $result );
				$this->assertStringEndsWith( "'", $result );
				break;
			case 'quoted_with_numbers':
				$this->assertStringContainsString( "'", $result );
				break;
		}
	}

	/**
	 * Data provider for test_array_to_sql_safe_list.
	 */
	public function array_to_sql_safe_list_data() {
		return [
			'empty array'                   => [
				'items'    => [],
				'expected' => 'empty',
			],
			'single string item'            => [
				'items'    => [ 'test' ],
				'expected' => 'single_quoted',
			],
			'multiple string items'         => [
				'items'    => [ 'item1', 'item2', 'item3' ],
				'expected' => 'multiple_quoted',
			],
			'mixed string and number items' => [
				'items'    => [ 'string', 123, 'another' ],
				'expected' => 'quoted_with_numbers',
			],
			'items with special characters' => [
				'items'    => [ "item'with'quotes", 'item"with"doublequotes', 'item;with;semicolon' ],
				'expected' => 'multiple_quoted',
			],
			'numeric items'                 => [
				'items'    => [ 1, 2, 3 ],
				'expected' => 'quoted_with_numbers',
			],
		];
	}

	/**
	 * Test the array_to_sql_safe_list method with potential SQL injection strings.
	 */
	public function test_array_to_sql_safe_list_sql_injection_protection() {
		$malicious_items = [
			"'; DROP TABLE users; --",
			"1' OR '1'='1",
			"admin'; DELETE FROM posts; --",
		];

		$result = Helpers::array_to_sql_safe_list( $malicious_items );

		// The result should be a string.
		$this->assertIsString( $result );

		// Should contain quoted items separated by commas.
		$this->assertStringContainsString( ',', $result );

		// Should not contain unescaped dangerous SQL sequences that could break out of quotes.
		// The dangerous content should be safely contained within single quotes.
		$this->assertStringNotContainsString( "' OR '1'='1", $result );
		
		// But the escaped content should still be present (safely quoted).
		$this->assertStringContainsString( "\\'; DROP", $result ); // Should be escaped.
		$this->assertStringContainsString( "\\'; DELETE", $result ); // Should be escaped.
		$this->assertStringContainsString( "\\' OR \\'", $result ); // Should be escaped.

		// All items should be properly quoted.
		$parts = explode( ',', $result );
		foreach ( $parts as $part ) {
			$trimmed = trim( $part );
			$this->assertStringStartsWith( "'", $trimmed );
			$this->assertStringEndsWith( "'", $trimmed );
		}
	}

	/**
	 * Test the array_to_sql_safe_list method ensures proper comma separation.
	 */
	public function test_array_to_sql_safe_list_comma_separation() {
		$items  = [ 'a', 'b', 'c', 'd' ];
		$result = Helpers::array_to_sql_safe_list( $items );

		// Should have exactly 3 commas for 4 items.
		$this->assertEquals( 3, substr_count( $result, ',' ) );

		// Split by comma and verify we get 4 parts.
		$parts = explode( ',', $result );
		$this->assertCount( 4, $parts );

		// Each part should be quoted.
		foreach ( $parts as $part ) {
			$trimmed = trim( $part );
			$this->assertStringStartsWith( "'", $trimmed );
			$this->assertStringEndsWith( "'", $trimmed );
		}
	}

	/**
	 * Test the array_to_sql_safe_list method with empty strings and null values.
	 */
	public function test_array_to_sql_safe_list_edge_cases() {
		$items  = [ '', null, 'valid', 0 ];
		$result = Helpers::array_to_sql_safe_list( $items );

		// Should be a string with commas.
		$this->assertIsString( $result );
		$this->assertStringContainsString( ',', $result );

		// Should have 3 commas for 4 items.
		$this->assertEquals( 3, substr_count( $result, ',' ) );

		// All parts should be quoted.
		$parts = explode( ',', $result );
		$this->assertCount( 4, $parts );

		foreach ( $parts as $part ) {
			$trimmed = trim( $part );
			$this->assertStringStartsWith( "'", $trimmed );
			$this->assertStringEndsWith( "'", $trimmed );
		}
	}
}
