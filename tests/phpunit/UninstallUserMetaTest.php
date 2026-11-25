<?php
/**
 * PHPUnit tests for user meta cleanup in uninstall.php.
 *
 * @package Accessibility_Checker\Tests
 */

/**
 * Class UninstallUserMetaTest
 */
class UninstallUserMetaTest extends WP_UnitTestCase {

	/**
	 * User meta keys the plugin writes.
	 *
	 * @var string[]
	 */
	private const META_KEYS = [
		'edac_admin_toolbar_display',
		'edac_email_optin',
		'edac_email_optin_seen_modal',
		'edac_welcome_cta_dismissed',
		'edac_dashboard_cta_dismissed',
	];

	/**
	 * Runs the uninstall script.
	 */
	private function run_uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'accessibility-checker/accessibility-checker.php' );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';
	}

	/**
	 * Sets every plugin meta key on the given users.
	 *
	 * @param int[] $user_ids User IDs.
	 */
	private function seed_meta( array $user_ids ) {
		foreach ( $user_ids as $user_id ) {
			foreach ( self::META_KEYS as $key ) {
				update_user_meta( $user_id, $key, 'x' );
			}
			update_user_meta( $user_id, 'unrelated_meta', 'keep' );
		}
	}

	/**
	 * User meta is removed for all users when delete data is enabled.
	 */
	public function test_user_meta_removed_when_delete_data_enabled() {
		$users = self::factory()->user->create_many( 2 );
		$this->seed_meta( $users );
		update_option( 'edac_delete_data', true );

		$this->run_uninstall();

		foreach ( $users as $user_id ) {
			foreach ( self::META_KEYS as $key ) {
				$this->assertSame( '', get_user_meta( $user_id, $key, true ), "$key should be removed." );
			}
			$this->assertSame( 'keep', get_user_meta( $user_id, 'unrelated_meta', true ) );
		}
	}

	/**
	 * User meta is kept when delete data is not enabled.
	 */
	public function test_user_meta_kept_when_delete_data_disabled() {
		$users = self::factory()->user->create_many( 1 );
		$this->seed_meta( $users );
		delete_option( 'edac_delete_data' );

		$this->run_uninstall();

		foreach ( self::META_KEYS as $key ) {
			$this->assertSame( 'x', get_user_meta( $users[0], $key, true ), "$key should be kept." );
		}
	}
}
