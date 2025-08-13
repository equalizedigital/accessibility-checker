<?php
/**
 * Class FilterByValueTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_filter_by_value() function.
 */
class FilterByValueTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_filter_by_value function.
	 *
	 * @dataProvider filter_by_value_data
	 *
	 * @param array  $items    The multi-dimensional array to filter.
	 * @param string $index    The index to check in each sub-array.
	 * @param string $value    The value to match for filtering.
	 * @param array  $expected The expected filtered result.
	 */
	public function test_edac_filter_by_value( $items, $index, $value, $expected ) {
		$this->assertSame(
			$expected,
			edac_filter_by_value( $items, $index, $value )
		);
	}

	/**
	 * Data provider for test_edac_filter_by_value.
	 */
	public function filter_by_value_data() {
		return [
			'filter single matching element'       => [
				'items'    => [
					[
						'id'     => 1,
						'status' => 'active',
					],
					[
						'id'     => 2,
						'status' => 'inactive',
					],
					[
						'id'     => 3,
						'status' => 'pending',
					],
				],
				'index'    => 'status',
				'value'    => 'active',
				'expected' => [
					[
						'id'     => 1,
						'status' => 'active',
					],
				],
			],
			'filter multiple matching elements'    => [
				'items'    => [
					[
						'type'  => 'post',
						'title' => 'Post 1',
					],
					[
						'type'  => 'page',
						'title' => 'Page 1',
					],
					[
						'type'  => 'post',
						'title' => 'Post 2',
					],
					[
						'type'  => 'media',
						'title' => 'Image 1',
					],
				],
				'index'    => 'type',
				'value'    => 'post',
				'expected' => [
					[
						'type'  => 'post',
						'title' => 'Post 1',
					],
					[
						'type'  => 'post',
						'title' => 'Post 2',
					],
				],
			],
			'no matching elements'                 => [
				'items'    => [
					[
						'category' => 'news',
						'title'    => 'Article 1',
					],
					[
						'category' => 'blog',
						'title'    => 'Article 2',
					],
				],
				'index'    => 'category',
				'value'    => 'events',
				'expected' => [],
			],
			'empty array'                          => [
				'items'    => [],
				'index'    => 'id',
				'value'    => 1,
				'expected' => [],
			],
			'all elements match'                   => [
				'items'    => [
					[
						'role' => 'user',
						'name' => 'John',
					],
					[
						'role' => 'user',
						'name' => 'Jane',
					],
					[
						'role' => 'user',
						'name' => 'Bob',
					],
				],
				'index'    => 'role',
				'value'    => 'user',
				'expected' => [
					[
						'role' => 'user',
						'name' => 'John',
					],
					[
						'role' => 'user',
						'name' => 'Jane',
					],
					[
						'role' => 'user',
						'name' => 'Bob',
					],
				],
			],
			'numeric value matching'               => [
				'items'    => [
					[
						'priority' => 1,
						'task'     => 'High priority task',
					],
					[
						'priority' => 2,
						'task'     => 'Medium priority task',
					],
					[
						'priority' => 1,
						'task'     => 'Another high priority',
					],
				],
				'index'    => 'priority',
				'value'    => 1,
				'expected' => [
					[
						'priority' => 1,
						'task'     => 'High priority task',
					],
					[
						'priority' => 1,
						'task'     => 'Another high priority',
					],
				],
			],
			'string value with special characters' => [
				'items'    => [
					[
						'url'  => 'example.com',
						'type' => 'external',
					],
					[
						'url'  => '/internal-page',
						'type' => 'internal',
					],
					[
						'url'  => 'mailto:test@example.com',
						'type' => 'email',
					],
				],
				'index'    => 'type',
				'value'    => 'external',
				'expected' => [
					[
						'url'  => 'example.com',
						'type' => 'external',
					],
				],
			],
		];
	}
}
