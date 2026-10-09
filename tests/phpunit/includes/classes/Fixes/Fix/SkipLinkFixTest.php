<?php
/**
 * Test class for SkipLinkFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\SkipLinkFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the SkipLinkFix class.
 */
class SkipLinkFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new SkipLinkFix();
		$this->common_setup();
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->common_teardown();
		// Clean up the target options the skip link fix reads.
		delete_option( 'edac_fix_add_skip_link_target_id' );
		delete_option( 'edac_fix_add_skip_link_nav_target_id' );
		parent::tearDown();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'skip_link';
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
		return SkipLinkFix::class;
	}

	/**
	 * Get the fix option names.
	 *
	 * @return array
	 */
	protected function get_fix_option_names(): array {
		return [ 'edac_fix_add_skip_link' ];
	}

	/**
	 * SkipLinkFix only adds frontend data once a target ID is configured, so with just
	 * the first option enabled it must not add itself to the payload. The case with a
	 * target is covered by test_frontend_data_includes_settings() below.
	 *
	 * @return bool
	 */
	protected function fix_registers_frontend_data(): bool {
		return false;
	}

	/**
	 * Test skip link has additional configuration fields.
	 *
	 * @return void
	 */
	public function test_skip_link_has_configuration_fields() {
		$fields = $this->fix->get_fields_array();
		
		$this->assertArrayHasKey( 'edac_fix_add_skip_link_target_id', $fields );
		$this->assertArrayHasKey( 'edac_fix_add_skip_link_nav_target_id', $fields );
	}

	/**
	 * Test frontend data includes settings when enabled with target.
	 *
	 * @return void
	 */
	public function test_frontend_data_includes_settings() {
		update_option( 'edac_fix_add_skip_link', true );
		update_option( 'edac_fix_add_skip_link_target_id', 'main,content' );
		
		$this->fix->run();
		
		$data      = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$skip_data = $data['skip_link'];
		
		$this->assertTrue( $skip_data['enabled'] );
		$this->assertArrayHasKey( 'targets', $skip_data );
		$this->assertContains( '#main', $skip_data['targets'] );
		$this->assertContains( '#content', $skip_data['targets'] );
	}

	/**
	 * Test frontend data is not added when targets are empty.
	 *
	 * @dataProvider provide_empty_targets
	 *
	 * @param string $target_string The raw target setting value.
	 *
	 * @return void
	 */
	public function test_frontend_data_skips_empty_targets( string $target_string ) {
		update_option( 'edac_fix_add_skip_link', true );
		update_option( 'edac_fix_add_skip_link_target_id', $target_string );

		$this->fix->run();

		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ), 'No frontend data filter should be registered.' );

		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );

		$this->assertArrayNotHasKey( 'skip_link', $data );
	}

	/**
	 * Data provider for empty target values.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provide_empty_targets(): array {
		return [
			'empty string'                    => [ '' ],
			'whitespace only'                 => [ '   ' ],
			'commas only'                     => [ ',,' ],
			'commas and whitespace'           => [ ' , , ' ],
			'hash only'                       => [ '#' ],
			'hash with whitespace and commas' => [ ' , , # , ' ],
		];
	}
}
