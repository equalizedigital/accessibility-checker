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
	private const USER_GRANTS_OPT = 'edac_capability_user_grants';

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
				self::USER_GRANTS_OPT,
				'edac_synced_capabilities_' . self::ROLE_MAP_OPTION,
				'edac_capability_migration_version_' . self::ROLE_MAP_OPTION,
				'edac_synced_user_grants_' . self::ROLE_MAP_OPTION,
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
	 * A capability granted directly to a user passes the helper for that user.
	 *
	 * @return void
	 */
	public function test_per_user_grant_passes_helper() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		edac_ignore_capability()->sync_user_grants( [ self::CAP => [ $user_id ] ] );

		wp_set_current_user( $user_id );
		$this->assertTrue( edac_user_can_dismiss_own_issues() );

		edac_ignore_capability()->sync_user_grants( [ self::CAP => [] ] );
		// Reload the current user so its cached capabilities reflect the revoke.
		wp_set_current_user( 0 );
		wp_set_current_user( $user_id );
		$this->assertFalse( edac_user_can_dismiss_own_issues() );
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
}
