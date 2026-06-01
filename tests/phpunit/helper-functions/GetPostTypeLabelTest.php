<?php
/**
 * Tests for the edac_get_post_type_label helper.
 *
 * @package Accessibility_Checker
 * @copyright 2025 Equalize Digital
 * @license GPL-2.0+
 * @since 1.37.0
 */

/**
 * Tests for edac_get_post_type_label.
 *
 * @covers ::edac_get_post_type_label
 * @since 1.37.0
 */
class GetPostTypeLabelTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_get_post_type_label function for registered post types.
	 */
	public function test_edac_get_post_type_label_registered_post_type(): void {
		register_post_type(
			'edac_sample_type',
			[
				'labels' => [
					'name' => 'Sample Items',
				],
				'public' => true,
			]
		);

		$this->assertSame( 'Sample Items', edac_get_post_type_label( 'edac_sample_type' ) );

		unregister_post_type( 'edac_sample_type' );
	}

	/**
	 * Tests the edac_get_post_type_label function for unregistered post types.
	 *
	 * @dataProvider provider_unregistered_post_types
	 *
	 * @param string $post_type The post type slug to test.
	 * @param string $expected  The expected label.
	 */
	public function test_edac_get_post_type_label_unregistered_post_type( string $post_type, string $expected ): void {
		$this->assertSame( $expected, edac_get_post_type_label( $post_type ) );
	}

	/**
	 * Data provider for test_edac_get_post_type_label_unregistered_post_type.
	 *
	 * @return array
	 */
	public function provider_unregistered_post_types(): array {
		return [
			'standard slug'       => [ 'custom-type', 'Custom-type' ],
			'slug with space'     => [ 'custom type', 'Customtype' ],
			'slug with uppercase' => [ 'CustomType', 'Customtype' ],
			'numeric slug'        => [ '123', '123' ],
		];
	}

	/**
	 * Tests the edac_get_post_type_label function for empty input.
	 */
	public function test_edac_get_post_type_label_empty_input(): void {
		$this->assertSame( '', edac_get_post_type_label( '' ) );
	}
}
