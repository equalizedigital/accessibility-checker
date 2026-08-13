<?php
/**
 * Tests for the edac_dismiss_own_issues capability sync and helper functions.
 *
 * @package accessibility-checker
 */

/**
 * Integration coverage for the real edac_ignore_capability() instance and the
 * edac_user_can_dismiss_own_issues() helper against the capability-role matrix. Uses
 * edac_dismiss_own_issues (a capability the free plugin owns, so it is always in the
 * bundle in this free-only test environment).
 */
class IgnoreCapabilityTest extends WP_UnitTestCase {

	private const CAP             = 'edac_dismiss_own_issues';
	private const ROLE_MAP_OPTION = 'edac_capability_role_map';

	/**
	 * Reset roles, users and the matrix/bookkeeping options after each test.
	 */
	public function tearDown(): void {
		edac_ignore_capability()->sync_matrix( [] );
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP );
			$role->remove_cap( 'edac_view_frontend_highlighter' );
		}
		foreach (
			[
				self::ROLE_MAP_OPTION,
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
	 * The edac_dismiss_own_issues capability is part of the free bundle.
	 *
	 * @return void
	 */
	public function test_ignore_issues_is_in_the_bundle() {
		$this->assertContains( self::CAP, edac_capability_bundle() );
	}

	/**
	 * Applying the matrix grants the capability to its mapped role and revokes
	 * it from others.
	 *
	 * @return void
	 */
	public function test_matrix_grants_and_revokes_by_role() {
		wp_roles()->get_role( 'editor' )->add_cap( self::CAP );

		edac_ignore_capability()->sync_matrix( [ self::CAP => [ 'author' ] ] );

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::CAP ), 'Editor should have lost the capability.' );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::CAP ), 'Author should have gained the capability.' );
	}

	/**
	 * A user in a granted role passes edac_user_can_dismiss_own_issues().
	 *
	 * @return void
	 */
	public function test_user_can_ignore_true_for_granted_role() {
		edac_ignore_capability()->sync_matrix( [ self::CAP => [ 'author' ] ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'author' ] ) );

		$this->assertTrue( edac_user_can_dismiss_own_issues() );
	}

	/**
	 * A user in an ungranted role fails edac_user_can_dismiss_own_issues().
	 *
	 * @return void
	 */
	public function test_user_can_ignore_false_for_ungranted_role() {
		edac_ignore_capability()->sync_matrix( [ self::CAP => [ 'author' ] ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertFalse( edac_user_can_dismiss_own_issues() );
	}

	/**
	 * Manage_options users always pass, even when their role was not granted.
	 *
	 * @return void
	 */
	public function test_manage_options_user_always_can_ignore() {
		edac_ignore_capability()->sync_matrix( [ self::CAP => [ 'author' ] ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertFalse( wp_roles()->get_role( 'administrator' )->has_cap( self::CAP ), 'Precondition: administrator role itself was not granted.' );
		$this->assertTrue( edac_user_can_dismiss_own_issues(), 'manage_options users must always be able to ignore/dismiss.' );
	}

	/**
	 * Saving the role-map option syncs automatically via the option hooks.
	 *
	 * @return void
	 */
	public function test_saving_role_map_triggers_sync() {
		delete_option( self::ROLE_MAP_OPTION );
		add_option( self::ROLE_MAP_OPTION, [ self::CAP => [ 'author' ] ] );

		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::CAP ) );

		update_option( self::ROLE_MAP_OPTION, [ self::CAP => [ 'editor' ] ] );

		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( self::CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::CAP ) );
	}

	/**
	 * The version-gated migration seeds the matrix from the legacy
	 * edacp_ignore_user_roles option, but ONLY with the ignore/dismiss family the
	 * old "Ignore Permissions" setting actually granted - not the whole bundle.
	 *
	 * @return void
	 */
	public function test_migration_seeds_matrix_from_legacy_option() {
		delete_option( self::ROLE_MAP_OPTION );
		delete_option( 'edac_synced_capabilities_' . self::ROLE_MAP_OPTION );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
		update_option( 'edacp_ignore_user_roles', [ 'author' ] );

		edac_ignore_capability()->reconcile();

		// The legacy role gets the dismiss-own capability (the successor to
		// per-post "ignore issues").
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::CAP ), 'Migration should grant the dismiss-own capability to the legacy roles.' );

		// It must NOT inherit capabilities the old setting never granted: the
		// front-end highlighter or the site-wide "dismiss any" cap.
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( 'edac_view_frontend_highlighter' ), 'The legacy setting never granted the highlighter.' );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( 'edac_dismiss_issues' ), 'The legacy setting never granted site-wide dismiss.' );

		update_option( 'edacp_ignore_user_roles', [] );
	}

	/**
	 * A migrating site keeps ONLY the grants the legacy "Ignore Permissions"
	 * setting gave it. An upgrading site never runs the activation hook, so only
	 * the version-gated migration in reconcile() runs - it must migrate the
	 * dismiss family and never hand the site fresh-install defaults (e.g. the
	 * front-end highlighter) for capabilities the legacy setting never governed.
	 * Fresh-install seeding is covered by test_fresh_install_seeds_defaults_onto_roles.
	 *
	 * @return void
	 */
	public function test_migrating_site_is_not_back_filled_with_defaults() {
		// Migrating-site slate: legacy config present, nothing seeded, no role map,
		// migration boundary not yet crossed.
		delete_option( self::ROLE_MAP_OPTION );
		delete_option( 'edac_capability_defaults_seeded' );
		delete_option( 'edac_synced_capabilities_' . self::ROLE_MAP_OPTION );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP );
			$role->remove_cap( 'edac_view_frontend_highlighter' );
		}
		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		// Only reconcile() runs on an upgrade (no activation). Run it twice to prove
		// a subsequent init changes nothing.
		edac_ignore_capability()->reconcile();
		edac_ignore_capability()->reconcile();

		$role_map = (array) get_option( self::ROLE_MAP_OPTION, [] );

		// The migrated dismiss capability IS present for the legacy role.
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::CAP ), 'The migrated dismiss capability must be granted to the legacy role.' );

		// The highlighter default must NOT be back-filled onto a migrating site.
		$this->assertArrayNotHasKey( 'edac_view_frontend_highlighter', $role_map, 'A migrating site must not be seeded the highlighter default.' );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( 'edac_view_frontend_highlighter' ), 'The highlighter must not be granted to editor on a migrating site.' );
		$this->assertFalse( wp_roles()->get_role( 'author' )->has_cap( 'edac_view_frontend_highlighter' ), 'The highlighter must not be granted to author on a migrating site.' );

		// Cleanup so later tests start from the shared no-caps baseline.
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP );
			$role->remove_cap( 'edac_view_frontend_highlighter' );
		}
		delete_option( 'edac_capability_defaults_seeded' );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
		update_option( 'edacp_ignore_user_roles', [] );
	}

	/**
	 * An established site that set the legacy "Ignore Permissions" option to NO
	 * roles must not be mistaken for a fresh install and handed the default suite
	 * (review #10). It runs the migration (empty legacy -> nothing) and gets no
	 * default grants.
	 *
	 * @return void
	 */
	public function test_migrating_site_with_empty_legacy_gets_no_defaults() {
		delete_option( self::ROLE_MAP_OPTION );
		delete_option( 'edac_capability_defaults_seeded' );
		delete_option( 'edac_synced_capabilities_' . self::ROLE_MAP_OPTION );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP );
			$role->remove_cap( 'edac_view_frontend_highlighter' );
		}
		update_option( 'edacp_ignore_user_roles', [] );

		edac_ignore_capability()->reconcile();

		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::CAP ), 'An empty-legacy site must not receive the dismiss-own default.' );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( 'edac_view_frontend_highlighter' ), 'An empty-legacy site must not receive the highlighter default.' );

		delete_option( 'edac_capability_defaults_seeded' );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
		update_option( 'edacp_ignore_user_roles', [] );
	}

	/**
	 * The top-level menu is shown to a capability holder even without edit_posts
	 * (PRO-1283): a role granted an Accessibility Checker capability must be able
	 * to reach its page, which requires the parent menu to register.
	 *
	 * @return void
	 */
	public function test_admin_menu_visible_for_capability_holder_without_edit_posts() {
		// A bare subscriber (no edit_posts, no edac capability) does not see it.
		$bare = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $bare );
		$this->assertFalse( edac_user_can_see_admin_menu(), 'A subscriber with no capabilities must not see the menu.' );

		// Grant an Accessibility Checker capability directly (bypassing the floor,
		// as a role-editor plugin might): the menu must now register.
		$granted = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		( new WP_User( $granted ) )->add_cap( self::CAP );
		wp_set_current_user( $granted );
		$this->assertFalse( current_user_can( 'edit_posts' ), 'Precondition: the user lacks edit_posts.' );
		$this->assertTrue( edac_user_can_see_admin_menu(), 'A capability holder without edit_posts must see the menu.' );

		// An edit_posts user with no edac capability still sees it (historical gate).
		$editor_caps = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $editor_caps );
		$this->assertTrue( edac_user_can_see_admin_menu(), 'An edit_posts user must see the menu.' );
	}

	/**
	 * A fresh install (via edac_seed_capability_defaults_on_install(), what the
	 * activation hook calls) grants each capability's default_roles - filtered by
	 * floor - onto the roles, leaves no-default capabilities (e.g. the site-wide
	 * "dismiss any" cap) unassigned, and stamps the migration as satisfied so a
	 * fresh install is never re-migrated.
	 *
	 * @return void
	 */
	public function test_fresh_install_seeds_defaults_onto_roles() {
		// Fresh-install slate: nothing seeded, no role map, no legacy config, no stamp.
		delete_option( self::ROLE_MAP_OPTION );
		delete_option( 'edac_capability_defaults_seeded' );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
		delete_option( 'edacp_ignore_user_roles' );
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP );
			$role->remove_cap( 'edac_view_frontend_highlighter' );
		}

		// What edac_activation() runs on a genuinely fresh install. It seeds the
		// role map, marks caps seeded, stamps the migration, and applies to roles.
		edac_seed_capability_defaults_on_install();

		$role_map = (array) get_option( self::ROLE_MAP_OPTION, [] );

		// edac_dismiss_own_issues defaults to editor + author + contributor (all
		// meet its edit_posts floor).
		$this->assertEqualsCanonicalizing( [ 'editor', 'author', 'contributor' ], $role_map[ self::CAP ], 'dismiss-own should seed to editor, author, contributor.' );
		// The highlighter defaults to editor + author only.
		$this->assertEqualsCanonicalizing( [ 'editor', 'author' ], $role_map['edac_view_frontend_highlighter'], 'highlighter should seed to editor and author.' );
		// The site-wide "dismiss any" cap has no default and must not be seeded.
		$this->assertArrayNotHasKey( 'edac_dismiss_issues', $role_map, 'dismiss-any has no default and must not be seeded.' );

		// The caps are actually applied to the roles, and the floor keeps them off
		// a role that does not qualify (subscriber lacks edit_posts).
		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'author' )->has_cap( self::CAP ) );
		$this->assertTrue( wp_roles()->get_role( 'contributor' )->has_cap( self::CAP ) );
		$this->assertFalse( wp_roles()->get_role( 'subscriber' )->has_cap( self::CAP ) );

		// The migration is stamped satisfied, so reconcile() never re-migrates it.
		$this->assertSame( EDAC_CAPABILITY_MIGRATION_VERSION, get_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION ), 'A fresh install must stamp the migration as satisfied.' );

		// Cleanup so later tests start from the shared no-caps baseline.
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP );
			$role->remove_cap( 'edac_view_frontend_highlighter' );
		}
		delete_option( 'edac_capability_defaults_seeded' );
		delete_option( 'edac_capability_migration_version_' . self::ROLE_MAP_OPTION );
	}

	/**
	 * The shared role-map sanitizer (used by the Permissions save handler AND the
	 * settings importer, wired as the sanitize_option filter) drops any role that
	 * fails a capability's floor and any unknown role - so a hand-edited import
	 * file can never grant more than the UI would.
	 *
	 * @return void
	 */
	public function test_import_sanitizer_enforces_floor_and_editable_roles() {
		delete_option( self::ROLE_MAP_OPTION );

		// self::CAP (edac_dismiss_own_issues) has an edit_posts floor. Subscriber
		// fails it; author meets it; an unknown role is not assignable.
		$incoming = [
			self::CAP => [ 'subscriber', 'author', 'bogus_role' ],
		];

		// Call through the sanitize_option filter to prove the wiring, not just the
		// function (the importer reaches it via sanitize_option()).
		$clean = (array) apply_filters( 'sanitize_option_' . self::ROLE_MAP_OPTION, $incoming );

		$this->assertArrayHasKey( self::CAP, $clean, 'A capability with at least one valid role is kept.' );
		$this->assertContains( 'author', $clean[ self::CAP ], 'author meets the edit_posts floor and is kept.' );
		$this->assertNotContains( 'subscriber', $clean[ self::CAP ], 'subscriber fails the edit_posts floor and is dropped.' );
		$this->assertNotContains( 'bogus_role', $clean[ self::CAP ], 'an unknown role is dropped.' );

		delete_option( self::ROLE_MAP_OPTION );
	}

	/**
	 * The public edac_sync_capability_roles() helper applies the stored role map
	 * onto the roles - the trigger importers use after writing the option.
	 *
	 * @return void
	 */
	public function test_sync_capability_roles_applies_the_stored_map() {
		delete_option( self::ROLE_MAP_OPTION );
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAP );
		}

		// Seed the option, then strip the cap the option-update hook just applied,
		// so we can prove the explicit helper re-applies it.
		update_option( self::ROLE_MAP_OPTION, [ self::CAP => [ 'editor' ] ] );
		wp_roles()->get_role( 'editor' )->remove_cap( self::CAP );
		$this->assertFalse( wp_roles()->get_role( 'editor' )->has_cap( self::CAP ), 'Precondition: the cap is not on the role.' );

		edac_sync_capability_roles();

		$this->assertTrue( wp_roles()->get_role( 'editor' )->has_cap( self::CAP ), 'The helper applies the stored map to the roles.' );

		// Cleanup.
		edac_ignore_capability()->sync_matrix( [] );
		delete_option( self::ROLE_MAP_OPTION );
	}
}
