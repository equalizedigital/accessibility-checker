<?php
/**
 * Test cases for the options page scan functionality.
 *
 * @package accessibility-checker
 */

/**
 * Tests for scan page functionality in options-page.php.
 */
class OptionsPageScanTest extends WP_UnitTestCase {

	/**
	 * Setup test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Load the options-page.php file which contains our functions.
		require_once EDAC_PLUGIN_DIR . 'includes/options-page.php';
	}

	/**
	 * Test that edac_display_scan_page function exists.
	 *
	 * @return void
	 */
	public function testScanPageDisplayFunctionExists() {
		$this->assertTrue( function_exists( 'edac_display_scan_page' ) );
	}

	/**
	 * Test that scan page partial file exists.
	 *
	 * @return void
	 */
	public function testScanPagePartialExists() {
		$scan_page_file = EDAC_PLUGIN_DIR . 'partials/scan-page.php';
		$this->assertFileExists( $scan_page_file );
	}

	/**
	 * Test that the scan page partial can be included without errors.
	 *
	 * @return void
	 */
	public function testScanPagePartialCanBeIncluded() {
		$scan_page_file = EDAC_PLUGIN_DIR . 'partials/scan-page.php';
		
		// Capture output to avoid displaying content during test.
		ob_start();
		include $scan_page_file;
		$output = ob_get_clean();
		
		// Check that some expected content is present.
		$this->assertStringContainsString( 'Scan This Page', $output );
		$this->assertStringContainsString( 'Page Accessibility Scanner', $output );
		$this->assertStringContainsString( 'edac-scan-results', $output );
		$this->assertStringContainsString( 'runAccessibilityScan', $output );
	}
}
