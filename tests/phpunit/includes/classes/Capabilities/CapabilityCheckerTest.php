<?php
/**
 * Tests for CapabilityChecker, independent of SyncCapability.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Capabilities\CapabilityChecker;

/**
 * CapabilityChecker is deliberately agnostic - it should answer correctly
 * for any capability string regardless of how that capability came to be
 * true (a role-level grant here, a plain core WP capability, etc.), with no
 * knowledge of SyncCapability or option-backed bundles.
 */
class CapabilityCheckerTest extends WP_UnitTestCase {

	/**
	 * Capability string used only by this test class.
	 *
	 * @var string
	 */
	private const TEST_CAP = 'edac_test_checker_capability';

	/**
	 * Remove the capability from every role after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::TEST_CAP );
		}
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * User_can() should reflect a plain role-level capability grant, with no
	 * SyncCapability instance involved at all.
	 *
	 * @return void
	 */
	public function test_user_can_reflects_role_grant() {
		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );

		$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$this->assertTrue( CapabilityChecker::user_can( self::TEST_CAP ) );
	}

	/**
	 * User_can() should return false for a user whose role lacks the
	 * capability.
	 *
	 * @return void
	 */
	public function test_user_can_false_without_grant() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( CapabilityChecker::user_can( self::TEST_CAP ) );
	}

	/**
	 * User_can() should accept an explicit user ID instead of relying on the
	 * current user.
	 *
	 * @return void
	 */
	public function test_user_can_checks_explicit_user_id() {
		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );
		$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );

		$this->assertTrue( CapabilityChecker::user_can( self::TEST_CAP, $user_id ) );
	}

	/**
	 * Permission_callback() should return a callable proxying user_can(),
	 * suitable for a REST route's permission_callback directly.
	 *
	 * @return void
	 */
	public function test_permission_callback_proxies_user_can() {
		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );

		$callback = CapabilityChecker::permission_callback( self::TEST_CAP );
		$this->assertIsCallable( $callback );

		$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		$this->assertTrue( $callback() );

		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		$this->assertFalse( $callback() );
	}
}
