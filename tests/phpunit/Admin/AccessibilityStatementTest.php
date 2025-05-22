<?php
/**
 * Tests for the Accessibility Statement functionality.
 *
 * @package EDAC\Tests\Admin
 */

namespace EDAC\Tests\Admin;

use EDAC\Admin\Accessibility_Statement;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey;
use WP_User;
use WP_Post;

/**
 * Test class for EDAC\Admin\Accessibility_Statement
 */
class AccessibilityStatementTest extends TestCase {
	/**
	 * Mock WP_User instance.
	 *
	 * @var WP_User
	 */
	protected WP_User $mock_user;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->mock_user     = \Mockery::mock( WP_User::class );
		$this->mock_user->ID = 1;
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Tests that a new accessibility statement page is created when it doesn't exist.
	 *
	 * @return void
	 */
	public function testAddPageCreatesPageWhenItDoesNotExist() {
		Monkey\Functions\expect( 'current_user_can' )
			->once()
			->andReturn( true );
		Monkey\Functions\expect( 'wp_get_current_user' )
			->once()
			->andReturn( $this->mock_user );
		Monkey\Functions\expect( 'get_page_by_path' )
			->once()
			->andReturn( null );
		Monkey\Functions\expect( 'wp_insert_post' )
			->once()
			->with(
				\Mockery::on(
					function ( $arg ) {
						return is_array( $arg ) &&
							'Our Commitment to Web Accessibility' === $arg['post_title'] &&
							'draft' === $arg['post_status'] &&
							1 === $arg['post_author'] &&
							'accessibility-statement' === $arg['post_name'] &&
							'page' === $arg['post_type'];
					}
				)
			);

		Accessibility_Statement::add_page();
	}

	/**
	 * Tests that no new page is created when it already exists.
	 *
	 * @return void
	 */
	public function testAddPageDoesNotCreatePageWhenItAlreadyExists() {
		Monkey\Functions\expect( 'current_user_can' )
			->once()
			->andReturn( true );
		Monkey\Functions\expect( 'get_page_by_path' )
			->once()
			->andReturn( \Mockery::mock( WP_Post::class ) );
		Monkey\Functions\expect( 'wp_insert_post' )->never();
		Monkey\Functions\expect( 'wp_get_current_user' )->never();

		Accessibility_Statement::add_page();
	}

	/**
	 * Tests that nothing happens if the user lacks sufficient permissions.
	 *
	 * @return void
	 */
	public function testAddPageDoesNothingIfUserCannotActivatePlugins() {
		Monkey\Functions\expect( 'current_user_can' )
			->once()
			->andReturn( false );
		Monkey\Functions\expect( 'get_page_by_path' )->never();
		Monkey\Functions\expect( 'wp_insert_post' )->never();
		Monkey\Functions\expect( 'wp_get_current_user' )->never();

		Accessibility_Statement::add_page();
	}
}
