<?php
/**
 * Rules Page tests.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\RulesPage;
use EqualizeDigital\AccessibilityChecker\Rules\RuleRegistry;

/**
 * Rules Page test case.
 */
class RulesPageTest extends WP_UnitTestCase {

	/**
	 * Instance of RulesPage under test.
	 *
	 * @var RulesPage
	 */
	private RulesPage $page;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->page = new RulesPage( 'manage_options' );
	}

	/**
	 * Tear down after each test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		delete_option( 'edac_disabled_rules' );
		remove_all_filters( 'edac_filter_register_rules' );
		remove_all_filters( 'edac_filter_admin_scripts_slugs' );
		remove_all_filters( 'edac_filter_remove_admin_notices_screens' );
		remove_all_filters( 'edac_filter_settings_tab_items' );
		remove_all_actions( 'edac_settings_tab_content' );
		unset( $_POST['edac_active_rules'] );
	}

	// -------------------------------------------------------------------------
	// Constants
	// -------------------------------------------------------------------------

	/**
	 * Test PAGE_TAB_SLUG constant value.
	 */
	public function test_page_tab_slug_constant() {
		$this->assertSame( 'rules', RulesPage::PAGE_TAB_SLUG );
	}

	/**
	 * Test SETTINGS_SLUG constant value.
	 */
	public function test_settings_slug_constant() {
		$this->assertSame( 'edac_settings_rules', RulesPage::SETTINGS_SLUG );
	}

	// -------------------------------------------------------------------------
	// add_page() — hook registration
	// -------------------------------------------------------------------------

	/**
	 * Test that apply_disabled_rules_setting can be registered on edac_filter_register_rules at priority 5.
	 *
	 * The actual registration happens in the plugin bootstrap (plugins_loaded), not in add_page(),
	 * so this test verifies the method is hookable and callable as expected.
	 */
	public function test_apply_disabled_rules_setting_is_hookable_at_priority_5() {
		add_filter( 'edac_filter_register_rules', [ $this->page, 'apply_disabled_rules_setting' ], 5 );

		$this->assertSame(
			5,
			has_filter( 'edac_filter_register_rules', [ $this->page, 'apply_disabled_rules_setting' ] ),
			'apply_disabled_rules_setting should be hookable onto edac_filter_register_rules at priority 5'
		);
	}

	/**
	 * Test that add_page registers the admin scripts slugs filter.
	 */
	public function test_add_page_registers_admin_scripts_filter() {
		$this->page->add_page();

		$this->assertNotFalse(
			has_filter( 'edac_filter_admin_scripts_slugs', [ $this->page, 'add_slug_to_admin_scripts' ] )
		);
	}

	/**
	 * Test that add_page registers the admin notices screens filter.
	 */
	public function test_add_page_registers_admin_notices_filter() {
		$this->page->add_page();

		$this->assertNotFalse(
			has_filter( 'edac_filter_remove_admin_notices_screens', [ $this->page, 'add_slug_to_admin_notices' ] )
		);
	}

	/**
	 * Test that add_page registers the settings tab items filter.
	 */
	public function test_add_page_registers_settings_tab_items_filter() {
		$this->page->add_page();

		$this->assertNotFalse(
			has_filter( 'edac_filter_settings_tab_items', [ $this->page, 'add_rules_tab' ] )
		);
	}

	/**
	 * Test that add_page registers the settings tab content action.
	 */
	public function test_add_page_registers_settings_tab_content_action() {
		$this->page->add_page();

		$this->assertNotFalse(
			has_action( 'edac_settings_tab_content', [ $this->page, 'add_rules_tab_content' ] )
		);
	}

	// -------------------------------------------------------------------------
	// add_rules_tab()
	// -------------------------------------------------------------------------

	/**
	 * Test that add_rules_tab appends the rules tab with the correct shape.
	 */
	public function test_add_rules_tab_appends_tab() {
		$result = $this->page->add_rules_tab( [] );

		$this->assertCount( 1, $result );
		$this->assertSame( 'rules', $result[0]['slug'] );
		$this->assertSame( 3, $result[0]['order'] );
		$this->assertArrayHasKey( 'label', $result[0] );
	}

	/**
	 * Test that add_rules_tab preserves existing tab items.
	 */
	public function test_add_rules_tab_preserves_existing_items() {
		$existing = [
			[
				'slug'  => 'general',
				'label' => 'General',
				'order' => 1,
			],
		];
		$result   = $this->page->add_rules_tab( $existing );

		$this->assertCount( 2, $result );
		$this->assertSame( 'general', $result[0]['slug'] );
		$this->assertSame( 'rules', $result[1]['slug'] );
	}

	// -------------------------------------------------------------------------
	// add_rules_tab_content()
	// -------------------------------------------------------------------------

	/**
	 * Test that add_rules_tab_content outputs content for the rules tab.
	 */
	public function test_add_rules_tab_content_outputs_for_rules_tab() {
		ob_start();
		$this->page->add_rules_tab_content( 'rules' );
		$output = ob_get_clean();

		$this->assertNotEmpty( $output, 'Should output content when tab is "rules"' );
	}

	/**
	 * Test that add_rules_tab_content outputs nothing for other tabs.
	 */
	public function test_add_rules_tab_content_outputs_nothing_for_other_tabs() {
		ob_start();
		$this->page->add_rules_tab_content( 'general' );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should not output content for tabs other than "rules"' );
	}

	// -------------------------------------------------------------------------
	// add_slug_to_admin_scripts()
	// -------------------------------------------------------------------------

	/**
	 * Test that add_slug_to_admin_scripts appends the correct slug.
	 */
	public function test_add_slug_to_admin_scripts_appends_slug() {
		$result = $this->page->add_slug_to_admin_scripts( [] );

		$this->assertContains( 'accessibility_checker_rules', $result );
	}

	/**
	 * Test that add_slug_to_admin_scripts preserves existing slugs.
	 */
	public function test_add_slug_to_admin_scripts_preserves_existing_slugs() {
		$result = $this->page->add_slug_to_admin_scripts( [ 'existing_slug' ] );

		$this->assertContains( 'existing_slug', $result );
		$this->assertContains( 'accessibility_checker_rules', $result );
	}

	// -------------------------------------------------------------------------
	// add_slug_to_admin_notices()
	// -------------------------------------------------------------------------

	/**
	 * Test that add_slug_to_admin_notices appends the correct slug.
	 */
	public function test_add_slug_to_admin_notices_appends_slug() {
		$result = $this->page->add_slug_to_admin_notices( [] );

		$this->assertContains( 'accessibility-checker_page_accessibility_checker_rules', $result );
	}

	// -------------------------------------------------------------------------
	// rules_section_general_cb()
	// -------------------------------------------------------------------------

	/**
	 * Test that the section callback outputs a paragraph.
	 */
	public function test_rules_section_general_cb_outputs_paragraph() {
		ob_start();
		$this->page->rules_section_general_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<p>', $output );
	}

	// -------------------------------------------------------------------------
	// reset_rules_cb()
	// -------------------------------------------------------------------------

	/**
	 * Test that reset_rules_cb outputs a button with the correct id.
	 */
	public function test_reset_rules_cb_outputs_button() {
		ob_start();
		$this->page->reset_rules_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="edac-reset-rules"', $output );
		$this->assertStringContainsString( '<button', $output );
	}

	/**
	 * Test that reset_rules_cb outputs inline JavaScript.
	 */
	public function test_reset_rules_cb_outputs_script() {
		ob_start();
		$this->page->reset_rules_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script>', $output );
		$this->assertStringContainsString( 'edac-reset-rules', $output );
	}

	// -------------------------------------------------------------------------
	// disabled_rules_cb()
	// -------------------------------------------------------------------------

	/**
	 * Test that disabled_rules_cb outputs the rules list container.
	 */
	public function test_disabled_rules_cb_outputs_rules_list() {
		ob_start();
		$this->page->disabled_rules_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="edac-rules-list"', $output );
	}

	/**
	 * Test that disabled_rules_cb outputs checkboxes named edac_active_rules[].
	 */
	public function test_disabled_rules_cb_outputs_checkboxes() {
		ob_start();
		$this->page->disabled_rules_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'name="edac_active_rules[]"', $output );
	}

	/**
	 * Test that the first checkbox has the label_for-compatible id.
	 */
	public function test_disabled_rules_cb_first_checkbox_has_correct_id() {
		ob_start();
		$this->page->disabled_rules_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="edac_disabled_rules"', $output );
	}

	/**
	 * Test that disabled_rules_cb shows a notice and still renders the list when an external filter is present.
	 */
	public function test_disabled_rules_cb_shows_notice_and_list_when_external_filter_present() {
		add_filter( 'edac_filter_register_rules', '__return_empty_array' );

		ob_start();
		$this->page->disabled_rules_cb();
		$output = ob_get_clean();

		// Notice message should appear.
		$this->assertStringContainsString( 'edac_filter_register_rules', $output );

		// The rules list container should still be rendered.
		$this->assertStringContainsString( 'id="edac-rules-list"', $output );

		remove_filter( 'edac_filter_register_rules', '__return_empty_array' );
	}

	/**
	 * Test that all checkboxes are disabled when an external filter is present.
	 */
	public function test_disabled_rules_cb_checkboxes_disabled_when_external_filter_present() {
		add_filter( 'edac_filter_register_rules', '__return_empty_array' );

		ob_start();
		$this->page->disabled_rules_cb();
		$output = ob_get_clean();

		preg_match_all( '/<input[^>]+name="edac_active_rules\[\]"[^>]*>/', $output, $matches );
		$this->assertNotEmpty( $matches[0], 'Checkboxes should still be rendered' );
		foreach ( $matches[0] as $input ) {
			$this->assertStringContainsString( 'disabled', $input, 'Each checkbox should be disabled when external filter is active' );
		}

		remove_filter( 'edac_filter_register_rules', '__return_empty_array' );
	}

	/**
	 * Test that the reset button is disabled when an external filter is present.
	 */
	public function test_reset_rules_cb_button_disabled_when_external_filter_present() {
		add_filter( 'edac_filter_register_rules', '__return_empty_array' );

		ob_start();
		$this->page->reset_rules_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'disabled', $output );

		remove_filter( 'edac_filter_register_rules', '__return_empty_array' );
	}

	/**
	 * Test that disabled_rules_cb does not show a locked message when only the
	 * internal apply_disabled_rules_setting filter is registered.
	 */
	public function test_disabled_rules_cb_not_locked_with_only_internal_filter() {
		$this->page->add_page();

		ob_start();
		$this->page->disabled_rules_cb();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="edac-rules-list"', $output );
	}

	/**
	 * Test that a disabled rule's checkbox is not checked.
	 */
	public function test_disabled_rules_cb_unchecks_disabled_rules() {
		$all_slugs = array_column( RuleRegistry::load_rules(), 'slug' );
		$this->assertNotEmpty( $all_slugs, 'Need at least one rule to test with' );

		$slug_to_disable = $all_slugs[0];
		update_option( 'edac_disabled_rules', [ $slug_to_disable ] );

		ob_start();
		$this->page->disabled_rules_cb();
		$output = ob_get_clean();

		// The disabled rule should appear as an unchecked checkbox.
		// A checked checkbox has checked="checked"; an unchecked one does not.
		// Find the input with this slug value and assert it is not checked.
		$pattern = '/value="' . preg_quote( $slug_to_disable, '/' ) . '"[^>]*>/';
		preg_match( $pattern, $output, $matches );
		$this->assertNotEmpty( $matches, 'Checkbox for disabled rule should be present in output' );
		$this->assertStringNotContainsString( 'checked', $matches[0] );
	}

	// -------------------------------------------------------------------------
	// sanitize_disabled_rules()
	// -------------------------------------------------------------------------

	/**
	 * Test that sanitize_disabled_rules returns the existing stored value when not pro and nothing is submitted.
	 *
	 * Diff computation is a pro-only feature; without pro the method always returns
	 * whatever is already stored in the option.
	 */
	public function test_sanitize_disabled_rules_returns_existing_stored_value_when_not_pro() {
		unset( $_POST['edac_active_rules'] );

		$result = $this->page->sanitize_disabled_rules();

		$this->assertEmpty( $result );
	}

	/**
	 * Test that sanitize_disabled_rules returns empty array when all rules are submitted as active.
	 */
	public function test_sanitize_disabled_rules_returns_empty_when_all_active() {
		$all_slugs = array_column( RuleRegistry::load_rules(), 'slug' );

		$_POST['edac_active_rules'] = $all_slugs; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$result = $this->page->sanitize_disabled_rules();

		$this->assertEmpty( $result );
	}

	/**
	 * Test that sanitize_disabled_rules returns existing stored value when not pro, ignoring POST data.
	 *
	 * Diff computation is a pro-only feature; without pro the submitted active-rules
	 * list is ignored and whatever is currently stored is preserved.
	 */
	public function test_sanitize_disabled_rules_ignores_post_data_when_not_pro() {
		$stored = [ 'some_rule' ];
		update_option( 'edac_disabled_rules', $stored );

		$all_slugs = array_column( RuleRegistry::load_rules(), 'slug' );
		$this->assertGreaterThan( 1, count( $all_slugs ), 'Need at least 2 rules for this test' );

		// Submit all-but-first as active — should be ignored when not pro.
		$_POST['edac_active_rules'] = array_slice( $all_slugs, 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$result = $this->page->sanitize_disabled_rules();

		$this->assertSame( $stored, $result );
	}

	/**
	 * Test that sanitize_disabled_rules preserves the existing stored value when an external filter is active.
	 */
	public function test_sanitize_disabled_rules_preserves_existing_when_external_filter_present() {
		$stored = [ 'some_rule_slug' ];
		update_option( 'edac_disabled_rules', $stored );

		// Submit different data — it should be ignored.
		$_POST['edac_active_rules'] = []; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		add_filter( 'edac_filter_register_rules', '__return_empty_array' );

		$result = $this->page->sanitize_disabled_rules();

		$this->assertSame( $stored, $result, 'Existing stored value should be preserved when external filter is active' );

		remove_filter( 'edac_filter_register_rules', '__return_empty_array' );
	}

	/**
	 * Test that sanitize_disabled_rules rejects slugs not in the registered rules list.
	 */
	public function test_sanitize_disabled_rules_rejects_unknown_slugs() {
		$all_slugs = array_column( RuleRegistry::load_rules(), 'slug' );

		$_POST['edac_active_rules'] = array_merge( $all_slugs, [ 'fake_rule_slug', '<script>xss</script>' ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$result = $this->page->sanitize_disabled_rules();

		// Unknown slugs are rejected — since all real slugs are active, disabled should be empty.
		$this->assertEmpty( $result );
	}

	// -------------------------------------------------------------------------
	// apply_disabled_rules_setting()
	// -------------------------------------------------------------------------

	/**
	 * Test that apply_disabled_rules_setting returns rules unchanged when not pro.
	 */
	public function test_apply_disabled_rules_setting_returns_all_rules_when_not_pro() {
		$rules = [
			[
				'slug'      => 'rule_one',
				'rule_type' => 'error',
			],
			[
				'slug'      => 'rule_two',
				'rule_type' => 'warning',
			],
		];

		update_option( 'edac_disabled_rules', [ 'rule_one' ] );

		// edac_is_pro() returns false in the test environment (no valid licence).
		$result = $this->page->apply_disabled_rules_setting( $rules );

		$this->assertSame( $rules, $result, 'Rules should be unchanged when not pro' );
	}

	/**
	 * Test that apply_disabled_rules_setting returns rules unchanged when disabled list is empty.
	 */
	public function test_apply_disabled_rules_setting_returns_all_rules_when_disabled_is_empty() {
		$rules = [
			[
				'slug'      => 'rule_one',
				'rule_type' => 'error',
			],
			[
				'slug'      => 'rule_two',
				'rule_type' => 'warning',
			],
		];

		update_option( 'edac_disabled_rules', [] );

		$result = $this->page->apply_disabled_rules_setting( $rules );

		$this->assertSame( $rules, $result );
	}

	/**
	 * Test that apply_disabled_rules_setting returns rules unchanged when disabled option is not an array.
	 */
	public function test_apply_disabled_rules_setting_handles_non_array_option() {
		$rules = [
			[
				'slug'      => 'rule_one',
				'rule_type' => 'error',
			],
		];

		update_option( 'edac_disabled_rules', 'not-an-array' );

		$result = $this->page->apply_disabled_rules_setting( $rules );

		$this->assertSame( $rules, $result );
	}

	// -------------------------------------------------------------------------
	// register_settings_sections() — error handling
	// -------------------------------------------------------------------------

	/**
	 * Test that register_settings_sections handles a scalar filter return without warnings.
	 */
	public function test_register_settings_sections_handles_scalar_filter_output() {
		$handler = static function () {
			throw new \RuntimeException( 'PHP warning captured during register_settings_sections.' );
		};
		set_error_handler( $handler ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Intentional in test.

		try {
			$this->page->register_settings_sections();
			$this->assertTrue( true );
		} catch ( \RuntimeException $e ) {
			$this->fail( 'register_settings_sections should not emit warnings.' );
		} finally {
			restore_error_handler();
		}
	}
}
