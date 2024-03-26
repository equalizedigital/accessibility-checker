<?php
/**
 * Tests for the Summary_Generator.
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Summary_Generator;

/**
 * Testing that the summary generator outputs what is expected when invoked.
 */
class SummaryGeneratorTest extends WP_UnitTestCase {
	/**
	 * Validates that a bug was fixed where the summary density would cause
	 * a fatal error when the density_data had a string instead of an array.
	 *
	 * @throws ReflectionException If the method does not exist this is thrown.
	 */
	public function test_summary_density_wont_error_when_density_array_does_not_have_array_inside() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_density_data', '0,0' );

		$simplified_summary = new Summary_Generator( $post_id );

		// Reflection means that the method was hard to test in isolation and
		// likely warrants a refactor so that the method is more testable.
		$method = ( new ReflectionClass( get_class( $simplified_summary ) ) )
			->getMethod( 'update_issue_density' );
		$method->setAccessible( true );

		$method->invoke( $simplified_summary, array() );

		// We are really testing here that the method does not throw an error,
		// but we may as well check that the meta didn't change as well since
		// we are here and by this point already know the method did not fatal.
		$this->assertEquals(
			'0,0',
			get_post_meta( $post_id, '_edac_density_data', true )
		);
	}
}
