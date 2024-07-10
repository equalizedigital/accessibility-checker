<?php
/**
 * Test cases for the get-site-stats command.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\GetSiteStats;

/**
 * Test cases to verify that the get-site-stats command operates correctly in various situations.
 */
class GetSiteStatsTest extends WP_UnitTestCase {

	/**
	 * Set the WP_CLI constant, create property of the class under test
	 * and create the database.
	 */
	protected function setUp(): void {
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}

		$this->get_site_stats = new GetSiteStats( new Mock_WP_CLI() );

		DatabaseHelpers::create_table();

		parent::setUp();
	}

	/**
	 * Drop the table to clean up after tests.
	 */
	protected function tearDown(): void {
		DatabaseHelpers::drop_table();
		parent::tearDown();
	}

	/**
	 * Test the get site stats command can complete when run.
	 */
	public function test_get_site_stats_command_can_complete() {
		ob_start();
		$this->get_site_stats->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: {', $output );
	}

	/**
	 * Test the get site stats command can return filtered stats for one key or multiple keys.
	 */
	public function test_get_site_stats_command_can_return_filtered_stats() {

		ob_start();
		$this->get_site_stats->__invoke( [], [ 'stat' => 'rule_count' ] );
		$stats = ob_get_clean();

		$this->assertStringContainsString( 'Success: {', $stats );

		$this->assertStringContainsString( 'Success: {', $stats );

		$stats_array = json_decode(
			html_entity_decode(
				str_replace( 'Success: ', '', $stats )
			),
			true
		);

		$this->assertEquals( 1, count( $stats_array ) );
		$this->assertArrayHasKey( 'rule_count', $stats_array );

		ob_start();
		$this->get_site_stats->__invoke( [], [ 'stat' => 'rule_count,tests_count' ] );
		$stats = ob_get_clean();

		$this->assertStringContainsString( 'Success: {', $stats );

		$stats_array = json_decode(
			html_entity_decode(
				str_replace( 'Success: ', '', $stats )
			),
			true
		);

		$this->assertEquals( 2, count( $stats_array ) );
		$this->assertArrayHasKey( 'rule_count', $stats_array );
		$this->assertArrayHasKey( 'tests_count', $stats_array );
	}

	/**
	 * Test the get site stats command errors if the requested stat key doesn't exist.
	 */
	public function test_get_site_stats_command_errors_when_stat_key_does_not_exist() {
		ob_start();
		$this->get_site_stats->__invoke( [], [ 'stat' => 'non_existent_key' ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Error: Stat key: non_existent_key not found in stats.', $output );
	}

	/**
	 * Test the get site stats command can clear the cache.
	 */
	public function test_get_site_stats_command_can_clear_cache() {
		ob_start();
		$this->get_site_stats->__invoke( [], [] );
		$stats_initial = ob_get_clean();

		DatabaseHelpers::insert_test_issue_to_db( get_post( $this->factory()->post->create() ) );

		ob_start();
		$this->get_site_stats->__invoke( [], [ 'clear-cache' => true ] );
		$stats_after_clear = ob_get_clean();

		$this->assertNotEquals( $stats_initial, $stats_after_clear );
	}
}
