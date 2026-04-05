<?php
/**
 * Class MapTypeSlugTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_map_type_slug() function.
 */
class MapTypeSlugTest extends WP_UnitTestCase {

	/**
	 * Tests edac_map_type_slug in the default (forward) direction.
	 *
	 * @dataProvider map_type_slug_forward_data
	 *
	 * @param string $type     The type slug to map.
	 * @param string $expected The expected mapped result.
	 */
	public function test_edac_map_type_slug_forward( $type, $expected ) {
		$this->assertSame( $expected, edac_map_type_slug( $type ) );
	}

	/**
	 * Data provider for test_edac_map_type_slug_forward.
	 */
	public function map_type_slug_forward_data() {
		return [
			'problem maps to error'            => [
				'type'     => 'problem',
				'expected' => 'error',
			],
			'needs_review maps to warning'     => [
				'type'     => 'needs_review',
				'expected' => 'warning',
			],
			'error passes through unchanged'   => [
				'type'     => 'error',
				'expected' => 'error',
			],
			'warning passes through unchanged' => [
				'type'     => 'warning',
				'expected' => 'warning',
			],
			'unknown slug passes through'      => [
				'type'     => 'unknown_type',
				'expected' => 'unknown_type',
			],
			'empty string passes through'      => [
				'type'     => '',
				'expected' => '',
			],
		];
	}

	/**
	 * Tests edac_map_type_slug in the reverse direction.
	 *
	 * @dataProvider map_type_slug_reverse_data
	 *
	 * @param string $type     The type slug to map.
	 * @param string $expected The expected mapped result.
	 */
	public function test_edac_map_type_slug_reverse( $type, $expected ) {
		$this->assertSame( $expected, edac_map_type_slug( $type, true ) );
	}

	/**
	 * Data provider for test_edac_map_type_slug_reverse.
	 */
	public function map_type_slug_reverse_data() {
		return [
			'error maps to problem'            => [
				'type'     => 'error',
				'expected' => 'problem',
			],
			'warning maps to needs_review'     => [
				'type'     => 'warning',
				'expected' => 'needs_review',
			],
			'problem passes through unchanged' => [
				'type'     => 'problem',
				'expected' => 'problem',
			],
			'needs_review passes through'      => [
				'type'     => 'needs_review',
				'expected' => 'needs_review',
			],
			'unknown slug passes through'      => [
				'type'     => 'unknown_type',
				'expected' => 'unknown_type',
			],
			'empty string passes through'      => [
				'type'     => '',
				'expected' => '',
			],
		];
	}
}
