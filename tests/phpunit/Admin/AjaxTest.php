<?php
/**
 * Tests for the Ajax orchestrator class
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Ajax;

/**
 * Test case for Ajax class
 */
class AjaxTest extends WP_UnitTestCase {

	/**
	 * The Ajax instance.
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->ajax = new Ajax();
	}

	/**
	 * Test that Ajax can be instantiated.
	 */
	public function test_can_instantiate() {
		$this->assertInstanceOf( Ajax::class, $this->ajax );
	}

	/**
	 * Test that init_hooks registers all expected actions.
	 */
	public function test_init_hooks_registers_all_actions() {
		// Remove any existing hooks first.
		remove_all_actions( 'wp_ajax_edac_summary_ajax' );
		remove_all_actions( 'wp_ajax_edac_details_ajax' );
		remove_all_actions( 'wp_ajax_edac_readability_ajax' );
		remove_all_actions( 'wp_ajax_edac_insert_ignore_data' );
		remove_all_actions( 'wp_ajax_edac_update_simplified_summary' );
		remove_all_actions( 'wp_ajax_edac_dismiss_welcome_cta_ajax' );
		remove_all_actions( 'wp_ajax_edac_dismiss_dashboard_cta_ajax' );

		// Initialize hooks.
		$this->ajax->init_hooks();

		// Check that all AJAX actions are registered.
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_summary_ajax' ) );
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_details_ajax' ) );
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_readability_ajax' ) );
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_insert_ignore_data' ) );
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_update_simplified_summary' ) );
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax' ) );
		$this->assertNotEmpty( has_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax' ) );
	}

	/**
	 * Test that specialized classes are instantiated properly.
	 */
	public function test_specialized_classes_instantiated() {
		// Use reflection to access private properties.
		$reflection = new ReflectionClass( $this->ajax );

		$summary_ajax_property = $reflection->getProperty( 'summary_ajax' );
		$summary_ajax_property->setAccessible( true );
		$summary_ajax = $summary_ajax_property->getValue( $this->ajax );
		$this->assertInstanceOf( 'EDAC\Admin\Summary_Ajax', $summary_ajax );

		$details_ajax_property = $reflection->getProperty( 'details_ajax' );
		$details_ajax_property->setAccessible( true );
		$details_ajax = $details_ajax_property->getValue( $this->ajax );
		$this->assertInstanceOf( 'EDAC\Admin\Details_Ajax', $details_ajax );

		$readability_ajax_property = $reflection->getProperty( 'readability_ajax' );
		$readability_ajax_property->setAccessible( true );
		$readability_ajax = $readability_ajax_property->getValue( $this->ajax );
		$this->assertInstanceOf( 'EDAC\Admin\Readability_Ajax', $readability_ajax );

		$ui_ajax_property = $reflection->getProperty( 'ui_ajax' );
		$ui_ajax_property->setAccessible( true );
		$ui_ajax = $ui_ajax_property->getValue( $this->ajax );
		$this->assertInstanceOf( 'EDAC\Admin\UI_Ajax', $ui_ajax );
	}
}