<?php
/**
 * Tests for User_Capability_Grant.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Capabilities\User_Capability_Grant;

/**
 * Covers direct per-user capability grants, their attribution, and that
 * they coexist safely with role-level capability management (e.g.
 * Synced_Capability) rather than being clobbered by it.
 */
class UserCapabilityGrantTest extends WP_UnitTestCase {

	/**
	 * Capability string used only by this test class.
	 *
	 * @var string
	 */
	private const TEST_CAP = 'edac_test_direct_grant_capability';

	/**
	 * Remove the capability from every role and any test users after each
	 * test so state doesn't leak between tests.
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
	 * Granting a capability to a user should make user_can() true for that
	 * user, without needing their role to have the capability at all.
	 *
	 * @return void
	 */
	public function test_grant_makes_user_can_true() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$this->assertFalse( user_can( $user_id, self::TEST_CAP ) );

		User_Capability_Grant::grant( $user_id, self::TEST_CAP );

		$this->assertTrue( user_can( $user_id, self::TEST_CAP ) );
	}

	/**
	 * Revoking a directly-granted capability should make user_can() false
	 * again.
	 *
	 * @return void
	 */
	public function test_revoke_removes_the_grant() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		User_Capability_Grant::grant( $user_id, self::TEST_CAP );
		$this->assertTrue( user_can( $user_id, self::TEST_CAP ) );

		User_Capability_Grant::revoke( $user_id, self::TEST_CAP );
		$this->assertFalse( user_can( $user_id, self::TEST_CAP ) );
	}

	/**
	 * Granting should record who granted it and roughly when.
	 *
	 * @return void
	 */
	public function test_grant_records_attribution() {
		$granter_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id    = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		User_Capability_Grant::grant( $user_id, self::TEST_CAP, $granter_id );

		$info = User_Capability_Grant::get_grant_info( $user_id, self::TEST_CAP );

		$this->assertIsArray( $info );
		$this->assertSame( $granter_id, $info['granted_by'] );
		$this->assertEqualsWithDelta( time(), $info['granted_at'], 5 );
	}

	/**
	 * With no explicit granter passed, attribution should default to the
	 * current user.
	 *
	 * @return void
	 */
	public function test_grant_defaults_attribution_to_current_user() {
		$granter_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id    = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		wp_set_current_user( $granter_id );
		User_Capability_Grant::grant( $user_id, self::TEST_CAP );

		$info = User_Capability_Grant::get_grant_info( $user_id, self::TEST_CAP );

		$this->assertSame( $granter_id, $info['granted_by'] );
	}

	/**
	 * Revoking should clear the attribution record, not just the WordPress
	 * capability itself.
	 *
	 * @return void
	 */
	public function test_revoke_clears_attribution() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		User_Capability_Grant::grant( $user_id, self::TEST_CAP );
		User_Capability_Grant::revoke( $user_id, self::TEST_CAP );

		$this->assertNull( User_Capability_Grant::get_grant_info( $user_id, self::TEST_CAP ) );
	}

	/**
	 * Get_grant_info() should be null for a user who was never individually
	 * granted the capability, even if they can() it via their role.
	 *
	 * @return void
	 */
	public function test_grant_info_null_when_capability_only_from_role() {
		$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );

		$this->assertTrue( user_can( $user_id, self::TEST_CAP ), 'Precondition: user has the capability via their role.' );
		$this->assertNull( User_Capability_Grant::get_grant_info( $user_id, self::TEST_CAP ) );
	}

	/**
	 * Is_individually_granted() should distinguish "granted directly to
	 * this user" from "has it via their role" — both should pass
	 * user_can(), but only the direct grant should read as individually
	 * granted.
	 *
	 * @return void
	 */
	public function test_is_individually_granted_distinguishes_from_role_capability() {
		$role_user_id    = self::factory()->user->create( [ 'role' => 'editor' ] );
		$granted_user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );
		User_Capability_Grant::grant( $granted_user_id, self::TEST_CAP );

		$this->assertTrue( user_can( $role_user_id, self::TEST_CAP ) );
		$this->assertFalse( User_Capability_Grant::is_individually_granted( $role_user_id, self::TEST_CAP ), 'Role-derived capability is not an individual grant.' );

		$this->assertTrue( user_can( $granted_user_id, self::TEST_CAP ) );
		$this->assertTrue( User_Capability_Grant::is_individually_granted( $granted_user_id, self::TEST_CAP ) );
	}

	/**
	 * A capability granted directly to a user must survive a role-level
	 * sync that removes the capability from that user's role — this is the
	 * core coexistence guarantee with Synced_Capability (or any other
	 * role-only sync), proven here without depending on that class.
	 *
	 * @return void
	 */
	public function test_direct_grant_survives_role_level_capability_removal() {
		$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );

		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );
		User_Capability_Grant::grant( $user_id, self::TEST_CAP );

		// Simulate a role-level sync (like Synced_Capability::sync()) that
		// decides 'editor' should no longer have this capability.
		wp_roles()->get_role( 'editor' )->remove_cap( self::TEST_CAP );

		$this->assertTrue( user_can( $user_id, self::TEST_CAP ), 'Direct grant should survive removal of the capability from the user\'s role.' );
	}

	/**
	 * Granting/revoking for a user ID that doesn't exist should fail
	 * gracefully rather than erroring.
	 *
	 * @return void
	 */
	public function test_grant_and_revoke_return_false_for_nonexistent_user() {
		$bogus_id = 999999;

		$this->assertFalse( User_Capability_Grant::grant( $bogus_id, self::TEST_CAP ) );
		$this->assertFalse( User_Capability_Grant::revoke( $bogus_id, self::TEST_CAP ) );
	}
}
