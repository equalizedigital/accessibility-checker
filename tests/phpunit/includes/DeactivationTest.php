<?php
/**
 * Tests for the plugin deactivation cleanup.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Orphaned_Issues_Cleanup;

/**
 * Tests for edac_deactivation().
 *
 * @covers ::edac_deactivation
 * @covers \EDAC\Admin\Orphaned_Issues_Cleanup::unschedule_event
 */
class DeactivationTest extends WP_UnitTestCase {

	/**
	 * Clears the option and cron events the deactivation routine removes.
	 *
	 * The plugin schedules the orphaned issues cleanup event on load, so
	 * every test starts from a known state rather than inheriting it.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_option( 'edac_activation_date' );
		wp_clear_scheduled_hook( Orphaned_Issues_Cleanup::EVENT );
		wp_clear_scheduled_hook( 'edac_check_license_hook' );
	}

	/**
	 * Tests the activation date option is deleted.
	 *
	 * @return void
	 */
	public function test_deletes_the_activation_date_option(): void {
		update_option( 'edac_activation_date', '2026-01-01 00:00:00' );

		$this->assertSame( '2026-01-01 00:00:00', get_option( 'edac_activation_date' ) );

		edac_deactivation();

		$this->assertFalse( get_option( 'edac_activation_date' ) );
	}

	/**
	 * Tests the orphaned issues cleanup event is unscheduled.
	 *
	 * @return void
	 */
	public function test_unschedules_the_orphaned_issues_cleanup_event(): void {
		wp_schedule_event( time() + HOUR_IN_SECONDS, Orphaned_Issues_Cleanup::RECURRENCE, Orphaned_Issues_Cleanup::EVENT );

		$this->assertIsInt( wp_next_scheduled( Orphaned_Issues_Cleanup::EVENT ) );

		edac_deactivation();

		$this->assertFalse( wp_next_scheduled( Orphaned_Issues_Cleanup::EVENT ) );
	}

	/**
	 * Tests the daily license check event is unscheduled.
	 *
	 * @return void
	 */
	public function test_unschedules_the_license_check_event(): void {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'edac_check_license_hook' );

		$this->assertIsInt( wp_next_scheduled( 'edac_check_license_hook' ) );

		edac_deactivation();

		$this->assertFalse( wp_next_scheduled( 'edac_check_license_hook' ) );
	}

	/**
	 * Tests a repeated call does not error or leave anything scheduled.
	 *
	 * @return void
	 */
	public function test_is_safe_to_run_twice(): void {
		edac_deactivation();
		edac_deactivation();

		$this->assertFalse( get_option( 'edac_activation_date' ) );
		$this->assertFalse( wp_next_scheduled( Orphaned_Issues_Cleanup::EVENT ) );
		$this->assertFalse( wp_next_scheduled( 'edac_check_license_hook' ) );
	}
}
