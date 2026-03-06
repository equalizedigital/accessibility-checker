<?php
/**
 * Class RemoveElementWithValueTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_remove_element_with_value() function.
 */
class RemoveElementWithValueTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_remove_element_with_value function.
	 *
	 * @dataProvider remove_element_with_value_data
	 *
	 * @param array  $items    The multi-dimensional array to process.
	 * @param string $key      The key to check in each sub-array.
	 * @param string $value    The value to match for removal.
	 * @param array  $expected The expected result after removal.
	 */
	public function test_edac_remove_element_with_value( $items, $key, $value, $expected ) {
		$this->assertSame(
			$expected,
			edac_remove_element_with_value( $items, $key, $value )
		);
	}

	/**
	 * Data provider for test_edac_remove_element_with_value.
	 */
	public function remove_element_with_value_data() {
		return [
			'remove single matching element'    => [
				'items'    => [
					[
						'id'   => 1,
						'name' => 'John',
					],
					[
						'id'   => 2,
						'name' => 'Jane',
					],
					[
						'id'   => 3,
						'name' => 'Bob',
					],
				],
				'key'      => 'id',
				'value'    => 2,
				'expected' => [
					0 => [
						'id'   => 1,
						'name' => 'John',
					],
					2 => [
						'id'   => 3,
						'name' => 'Bob',
					],
				],
			],
			'remove multiple matching elements' => [
				'items'    => [
					[
						'status' => 'active',
						'name'   => 'User1',
					],
					[
						'status' => 'inactive',
						'name'   => 'User2',
					],
					[
						'status' => 'active',
						'name'   => 'User3',
					],
					[
						'status' => 'inactive',
						'name'   => 'User4',
					],
				],
				'key'      => 'status',
				'value'    => 'inactive',
				'expected' => [
					0 => [
						'status' => 'active',
						'name'   => 'User1',
					],
					2 => [
						'status' => 'active',
						'name'   => 'User3',
					],
				],
			],
			'no matching elements'              => [
				'items'    => [
					[
						'type'  => 'post',
						'title' => 'Post 1',
					],
					[
						'type'  => 'page',
						'title' => 'Page 1',
					],
				],
				'key'      => 'type',
				'value'    => 'media',
				'expected' => [
					[
						'type'  => 'post',
						'title' => 'Post 1',
					],
					[
						'type'  => 'page',
						'title' => 'Page 1',
					],
				],
			],
			'empty array'                       => [
				'items'    => [],
				'key'      => 'id',
				'value'    => 1,
				'expected' => [],
			],
			'remove all elements'               => [
				'items'    => [
					[ 'category' => 'test' ],
					[ 'category' => 'test' ],
					[ 'category' => 'test' ],
				],
				'key'      => 'category',
				'value'    => 'test',
				'expected' => [],
			],
			'string value matching'             => [
				'items'    => [
					[
						'role' => 'admin',
						'user' => 'user1',
					],
					[
						'role' => 'editor',
						'user' => 'user2',
					],
					[
						'role' => 'admin',
						'user' => 'user3',
					],
				],
				'key'      => 'role',
				'value'    => 'admin',
				'expected' => [
					1 => [
						'role' => 'editor',
						'user' => 'user2',
					],
				],
			],
		];
	}

	/**
	 * Ensures missing keys do not emit notices.
	 */
	public function test_edac_remove_element_with_value_missing_key() {
		$items = [
			[
				'id'   => 1,
				'name' => 'Alice',
			],
			[
				'name' => 'MissingId',
			],
		];

		$handler = static function ( ...$args ) {
			unset( $args );
			throw new \RuntimeException( 'Unexpected PHP warning or notice in helper call.' );
		};

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Intentional in test to fail on notices/warnings.
		set_error_handler( $handler );

		try {
			$result = edac_remove_element_with_value( $items, 'id', 2 );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( $items, $result );
	}
}
