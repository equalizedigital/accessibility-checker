<?php
/**
 * Tests for Ajax class
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Ajax;
use EDAC\Admin\OptIn\Email_Opt_In;
use EDAC\Admin\Helpers;
use EDAC\Inc\Summary_Generator;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Test class for Ajax functionality using WordPress test framework
 */
class ClassAjaxTest extends WP_UnitTestCase {

	/**
	 * Instance of the Ajax class.
	 *
	 * @var Ajax $ajax
	 */
	private $ajax;

	/**
	 * Test post ID for testing
	 *
	 * @var int $test_post_id
	 */
	private $test_post_id;

	/**
	 * Set up test environment before each test
	 */
	protected function setUp(): void {
		parent::setUp();
		
		$this->ajax = new Ajax();
		
		// Create a test post for AJAX operations
		$this->test_post_id = $this->factory->post->create( array(
			'post_title'   => 'Test Post for Ajax',
			'post_content' => 'This is test content for readability analysis. It should be long enough to generate meaningful readability scores.',
			'post_status'  => 'publish',
		) );
		
		// Mock nonce verification for tests
		add_filter( 'wp_verify_nonce', array( $this, 'mock_nonce_verification' ), 10, 2 );
		
		// Set up user with appropriate capabilities
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Tear down test environment after each test
	 */
	protected function tearDown(): void {
		// Clean up $_REQUEST
		$_REQUEST = array();
		
		// Remove filters
		remove_filter( 'wp_verify_nonce', array( $this, 'mock_nonce_verification' ) );
		
		parent::tearDown();
	}

	/**
	 * Mock nonce verification for testing
	 *
	 * @param string $nonce The nonce to verify.
	 * @return bool
	 */
	public function mock_nonce_verification( $nonce ) {
		// Return true for valid test nonces, false for invalid ones
		return $nonce !== 'invalid_nonce';
	}

	/**
	 * Test Ajax class constructor
	 */
	public function test_constructor() {
		$ajax = new Ajax();
		$this->assertInstanceOf( Ajax::class, $ajax );
	}

	/**
	 * Test init_hooks method registers correct WordPress hooks
	 */
	public function test_init_hooks() {
		$this->ajax->init_hooks();
		
		// Verify hooks are registered
		$this->assertTrue( has_action( 'wp_ajax_edac_summary_ajax' ) );
		$this->assertTrue( has_action( 'wp_ajax_edac_details_ajax' ) );
		$this->assertTrue( has_action( 'wp_ajax_edac_readability_ajax' ) );
		$this->assertTrue( has_action( 'wp_ajax_edac_insert_ignore_data' ) );
		$this->assertTrue( has_action( 'wp_ajax_edac_update_simplified_summary' ) );
		$this->assertTrue( has_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax' ) );
		$this->assertTrue( has_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax' ) );
	}

	/**
	 * Test summary method with invalid nonce returns error
	 */
	public function test_summary_invalid_nonce() {
		$_REQUEST['nonce'] = 'invalid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		// Capture output
		ob_start();
		$this->ajax->summary();
		$output = ob_get_clean();
		
		// Verify error response
		$this->assertStringContainsString( 'Permission Denied', $output );
	}

	/**
	 * Test summary method without post_id returns error
	 */
	public function test_summary_missing_post_id() {
		$_REQUEST['nonce'] = 'valid_nonce';
		// Intentionally omit post_id
		
		ob_start();
		$this->ajax->summary();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'The post ID was not set', $output );
	}

	/**
	 * Test summary method with valid data generates HTML
	 */
	public function test_summary_valid_data() {
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		// Mock some required functions and options
		add_filter( 'pre_option_edac_password_protected', '__return_false' );
		add_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'none'; } );
		
		// Mock Summary_Generator functionality
		$this->mock_summary_generator();
		
		ob_start();
		$this->ajax->summary();
		$output = ob_get_clean();
		
		// Should contain success response with HTML content
		$this->assertStringContainsString( '"success":true', $output );
		$this->assertStringContainsString( 'edac-summary-grid', $output );
		
		// Clean up
		remove_filter( 'pre_option_edac_password_protected', '__return_false' );
		remove_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'none'; } );
	}

	/**
	 * Test summary method with password protection enabled
	 */
	public function test_summary_with_password_protection() {
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		// Enable password protection
		add_filter( 'pre_option_edac_password_protected', '__return_true' );
		add_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'none'; } );
		
		$this->mock_summary_generator();
		
		ob_start();
		$this->ajax->summary();
		$output = ob_get_clean();
		
		// Should contain password protection notice
		$this->assertStringContainsString( 'edac-summary-notice', $output );
		
		remove_filter( 'pre_option_edac_password_protected', '__return_true' );
		remove_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'none'; } );
	}

	/**
	 * Test details method with invalid nonce
	 */
	public function test_details_invalid_nonce() {
		$_REQUEST['nonce'] = 'invalid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		ob_start();
		$this->ajax->details();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'Permission Denied', $output );
	}

	/**
	 * Test details method without post_id
	 */
	public function test_details_missing_post_id() {
		$_REQUEST['nonce'] = 'valid_nonce';
		
		ob_start();
		$this->ajax->details();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'The post ID was not set', $output );
	}

	/**
	 * Test details method with invalid table name
	 */
	public function test_details_invalid_table_name() {
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		// Mock function to return invalid table name
		add_filter( 'edac_get_valid_table_name', '__return_false' );
		
		ob_start();
		$this->ajax->details();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'Invalid table name', $output );
		
		remove_filter( 'edac_get_valid_table_name', '__return_false' );
	}

	/**
	 * Test readability method with invalid nonce
	 */
	public function test_readability_invalid_nonce() {
		$_REQUEST['nonce'] = 'invalid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		ob_start();
		$this->ajax->readability();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'Permission Denied', $output );
	}

	/**
	 * Test readability method without post_id
	 */
	public function test_readability_missing_post_id() {
		$_REQUEST['nonce'] = 'valid_nonce';
		
		ob_start();
		$this->ajax->readability();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'The post ID was not set', $output );
	}

	/**
	 * Test readability method with valid data
	 */
	public function test_readability_valid_data() {
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		// Mock required options
		add_filter( 'pre_option_edac_simplified_summary_position', function() { return 'before'; } );
		add_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'always'; } );
		
		// Mock post meta for simplified summary
		add_post_meta( $this->test_post_id, '_edac_simplified_summary', 'This is a simplified summary.' );
		add_post_meta( $this->test_post_id, '_edac_summary', array( 'readability' => '10th' ) );
		
		ob_start();
		$this->ajax->readability();
		$output = ob_get_clean();
		
		// Should contain readability analysis
		$this->assertStringContainsString( '"success":true', $output );
		$this->assertStringContainsString( 'edac-readability-list', $output );
		
		// Clean up
		remove_filter( 'pre_option_edac_simplified_summary_position', function() { return 'before'; } );
		remove_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'always'; } );
	}

	/**
	 * Test readability method with zero reading grade
	 */
	public function test_readability_zero_grade() {
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		// Set post content to empty to simulate zero grade
		wp_update_post( array(
			'ID' => $this->test_post_id,
			'post_content' => '',
		) );
		
		add_filter( 'pre_option_edac_simplified_summary_position', function() { return 'before'; } );
		add_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'always'; } );
		
		ob_start();
		$this->ajax->readability();
		$output = ob_get_clean();
		
		// Should handle zero grade case
		$this->assertStringContainsString( 'edac-readability-list', $output );
		
		remove_filter( 'pre_option_edac_simplified_summary_position', function() { return 'before'; } );
		remove_filter( 'pre_option_edac_simplified_summary_prompt', function() { return 'always'; } );
	}

	/**
	 * Test add_ignore method with invalid nonce
	 */
	public function test_add_ignore_invalid_nonce() {
		$_REQUEST['nonce'] = 'invalid_nonce';
		$_REQUEST['ids'] = array( '1', '2', '3' );
		$_REQUEST['ignore_action'] = 'enable';
		$_REQUEST['ignore_type'] = 'error';
		
		ob_start();
		$this->ajax->add_ignore();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'Permission Denied', $output );
	}

	/**
	 * Test add_ignore method with enable action
	 */
	public function test_add_ignore_enable_action() {
		global $wpdb;
		
		// Create test accessibility checker table
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$table_name} (
			id int(11) NOT NULL AUTO_INCREMENT,
			postid int(11) NOT NULL,
			siteid int(11) NOT NULL,
			ignre tinyint(1) DEFAULT 0,
			ignre_user int(11) DEFAULT NULL,
			ignre_date datetime DEFAULT NULL,
			ignre_comment text DEFAULT NULL,
			ignre_global tinyint(1) DEFAULT 0,
			object text,
			rule varchar(255),
			PRIMARY KEY (id)
		)" );
		
		// Insert test data
		$test_ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$wpdb->insert(
				$table_name,
				array(
					'postid' => $this->test_post_id,
					'siteid' => get_current_blog_id(),
					'object' => '<div>Test object ' . $i . '</div>',
					'rule' => 'test_rule',
					'ignre' => 0,
				)
			);
			$test_ids[] = $wpdb->insert_id;
		}
		
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['ids'] = array_map( 'strval', $test_ids );
		$_REQUEST['ignore_action'] = 'enable';
		$_REQUEST['ignore_type'] = 'error';
		$_REQUEST['comment'] = 'Test ignore comment';
		
		ob_start();
		$this->ajax->add_ignore();
		$output = ob_get_clean();
		
		// Should return success
		$this->assertStringContainsString( '"success":true', $output );
		
		// Verify database updates
		foreach ( $test_ids as $id ) {
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ) );
			$this->assertEquals( 1, $result->ignre );
			$this->assertEquals( get_current_user_id(), $result->ignre_user );
			$this->assertEquals( 'Test ignore comment', $result->ignre_comment );
		}
		
		// Clean up
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Test add_ignore method with large batch processing
	 */
	public function test_add_ignore_large_batch() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$table_name} (
			id int(11) NOT NULL AUTO_INCREMENT,
			postid int(11) NOT NULL,
			siteid int(11) NOT NULL,
			ignre tinyint(1) DEFAULT 0,
			ignre_user int(11) DEFAULT NULL,
			ignre_date datetime DEFAULT NULL,
			ignre_comment text DEFAULT NULL,
			ignre_global tinyint(1) DEFAULT 0,
			object text,
			rule varchar(255),
			PRIMARY KEY (id)
		)" );
		
		// Insert test data with same object
		$test_object = '<div>Large batch test object</div>';
		$test_ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$wpdb->insert(
				$table_name,
				array(
					'postid' => $this->test_post_id,
					'siteid' => get_current_blog_id(),
					'object' => $test_object,
					'rule' => 'test_rule',
					'ignre' => 0,
				)
			);
			$test_ids[] = $wpdb->insert_id;
		}
		
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['ids'] = array_map( 'strval', $test_ids );
		$_REQUEST['ignore_action'] = 'enable';
		$_REQUEST['ignore_type'] = 'error';
		$_REQUEST['largeBatch'] = 'true';
		
		ob_start();
		$this->ajax->add_ignore();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '"success":true', $output );
		
		// Clean up
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Test add_ignore method with disable action
	 */
	public function test_add_ignore_disable_action() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$table_name} (
			id int(11) NOT NULL AUTO_INCREMENT,
			postid int(11) NOT NULL,
			siteid int(11) NOT NULL,
			ignre tinyint(1) DEFAULT 1,
			ignre_user int(11) DEFAULT NULL,
			ignre_date datetime DEFAULT NULL,
			ignre_comment text DEFAULT NULL,
			ignre_global tinyint(1) DEFAULT 0,
			object text,
			rule varchar(255),
			PRIMARY KEY (id)
		)" );
		
		$wpdb->insert(
			$table_name,
			array(
				'postid' => $this->test_post_id,
				'siteid' => get_current_blog_id(),
				'object' => '<div>Test object</div>',
				'rule' => 'test_rule',
				'ignre' => 1,
				'ignre_user' => get_current_user_id(),
			)
		);
		$test_id = $wpdb->insert_id;
		
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['ids'] = array( strval( $test_id ) );
		$_REQUEST['ignore_action'] = 'disable';
		$_REQUEST['ignore_type'] = 'warning';
		
		ob_start();
		$this->ajax->add_ignore();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '"success":true', $output );
		
		// Verify ignore was disabled
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $test_id ) );
		$this->assertEquals( 0, $result->ignre );
		
		// Clean up
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Test simplified_summary method with invalid nonce
	 */
	public function test_simplified_summary_invalid_nonce() {
		$_REQUEST['nonce'] = 'invalid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		$_REQUEST['summary'] = 'Test summary';
		
		ob_start();
		$this->ajax->simplified_summary();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'Permission Denied', $output );
	}

	/**
	 * Test simplified_summary method without post_id
	 */
	public function test_simplified_summary_missing_post_id() {
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['summary'] = 'Test summary';
		
		ob_start();
		$this->ajax->simplified_summary();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'The post ID was not set', $output );
	}

	/**
	 * Test simplified_summary method without summary
	 */
	public function test_simplified_summary_missing_summary() {
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		
		ob_start();
		$this->ajax->simplified_summary();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'The summary was not set', $output );
	}

	/**
	 * Test simplified_summary method with valid data
	 */
	public function test_simplified_summary_valid_data() {
		$test_summary = 'This is a test simplified summary for accessibility.';
		
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['post_id'] = $this->test_post_id;
		$_REQUEST['summary'] = $test_summary;
		
		ob_start();
		$this->ajax->simplified_summary();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '"success":true', $output );
		
		// Verify the meta was saved
		$saved_summary = get_post_meta( $this->test_post_id, '_edac_simplified_summary', true );
		$this->assertEquals( $test_summary, $saved_summary );
	}

	/**
	 * Test dismiss_welcome_cta method
	 */
	public function test_dismiss_welcome_cta() {
		ob_start();
		$this->ajax->dismiss_welcome_cta();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'success', $output );
		
		// Verify user meta was updated
		$dismissed = get_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true );
		$this->assertTrue( (bool) $dismissed );
	}

	/**
	 * Test dismiss_dashboard_cta method
	 */
	public function test_dismiss_dashboard_cta() {
		ob_start();
		$this->ajax->dismiss_dashboard_cta();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'success', $output );
		
		// Verify user meta was updated
		$dismissed = get_user_meta( get_current_user_id(), 'edac_dashboard_cta_dismissed', true );
		$this->assertTrue( (bool) $dismissed );
	}

	/**
	 * Test edge case: large batch with no object found
	 */
	public function test_add_ignore_large_batch_no_object() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( "CREATE TABLE IF NOT EXISTS {$table_name} (
			id int(11) NOT NULL AUTO_INCREMENT,
			postid int(11) NOT NULL,
			siteid int(11) NOT NULL,
			ignre tinyint(1) DEFAULT 0,
			object text,
			PRIMARY KEY (id)
		)" );
		
		$_REQUEST['nonce'] = 'valid_nonce';
		$_REQUEST['ids'] = array( '999999' ); // Non-existent ID
		$_REQUEST['ignore_action'] = 'enable';
		$_REQUEST['ignore_type'] = 'error';
		$_REQUEST['largeBatch'] = 'true';
		
		ob_start();
		$this->ajax->add_ignore();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'No ignore data to return', $output );
		
		// Clean up
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Mock Summary_Generator for testing
	 */
	private function mock_summary_generator() {
		// Mock the Summary_Generator class and its methods
		add_filter( 'edac_generate_summary_stat', function( $class, $count, $label ) {
			return sprintf( '<li class="%s">%d %s</li>', $class, $count, $label );
		}, 10, 3 );
		
		// Mock EDAC constants and functions that might not be available
		if ( ! defined( 'EDAC_PLUGIN_URL' ) ) {
			define( 'EDAC_PLUGIN_URL', 'http://example.com/wp-content/plugins/accessibility-checker/' );
		}
		
		if ( ! defined( 'EDAC_SVG_IGNORE_ICON' ) ) {
			define( 'EDAC_SVG_IGNORE_ICON', '<svg>ignore icon</svg>' );
		}
		
		// Mock functions that may not exist in test environment
		if ( ! function_exists( 'edac_generate_summary_stat' ) ) {
			function edac_generate_summary_stat( $class, $count, $label ) {
				return sprintf( '<li class="%s">%d %s</li>', $class, $count, $label );
			}
		}
		
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			function edac_generate_link_type() {
				return 'http://example.com/help';
			}
		}
		
		if ( ! function_exists( 'edac_ordinal' ) ) {
			function edac_ordinal( $number ) {
				return $number . 'th';
			}
		}
		
		if ( ! function_exists( 'edac_link_wrapper' ) ) {
			function edac_link_wrapper( $url ) {
				return $url;
			}
		}
	}
}