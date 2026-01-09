<?php
/**
 * Class GetPostTypeLabelTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test case for edac_get_post_type_label.
 */
class GetPostTypeLabelTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_get_post_type_label function for registered post types.
	 */
	public function test_edac_get_post_type_label_registered_post_type() {
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
	 */
	public function test_edac_get_post_type_label_unregistered_post_type() {
		$this->assertSame( 'Custom-type', edac_get_post_type_label( 'custom-type' ) );
	}

	/**
	 * Tests the edac_get_post_type_label function for empty input.
	 */
	public function test_edac_get_post_type_label_empty_input() {
		$this->assertSame( '', edac_get_post_type_label( '' ) );
	}
}
