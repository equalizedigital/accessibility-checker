<?php
/**
 * Class HelpersTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Helpers;

/**
 * Test cases to run against methods in the admin helpers class.
 */
class HelpersTest extends WP_UnitTestCase {

	/**
	 * Test capability check for widgets and notices being visible
	 * or not in default configuration.
	 *
	 * @param string $role     The role to test.
	 * @param bool   $expected The expected return value.
	 *
	 * @dataProvider userDataProvider
	 */
	public function test_capability_check_for_widgets_and_notices( string $role, bool $expected ) {
		$user_id = $this->factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		$this->assertEquals( $expected, Helpers::current_user_can_see_widgets_and_notices() );
	}

	/**
	 * Test capability check for widgets and notices being visible
	 * is filterable for different capabilities and those are
	 * honoured when running the check.
	 *
	 * @param string $role              The role to test.
	 * @param bool   $value             The default return value.
	 * @param array  $filter_capability The capabilities to filter and their expected return values.
	 *
	 * @dataProvider userDataProvider
	 */
	public function test_capability_check_for_widgets_and_notices_is_filterable( string $role, bool $value, array $filter_capability ) {

		foreach ( $filter_capability as $capability => $expected ) {
			add_filter(
				'edac_filter_dashboard_widget_capability',
				function () use ( $capability ) {
					return $capability;
				}
			);
			$user_id = $this->factory()->user->create( array( 'role' => $role ) );
			wp_set_current_user( $user_id );
			$this->assertEquals( $expected, Helpers::current_user_can_see_widgets_and_notices() );
		}
	}

	/**
	 * The expected values for the capability check for widgets and notices.
	 *
	 * Passes in role, default value and an array of extra capabilities
	 * and their expected return values from the check after filtering.
	 *
	 * @dataProvider userDataProvider
	 */
	public function userDataProvider(): array {
		return array(
			array(
				'role'      => 'administrator',
				'default'   => true,
				'extra_cap' => array( 'manage_options' => true ),
			),
			array(
				'role'      => 'editor',
				'default'   => true,
				'extra_cap' => array( 'manage_options' => false ),
			),
			array(
				'role'      => 'author',
				'default'   => true,
				'extra_cap' => array(
					'manage_options' => false,
					'publish_posts'  => true,
					'upload_files'   => true,
				),
			),
			array(
				'role'      => 'contributor',
				'default'   => true,
				'extra_cap' => array(
					'publish_posts' => false,
					'upload_files'  => false,
				),
			),
			array(
				'role'      => 'subscriber',
				'default'   => false,
				'extra_cap' => array( 'manage_options' => false ),
			),
		);
	}
}
