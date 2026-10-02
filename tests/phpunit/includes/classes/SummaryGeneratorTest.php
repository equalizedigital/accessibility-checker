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

		$method->invoke( $simplified_summary, [] );

		// We are really testing here that the method does not throw an error,
		// but we may as well check that the meta didn't change as well since
		// we are here and by this point already know the method did not fatal.
		$this->assertEquals(
			'0,0',
			get_post_meta( $post_id, '_edac_density_data', true )
		);
	}

	/**
	 * Ensures that calculate_content_grade handles missing posts gracefully.
	 *
	 * @throws ReflectionException If the method does not exist this is thrown.
	 */
	public function test_calculate_content_grade_returns_zero_when_post_missing() {
		$post_id           = 999999;
		$summary_generator = new Summary_Generator( $post_id );

		$method = ( new ReflectionClass( get_class( $summary_generator ) ) )
			->getMethod( 'calculate_content_grade' );
		$method->setAccessible( true );

		$this->assertSame( 0, $method->invoke( $summary_generator ) );
	}

	/**
	 * Ensures count_contrast_errors() does not query the database, and returns 0,
	 * when the color contrast rule is not among the currently active rules (e.g. it
	 * has been filtered out), even if contrast violations still exist in the database.
	 *
	 * @throws ReflectionException If the method does not exist this is thrown.
	 */
	public function test_count_contrast_errors_returns_zero_when_rule_is_filtered_out() {
		global $wpdb;

		$post_id = self::factory()->post->create();

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is just one-time use data for testing.
			$wpdb->prefix . 'accessibility_checker',
			[
				'siteid'   => get_current_blog_id(),
				'postid'   => $post_id,
				'rule'     => 'color_contrast_failure',
				'ruletype' => 'error',
				'ignre'    => 0,
			]
		);

		$summary_generator = new Summary_Generator( $post_id );

		$method = ( new ReflectionClass( get_class( $summary_generator ) ) )
			->getMethod( 'count_contrast_errors' );
		$method->setAccessible( true );

		// Color contrast rule is not present in the active rules list, simulating it being filtered out.
		$active_rules = [
			[ 'slug' => 'some_other_rule' ],
		];

		$this->assertSame( 0, $method->invoke( $summary_generator, $active_rules ) );
	}

	/**
	 * Ensures count_contrast_errors() still counts violations when the color contrast
	 * rule is present among the currently active rules.
	 *
	 * @throws ReflectionException If the method does not exist this is thrown.
	 */
	public function test_count_contrast_errors_counts_when_rule_is_active() {
		global $wpdb;

		$post_id = self::factory()->post->create();

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is just one-time use data for testing.
			$wpdb->prefix . 'accessibility_checker',
			[
				'siteid'   => get_current_blog_id(),
				'postid'   => $post_id,
				'rule'     => 'color_contrast_failure',
				'ruletype' => 'error',
				'ignre'    => 0,
			]
		);

		$summary_generator = new Summary_Generator( $post_id );

		$method = ( new ReflectionClass( get_class( $summary_generator ) ) )
			->getMethod( 'count_contrast_errors' );
		$method->setAccessible( true );

		$active_rules = [
			[ 'slug' => 'color_contrast_failure' ],
		];

		$this->assertSame( 1, $method->invoke( $summary_generator, $active_rules ) );
	}
}
