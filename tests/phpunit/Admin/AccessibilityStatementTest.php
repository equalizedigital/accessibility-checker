<?php
/**
 * Tests for the Accessibility Statement functionality.
 *
 * @package EDAC\Tests\Admin
 */

namespace EDAC\Tests\Admin;

use EDAC\Admin\Accessibility_Statement;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use BrainMonkey\Functions;
use BrainMonkey\Actions;
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
		
		$this->mock_user     = \Mockery::mock( WP_User::class );
		$this->mock_user->ID = 1;
	}

	/**
	 * Tests that a new accessibility statement page is created when it doesn't exist.
	 *
	 * @return void
	 */
	public function testAddPageCreatesPageWhenItDoesNotExist() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_get_current_user' )->justReturn( $this->mock_user );
		
		Functions\when( 'get_page_by_path' )->justReturn( null );
		Functions\expect( 'wp_insert_post' )->once()->with(
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
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'get_page_by_path' )->justReturn( \Mockery::mock( WP_Post::class ) );
		Functions\expect( 'wp_insert_post' )->never();
		Functions\expect( 'wp_get_current_user' )->never(); // Should not be called if page exists.

		Accessibility_Statement::add_page();
	}

	/**
	 * Tests that nothing happens if the user lacks sufficient permissions.
	 *
	 * @return void
	 */
	public function testAddPageDoesNothingIfUserCannotActivatePlugins() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'get_page_by_path' )->never();
		Functions\expect( 'wp_insert_post' )->never();
		Functions\expect( 'wp_get_current_user' )->never();

		Accessibility_Statement::add_page();
	}
}
