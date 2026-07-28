<?php
/**
 * Tests for Synced_Capability, independent of any specific feature's use of it.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Capabilities\Synced_Capability;

/**
 * Covers the reusable sync/bypass/migration/REST-callback behavior in
 * isolation, using a capability string not used by any real feature so
 * these tests can't collide with edac_ignore_issues's own test coverage
 * (IgnoreCapabilityTest) or leftover role state from it.
 */
class SyncedCapabilityTest extends WP_UnitTestCase {

	/**
	 * Capability string used only by this test class.
	 *
	 * @var string
	 */
	private const TEST_CAP = 'edac_test_synced_capability';

	/**
	 * Option name used only by this test class.
	 *
	 * @var string
	 */
	private const TEST_OPTION = 'edac_test_synced_capability_roles';

	/**
	 * Remove the capability from every role and the option/migration
	 * markers after each test so they don't leak into each other.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::TEST_CAP );
		}
		delete_option( self::TEST_OPTION );
		delete_option( 'edac_capability_version_' . self::TEST_CAP );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Sync() should add the capability only to the roles passed in, and
	 * remove it from roles not included.
	 *
	 * @return void
	 */
	public function test_sync_adds_and_removes_capability_by_role() {
		$capability = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION );

		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );

		$capability->sync( [ 'author' ] );

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * Register() should wire live sync to the option's add/update hooks.
	 *
	 * @return void
	 */
	public function test_register_syncs_on_option_save() {
		$capability = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION );
		$capability->register();

		add_option( self::TEST_OPTION, [ 'author' ] );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );

		update_option( self::TEST_OPTION, [ 'editor' ] );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * Manage_options users must always pass user_can(), regardless of
	 * whether their role was synced.
	 *
	 * @return void
	 */
	public function test_manage_options_bypasses_sync() {
		$capability = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION );
		$capability->register();
		$capability->sync( [ 'author' ] );

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$this->assertFalse( wp_roles()->get_role( 'administrator' )->has_cap( self::TEST_CAP ), 'Precondition: administrator role itself was not synced.' );
		$this->assertTrue( $capability->user_can() );
	}

	/**
	 * Permission_callback() should return a callable proxying user_can(),
	 * suitable for a REST route's permission_callback directly.
	 *
	 * @return void
	 */
	public function test_permission_callback_proxies_user_can() {
		$capability = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION );
		$capability->sync( [ 'author' ] );

		$callback = $capability->permission_callback();
		$this->assertIsCallable( $callback );

		$author_id = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $author_id );
		$this->assertTrue( $callback() );

		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		$this->assertFalse( $callback() );
	}

	/**
	 * Maybe_migrate() should run the initial sync from default_roles when
	 * the option was never set and no migration has run yet.
	 *
	 * @return void
	 */
	public function test_migration_runs_once_for_unset_option() {
		$capability = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION, [ 'editor' ] );

		$capability->maybe_migrate();

		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * Maybe_migrate() should not re-run (and shouldn't clobber roles synced
	 * some other way since) once it has already run for the current version.
	 *
	 * @return void
	 */
	public function test_migration_does_not_rerun_for_same_version() {
		$capability = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION, [ 'editor' ] );
		$capability->maybe_migrate();

		// Simulate the site's config changing after the one-time migration ran.
		$capability->sync( [ 'author' ] );

		$capability->maybe_migrate();

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'Migration should not have re-applied default_roles.' );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * Bumping the version should force maybe_migrate() to re-sync even
	 * though an earlier version's migration already ran once.
	 *
	 * @return void
	 */
	public function test_version_bump_forces_remigration() {
		$v1 = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION, [ 'editor' ], 1 );
		$v1->maybe_migrate();

		// Site never saved the option, so it's still on default_roles from v1.
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );

		$v2 = new Synced_Capability( self::TEST_CAP, self::TEST_OPTION, [ 'author' ], 2 );
		$v2->maybe_migrate();

		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ), 'v2 default_roles should have been applied.' );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'v1 default_roles should no longer apply after the v2 re-sync.' );
	}
}
