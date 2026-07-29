<?php
/**
 * Tests for SyncCapability, independent of any specific feature's use of it.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Capabilities\SyncCapability;

/**
 * Covers the reusable sync/bypass/migration/REST-callback behavior in
 * isolation, using a capability string not used by any real feature so
 * these tests can't collide with edac_ignore_issues's own test coverage
 * (IgnoreCapabilityTest) or leftover role state from it.
 */
class SyncCapabilityTest extends WP_UnitTestCase {

	/**
	 * Capability string used only by this test class.
	 *
	 * @var string
	 */
	private const TEST_CAP = 'edac_test_synced_capability';

	/**
	 * A second capability string, used only by the multi-capability bundle
	 * tests in this class.
	 *
	 * @var string
	 */
	private const TEST_CAP_2 = 'edac_test_synced_capability_two';

	/**
	 * Option name used only by this test class.
	 *
	 * @var string
	 */
	private const TEST_OPTION = 'edac_test_synced_capability_roles';

	/**
	 * Remove the capabilities from every role and the option/migration
	 * markers after each test so they don't leak into each other.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;

		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::TEST_CAP );
			$role->remove_cap( self::TEST_CAP_2 );
		}
		delete_option( self::TEST_OPTION );
		// Version markers are keyed by option name + a hash of the capability
		// set, so different tests in this file produce different keys -
		// delete anything matching the option prefix rather than one exact key.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'edac_capability_version_' . self::TEST_OPTION ) . '%'
			)
		);
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
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION );

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
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION );
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
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION );
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
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION );
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
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION, [ 'editor' ] );

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
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION, [ 'editor' ] );
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
		$v1 = new SyncCapability( self::TEST_CAP, self::TEST_OPTION, [ 'editor' ], 1 );
		$v1->maybe_migrate();

		// Site never saved the option, so it's still on default_roles from v1.
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );

		$v2 = new SyncCapability( self::TEST_CAP, self::TEST_OPTION, [ 'author' ], 2 );
		$v2->maybe_migrate();

		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ), 'v2 default_roles should have been applied.' );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'v1 default_roles should no longer apply after the v2 re-sync.' );
	}

	/**
	 * A single string capability (the pre-bundle constructor signature)
	 * must still work unchanged, since existing single-capability callers
	 * pass a string, not an array.
	 *
	 * @return void
	 */
	public function test_single_string_capability_still_works() {
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION );
		$capability->sync( [ 'author' ] );

		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );

		$author_id = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $author_id );
		$this->assertTrue( $capability->user_can() );
	}

	/**
	 * Passing an array of capabilities should sync all of them together onto
	 * the same roles - the "bundle" case, e.g. ignore/global-ignore/explorer.
	 *
	 * @return void
	 */
	public function test_bundle_syncs_multiple_capabilities_together() {
		$capability = new SyncCapability( [ self::TEST_CAP, self::TEST_CAP_2 ], self::TEST_OPTION );
		$capability->sync( [ 'author' ] );

		$author = wp_roles()->get_role( 'author' );
		$this->assertTrue( $author->has_cap( self::TEST_CAP ) );
		$this->assertTrue( $author->has_cap( self::TEST_CAP_2 ) );

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ) );
	}

	/**
	 * User_can()/permission_callback() must accept an explicit capability
	 * argument so bundle consumers can check one specific capability instead
	 * of only ever getting the first one in the bundle.
	 *
	 * @return void
	 */
	public function test_bundle_user_can_checks_the_capability_passed_in() {
		$capability = new SyncCapability( [ self::TEST_CAP, self::TEST_CAP_2 ], self::TEST_OPTION );
		$capability->sync( [ 'author' ] );

		$author_id = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $author_id );

		$this->assertTrue( $capability->user_can( self::TEST_CAP ) );
		$this->assertTrue( $capability->user_can( self::TEST_CAP_2 ) );

		$callback = $capability->permission_callback( self::TEST_CAP_2 );
		$this->assertTrue( $callback() );
	}

	/**
	 * Manage_options bypass must apply to every capability in the bundle,
	 * not just the first one.
	 *
	 * @return void
	 */
	public function test_manage_options_bypasses_every_capability_in_bundle() {
		$capability = new SyncCapability( [ self::TEST_CAP, self::TEST_CAP_2 ], self::TEST_OPTION );
		$capability->register();
		$capability->sync( [ 'author' ] );

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$this->assertTrue( $capability->user_can( self::TEST_CAP ) );
		$this->assertTrue( $capability->user_can( self::TEST_CAP_2 ) );
	}

	/**
	 * Sync_role_capability() is the generic (role, capability) primitive
	 * sync() is built on. It's private (calling it directly for one
	 * capability out of a bundle would desync the rest of the bundle for
	 * that role), so this test reaches it via reflection rather than a
	 * public call - it's still worth covering in isolation from sync()'s
	 * roles-loop.
	 *
	 * @return void
	 */
	public function test_sync_role_capability_generic_primitive() {
		$capability = new SyncCapability( self::TEST_CAP, self::TEST_OPTION );

		$method = new ReflectionMethod( SyncCapability::class, 'sync_role_capability' );
		$method->setAccessible( true );

		$method->invoke( $capability, 'editor', self::TEST_CAP, true );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );

		$method->invoke( $capability, 'editor', self::TEST_CAP, false );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
	}
}
