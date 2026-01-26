<?php
/**
 * Tests for Activity Log Logger class.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\ActivityLog;

use EqualizeDigital\AccessibilityChecker\ActivityLog\Logger;
use WP_UnitTestCase;

/**
 * Test Logger class.
 */
class LoggerTest extends WP_UnitTestCase {

	/**
	 * Test logging an activity.
	 */
	public function test_log_activity() {
		$action  = 'post_scan';
		$message = 'Test scan activity';
		$metadata = [
			'post_id' => 1,
		];

		$log_id = Logger::log( $action, $message, $metadata );

		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );
	}

	/**
	 * Test logging activity with all metadata fields.
	 */
	public function test_log_activity_with_full_metadata() {
		$action  = 'ignore_issue';
		$message = 'Test ignore activity';
		$metadata = [
			'post_id'   => 1,
			'issue_id'  => 42,
			'rule_type' => 'error',
		];

		$log_id = Logger::log( $action, $message, $metadata );

		$this->assertIsInt( $log_id );
		$this->assertGreaterThan( 0, $log_id );
	}

	/**
	 * Test retrieving log entries.
	 */
	public function test_get_logs() {
		// Create a test log entry.
		Logger::log( 'post_scan', 'Test scan', [ 'post_id' => 1 ] );

		$logs = Logger::get_logs( [ 'limit' => 10 ] );

		$this->assertIsArray( $logs );
		$this->assertNotEmpty( $logs );
		$this->assertArrayHasKey( 'action', $logs[0] );
		$this->assertArrayHasKey( 'message', $logs[0] );
		$this->assertArrayHasKey( 'user_id', $logs[0] );
	}

	/**
	 * Test filtering logs by action.
	 */
	public function test_get_logs_filtered_by_action() {
		// Create test log entries.
		Logger::log( 'post_scan', 'Test scan 1', [ 'post_id' => 1 ] );
		Logger::log( 'clear_issues', 'Test clear', [ 'post_id' => 2 ] );
		Logger::log( 'post_scan', 'Test scan 2', [ 'post_id' => 3 ] );

		$logs = Logger::get_logs( [ 'action' => 'post_scan', 'limit' => 10 ] );

		$this->assertIsArray( $logs );
		$this->assertNotEmpty( $logs );
		foreach ( $logs as $log ) {
			$this->assertEquals( 'post_scan', $log['action'] );
		}
	}

	/**
	 * Test filtering logs by post_id.
	 */
	public function test_get_logs_filtered_by_post_id() {
		// Create test log entries.
		Logger::log( 'post_scan', 'Test scan', [ 'post_id' => 1 ] );
		Logger::log( 'post_scan', 'Test scan', [ 'post_id' => 2 ] );

		$logs = Logger::get_logs( [ 'post_id' => 1, 'limit' => 10 ] );

		$this->assertIsArray( $logs );
		foreach ( $logs as $log ) {
			$this->assertEquals( 1, (int) $log['post_id'] );
		}
	}

	/**
	 * Test log ordering.
	 */
	public function test_get_logs_ordering() {
		// Create test log entries.
		$id1 = Logger::log( 'post_scan', 'First', [ 'post_id' => 1 ] );
		sleep( 1 );
		$id2 = Logger::log( 'post_scan', 'Second', [ 'post_id' => 1 ] );

		// Test DESC ordering (default).
		$logs = Logger::get_logs( [ 'limit' => 2, 'order' => 'DESC' ] );
		$this->assertEquals( $id2, (int) $logs[0]['id'] );
		$this->assertEquals( $id1, (int) $logs[1]['id'] );

		// Test ASC ordering.
		$logs = Logger::get_logs( [ 'limit' => 2, 'order' => 'ASC' ] );
		$this->assertEquals( $id1, (int) $logs[0]['id'] );
		$this->assertEquals( $id2, (int) $logs[1]['id'] );
	}
}
