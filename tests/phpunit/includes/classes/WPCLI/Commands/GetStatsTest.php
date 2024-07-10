<?php
/**
 * Test the GetStats command.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Update_Database;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\BootstrapCLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\GetStats;

/**
 * Test cases to verify that the GetStats command can get the stats.
 */
class GetStatsTest extends WP_UnitTestCase {

	/**
	 * Sets up the mock and injects it into the class under test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		// since this is a synthetic run on WP-CLI, we need to define WP_CLI.
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}

		$wp_cli = new Mock_WP_CLI();

		( new BootstrapCLI( $wp_cli ) )->register();
		$this->get_stats = new GetStats( $wp_cli );

		// create database tables if they don't exist.
		DatabaseHelpers::create_table();

		parent::setUp();
	}

	/**
	 * Drop the table to clean up after tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		DatabaseHelpers::drop_table();

		parent::tearDown();
	}

	/**
	 * Test the get stats command errors if the post doesn't exist/
	 */
	public function test_get_stats_command_errors_when_post_does_not_exist() {

		$non_existent_id = 132456789;

		ob_start();
		$this->get_stats->__invoke( [ $non_existent_id ], [] );
		$stats = ob_get_clean();

		// check we have the expected error.
		$this->assertEquals( 'Error: Post ID ' . $non_existent_id . ' does not exist.', $stats );
	}

	/**
	 * Test the get stats command can complete when no stats exist for a post.
	 */
	public function test_get_stats_command_completes_when_no_stats_exist_for_post() {

		$post_id = $this->factory()->post->create();

		ob_start();
		$this->get_stats->__invoke( [ $post_id ], [] );
		$stats = ob_get_clean();

		$this->assertStringStartsWith( 'Success: Either the post is not yet scanned or all tests passed', $stats );
	}

	/**
	 * Test the get stats command can get stats for a post can get the stats.
	 */
	public function test_get_stats_command_returns_results_when_stats_exist_for_post() {
		$post_id = $this->factory()->post->create();
		$post    = get_post( $post_id );

		DatabaseHelpers::insert_test_issue_to_db( $post );

		ob_start();
		$this->get_stats->__invoke( [ $post_id ], [] );
		$stats = ob_get_clean();

		$this->assertStringStartsWith( 'Success: {', $stats );
	}

	/**
	 * Test that the get stats command can get just the requested stats keys.
	 *
	 * @return void
	 */
	public function test_get_stats_can_get_filtered_stats_when_requested() {
		$post_id = $this->factory()->post->create();
		$post    = get_post( $post_id );

		DatabaseHelpers::insert_test_issue_to_db( $post );

		ob_start();
		$this->get_stats->__invoke( [ $post_id ], [ 'stat' => 'passed_tests' ] );
		$stats = ob_get_clean();

		// check the output is still a success message.
		$this->assertStringStartsWith( 'Success: {', $stats );

		$stats_array = json_decode(
			html_entity_decode(
				str_replace( 'Success: ', '', $stats )
			),
			true
		);

		// is only one key long and is the key we requested.
		$this->assertCount( 1, $stats_array );
		$this->assertArrayHasKey( 'passed_tests', $stats_array );

		ob_start();
		$this->get_stats->__invoke( [ $post_id ], [ 'stat' => 'passed_tests, errors, warnings' ] );
		$stats = ob_get_clean();

		// check the output is still a success message.
		$this->assertStringStartsWith( 'Success: {', $stats );

		$stats_array = json_decode(
			html_entity_decode(
				str_replace( 'Success: ', '', $stats )
			),
			true
		);

		// is 3 keys.
		$this->assertCount( 3, $stats_array );
		$this->assertArrayHasKey( 'passed_tests', $stats_array );
		$this->assertArrayHasKey( 'errors', $stats_array );
		$this->assertArrayHasKey( 'warnings', $stats_array );
	}

	/**
	 * Test the get stats command errors when filtered stats are requested for non-existent keys.
	 */
	public function test_get_stats_errors_when_filtered_stats_are_requested_for_non_existent_keys() {
		$post_id = $this->factory()->post->create();
		$post    = get_post( $post_id );

		DatabaseHelpers::insert_test_issue_to_db( $post );

		ob_start();
		$this->get_stats->__invoke( [ $post_id ], [ 'stat' => 'a_non_existant_stat' ] );
		$stats = ob_get_clean();

		$this->assertStringStartsWith( 'Error: Invalid stat key', $stats );
	}
}
