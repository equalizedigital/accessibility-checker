<?php
/**
 * Tests for AddNewWindowWarningFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddNewWindowWarningFix;
use WP_UnitTestCase;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * AddNewWindowWarningFix test case
 */
class AddNewWindowWarningFixTest extends WP_UnitTestCase {

	use \FixTestTrait;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->fix = new AddNewWindowWarningFix();
		$this->common_setup();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		$this->common_teardown();
		parent::tearDown();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'new_window_warning';
	}

	/**
	 * Get the expected type for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_type(): string {
		return 'frontend';
	}

	/**
	 * Get the fix class name.
	 *
	 * @return string
	 */
	protected function get_fix_class_name(): string {
		return AddNewWindowWarningFix::class;
	}

	/**
	 * Test that styles are added when enabled.
	 *
	 * @return void
	 */
	public function test_run_adds_styles_when_enabled() {
		update_option( 'edac_fix_new_window_warning', true );

		$this->fix->run();

		$this->assertTrue( has_action( 'wp_head', [ $this->fix, 'add_styles' ] ) !== false );
	}

	/**
	 * Test that add_styles outputs the expected CSS.
	 *
	 * @return void
	 */
	public function test_add_styles_outputs_css() {
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<style id="edac-nww">', $output );
		$this->assertStringContainsString( '@font-face', $output );
		$this->assertStringContainsString( "font-family: 'anww'", $output );
		$this->assertStringContainsString( '.edac-nww-external-link-icon', $output );
		$this->assertStringContainsString( '</style>', $output );
	}

	/**
	 * Test that add_styles includes the correct font URLs.
	 *
	 * @return void
	 */
	public function test_add_styles_includes_font_urls() {
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'assets/fonts/anww.eot', $output );
		$this->assertStringContainsString( 'assets/fonts/anww.ttf', $output );
		$this->assertStringContainsString( 'assets/fonts/anww.woff', $output );
		$this->assertStringContainsString( 'assets/fonts/anww.svg', $output );
	}
}
