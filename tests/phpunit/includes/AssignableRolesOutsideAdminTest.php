<?php
/**
 * Regression test: edac_assignable_roles() must work without wp-admin loaded.
 *
 * @package accessibility-checker
 */

/**
 * Edac_assignable_roles() used to call the real get_editable_roles(), which
 * lives in wp-admin/includes/user.php and is only autoloaded within the
 * wp-admin bootstrap (is_admin()). Because edac_assignable_roles() is called
 * from edac_sanitize_capability_role_map(), which runs on every
 * update_option( 'edac_capability_role_map', ... ) via the sanitize_option
 * filter, that made a fatal error reachable from any caller outside wp-admin -
 * REST routes (e.g. the accessibility-checker-multisite settings-clone route)
 * and WP-CLI, neither of which load wp-admin. Reproduced via wp-env's `wp eval`
 * and the capability-migration e2e spec before the fix. The fix inlines
 * get_editable_roles()'s own logic (apply_filters( 'editable_roles',
 * wp_roles()->roles )) instead of calling the wp-admin-only function.
 */
class AssignableRolesOutsideAdminTest extends WP_UnitTestCase {

	/**
	 * Runtime behavior check: edac_assignable_roles() still returns the
	 * correct role set (non-admin roles, via the editable_roles filter).
	 *
	 * This PHPUnit environment has wp-admin/includes/user.php loaded (WP
	 * core's own test bootstrap pulls it in), so this alone would NOT have
	 * caught the original bug - real REST requests and WP-CLI do not load
	 * it. See test_source_does_not_call_wp_admin_only_function() below for
	 * the part of this regression that actually needs source inspection.
	 *
	 * @return void
	 */
	public function test_returns_the_correct_role_set() {
		$roles = edac_assignable_roles();

		$this->assertNotEmpty( $roles );
		$this->assertArrayHasKey( 'editor', $roles );
		$this->assertArrayNotHasKey( 'administrator', $roles, 'Administrator must still be excluded (manage_options bypass).' );
	}

	/**
	 * Documents the fix exists in source: edac_assignable_roles() must not
	 * call the wp-admin-only get_editable_roles() - confirmed via a quick
	 * check that this PHPUnit environment (which loads wp-admin/includes/
	 * user.php) is NOT representative of the REST/WP-CLI contexts the bug
	 * actually manifested in, so a plain runtime call here couldn't
	 * distinguish "fixed" from "still calling the wp-admin function and
	 * getting lucky that it happens to be loaded."
	 *
	 * @return void
	 */
	public function test_source_does_not_call_wp_admin_only_function() {
		$source = file_get_contents( EDAC_PLUGIN_DIR . 'includes/options-page.php' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reads a local plugin file for a static source check, not a remote URL.

		$start = strpos( $source, 'function edac_assignable_roles' );
		$this->assertNotFalse( $start, 'edac_assignable_roles() should still exist.' );
		$end = strpos( $source, "\n}\n", $start );
		$this->assertNotFalse( $end, 'Could not locate the end of edac_assignable_roles() in the source - if this fails, the function body no longer ends with a line containing only "}" and the detection below needs updating rather than silently scanning a garbled slice.' );
		$body = substr( $source, $start, $end - $start );

		$this->assertStringNotContainsString(
			'get_editable_roles(',
			$body,
			'edac_assignable_roles() must not call the wp-admin-only get_editable_roles() - it is unavailable to callers of edac_sanitize_capability_role_map() outside wp-admin (REST routes like the multisite settings-clone route, and WP-CLI).'
		);
		$this->assertStringContainsString(
			"apply_filters( 'editable_roles'",
			$body,
			'edac_assignable_roles() should reimplement get_editable_roles() inline (apply_filters( \'editable_roles\', wp_roles()->roles )) so third-party editable_roles filters still apply.'
		);
	}

	/**
	 * Sanity check that the full sanitizer path (edac_sanitize_capability_role_map(),
	 * reached via the sanitize_option_edac_capability_role_map filter on a
	 * plain update_option() call) still floor-validates correctly after the
	 * fix - this is the exact call chain the multisite clone route and
	 * WP-CLI both go through.
	 *
	 * @return void
	 */
	public function test_role_map_option_write_still_floor_validates() {
		update_option( 'edac_capability_role_map', [ 'edac_dismiss_own_issues' => [ 'editor', 'subscriber' ] ] );

		$stored = get_option( 'edac_capability_role_map' );
		$this->assertIsArray( $stored, 'The sanitized role map should be stored as an array.' );
		$this->assertArrayHasKey(
			'edac_dismiss_own_issues',
			$stored,
			'The sanitizer should keep the capability entry when at least one role survives floor validation.'
		);
		$this->assertContains( 'editor', $stored['edac_dismiss_own_issues'] );
		$this->assertNotContains( 'subscriber', $stored['edac_dismiss_own_issues'], 'Floor validation should still run and strip the ineligible role.' );

		delete_option( 'edac_capability_role_map' );
	}
}
