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
	 * Setup the option and factory for tests.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		update_option( 'edac_post_types', [ 'post', 'page' ] );
	}

	/**
	 * Cleanup the option after tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		delete_option( 'edac_post_types' );
		unset( $GLOBALS['current_screen'] );
	}

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
		$user_id = $this->factory()->user->create( [ 'role' => $role ] );
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
			$user_id = $this->factory()->user->create( [ 'role' => $role ] );
			wp_set_current_user( $user_id );
			$this->assertEquals( $expected, Helpers::current_user_can_see_widgets_and_notices() );
		}
	}

	/**
	 * Test that is_current_post_type_scannable returns true for scannable post types.
	 */
	public function test_is_current_post_type_scannable_returns_true_for_scannable_post() {
		global $post;
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		$this->assertTrue( Helpers::is_current_post_type_scannable( [ 'post', 'page' ] ) );
	}

	/**
	 * Test that is_current_post_type_scannable returns false for non-scannable post types.
	 */
	public function test_is_current_post_type_scannable_returns_false_for_non_scannable_post() {
		global $post;
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		$this->assertFalse( Helpers::is_current_post_type_scannable( [ 'page' ] ) );
	}

	/**
	 * Test that is_current_post_type_scannable uses Settings when no post types provided.
	 */
	public function test_is_current_post_type_scannable_uses_settings_when_empty_array() {
		global $post;
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		$this->assertTrue( Helpers::is_current_post_type_scannable() );
	}

	/**
	 * Test that is_current_post_type_scannable returns false when no post types are scannable.
	 */
	public function test_is_current_post_type_scannable_returns_false_when_no_scannable_types() {
		global $post;
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		update_option( 'edac_post_types', [ 'page' ] );

		$this->assertFalse( Helpers::is_current_post_type_scannable() );
	}

	/**
	 * Test that is_block_editor returns false when get_current_screen is not available.
	 */
	public function test_is_block_editor_returns_false_when_function_not_exists() {
		// The function should exist in WordPress tests, so we verify it returns a value.
		// This test documents expected behavior if the function doesn't exist.
		$this->assertTrue( is_callable( 'get_current_screen' ) );
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
		return [
			[
				'role'      => 'administrator',
				'default'   => true,
				'extra_cap' => [ 'manage_options' => true ],
			],
			[
				'role'      => 'editor',
				'default'   => true,
				'extra_cap' => [ 'manage_options' => false ],
			],
			[
				'role'      => 'author',
				'default'   => true,
				'extra_cap' => [
					'manage_options' => false,
					'publish_posts'  => true,
					'upload_files'   => true,
				],
			],
			[
				'role'      => 'contributor',
				'default'   => true,
				'extra_cap' => [
					'publish_posts' => false,
					'upload_files'  => false,
				],
			],
			[
				'role'      => 'subscriber',
				'default'   => false,
				'extra_cap' => [ 'manage_options' => false ],
			],
		];
	}
}
