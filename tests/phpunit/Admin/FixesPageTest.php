<?php
/**
 * Fixes Page tests.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;

/**
 * Fixes Page test case.
 */
class FixesPageTest extends WP_UnitTestCase {

	/**
	 * The page under test.
	 *
	 * @var FixesPage
	 */
	private $page;

	/**
	 * The sections filter callback registered for the tests in this class.
	 *
	 * @var callable
	 */
	private $sections_filter;

	/**
	 * The fields filter callback registered for the tests in this class.
	 *
	 * @var callable
	 */
	private $fields_filter;

	/**
	 * The filtered sections the page under test should see.
	 *
	 * @var array
	 */
	private $sections_payload = [];

	/**
	 * The filtered fields the page under test should see.
	 *
	 * @var array
	 */
	private $fields_payload = [];

	/**
	 * The registered settings fields before this test class ran.
	 *
	 * @var array|null
	 */
	private $original_settings_fields;

	/**
	 * The registered settings sections before this test class ran.
	 *
	 * @var array|null
	 */
	private $original_settings_sections;

	/**
	 * The registered settings before this test class ran.
	 *
	 * @var array|null
	 */
	private $original_registered_settings;

	/**
	 * Build the page under test and isolate the settings registries.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// add_settings_section()/add_settings_field() live in wp-admin. The suite
		// loads template.php because the admin partials are rendered by other
		// tests; require it explicitly so this class does not depend on that.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		global $wp_settings_fields, $wp_settings_sections, $wp_registered_settings;

		// Core never initialises these registries, so they may not exist yet in
		// this process. isset() keeps reading them notice-free either way.
		$this->original_settings_fields     = isset( $wp_settings_fields ) ? $wp_settings_fields : [];
		$this->original_settings_sections   = isset( $wp_settings_sections ) ? $wp_settings_sections : [];
		$this->original_registered_settings = isset( $wp_registered_settings ) ? $wp_registered_settings : [];

		$wp_settings_fields     = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- resetting a WP registry between tests.
		$wp_settings_sections   = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- resetting a WP registry between tests.
		$wp_registered_settings = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- resetting a WP registry between tests.

		// The fields and sections the registered fixes supply are not guaranteed to
		// be in the hook registry here (the fix test trait removes them in its
		// teardown), so each test declares the payload it wants instead. Priority 20
		// runs after the fixes' own callbacks and replaces their output.
		$this->sections_payload = [];
		$this->fields_payload   = [];
		$this->sections_filter  = function () {
			return $this->sections_payload;
		};
		$this->fields_filter    = function () {
			return $this->fields_payload;
		};
		add_filter( 'edac_filter_fixes_settings_sections', $this->sections_filter, 20 );
		add_filter( 'edac_filter_fixes_settings_fields', $this->fields_filter, 20 );

		$this->page = new FixesPage( 'manage_options' );
	}

	/**
	 * Remove the test filters and restore the settings registries.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'edac_filter_fixes_settings_sections', $this->sections_filter, 20 );
		remove_filter( 'edac_filter_fixes_settings_fields', $this->fields_filter, 20 );

		global $wp_settings_fields, $wp_settings_sections, $wp_registered_settings;
		$wp_settings_fields     = $this->original_settings_fields; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the registry saved in set_up().
		$wp_settings_sections   = $this->original_settings_sections; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the registry saved in set_up().
		$wp_registered_settings = $this->original_registered_settings; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the registry saved in set_up().

		parent::tear_down();
	}

	/**
	 * Test register_settings_sections handles scalar filter output without warnings.
	 */
	public function test_register_settings_sections_handles_scalar_filter_output() {
		$page = new FixesPage( 'manage_options' );

		add_filter(
			'edac_filter_fixes_settings_sections',
			static function () {
				return 'not-an-array';
			}
		);

		$handler = static function () {
			throw new \RuntimeException( 'PHP warning captured during register_settings_sections.' );
		};
		set_error_handler( $handler ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Intentional in test to convert warnings into failures.

		try {
			$page->register_settings_sections();
			$this->assertTrue( true );
		} catch ( \RuntimeException $exception ) {
			$this->fail( 'register_settings_sections should ignore scalar filter output without warnings.' );
		} finally {
			restore_error_handler();
			remove_all_filters( 'edac_filter_fixes_settings_sections' );
		}
	}

	/**
	 * The add_page() method wires the fixes tab, its content callback and the two
	 * screen slugs onto the settings page, and registers the sections and fields
	 * it is handed. The filter and action names here are the ones the settings
	 * page partial and the enqueue/notice classes consume.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::add_page
	 */
	public function test_add_page_wires_the_tab_and_registers_the_settings() {
		$this->sections_payload = [
			'edac_fixes_test_section' => [
				'title'    => 'Test Section',
				'callback' => '__return_empty_string',
			],
		];
		$this->fields_payload   = [
			'edac_fix_test_field' => [
				'label'       => 'Test Field',
				'description' => 'Test description.',
			],
		];

		$this->page->add_page();

		$this->assertNotFalse( has_filter( 'edac_filter_settings_tab_items', [ $this->page, 'add_fixes_tab' ] ) );
		$this->assertSame( 11, has_action( 'edac_settings_tab_content', [ $this->page, 'add_fixes_tab_content' ] ) );
		$this->assertNotFalse( has_filter( 'edac_filter_admin_scripts_slugs', [ $this->page, 'add_slug_to_admin_scripts' ] ) );
		$this->assertNotFalse( has_filter( 'edac_filter_remove_admin_notices_screens', [ $this->page, 'add_slug_to_admin_notices' ] ) );

		global $wp_settings_sections, $wp_settings_fields;
		$this->assertArrayHasKey( 'edac_fixes_test_section', $wp_settings_sections[ FixesPage::SETTINGS_SLUG ] );
		$this->assertArrayHasKey( 'edac_fix_test_field', $wp_settings_fields[ FixesPage::SETTINGS_SLUG ]['edac_fixes_general'] );
	}

	/**
	 * The fixes tab is appended to the tabs the settings page already has, with
	 * the id, label and order the page sorts and renders by.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::add_fixes_tab
	 */
	public function test_add_fixes_tab_appends_the_fixes_tab() {
		$existing = [
			[
				'slug'  => 'general',
				'label' => 'General',
				'order' => 1,
			],
		];

		$tabs = $this->page->add_fixes_tab( $existing );

		$this->assertCount( 2, $tabs );
		$this->assertSame( 'general', $tabs[0]['slug'] );
		$this->assertSame( 'fixes', $tabs[1]['slug'] );
		$this->assertSame( 'Fixes', $tabs[1]['label'] );
		$this->assertSame( 2, $tabs[1]['order'] );
	}

	/**
	 * The tab content callback renders the fixes page on the fixes tab only, so
	 * the partial is not echoed into every other tab.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::add_fixes_tab_content
	 */
	public function test_add_fixes_tab_content_renders_on_the_fixes_tab_only() {
		$this->assertSame( '', $this->capture_output( [ $this->page, 'add_fixes_tab_content' ], 'general' ) );
		$this->assertSame( '', $this->capture_output( [ $this->page, 'add_fixes_tab_content' ], null ) );
		$this->assertStringContainsString(
			'id="edac-fixes-page"',
			$this->capture_output( [ $this->page, 'add_fixes_tab_content' ], 'fixes' )
		);
	}

	/**
	 * The fixes screen slug added to the admin script slugs keeps the value the
	 * enqueue class compares the current page slug against.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::add_slug_to_admin_scripts
	 */
	public function test_add_slug_to_admin_scripts_appends_the_fixes_slug() {
		$this->assertSame( [ 'accessibility_checker_fixes' ], $this->page->add_slug_to_admin_scripts( [] ) );
		$this->assertSame(
			[ 'accessibility_checker_settings', 'accessibility_checker_fixes' ],
			$this->page->add_slug_to_admin_scripts( [ 'accessibility_checker_settings' ] )
		);
	}

	/**
	 * The fixes screen id added to the notice screens keeps the admin screen id
	 * the notices class compares against.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::add_slug_to_admin_notices
	 */
	public function test_add_slug_to_admin_notices_appends_the_fixes_screen_id() {
		$this->assertSame(
			[ 'accessibility-checker_page_accessibility_checker_fixes' ],
			$this->page->add_slug_to_admin_notices( [] )
		);
	}

	/**
	 * Only sections with a string id, a title and a callable callback are
	 * registered; every other shape in the filtered array is dropped instead of
	 * reaching add_settings_section().
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::register_settings_sections
	 */
	public function test_register_settings_sections_drops_malformed_entries() {
		$this->sections_payload = [
			'edac_valid_section'    => [
				'title'    => 'Valid',
				'callback' => '__return_empty_string',
			],
			'edac_missing_title'    => [
				'callback' => '__return_empty_string',
			],
			'edac_missing_callback' => [
				'title' => 'Missing callback',
			],
			'edac_uncallable'       => [
				'title'    => 'Uncallable',
				'callback' => 'edac_no_such_function_exists_here',
			],
			'edac_not_an_array'     => 'not-an-array',
		];

		$this->page->register_settings_sections();

		global $wp_settings_sections;
		$registered = $wp_settings_sections[ FixesPage::SETTINGS_SLUG ];

		$this->assertSame( [ 'edac_valid_section' ], array_keys( $registered ) );
		$this->assertSame( 'Valid', $registered['edac_valid_section']['title'] );
		$this->assertSame( '__return_empty_string', $registered['edac_valid_section']['callback'] );
	}

	/**
	 * Every filtered field becomes a registered settings field on its declared
	 * section, carrying the args the field renderers read, and takes the
	 * checkbox/checkbox-sanitizer/general-section defaults when it declares
	 * none of its own.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::register_fields_and_settings
	 */
	public function test_register_fields_and_settings_registers_fields_and_defaults() {
		$this->sections_payload = [
			'edac_fixes_general'      => [
				'title'    => 'General Fixes',
				'callback' => '__return_empty_string',
			],
			'edac_fixes_text_section' => [
				'title'    => 'Text Section',
				'callback' => '__return_empty_string',
			],
		];
		$this->fields_payload   = [
			'edac_fix_test_checkbox'        => [
				'label'       => 'Test Checkbox',
				'description' => 'Checkbox description.',
			],
			'edac_fix_test_text'            => [
				'label'             => 'Test Text',
				'description'       => 'Text description.',
				'type'              => 'text',
				'section'           => 'edac_fixes_text_section',
				'sanitize_callback' => 'sanitize_text_field',
				'condition'         => 'edac_fix_test_checkbox',
				'required_when'     => 'edac_fix_test_checkbox',
				'default'           => 'target',
				'help_id'           => 8638,
				'upsell'            => true,
			],
			'edac_fix_test_custom_callback' => [
				'label'       => 'Custom Callback',
				'description' => 'Custom description.',
				'callback'    => '__return_empty_string',
			],
		];

		$this->page->register_settings_sections();
		$this->page->register_fields_and_settings();

		global $wp_settings_fields, $wp_settings_sections, $wp_registered_settings;

		$checkbox = $wp_settings_fields[ FixesPage::SETTINGS_SLUG ]['edac_fixes_general']['edac_fix_test_checkbox'];
		$this->assertSame( 'Test Checkbox', $checkbox['title'] );
		$this->assertSame( [ $this->page, 'checkbox' ], $checkbox['callback'] );
		$this->assertSame( 'edac_fix_test_checkbox', $checkbox['args']['name'] );
		$this->assertSame( 'edac_fix_test_checkbox', $checkbox['args']['labelledby'] );
		$this->assertSame( 'Checkbox description.', $checkbox['args']['description'] );
		$this->assertSame( '', $checkbox['args']['condition'] );
		$this->assertSame( '', $checkbox['args']['required_when'] );
		$this->assertSame( '', $checkbox['args']['default'] );
		$this->assertFalse( $checkbox['args']['upsell'] );
		$this->assertSame( '', $checkbox['args']['help_id'] );
		$this->assertSame( 'Test Checkbox', $checkbox['args']['label'] );

		// A declared type picks the matching renderer.
		$text = $wp_settings_fields[ FixesPage::SETTINGS_SLUG ]['edac_fixes_text_section']['edac_fix_test_text'];
		$this->assertSame( [ $this->page, 'text' ], $text['callback'] );
		$this->assertSame( 'edac_fix_test_checkbox', $text['args']['condition'] );
		$this->assertSame( 'edac_fix_test_checkbox', $text['args']['required_when'] );
		$this->assertSame( 'target', $text['args']['default'] );
		$this->assertTrue( $text['args']['upsell'] );
		$this->assertSame( 8638, $text['args']['help_id'] );

		// A declared callback is used instead of the type's renderer.
		$custom = $wp_settings_fields[ FixesPage::SETTINGS_SLUG ]['edac_fixes_general']['edac_fix_test_custom_callback'];
		$this->assertSame( '__return_empty_string', $custom['callback'] );

		// The sanitizer follows the same default rule.
		$this->assertSame( [ $this->page, 'sanitize_checkbox' ], $wp_registered_settings['edac_fix_test_checkbox']['sanitize_callback'] );
		$this->assertSame( 'sanitize_text_field', $wp_registered_settings['edac_fix_test_text']['sanitize_callback'] );

		// A field registered on a section that was never registered renders nowhere.
		foreach ( array_keys( $wp_settings_fields[ FixesPage::SETTINGS_SLUG ] ) as $section_id ) {
			$this->assertArrayHasKey(
				$section_id,
				$wp_settings_sections[ FixesPage::SETTINGS_SLUG ],
				"Fields are registered on the section {$section_id}, which is not a registered section."
			);
		}
	}

	/**
	 * The general section callback prints the escaped description paragraph that
	 * appears above the fix fields.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::fixes_section_general_cb
	 */
	public function test_fixes_section_general_cb_prints_the_section_description() {
		$this->assertSame(
			'<p>' . esc_html__( 'These fixes help improve accessibility by modifying HTML elements and behaviors on your site.', 'accessibility-checker' ) . '</p>',
			$this->capture_output( [ $this->page, 'fixes_section_general_cb' ] )
		);
	}

	/**
	 * The render_page() method includes the fixes page partial.
	 *
	 * The partial is included with include_once, so if another test in this
	 * process already included it this call emits nothing. That case is
	 * detected rather than assumed: the include target is resolved first, and
	 * only when it has not been included yet is the rendered markup asserted.
	 *
	 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::render_page
	 */
	public function test_render_page_includes_the_fixes_page_partial() {
		$partial = realpath( EDAC_PLUGIN_DIR . 'partials/admin-page/fixes-page.php' );
		$this->assertNotFalse( $partial, 'The fixes page partial should exist in the plugin.' );

		$already_included = in_array( $partial, array_map( 'realpath', get_included_files() ), true );

		$output = $this->capture_output( [ $this->page, 'render_page' ] );

		if ( $already_included ) {
			$this->assertSame( '', $output );
			return;
		}

		$this->assertStringContainsString( 'id="edac-fixes-page"', $output );
	}

	/**
	 * Call a callback and return everything it echoed.
	 *
	 * @param callable $callback The callback to call.
	 * @param mixed    ...$args  The arguments to pass to the callback.
	 * @return string
	 */
	private function capture_output( callable $callback, ...$args ) {
		ob_start();
		$callback( ...$args );
		return ob_get_clean();
	}
}
