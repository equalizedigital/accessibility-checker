<?php
/**
 * PHPUnit tests for PermissionsPage's optimistic-concurrency save guard.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage;

/**
 * The Permissions page bakes the full current role map into hidden inputs at
 * render time; handle_save() used to unconditionally overwrite the option
 * with whatever the browser submitted - so a second admin saving from a page
 * load that predates a first admin's save would silently discard it (lost
 * update). handle_save() now rejects a save whose submitted
 * edac_role_map_revision doesn't match the option's current value.
 */
class PermissionsPageConcurrencyTest extends WP_UnitTestCase {

	private const ROLE_MAP_OPTION = 'edac_capability_role_map';

	/**
	 * Set up: log in as an admin and intercept wp_safe_redirect() so
	 * handle_save()'s trailing exit is never reached mid-test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		add_filter(
			'wp_redirect',
			static function ( $location ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test exception message.
				throw new Exception( 'Redirect to: ' . $location );
			}
		);
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( self::ROLE_MAP_OPTION );
		unset( $_POST['edac_role_map'], $_POST['edac_role_map_revision'], $_POST['_wpnonce'], $_REQUEST['_wpnonce'] );
		remove_all_filters( 'wp_redirect' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Compute the same fingerprint handle_save() checks against, via
	 * reflection since role_map_revision() is a private implementation
	 * detail - tests should not force it public just to be reachable here.
	 *
	 * @param PermissionsPage $page     The page instance.
	 * @param array           $role_map The role map to fingerprint.
	 * @return string
	 */
	private function revision_of( PermissionsPage $page, array $role_map ): string {
		$method = new ReflectionMethod( PermissionsPage::class, 'role_map_revision' );
		$method->setAccessible( true );

		return $method->invoke( $page, $role_map );
	}

	/**
	 * Populate $_POST the way the Permissions form does and dispatch the save.
	 *
	 * @param array  $role_map_submission The edac_role_map POST value.
	 * @param string $revision            The edac_role_map_revision POST value.
	 * @return void
	 */
	private function post_save( array $role_map_submission, string $revision ): void {
		$_POST['edac_role_map']          = $role_map_submission;
		$_POST['edac_role_map_revision'] = $revision;
		$_POST['_wpnonce']               = wp_create_nonce( PermissionsPage::SAVE_ACTION );
		// check_admin_referer() reads $_REQUEST, not $_POST - PHP does not
		// dynamically re-merge $_REQUEST when $_POST is mutated mid-test.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- test fixture setup, not request handling; the nonce was just created above on this same line group.
		$_REQUEST['_wpnonce'] = $_POST['_wpnonce'];
	}

	/**
	 * A save whose submitted revision matches the option's current state
	 * (the common case: nobody else saved in between) must persist.
	 *
	 * @return void
	 */
	public function test_save_succeeds_when_revision_matches_current_state() {
		update_option( self::ROLE_MAP_OPTION, [ 'edac_dismiss_own_issues' => [ 'editor' ] ] );

		$page = new PermissionsPage( 'manage_options' );
		$this->post_save(
			[ 'edac_dismiss_own_issues' => [ 'editor', 'author' ] ],
			$this->revision_of( $page, [ 'edac_dismiss_own_issues' => [ 'editor' ] ] )
		);

		try {
			$page->handle_save();
			$this->fail( 'handle_save() should redirect (and therefore throw via the wp_redirect interceptor).' );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'updated=true', $e->getMessage(), 'A successful save must redirect with updated=true.' );
		}

		$stored = get_option( self::ROLE_MAP_OPTION );
		$this->assertContains( 'author', $stored['edac_dismiss_own_issues'], 'The new submission must be persisted when the revision matches.' );
	}

	/**
	 * A save whose submitted revision does NOT match the option's current
	 * state (someone else saved after this admin's page loaded) must be
	 * rejected, and must not touch the stored option at all.
	 *
	 * @return void
	 */
	public function test_save_rejected_when_revision_is_stale() {
		update_option( self::ROLE_MAP_OPTION, [ 'edac_dismiss_own_issues' => [ 'editor' ] ] );

		$page = new PermissionsPage( 'manage_options' );
		// Fingerprint of a DIFFERENT starting state than what's actually
		// stored now - simulates a page loaded before someone else's save.
		$stale_revision = $this->revision_of( $page, [] );

		$this->post_save( [ 'edac_dismiss_own_issues' => [ 'author' ] ], $stale_revision );

		try {
			$page->handle_save();
			$this->fail( 'handle_save() should redirect (and therefore throw via the wp_redirect interceptor).' );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'updated=conflict', $e->getMessage(), 'A stale-revision save must redirect with updated=conflict.' );
		}

		$stored = get_option( self::ROLE_MAP_OPTION );
		$this->assertSame(
			[ 'editor' ],
			$stored['edac_dismiss_own_issues'],
			'A rejected save must leave the stored role map completely untouched - not even partially applied.'
		);
	}

	/**
	 * The very first save ever made (option not yet set) must succeed: the
	 * fingerprint of an empty/absent map must match what the page rendered
	 * from get_option(..., []) on a site that has never saved this option.
	 *
	 * @return void
	 */
	public function test_first_ever_save_succeeds_against_unset_option() {
		delete_option( self::ROLE_MAP_OPTION );

		$page = new PermissionsPage( 'manage_options' );
		$this->post_save(
			[ 'edac_dismiss_own_issues' => [ 'editor' ] ],
			$this->revision_of( $page, [] )
		);

		try {
			$page->handle_save();
			$this->fail( 'handle_save() should redirect (and therefore throw via the wp_redirect interceptor).' );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'updated=true', $e->getMessage() );
		}

		$stored = get_option( self::ROLE_MAP_OPTION );
		$this->assertContains( 'editor', $stored['edac_dismiss_own_issues'] );
	}

	/**
	 * The fingerprint must be stable across irrelevant key/array ordering
	 * differences returned by get_option(), so it never produces a false
	 * conflict for a map that is semantically identical.
	 *
	 * @return void
	 */
	public function test_revision_is_stable_regardless_of_key_order() {
		$page = new PermissionsPage( 'manage_options' );

		$a = $this->revision_of(
			$page,
			[
				'edac_dismiss_own_issues' => [ 'editor', 'author' ],
				'edac_dismiss_issues'     => [ 'editor' ],
			]
		);
		$b = $this->revision_of(
			$page,
			[
				'edac_dismiss_issues'     => [ 'editor' ],
				'edac_dismiss_own_issues' => [ 'author', 'editor' ],
			]
		);

		$this->assertSame( $a, $b, 'Key order and within-role-list order must not affect the fingerprint.' );
	}
}
