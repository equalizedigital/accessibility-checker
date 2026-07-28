<?php
/**
 * Tests for the edac_ignore_issues capability sync and helper functions.
 *
 * @package accessibility-checker
 */

/**
 * Tests for edac_sync_ignore_capability(), edac_user_can_ignore(), and the
 * map_meta_cap manage_options override.
 */
class IgnoreCapabilityTest extends WP_UnitTestCase {

	/**
	 * Remove the capability from every role after each test so tests don't
	 * leak state into each other via the shared roles table.
	 */
	public function tearDown(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( 'edac_ignore_issues' );
		}
		parent::tearDown();
	}

	/**
	 * Syncing should add the capability only to the roles passed in, and
	 * remove it from roles not included.
	 *
	 * @return void
	 */
	public function test_sync_adds_and_removes_capability_by_role() {
		wp_roles()->get_role( 'editor' )->add_cap( 'edac_ignore_issues' );

		edac_sync_ignore_capability( [ 'author' ] );

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( 'edac_ignore_issues' ), 'Editor should have lost the capability.' );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( 'edac_ignore_issues' ), 'Author should have gained the capability.' );
	}

	/**
	 * A user in a role that was granted the capability should pass
	 * edac_user_can_ignore().
	 *
	 * @return void
	 */
	public function test_user_can_ignore_true_for_synced_role() {
		edac_sync_ignore_capability( [ 'author' ] );

		$user_id = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $user_id );

		$this->assertTrue( edac_user_can_ignore() );
	}

	/**
	 * A user in a role that was not granted the capability should fail
	 * edac_user_can_ignore().
	 *
	 * @return void
	 */
	public function test_user_can_ignore_false_for_unsynced_role() {
		edac_sync_ignore_capability( [ 'author' ] );

		$user_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( edac_user_can_ignore() );
	}

	/**
	 * Manage_options users must always pass the check, even if their role
	 * was left out of edacp_ignore_user_roles (e.g. an admin restricted
	 * even the administrator role by mistake).
	 *
	 * @return void
	 */
	public function test_manage_options_user_always_can_ignore() {
		edac_sync_ignore_capability( [ 'author' ] );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( wp_roles()->get_role( 'administrator' )->has_cap( 'edac_ignore_issues' ), 'Precondition: administrator role itself was not synced.' );
		$this->assertTrue( edac_user_can_ignore(), 'manage_options users must always be able to ignore/dismiss.' );
	}

	/**
	 * Saving the edacp_ignore_user_roles option should sync the capability
	 * automatically via the update_option/add_option hooks.
	 *
	 * @return void
	 */
	public function test_saving_option_triggers_sync() {
		delete_option( 'edacp_ignore_user_roles' );
		add_option( 'edacp_ignore_user_roles', [ 'author' ] );

		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( 'edac_ignore_issues' ) );

		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( 'edac_ignore_issues' ) );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( 'edac_ignore_issues' ) );
	}
}
