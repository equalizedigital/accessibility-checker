<?php
/**
 * Tests for EmptySearchFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\EmptySearchFix;
use WP_UnitTestCase;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * EmptySearchFix test case
 */
class EmptySearchFixTest extends WP_UnitTestCase {

	use \FixTestTrait;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->fix = new EmptySearchFix();
		$this->common_setup();
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		$this->common_teardown();
		remove_all_actions( 'pre_get_posts' );
		remove_all_filters( 'template_include' );
		unset( $_GET['s'] );
		parent::tearDown();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'empty-search';
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
		return EmptySearchFix::class;
	}

	/**
	 * EmptySearchFix doesn't use frontend data filter.
	 *
	 * @return bool
	 */
	protected function skip_frontend_data_filter_test(): bool {
		return true;
	}

	/**
	 * Test that register adds settings sections.
	 *
	 * @return void
	 */
	public function test_register_adds_settings_sections() {
		$this->fix->register();

		$sections = apply_filters( 'edac_filter_fixes_settings_sections', [] );
		$this->assertArrayHasKey( 'empty_search', $sections );
	}

	/**
	 * Test that settings section has a callable callback.
	 *
	 * @return void
	 */
	public function test_register_section_has_callable_callback() {
		$this->fix->register();

		$sections = apply_filters( 'edac_filter_fixes_settings_sections', [] );
		$this->assertArrayHasKey( 'callback', $sections['empty_search'] );
		$this->assertTrue( is_callable( $sections['empty_search']['callback'] ) );
	}

	/**
	 * Test that get_fields_array returns the expected field.
	 *
	 * @return void
	 */
	public function test_get_fields_array_contains_empty_search_field() {
		$fields = $this->fix->get_fields_array();

		$this->assertArrayHasKey( 'edac_fix_empty_search', $fields );
		$this->assertEquals( 'checkbox', $fields['edac_fix_empty_search']['type'] );
		$this->assertEquals( 'empty_search', $fields['edac_fix_empty_search']['section'] );
		$this->assertEquals( 'empty-search', $fields['edac_fix_empty_search']['fix_slug'] );
	}

	/**
	 * Test that run does not register pre_get_posts hook when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_does_not_hook_when_disabled() {
		update_option( 'edac_fix_empty_search', false );

		$this->fix->run();

		$this->assertFalse( has_action( 'pre_get_posts', [ $this->fix, 'handle_empty_search' ] ) );
	}

	/**
	 * Test that run registers pre_get_posts hook when option is enabled.
	 *
	 * @return void
	 */
	public function test_run_hooks_pre_get_posts_when_enabled() {
		update_option( 'edac_fix_empty_search', true );

		$this->fix->run();

		$this->assertNotFalse( has_action( 'pre_get_posts', [ $this->fix, 'handle_empty_search' ] ) );
	}

	/**
	 * Test that handle_empty_search skips non-main queries.
	 *
	 * @return void
	 */
	public function test_handle_empty_search_skips_non_main_query() {
		$_GET['s'] = '';

		$query = new \WP_Query();
		// A freshly constructed WP_Query is not the main query.
		$this->fix->handle_empty_search( $query );

		$this->assertNotEquals( '&#32;', $query->query_vars['s'] ?? '' );
	}

	/**
	 * Test that handle_empty_search skips when s param is not set.
	 *
	 * @return void
	 */
	public function test_handle_empty_search_skips_without_s_param() {
		unset( $_GET['s'] );

		$query = $this->get_main_query();

		$this->fix->handle_empty_search( $query );

		$this->assertNotEquals( '&#32;', $query->query_vars['s'] ?? '' );

		$this->restore_main_query();
	}

	/**
	 * Test that handle_empty_search skips when s param is non-empty.
	 *
	 * @return void
	 */
	public function test_handle_empty_search_skips_nonempty_search() {
		$_GET['s'] = 'hello';

		$query = $this->get_main_query();

		$this->fix->handle_empty_search( $query );

		$this->assertNotEquals( '&#32;', $query->query_vars['s'] ?? '' );

		$this->restore_main_query();
	}

	/**
	 * Test that handle_empty_search modifies query for empty search.
	 *
	 * @return void
	 */
	public function test_handle_empty_search_sets_query_vars_on_empty_search() {
		$_GET['s'] = '';

		$query = $this->get_main_query();

		$this->fix->handle_empty_search( $query );

		$this->assertEquals( '&#32;', $query->query_vars['s'] );

		$this->restore_main_query();
	}

	/**
	 * Test that handle_empty_search modifies query for whitespace-only search.
	 *
	 * @return void
	 */
	public function test_handle_empty_search_handles_whitespace_only() {
		$_GET['s'] = '   ';

		$query = $this->get_main_query();

		$this->fix->handle_empty_search( $query );

		$this->assertEquals( '&#32;', $query->query_vars['s'] );

		$this->restore_main_query();
	}

	/**
	 * Test that handle_empty_search adds template_include filter.
	 *
	 * @return void
	 */
	public function test_handle_empty_search_adds_template_include_filter() {
		$_GET['s'] = '';

		$query = $this->get_main_query();

		$this->fix->handle_empty_search( $query );

		$this->assertNotFalse( has_filter( 'template_include', [ $this->fix, 'force_search_template' ] ) );

		$this->restore_main_query();
	}

	/**
	 * Test that force_search_template returns original template when search.php not found.
	 *
	 * @return void
	 */
	public function test_force_search_template_returns_fallback() {
		$original_template = '/path/to/index.php';
		$result            = $this->fix->force_search_template( $original_template );

		// In the test environment, locate_template('search.php') will return empty
		// since there's no theme with that file, so it should fall back.
		$this->assertEquals( $original_template, $result );
	}

	/**
	 * Test settings_section_callback outputs content.
	 *
	 * @return void
	 */
	public function test_settings_section_callback_outputs_content() {
		ob_start();
		$this->fix->settings_section_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<p>', $output );
		$this->assertStringContainsString( 'empty query', $output );
	}

	/**
	 * The original wp_the_query global value.
	 *
	 * @var \WP_Query|null
	 */
	private $original_wp_the_query = null;

	/**
	 * Create a WP_Query that is recognized as the main query and return it.
	 *
	 * @return \WP_Query
	 */
	private function get_main_query(): \WP_Query {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Needed to simulate main query in tests.
		global $wp_the_query;
		$this->original_wp_the_query = $wp_the_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Needed to simulate main query in tests.
		$wp_the_query = new \WP_Query();
		return $wp_the_query;
	}

	/**
	 * Restore the original wp_the_query global.
	 *
	 * @return void
	 */
	private function restore_main_query(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring original value after test.
		global $wp_the_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring original value after test.
		$wp_the_query = $this->original_wp_the_query;
	}
}
