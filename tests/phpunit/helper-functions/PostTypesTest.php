<?php
/**
 * Tests for edac_post_types helper.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_post_types.
 */
class PostTypesTest extends WP_UnitTestCase {

	/**
	 * Ensures edac_post_types tolerates non-array filter values.
	 */
	public function test_edac_post_types_casts_filter_value_to_array(): void {
		add_filter(
			'edac_filter_post_types',
			static function () {
				return 'post';
			}
		);

		$this->assertSame( [ 'post' ], edac_post_types() );

		remove_all_filters( 'edac_filter_post_types' );
	}
}
