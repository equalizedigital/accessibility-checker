<?php
/**
 * Class LazyloadFilterTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Lazyload_Filter;

/**
 * Lazyload_Filter test case.
 */
class LazyloadFilterTest extends WP_UnitTestCase {

	/**
	 * Instance of the Lazyload_Filter class.
	 *
	 * Holds an instance of the Lazyload_Filter class
	 * which is used to test its methods.
	 *
	 * @var Lazyload_Filter $lazyload_filter
	 */
	private $lazyload_filter;

	/**
	 * Set up the test fixture.
	 *
	 * Initializes the testing environment before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->lazyload_filter = new Lazyload_Filter();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up any GET parameters that might have been set.
		unset( $_GET['edac_nonce'] );
		unset( $_GET['edac'] );
		
		parent::tearDown();
	}

	/**
	 * Test that init_hooks adds the expected filter.
	 *
	 * Verifies that the perfmatters_lazyload filter is added
	 * when init_hooks is called.
	 *
	 * @return void
	 */
	public function test_init_hooks_adds_perfmatters_filter() {
		// Remove any existing filter first.
		remove_filter( 'perfmatters_lazyload', [ $this->lazyload_filter, 'perfmatters' ] );
		
		// Verify filter is not set initially.
		$this->assertFalse( has_filter( 'perfmatters_lazyload', [ $this->lazyload_filter, 'perfmatters' ] ) );
		
		// Call init_hooks.
		$this->lazyload_filter->init_hooks();
		
		// Verify filter is now added.
		$this->assertNotFalse( has_filter( 'perfmatters_lazyload', [ $this->lazyload_filter, 'perfmatters' ] ) );
	}

	/**
	 * Test perfmatters method returns original value when no GET parameters set.
	 *
	 * When neither edac_nonce nor edac GET parameters are set,
	 * the method should return the original lazyload value unchanged.
	 *
	 * @return void
	 */
	public function test_perfmatters_returns_original_value_when_no_params() {
		// Clear any existing GET parameters.
		unset( $_GET['edac_nonce'] );
		unset( $_GET['edac'] );
		
		$this->assertTrue( $this->lazyload_filter->perfmatters( true ) );
		$this->assertFalse( $this->lazyload_filter->perfmatters( false ) );
	}

	/**
	 * Test perfmatters method returns original value when nonce is invalid.
	 *
	 * When edac_nonce is set but invalid, the method should return
	 * the original lazyload value unchanged.
	 *
	 * @return void
	 */
	public function test_perfmatters_returns_original_value_when_invalid_nonce() {
		$_GET['edac_nonce'] = 'invalid_nonce';
		
		$this->assertTrue( $this->lazyload_filter->perfmatters( true ) );
		$this->assertFalse( $this->lazyload_filter->perfmatters( false ) );
		
		unset( $_GET['edac_nonce'] );
	}

	/**
	 * Test perfmatters method returns original value when valid nonce but no edac param.
	 *
	 * When edac_nonce is valid but edac parameter is not set,
	 * the method should return the original lazyload value unchanged.
	 *
	 * @return void
	 */
	public function test_perfmatters_returns_original_value_when_valid_nonce_no_edac() {
		$_GET['edac_nonce'] = wp_create_nonce( 'edac_highlight' );
		unset( $_GET['edac'] );
		
		$this->assertTrue( $this->lazyload_filter->perfmatters( true ) );
		$this->assertFalse( $this->lazyload_filter->perfmatters( false ) );
		
		unset( $_GET['edac_nonce'] );
	}

	/**
	 * Test perfmatters method returns false when valid nonce and edac param set.
	 *
	 * When both edac_nonce is valid and edac parameter is set,
	 * the method should return false to disable lazyloading.
	 *
	 * @return void
	 */
	public function test_perfmatters_returns_false_when_valid_nonce_and_edac() {
		$_GET['edac_nonce'] = wp_create_nonce( 'edac_highlight' );
		$_GET['edac']       = '1';
		
		// Should return false regardless of the input value.
		$this->assertFalse( $this->lazyload_filter->perfmatters( true ) );
		$this->assertFalse( $this->lazyload_filter->perfmatters( false ) );
		
		unset( $_GET['edac_nonce'] );
		unset( $_GET['edac'] );
	}

	/**
	 * Test perfmatters method with different edac parameter values.
	 *
	 * When edac_nonce is valid and edac parameter is set to any value,
	 * the method should return false to disable lazyloading.
	 *
	 * @return void
	 */
	public function test_perfmatters_returns_false_with_different_edac_values() {
		$_GET['edac_nonce'] = wp_create_nonce( 'edac_highlight' );
		
		$edac_values = [ '1', 'true', 'highlight', '0', 'false', '' ];
		
		foreach ( $edac_values as $value ) {
			$_GET['edac'] = $value;
			$this->assertFalse( $this->lazyload_filter->perfmatters( true ), "Failed for edac value: $value" );
		}
		
		unset( $_GET['edac_nonce'] );
		unset( $_GET['edac'] );
	}
}
