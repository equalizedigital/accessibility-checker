<?php
/**
 * Tests for the edac_user_can_* capability reader helpers.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for the edac_user_can_*() helpers in includes/options-page.php.
 *
 * These helpers are a documented cross-plugin API: each add-on (Pro, Export,
 * Audit History) feature-detects them with function_exists() and gates its own
 * admin page, REST route or AJAX handler on the boolean result, falling back to
 * manage_options when an older free plugin lacks the helper. This pins the
 * contract - which capability slug each helper reads, the manage_options bypass
 * the free bundle installs through SyncCapability, and the two deprecated shims
 * that alias the dismiss family - so an accidental rename or re-mapping fails
 * loudly here instead of silently downgrading an add-on's gate at runtime.
 *
 * @covers ::edac_user_can_dismiss_own_issues
 * @covers ::edac_user_can_dismiss_issues
 * @covers ::edac_user_can_dismiss_issues_globally
 * @covers ::edac_user_can_ignore
 * @covers ::edac_user_can_ignore_globally
 * @covers ::edac_user_can_access_issues_explorer
 * @covers ::edac_user_can_view_audit_history
 * @covers ::edac_user_can_export_data
 * @covers ::edac_user_can_run_full_site_scan
 * @covers ::edac_user_can_use_frontend_highlighter
 */
class OptionsPageUserCanHelpersTest extends WP_UnitTestCase {

	/**
	 * Reset the current user after each test. Capability grants in these tests
	 * go onto the user object (never a shared role), so the test suite's
	 * per-test transaction rolls them back and no role cleanup is needed.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Maps each helper to the capability slug it must read.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_helper_capability_map(): array {
		return [
			'edac_user_can_dismiss_own_issues'       => [ 'edac_user_can_dismiss_own_issues', 'edac_dismiss_own_issues' ],
			'edac_user_can_dismiss_issues'           => [ 'edac_user_can_dismiss_issues', 'edac_dismiss_issues' ],
			'edac_user_can_dismiss_issues_globally'  => [ 'edac_user_can_dismiss_issues_globally', 'edac_dismiss_issues_globally' ],
			'edac_user_can_access_issues_explorer'   => [ 'edac_user_can_access_issues_explorer', 'edac_issues_explorer_access' ],
			'edac_user_can_view_audit_history'       => [ 'edac_user_can_view_audit_history', 'edac_view_audit_history' ],
			'edac_user_can_export_data'              => [ 'edac_user_can_export_data', 'edac_export_data' ],
			'edac_user_can_run_full_site_scan'       => [ 'edac_user_can_run_full_site_scan', 'edac_full_site_scan' ],
			'edac_user_can_use_frontend_highlighter' => [ 'edac_user_can_use_frontend_highlighter', 'edac_view_frontend_highlighter' ],
		];
	}

	/**
	 * A user with no Accessibility Checker capability gets false from every
	 * helper.
	 *
	 * @dataProvider provider_helper_capability_map
	 *
	 * @param string $helper The helper function name.
	 * @param string $capability The capability it must read.
	 * @return void
	 */
	public function test_helper_is_false_without_the_capability( string $helper, string $capability ): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertFalse(
			$helper(),
			sprintf( '%s() must be false when the user does not hold %s.', $helper, $capability )
		);
	}

	/**
	 * A user holding the capability through a direct user-level grant gets true
	 * from the matching helper.
	 *
	 * @dataProvider provider_helper_capability_map
	 *
	 * @param string $helper The helper function name.
	 * @param string $capability The capability it must read.
	 * @return void
	 */
	public function test_helper_is_true_when_the_capability_is_held( string $helper, string $capability ): void {
		wp_set_current_user( $this->user_with_capability( $capability ) );

		$this->assertTrue(
			$helper(),
			sprintf( '%s() must be true when the user holds %s.', $helper, $capability )
		);
	}

	/**
	 * The helper and a raw current_user_can() check against the mapped slug
	 * never disagree: the helper is a thin reader of that one capability.
	 *
	 * @dataProvider provider_helper_capability_map
	 *
	 * @param string $helper The helper function name.
	 * @param string $capability The capability it must read.
	 * @return void
	 */
	public function test_helper_matches_current_user_can_for_its_capability( string $helper, string $capability ): void {
		wp_set_current_user( $this->user_with_capability( $capability ) );

		$this->assertSame( current_user_can( $capability ), $helper() );
	}

	/**
	 * Granting one capability enables only the helper that reads it - the
	 * others must stay false.
	 *
	 * @return void
	 */
	public function test_helper_reads_only_its_own_capability(): void {
		wp_set_current_user( $this->user_with_capability( 'edac_export_data' ) );

		$this->assertTrue( edac_user_can_export_data() );
		$this->assertFalse( edac_user_can_view_audit_history() );
		$this->assertFalse( edac_user_can_run_full_site_scan() );
		$this->assertFalse( edac_user_can_access_issues_explorer() );
		$this->assertFalse( edac_user_can_dismiss_own_issues() );
	}

	/**
	 * A manage_options (administrator) user passes the helpers for the three
	 * capabilities the free plugin owns, without the capability being assigned
	 * to their role - this is the SyncCapability manage_options bypass.
	 *
	 * @return void
	 */
	public function test_manage_options_user_passes_the_free_bundle_helpers(): void {
		// Register the real bundle instance so its map_meta_cap bypass is live.
		edac_ignore_capability();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertTrue( edac_user_can_dismiss_own_issues() );
		$this->assertTrue( edac_user_can_dismiss_issues() );
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );
	}

	/**
	 * The bypass only covers capabilities in the active bundle. A capability no
	 * installed plugin has contributed (here, an add-on's export capability in a
	 * free-only install) is not bypassed, so a manage_options user without it
	 * gets false.
	 *
	 * @return void
	 */
	public function test_manage_options_user_is_not_bypassed_for_a_capability_outside_the_bundle(): void {
		edac_ignore_capability();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertNotContains( 'edac_export_data', edac_capability_bundle(), 'Precondition: the export capability is not part of the free bundle.' );
		$this->assertFalse(
			edac_user_can_export_data(),
			'The manage_options bypass must only apply to capabilities in the active bundle.'
		);
	}

	/**
	 * The deprecated edac_user_can_ignore() shim is true when the user holds
	 * either per-post dismiss capability, and false when they hold neither.
	 *
	 * @return void
	 */
	public function test_deprecated_ignore_shim_is_true_for_either_dismiss_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertFalse( edac_user_can_ignore(), 'Holding neither dismiss capability must be false.' );

		wp_set_current_user( $this->user_with_capability( 'edac_dismiss_issues' ) );
		$this->assertTrue( edac_user_can_ignore(), 'Holding edac_dismiss_issues alone must be true.' );

		wp_set_current_user( $this->user_with_capability( 'edac_dismiss_own_issues' ) );
		$this->assertTrue( edac_user_can_ignore(), 'Holding edac_dismiss_own_issues alone must be true.' );
	}

	/**
	 * The deprecated edac_user_can_ignore_globally() shim tracks the global
	 * dismiss capability and is unaffected by the per-post dismiss ones.
	 *
	 * @return void
	 */
	public function test_deprecated_ignore_globally_shim_tracks_the_global_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertFalse( edac_user_can_ignore_globally() );

		wp_set_current_user( $this->user_with_capability( 'edac_dismiss_issues' ) );
		$this->assertFalse(
			edac_user_can_ignore_globally(),
			'A per-post dismiss capability must not satisfy the global shim.'
		);

		wp_set_current_user( $this->user_with_capability( 'edac_dismiss_issues_globally' ) );
		$this->assertTrue( edac_user_can_ignore_globally() );
	}

	/**
	 * Create a subscriber and grant them one capability directly on the user
	 * object, then make them the current user.
	 *
	 * @param string $capability The capability to grant.
	 * @return int The new user's ID.
	 */
	private function user_with_capability( string $capability ): int {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$user = new WP_User( $user_id );
		$user->add_cap( $capability );

		return $user_id;
	}
}
