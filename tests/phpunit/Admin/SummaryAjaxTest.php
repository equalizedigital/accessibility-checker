<?php
/**
 * Tests for the Summary_Ajax class
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Summary_Ajax;

/**
 * Test case for Summary_Ajax class
 */
class SummaryAjaxTest extends WP_UnitTestCase {

	/**
	 * The Summary_Ajax instance.
	 *
	 * @var Summary_Ajax
	 */
	private $summary_ajax;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->summary_ajax = new Summary_Ajax();
	}

	/**
	 * Test that Summary_Ajax can be instantiated.
	 */
	public function test_can_instantiate() {
		$this->assertInstanceOf( Summary_Ajax::class, $this->summary_ajax );
	}

	/**
	 * Test that init_hooks registers the expected actions.
	 */
	public function test_init_hooks_registers_actions() {
		// Remove any existing hooks first.
		remove_all_actions( 'wp_ajax_edac_summary_ajax' );
		remove_all_actions( 'wp_ajax_edac_update_simplified_summary' );

		// Initialize hooks.
		$this->summary_ajax->init_hooks();

		// Check that actions are registered.
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_summary_ajax' ) );
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_update_simplified_summary' ) );
	}

	/**
	 * Test that summary method responds to missing nonce.
	 */
	public function test_summary_requires_nonce() {
		// Set up request without nonce.
		$_REQUEST = [
			'post_id' => 1,
		];

		// Capture output.
		ob_start();
		$this->summary_ajax->summary();
		$output = ob_get_clean();

		// Should have JSON error response.
		$this->assertStringContainsString( 'Permission Denied', $output );
	}

	/**
	 * Test that summary method requires post_id.
	 */
	public function test_summary_requires_post_id() {
		// Set up request with valid nonce but no post_id.
		$_REQUEST = [
			'nonce' => wp_create_nonce( 'ajax-nonce' ),
		];

		// Capture output.
		ob_start();
		$this->summary_ajax->summary();
		$output = ob_get_clean();

		// Should have JSON error response.
		$this->assertStringContainsString( 'The post ID was not set', $output );
	}

	/**
	 * Test that simplified_summary method requires nonce.
	 */
	public function test_simplified_summary_requires_nonce() {
		// Set up request without nonce.
		$_REQUEST = [
			'post_id' => 1,
			'summary' => 'test summary',
		];

		// Capture output.
		ob_start();
		$this->summary_ajax->simplified_summary();
		$output = ob_get_clean();

		// Should have JSON error response.
		$this->assertStringContainsString( 'Permission Denied', $output );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		unset( $_REQUEST );
		parent::tearDown();
	}
}