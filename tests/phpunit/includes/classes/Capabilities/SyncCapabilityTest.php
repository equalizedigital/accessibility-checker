<?php
/**
 * Tests for SyncCapability, independent of any specific feature's use of it.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Capabilities\SyncCapability;

/**
 * Covers the reusable sync/bypass/reconcile/REST-callback behavior in
 * isolation, using capability strings not used by any real feature so these
 * tests can't collide with edac_ignore_issues's own coverage or leftover role
 * state from it.
 */
class SyncCapabilityTest extends WP_UnitTestCase {

	private const TEST_CAP        = 'edac_test_synced_capability';
	private const TEST_CAP_2      = 'edac_test_synced_capability_two';
	private const ROLE_MAP_OPTION = 'edac_test_capability_role_map';
	private const LEGACY_OPTION   = 'edac_test_legacy_roles';

	/**
	 * Remove the capabilities from every role and clear all option markers so
	 * tests don't leak into each other.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::TEST_CAP );
			$role->remove_cap( self::TEST_CAP_2 );
		}
		foreach (
			[
				self::ROLE_MAP_OPTION,
				self::LEGACY_OPTION,
				'edac_synced_capabilities_' . self::ROLE_MAP_OPTION,
				'edac_capability_migration_version_' . self::ROLE_MAP_OPTION,
			] as $option
		) {
			delete_option( $option );
		}
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Convenience constructor for the common single-bundle instance.
	 *
	 * @param string|string[] $caps              Capability or bundle.
	 * @param string          $migration_version Migration version.
	 * @return SyncCapability
	 */
	private function make( $caps = self::TEST_CAP, string $migration_version = '0' ): SyncCapability {
		return new SyncCapability( $caps, self::ROLE_MAP_OPTION, $migration_version, self::LEGACY_OPTION );
	}

	/**
	 * The sync_matrix() call should add a capability only to its mapped roles and
	 * remove it from roles not listed for it.
	 *
	 * @return void
	 */
	public function test_sync_matrix_grants_and_revokes_by_role() {
		$capability = $this->make();

		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );

		$capability->sync_matrix( [ self::TEST_CAP => [ 'author' ] ] );

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * A multi-capability bundle should grant each capability only to the roles
	 * mapped for that specific capability.
	 *
	 * @return void
	 */
	public function test_sync_matrix_is_granular_per_capability() {
		$capability = $this->make( [ self::TEST_CAP, self::TEST_CAP_2 ] );

		$capability->sync_matrix(
			[
				self::TEST_CAP   => [ 'editor' ],
				self::TEST_CAP_2 => [ 'author' ],
			]
		);

		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ) );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP_2 ) );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * The floor policy prevents sync_matrix() from granting a capability to a
	 * role that fails it, even when the role map explicitly lists that role.
	 *
	 * @return void
	 */
	public function test_sync_matrix_respects_the_floor_policy() {
		// Floor policy: only 'editor' may hold the capability.
		$floor      = function ( $role_slug ) {
			return 'editor' === $role_slug;
		};
		$capability = new SyncCapability(
			self::TEST_CAP,
			self::ROLE_MAP_OPTION,
			'0',
			self::LEGACY_OPTION,
			$floor
		);

		$capability->sync_matrix( [ self::TEST_CAP => [ 'editor', 'author' ] ] );

		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'Editor meets the floor and should be granted.' );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ), 'Author fails the floor and must not be granted even though the map lists it.' );
	}

	/**
	 * The version-gated migration seeds only the legacy roles that meet each
	 * capability's floor, so it can never grant a capability a role does not
	 * qualify for.
	 *
	 * @return void
	 */
	public function test_migration_seed_respects_the_floor_policy() {
		update_option( self::LEGACY_OPTION, [ 'editor', 'author' ] );

		$floor      = function ( $role_slug ) {
			return 'editor' === $role_slug;
		};
		$capability = new SyncCapability(
			self::TEST_CAP,
			self::ROLE_MAP_OPTION,
			'1.0.0',
			self::LEGACY_OPTION,
			$floor
		);

		$capability->reconcile();

		$map = get_option( self::ROLE_MAP_OPTION );
		$this->assertSame( [ 'editor' ], $map[ self::TEST_CAP ], 'Only the floor-qualified legacy role is seeded.' );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * The register() call should wire live sync to the role-map option's hooks.
	 *
	 * @return void
	 */
	public function test_register_syncs_on_role_map_save() {
		$capability = $this->make();
		$capability->register();

		add_option( self::ROLE_MAP_OPTION, [ self::TEST_CAP => [ 'author' ] ] );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );

		update_option( self::ROLE_MAP_OPTION, [ self::TEST_CAP => [ 'editor' ] ] );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * Deleting the role-map option should revoke the capability from every role.
	 *
	 * @return void
	 */
	public function test_deleting_role_map_revokes_from_all_roles() {
		$capability = $this->make();
		$capability->register();

		add_option( self::ROLE_MAP_OPTION, [ self::TEST_CAP => [ 'editor', 'author' ] ] );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );

		delete_option( self::ROLE_MAP_OPTION );

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * Manage_options users always pass a check against a bundle capability,
	 * regardless of assignment (via the bypass_for_admins map_meta_cap filter).
	 *
	 * @return void
	 */
	public function test_manage_options_bypasses_matrix() {
		$capability = $this->make();
		$capability->register();
		$capability->sync_matrix( [ self::TEST_CAP => [ 'author' ] ] );

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$this->assertFalse( wp_roles()->get_role( 'administrator' )->has_cap( self::TEST_CAP ), 'Precondition: administrator role itself was not synced.' );
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability, synced by SyncCapability.
		$this->assertTrue( current_user_can( self::TEST_CAP ) );
	}

	/**
	 * The reconcile() version-gated migration seeds the role map from the legacy
	 * single-list roles option, granting every bundle capability to those roles.
	 *
	 * @return void
	 */
	public function test_reconcile_migration_seeds_map_from_legacy_option() {
		update_option( self::LEGACY_OPTION, [ 'editor' ] );

		$capability = $this->make( [ self::TEST_CAP, self::TEST_CAP_2 ], '1.0.0' );
		$capability->reconcile();

		$map = get_option( self::ROLE_MAP_OPTION );
		$this->assertSame( [ 'editor' ], $map[ self::TEST_CAP ] );
		$this->assertSame( [ 'editor' ], $map[ self::TEST_CAP_2 ] );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ) );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * A site that already has a saved (non-empty) role map when it crosses the
	 * migration-version boundary must be left completely untouched, even when a
	 * legacy option is ALSO present and would seed something different. This is
	 * the affordance that keeps a version bump from silently overwriting
	 * hand-configured Permissions settings on an already-configured site
	 * (e.g. a local dev/test site an admin already set up by hand) - the legacy
	 * seed only ever applies to a genuinely unconfigured (empty) map.
	 *
	 * @return void
	 */
	public function test_reconcile_never_touches_an_already_configured_role_map() {
		// Deliberately different from what the legacy seed below would produce,
		// so any bleed-through would be immediately visible.
		update_option( self::ROLE_MAP_OPTION, [ self::TEST_CAP => [ 'author' ] ] );
		update_option( self::LEGACY_OPTION, [ 'editor' ] );

		$capability = $this->make( [ self::TEST_CAP, self::TEST_CAP_2 ], '1.0.0' );
		$capability->sync_matrix( [ self::TEST_CAP => [ 'author' ] ] ); // Apply the pre-existing config to roles, as a real site would already have.
		$capability->reconcile();

		// The stored map is byte-for-byte unchanged - no legacy grant merged in.
		$this->assertSame( [ self::TEST_CAP => [ 'author' ] ], get_option( self::ROLE_MAP_OPTION ) );
		// Editor (the legacy role) gained nothing.
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ) );
		// The pre-existing grant survives.
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * A second reconcile at the same version must not re-seed the map or clobber
	 * assignments changed since the migration ran.
	 *
	 * @return void
	 */
	public function test_reconcile_does_not_rerun_for_same_version() {
		update_option( self::LEGACY_OPTION, [ 'editor' ] );

		$capability = $this->make( self::TEST_CAP, '1.0.0' );
		$capability->reconcile();

		// Config changes after the one-time migration.
		update_option( self::ROLE_MAP_OPTION, [ self::TEST_CAP => [ 'author' ] ] );
		$capability->sync_matrix( [ self::TEST_CAP => [ 'author' ] ] );

		$capability->reconcile();

		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'Migration should not have re-seeded from the legacy option.' );
	}

	/**
	 * Crossing a higher migration version forces a re-sync of the current map,
	 * healing any drift even though the set is unchanged.
	 *
	 * @return void
	 */
	public function test_reconcile_version_bump_reheals_drift() {
		$v1 = $this->make( self::TEST_CAP, '1.0.0' );
		update_option( self::ROLE_MAP_OPTION, [ self::TEST_CAP => [ 'editor' ] ] );
		$v1->reconcile();
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );

		// Drift: capability stripped from the role by something else.
		wp_roles()->get_role( 'editor' )->remove_cap( self::TEST_CAP );

		$v2 = $this->make( self::TEST_CAP, '2.0.0' );
		$v2->reconcile();

		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'A version bump should re-apply the map and heal drift.' );
	}

	/**
	 * The reconcile() call retains capabilities that have left the bundle (e.g. an
	 * add-on that contributed one was deactivated): deactivating an add-on must
	 * not strip its capability off roles, so re-activating restores prior state.
	 *
	 * @return void
	 */
	public function test_reconcile_keeps_capabilities_that_left_the_bundle() {
		update_option(
			self::ROLE_MAP_OPTION,
			[
				self::TEST_CAP   => [ 'editor' ],
				self::TEST_CAP_2 => [ 'editor' ],
			]
		);

		$two = $this->make( [ self::TEST_CAP, self::TEST_CAP_2 ], '1.0.0' );
		$two->reconcile();
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ) );

		// The bundle shrinks - TEST_CAP_2's contributing plugin was deactivated.
		$one = $this->make( self::TEST_CAP, '1.0.0' );
		$one->reconcile();

		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ), 'A capability that left the bundle should be retained, not revoked.' );
	}

	/**
	 * The revoke() call removes a capability from every role.
	 *
	 * @return void
	 */
	public function test_revoke_removes_from_roles() {
		$capability = $this->make( [ self::TEST_CAP, self::TEST_CAP_2 ] );

		$capability->sync_matrix( [ self::TEST_CAP => [ 'editor' ] ] );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );

		$capability->revoke( [ self::TEST_CAP ] );

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * A single string capability (not an array) must still work.
	 *
	 * @return void
	 */
	public function test_single_string_capability_still_works() {
		$capability = $this->make( self::TEST_CAP );
		$capability->sync_matrix( [ self::TEST_CAP => [ 'author' ] ] );

		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::TEST_CAP ) );

		$author_id = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $author_id );
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability, synced by SyncCapability.
		$this->assertTrue( current_user_can( self::TEST_CAP ) );
	}

	/**
	 * Manage_options bypass applies to every capability in the bundle.
	 *
	 * @return void
	 */
	public function test_manage_options_bypasses_every_capability_in_bundle() {
		$capability = $this->make( [ self::TEST_CAP, self::TEST_CAP_2 ] );
		$capability->register();

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capabilities, synced by SyncCapability.
		$this->assertTrue( current_user_can( self::TEST_CAP ) );
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capabilities, synced by SyncCapability.
		$this->assertTrue( current_user_can( self::TEST_CAP_2 ) );
	}

	/**
	 * The constructor rejects an empty capabilities array.
	 *
	 * @return void
	 */
	public function test_constructor_throws_on_empty_capabilities_array() {
		$this->expectException( \InvalidArgumentException::class );

		new SyncCapability( [], self::ROLE_MAP_OPTION );
	}

	/**
	 * The register() call must hook reconcile() to init, not admin_init.
	 *
	 * @return void
	 */
	public function test_register_hooks_reconcile_to_init_not_admin_init() {
		$capability = $this->make();
		$capability->register();

		$this->assertNotFalse(
			has_action( 'init', [ $capability, 'reconcile' ] ),
			'reconcile() should be hooked to init.'
		);
		$this->assertFalse(
			has_action( 'admin_init', [ $capability, 'reconcile' ] ),
			'reconcile() should not be hooked to admin_init.'
		);
	}

	/**
	 * The sync_role_capability() method is the generic (role, capability) primitive
	 * the matrix is built on. Private, so reached via reflection.
	 *
	 * @return void
	 */
	public function test_sync_role_capability_generic_primitive() {
		$capability = $this->make();

		$method = new ReflectionMethod( SyncCapability::class, 'sync_role_capability' );
		$method->setAccessible( true );

		$method->invoke( $capability, 'editor', self::TEST_CAP, true );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );

		$method->invoke( $capability, 'editor', self::TEST_CAP, false );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ) );
	}

	/**
	 * The version migration moves a renamed capability's grants from the old
	 * slug to the new one and strips the old capability off the role.
	 *
	 * @return void
	 */
	public function test_migration_renames_capability_grants() {
		// Pre-state: a site already granting the OLD capability to editor.
		wp_roles()->get_role( 'editor' )->add_cap( self::TEST_CAP );
		update_option( self::ROLE_MAP_OPTION, [ self::TEST_CAP => [ 'editor' ] ] );
		// Mark a prior migration so the version boundary is crossed on reconcile.
		update_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION, '1.0.0' );

		// New bundle knows only the NEW slug, with a rename map old -> new.
		$capability = new SyncCapability(
			self::TEST_CAP_2,
			self::ROLE_MAP_OPTION,
			'2.0.0',
			self::LEGACY_OPTION,
			null,
			[ self::TEST_CAP => self::TEST_CAP_2 ]
		);

		$capability->reconcile();

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'The retired slug should be stripped from the role.' );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ), 'The role should now hold the renamed capability.' );

		$role_map = (array) get_option( self::ROLE_MAP_OPTION, [] );
		$this->assertArrayNotHasKey( self::TEST_CAP, $role_map, 'The old slug should be gone from the role map.' );
		$this->assertSame( [ 'editor' ], $role_map[ self::TEST_CAP_2 ], 'The role map should carry the grant under the new slug.' );
	}

	/**
	 * When a legacy-capability subset is configured, the legacy-option migration
	 * seeds ONLY those capabilities, not the whole bundle.
	 *
	 * @return void
	 */
	public function test_legacy_migration_only_seeds_scoped_capabilities() {
		delete_option( self::ROLE_MAP_OPTION );
		delete_option( 'edac_synced_capabilities_' . self::ROLE_MAP_OPTION );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
		update_option( self::LEGACY_OPTION, [ 'editor' ] );

		// Bundle has two caps, but the legacy option is scoped to only TEST_CAP.
		$capability = new SyncCapability(
			[ self::TEST_CAP, self::TEST_CAP_2 ],
			self::ROLE_MAP_OPTION,
			'2.0.0',
			self::LEGACY_OPTION,
			null,
			[],
			[ self::TEST_CAP ]
		);

		$capability->reconcile();

		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP ), 'The scoped legacy capability should be seeded.' );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::TEST_CAP_2 ), 'A bundle capability outside the legacy scope must NOT be seeded from the legacy option.' );

		update_option( self::LEGACY_OPTION, [] );
	}
}
