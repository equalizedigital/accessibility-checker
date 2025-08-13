<?php
/**
 * Tests for CommentSearchLabelFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\CommentSearchLabelFix;
use WP_UnitTestCase;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * CommentSearchLabelFix test case
 */
class CommentSearchLabelFixTest extends WP_UnitTestCase {

	use \FixTestTrait;

	/**
	 * Set up test fixtures
	 */
	public function set_up(): void {
		parent::set_up();
		$this->fix = new CommentSearchLabelFix();
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
		return 'comment-search-label';
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
		return CommentSearchLabelFix::class;
	}

	/**
	 * Get the fix option names for this specific fix.
	 * This fix uses two options.
	 *
	 * @return array
	 */
	protected function get_fix_option_names(): array {
		return [ 'edac_fix_comment_label', 'edac_fix_search_label' ];
	}

	/**
	 * CommentSearchLabelFix doesn't use frontend data filter.
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
		$this->assertArrayHasKey( 'comment_search_label', $sections );
	}

	/**
	 * Test that comment form filter is registered when comment option enabled.
	 *
	 * @return void
	 */
	public function test_run_registers_comment_form_filter_when_enabled() {
		update_option( 'edac_fix_comment_label', true );
		update_option( 'edac_fix_search_label', false );
		
		$this->fix->run();
		
		$this->assertTrue( has_filter( 'comment_form_defaults', [ $this->fix, 'fix_comment_form_labels' ] ) !== false );
		$this->assertFalse( has_filter( 'get_search_form', [ $this->fix, 'fix_search_form_label' ] ) );
	}

	/**
	 * Test that search form filter is registered when search option enabled.
	 *
	 * @return void
	 */
	public function test_run_registers_search_form_filter_when_enabled() {
		update_option( 'edac_fix_comment_label', false );
		update_option( 'edac_fix_search_label', true );
		
		$this->fix->run();
		
		$this->assertFalse( has_filter( 'comment_form_defaults', [ $this->fix, 'fix_comment_form_labels' ] ) );
		$this->assertTrue( has_filter( 'get_search_form', [ $this->fix, 'fix_search_form_label' ] ) !== false );
	}

	/**
	 * Test that comment form labels are added correctly.
	 *
	 * @return void
	 */
	public function test_fix_comment_form_labels_adds_comment_field_label() {
		$defaults = [
			'comment_field' => '<p class="comment-form-comment"><textarea id="comment" name="comment" rows="4" required></textarea></p>',
		];
		
		$result = $this->fix->fix_comment_form_labels( $defaults );
		
		$this->assertStringContainsString( '<label for="comment" class="edac-generated-label">', $result['comment_field'] );
		$this->assertStringContainsString( 'Comment', $result['comment_field'] );
	}

	/**
	 * Test that search form labels are added correctly.
	 *
	 * @return void
	 */
	public function test_fix_search_form_label_adds_label() {
		$form = '<input type="search" class="search-field" value="" name="s" />';
		
		$result = $this->fix->fix_search_form_label( $form );
		
		$this->assertStringContainsString( '<label', $result );
		$this->assertStringContainsString( 'for=', $result );
		$this->assertStringContainsString( 'Search', $result );
	}
}
