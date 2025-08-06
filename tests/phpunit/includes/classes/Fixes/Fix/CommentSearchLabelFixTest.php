<?php
/**
 * Tests for CommentSearchLabelFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\CommentSearchLabelFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;
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
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_comment_label' );
		delete_option( 'edac_fix_search_label' );
		
		parent::tearDown();
	}

	/**
	 * Test register method adds settings sections
	 */
	public function test_register_adds_settings_sections() {
		$sections = [];
		$this->fix->register();
		
		$sections = apply_filters( 'edac_filter_fixes_settings_sections', $sections );
		
		$this->assertArrayHasKey( 'comment_search_label', $sections );
		$this->assertEquals( 'Comment and Search Form Labels', $sections['comment_search_label']['title'] );
		$this->assertEquals( 'Add missing labels to WordPress comment and search forms.', $sections['comment_search_label']['description'] );
		$this->assertEquals( [ $this->fix, 'comment_search_label_section_callback' ], $sections['comment_search_label']['callback'] );
	}

	/**
	 * Test get_fields_array method returns correct fields
	 */
	public function test_get_fields_array() {
		$fields = $this->fix->get_fields_array();
		
		// Test comment label field.
		$this->assertArrayHasKey( 'edac_fix_comment_label', $fields );
		$comment_field = $fields['edac_fix_comment_label'];
		$this->assertEquals( 'Label Comment Form', $comment_field['label'] );
		$this->assertEquals( 'checkbox', $comment_field['type'] );
		$this->assertEquals( 'add_comment_label', $comment_field['labelledby'] );
		$this->assertEquals( 'Add missing labels to the WordPress comment form.', $comment_field['description'] );
		$this->assertEquals( 'comment_search_label', $comment_field['section'] );
		$this->assertEquals( 'comment-search-label', $comment_field['fix_slug'] );
		$this->assertEquals( 'Add Labels to Comment and Search Forms', $comment_field['group_name'] );
		$this->assertEquals( 8658, $comment_field['help_id'] );
		
		// Test search label field.
		$this->assertArrayHasKey( 'edac_fix_search_label', $fields );
		$search_field = $fields['edac_fix_search_label'];
		$this->assertEquals( 'Label Search Form', $search_field['label'] );
		$this->assertEquals( 'checkbox', $search_field['type'] );
		$this->assertEquals( 'add_search_label', $search_field['labelledby'] );
		$this->assertEquals( 'Add a missing label to the WordPress search form.', $search_field['description'] );
		$this->assertEquals( 'comment_search_label', $search_field['section'] );
		$this->assertEquals( 'comment-search-label', $search_field['fix_slug'] );
		$this->assertEquals( 8659, $search_field['help_id'] );
	}

	/**
	 * Test run method does nothing when options are disabled
	 */
	public function test_run_does_nothing_when_disabled() {
		update_option( 'edac_fix_comment_label', false );
		update_option( 'edac_fix_search_label', false );
		
		$this->fix->run();
		
		// Verify no filters are registered.
		$this->assertFalse( has_filter( 'comment_form_defaults', [ $this->fix, 'fix_comment_form_labels' ] ) );
		$this->assertFalse( has_filter( 'get_search_form', [ $this->fix, 'fix_search_form_label' ] ) );
	}

	/**
	 * Test run method registers comment form filter when enabled
	 */
	public function test_run_registers_comment_form_filter_when_enabled() {
		update_option( 'edac_fix_comment_label', true );
		update_option( 'edac_fix_search_label', false );
		
		$this->fix->run();
		
		$this->assertNotFalse( has_filter( 'comment_form_defaults', [ $this->fix, 'fix_comment_form_labels' ] ) );
		$this->assertFalse( has_filter( 'get_search_form', [ $this->fix, 'fix_search_form_label' ] ) );
	}

	/**
	 * Test run method registers search form filter when enabled
	 */
	public function test_run_registers_search_form_filter_when_enabled() {
		update_option( 'edac_fix_comment_label', false );
		update_option( 'edac_fix_search_label', true );
		
		$this->fix->run();
		
		$this->assertFalse( has_filter( 'comment_form_defaults', [ $this->fix, 'fix_comment_form_labels' ] ) );
		$this->assertNotFalse( has_filter( 'get_search_form', [ $this->fix, 'fix_search_form_label' ] ) );
	}

	/**
	 * Test run method registers both filters when both enabled
	 */
	public function test_run_registers_both_filters_when_both_enabled() {
		update_option( 'edac_fix_comment_label', true );
		update_option( 'edac_fix_search_label', true );
		
		$this->fix->run();
		
		$this->assertNotFalse( has_filter( 'comment_form_defaults', [ $this->fix, 'fix_comment_form_labels' ] ) );
		$this->assertNotFalse( has_filter( 'get_search_form', [ $this->fix, 'fix_search_form_label' ] ) );
	}

	/**
	 * Test fix_comment_form_labels method adds labels to comment field
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
	 * Test fix_comment_form_labels method preserves existing labels in comment field
	 */
	public function test_fix_comment_form_labels_preserves_existing_comment_label() {
		$defaults = [
			'comment_field' => '<p class="comment-form-comment"><label for="comment">Existing Label</label><textarea id="comment" name="comment" rows="4" required></textarea></p>',
		];
		
		$result = $this->fix->fix_comment_form_labels( $defaults );
		
		$this->assertStringContainsString( 'Existing Label', $result['comment_field'] );
		$this->assertStringNotContainsString( 'edac-generated-label', $result['comment_field'] );
	}

	/**
	 * Test fix_comment_form_labels method adds labels to author field
	 */
	public function test_fix_comment_form_labels_adds_author_field_label() {
		$defaults = [
			'fields' => [
				'author' => '<p class="comment-form-author"><input id="author" name="author" type="text" value="" size="30" required /></p>',
			],
		];
		
		$result = $this->fix->fix_comment_form_labels( $defaults );
		
		$this->assertStringContainsString( '<label for="author" class="edac-generated-label">', $result['fields']['author'] );
		$this->assertStringContainsString( 'Name', $result['fields']['author'] );
	}

	/**
	 * Test fix_comment_form_labels method adds labels to email field
	 */
	public function test_fix_comment_form_labels_adds_email_field_label() {
		$defaults = [
			'fields' => [
				'email' => '<p class="comment-form-email"><input id="email" name="email" type="email" value="" size="30" required /></p>',
			],
		];
		
		$result = $this->fix->fix_comment_form_labels( $defaults );
		
		$this->assertStringContainsString( '<label for="email" class="edac-generated-label">', $result['fields']['email'] );
		$this->assertStringContainsString( 'Email', $result['fields']['email'] );
	}

	/**
	 * Test fix_comment_form_labels method adds labels to url field
	 */
	public function test_fix_comment_form_labels_adds_url_field_label() {
		$defaults = [
			'fields' => [
				'url' => '<p class="comment-form-url"><input id="url" name="url" type="url" value="" size="30" /></p>',
			],
		];
		
		$result = $this->fix->fix_comment_form_labels( $defaults );
		
		$this->assertStringContainsString( '<label for="url" class="edac-generated-label">', $result['fields']['url'] );
		$this->assertStringContainsString( 'Website', $result['fields']['url'] );
	}

	/**
	 * Test fix_comment_form_labels preserves existing field labels
	 */
	public function test_fix_comment_form_labels_preserves_existing_field_labels() {
		$defaults = [
			'fields' => [
				'author' => '<p class="comment-form-author"><label for="author">Custom Name Label</label><input id="author" name="author" type="text" value="" size="30" required /></p>',
				'email'  => '<p class="comment-form-email"><label for="email">Custom Email Label</label><input id="email" name="email" type="email" value="" size="30" required /></p>',
				'url'    => '<p class="comment-form-url"><label for="url">Custom Website Label</label><input id="url" name="url" type="url" value="" size="30" /></p>',
			],
		];
		
		$result = $this->fix->fix_comment_form_labels( $defaults );
		
		$this->assertStringContainsString( 'Custom Name Label', $result['fields']['author'] );
		$this->assertStringContainsString( 'Custom Email Label', $result['fields']['email'] );
		$this->assertStringContainsString( 'Custom Website Label', $result['fields']['url'] );
		$this->assertStringNotContainsString( 'edac-generated-label', $result['fields']['author'] );
		$this->assertStringNotContainsString( 'edac-generated-label', $result['fields']['email'] );
		$this->assertStringNotContainsString( 'edac-generated-label', $result['fields']['url'] );
	}

	/**
	 * Test fix_search_form_label method adds label to search form
	 */
	public function test_fix_search_form_label_adds_label() {
		$form = '<form role="search" method="get" class="search-form" action="http://example.com/">
			<input type="search" id="search-field-123" class="search-field" placeholder="Search …" value="" name="s" />
			<button type="submit" class="search-submit">Search</button>
		</form>';
		
		$result = $this->fix->fix_search_form_label( $form );
		
		$this->assertStringContainsString( 'class="edac-generated-label"', $result );
		$this->assertStringContainsString( 'Search for:', $result );
		$this->assertStringContainsString( '<label for=', $result );
	}

	/**
	 * Test fix_search_form_label preserves existing labels
	 */
	public function test_fix_search_form_label_preserves_existing_label() {
		$form = '<form role="search" method="get" class="search-form" action="http://example.com/">
			<label for="search-field-123">Existing Search Label</label>
			<input type="search" id="search-field-123" class="search-field" placeholder="Search …" value="" name="s" />
			<button type="submit" class="search-submit">Search</button>
		</form>';
		
		$result = $this->fix->fix_search_form_label( $form );
		
		$this->assertStringContainsString( 'Existing Search Label', $result );
		$this->assertStringNotContainsString( 'edac-generated-label', $result );
	}

	/**
	 * Test fix_search_form_label handles form without existing input id
	 */
	public function test_fix_search_form_label_handles_missing_input_id() {
		$form = '<form role="search" method="get" class="search-form" action="http://example.com/">
			<input type="search" class="search-field" placeholder="Search …" value="" name="s" />
			<button type="submit" class="search-submit">Search</button>
		</form>';
		
		$result = $this->fix->fix_search_form_label( $form );
		
		$this->assertStringContainsString( '<label for="search-form-', $result );
		$this->assertStringContainsString( 'Search for:', $result );
		$this->assertStringContainsString( 'id="search-form-', $result );
	}

	/**
	 * Test comment_search_label_section_callback method outputs description
	 */
	public function test_comment_search_label_section_callback() {
		ob_start();
		$this->fix->comment_search_label_section_callback();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'Settings to add missing labels to WordPress comment and search forms.', $output );
	}
}
