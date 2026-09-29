<?php
/**
 * Tests for the Site Health debug information sections.
 *
 * @package Accessibility_Checker
 * @since 1.50.1
 */

use EDAC\Admin\SiteHealth\Free;
use EDAC\Admin\SiteHealth\Information;
use EDAC\Admin\SiteHealth\Pro;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddFileSizeAndTypeToLinkedFilesFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\SkipLinkFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Tests the Site Health debug information collectors and their wiring.
 *
 * The Free and Pro sections appear on Tools → Site Health → Info, so the field
 * keys they emit and the way they format stored options are a contract that
 * support relies on when reading a site's debug information.
 *
 * @covers \EDAC\Admin\SiteHealth\Free::get
 * @covers \EDAC\Admin\SiteHealth\Pro::get
 * @covers \EDAC\Admin\SiteHealth\Information::get_data
 * @covers \EDAC\Admin\SiteHealth\Information::init_hooks
 * @since 1.50.1
 */
class DebugInformationTest extends WP_UnitTestCase {

	/**
	 * The fixes manager the sections under test read their fixes from.
	 *
	 * @var FixesManager
	 */
	private $fixes_manager;

	/**
	 * The fixes the manager held before a test replaced them.
	 *
	 * @var array
	 */
	private $original_fixes = [];

	/**
	 * Options the sections under test read.
	 *
	 * @var string[]
	 */
	private const OPTION_KEYS = [
		'edac_accessibility_policy_page',
		'edac_activation_date',
		'edac_add_footer_accessibility_statement',
		'edac_delete_data',
		'edac_include_accessibility_statement_link',
		'edac_post_types',
		'edac_simplified_summary_position',
		'edac_simplified_summary_prompt',
		'edacp_ignore_user_roles',
		'edacp_license_status',
		'edacp_simplified_summary_heading',
	];

	/**
	 * Transients the sections under test read.
	 *
	 * @var string[]
	 */
	private const TRANSIENT_KEYS = [
		'edacp_scan_id',
		'edacp_scan_total',
	];

	/**
	 * Starts every test from a known option, transient and fixes state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->fixes_manager  = FixesManager::get_instance();
		$this->original_fixes = $this->read_manager_fixes();

		foreach ( self::OPTION_KEYS as $option ) {
			delete_option( $option );
		}

		foreach ( self::TRANSIENT_KEYS as $transient ) {
			delete_transient( $transient );
		}
	}

	/**
	 * Restores the fixes the manager held before the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->write_manager_fixes( $this->original_fixes );

		foreach ( $this->count_cache_keys() as $cache_key ) {
			wp_cache_delete( $cache_key );
		}

		parent::tearDown();
	}

	/**
	 * Cache keys the count helpers read through.
	 *
	 * @return string[]
	 */
	private function count_cache_keys(): array {
		return [
			'edac_errors_' . get_current_blog_id(),
			'edac_warnings_' . get_current_blog_id(),
			'edac_table_count_accessibility_checker',
			'edac_table_count_accessibility_checker_global_ignores',
		];
	}

	/**
	 * Reads the fixes currently held by the manager.
	 *
	 * @return array
	 */
	private function read_manager_fixes(): array {
		$reflection = new \ReflectionClass( $this->fixes_manager );
		$property   = $reflection->getProperty( 'fixes' );
		$property->setAccessible( true );

		return (array) $property->getValue( $this->fixes_manager );
	}

	/**
	 * Replaces the fixes the manager holds.
	 *
	 * @param array $fixes Fixes keyed by slug.
	 * @return void
	 */
	private function write_manager_fixes( array $fixes ): void {
		$reflection = new \ReflectionClass( $this->fixes_manager );
		$property   = $reflection->getProperty( 'fixes' );
		$property->setAccessible( true );
		$property->setValue( $this->fixes_manager, $fixes );
	}

	/**
	 * Builds one free fix and one fix that declares itself as a pro fix.
	 *
	 * Fixes shipped by the pro plugin carry an `is_pro` property set to true, so
	 * the two sections must split on that flag. Real fix classes are used here
	 * and the flag is set on the instance to keep the fixture to the shape the
	 * sections actually read.
	 *
	 * @return array
	 */
	private function fixes_with_one_pro_entry(): array {
		$free_fix = new SkipLinkFix();
		$pro_fix  = new AddFileSizeAndTypeToLinkedFilesFix();

		$pro_fix->is_pro = true;

		return [
			$free_fix::get_slug() => $free_fix,
			$pro_fix::get_slug()  => $pro_fix,
		];
	}

	/**
	 * Decodes a fixes field value back into an array.
	 *
	 * The value is JSON that has been escaped for output, so the entities have
	 * to be decoded before it can be inspected.
	 *
	 * @param string $value The stored field value.
	 * @return array
	 */
	private function decode_fixes_field( $value ): array {
		return (array) json_decode( html_entity_decode( (string) $value, ENT_QUOTES ), true );
	}

	/**
	 * Tests the label and field keys of the free section.
	 *
	 * @return void
	 */
	public function test_free_section_has_the_expected_label_and_field_keys(): void {
		$section = ( new Free() )->get();

		$this->assertSame( 'Accessibility Checker &mdash; Free', $section['label'] );
		$this->assertSame(
			[
				'version',
				'database_version',
				'policy_page',
				'activation_date',
				'footer_statement',
				'delete_data',
				'include_statement_link',
				'post_types',
				'simplified_sum_position',
				'simplified_sum_prompt',
				'post_count',
				'error_count',
				'warning_count',
				'db_table_count',
				'fixes',
			],
			array_keys( $section['fields'] )
		);
		$this->assertSame( EDAC_VERSION, $section['fields']['version']['value'] );
		$this->assertSame( EDAC_DB_VERSION, $section['fields']['database_version']['value'] );
	}

	/**
	 * Tests that the free fields report the stored option values.
	 *
	 * @return void
	 */
	public function test_free_fields_report_stored_option_values(): void {
		update_option( 'edac_accessibility_policy_page', 42 );
		update_option( 'edac_activation_date', '2024-01-02 03:04:05' );
		update_option( 'edac_add_footer_accessibility_statement', 1 );
		update_option( 'edac_delete_data', 1 );
		update_option( 'edac_include_accessibility_statement_link', 1 );
		update_option( 'edac_post_types', [ 'post', 'page' ] );
		update_option( 'edac_simplified_summary_position', 'before' );
		update_option( 'edac_simplified_summary_prompt', 'always' );

		$fields = ( new Free() )->get()['fields'];

		$this->assertSame( '42', $fields['policy_page']['value'] );
		$this->assertSame( '2024-01-02 03:04:05', $fields['activation_date']['value'] );
		$this->assertSame( 'Enabled', $fields['footer_statement']['value'] );
		$this->assertSame( 'Enabled', $fields['delete_data']['value'] );
		// esc_url() prepends http:// to the scheme-less Enabled string.
		$this->assertSame( 'http://Enabled', $fields['include_statement_link']['value'] );
		$this->assertSame( 'post, page', $fields['post_types']['value'] );
		$this->assertSame( 'before', $fields['simplified_sum_position']['value'] );
		$this->assertSame( 'always', $fields['simplified_sum_prompt']['value'] );
	}

	/**
	 * Tests that the boolean free fields report as disabled when unset.
	 *
	 * @return void
	 */
	public function test_free_flags_report_disabled_when_the_options_are_unset(): void {
		$fields = ( new Free() )->get()['fields'];

		$this->assertSame( 'Disabled', $fields['footer_statement']['value'] );
		$this->assertSame( 'Disabled', $fields['delete_data']['value'] );
	}

	/**
	 * Tests the free fields that fall back to an "Unset" placeholder.
	 *
	 * @return void
	 */
	public function test_free_policy_page_and_post_types_fall_back_to_unset(): void {
		$fields = ( new Free() )->get()['fields'];

		$this->assertSame( 'Unset', $fields['policy_page']['value'] );
		$this->assertSame( 'Unset', $fields['post_types']['value'] );
	}

	/**
	 * Tests the include statement link field when the option is not set.
	 *
	 * The value goes through esc_url(), which prepends http:// to any string
	 * without a scheme, so the flag renders as a URL. Recorded as observed so a
	 * change to how this field is escaped is visible; the expectation flips to
	 * "Disabled" if that escaping is corrected.
	 *
	 * @return void
	 */
	public function test_free_include_statement_link_reports_the_escaped_flag_when_unset(): void {
		$fields = ( new Free() )->get()['fields'];

		$this->assertSame( 'http://Disabled', $fields['include_statement_link']['value'] );
	}

	/**
	 * Tests that the cached counts are cast to integers.
	 *
	 * The count helpers return whatever the database or the object cache holds,
	 * which is a string, and the section is expected to cast it before output.
	 *
	 * @return void
	 */
	public function test_free_counts_are_reported_as_integers(): void {
		wp_cache_set( 'edac_errors_' . get_current_blog_id(), '7' );
		wp_cache_set( 'edac_warnings_' . get_current_blog_id(), '3' );
		wp_cache_set( 'edac_table_count_accessibility_checker', '5' );

		$fields = ( new Free() )->get()['fields'];

		$this->assertSame( 7, $fields['error_count']['value'] );
		$this->assertSame( 3, $fields['warning_count']['value'] );
		$this->assertSame( 5, $fields['db_table_count']['value'] );
	}

	/**
	 * Tests the post count for a configured post type.
	 *
	 * @return void
	 */
	public function test_free_post_count_lists_published_posts_of_the_configured_types(): void {
		update_option( 'edac_post_types', [ 'post' ] );

		// Clear the posts the install created so the count is the test's own.
		do {
			$existing = get_posts(
				[
					'post_type'      => 'post',
					'post_status'    => [ 'publish', 'draft', 'pending', 'future', 'private', 'trash', 'auto-draft' ],
					'posts_per_page' => 100,
				]
			);
			foreach ( $existing as $post ) {
				wp_delete_post( $post->ID, true );
			}
		} while ( $existing );

		$this->factory->post->create_many( 3 );

		// Force a recount so the fixtures created above are included.
		wp_cache_flush();

		$fields = ( new Free() )->get()['fields'];

		$this->assertSame( 'post: publish = 3', $fields['post_count']['value'] );
	}

	/**
	 * Tests the post count when nothing is configured to be scanned.
	 *
	 * @return void
	 */
	public function test_free_post_count_is_empty_when_no_post_types_are_configured(): void {
		$fields = ( new Free() )->get()['fields'];

		// There is no count to report, and the helper's false is escaped to ''.
		$this->assertSame( '', $fields['post_count']['value'] );
	}

	/**
	 * Tests that the free section drops the pro fixes and the is_pro flag.
	 *
	 * @return void
	 */
	public function test_free_fixes_field_contains_only_non_pro_fixes(): void {
		$this->write_manager_fixes( $this->fixes_with_one_pro_entry() );

		$value = ( new Free() )->get()['fields']['fixes']['value'];
		$fixes = $this->decode_fixes_field( $value );

		$this->assertSame( [ SkipLinkFix::get_slug() ], array_keys( $fixes ) );
		$this->assertArrayNotHasKey( 'is_pro', $fixes[ SkipLinkFix::get_slug() ] );
		$this->assertArrayHasKey( 'edac_fix_add_skip_link', $fixes[ SkipLinkFix::get_slug() ]['fields'] );
	}

	/**
	 * Tests the label and field keys of the pro section.
	 *
	 * @return void
	 */
	public function test_pro_section_has_the_expected_label_and_field_keys(): void {
		$section = ( new Pro() )->get();

		$this->assertSame( 'Accessibility Checker &mdash; Pro', $section['label'] );
		$this->assertSame(
			[
				'version',
				'database_version',
				'license_status',
				'scan_id',
				'scan_total',
				'simplified_sum_heading',
				'ignore_permissions',
				'ignores_db_table_count',
				'fixes',
			],
			array_keys( $section['fields'] )
		);
	}

	/**
	 * Tests the pro version fields when the pro plugin is not active.
	 *
	 * EDACP_VERSION and EDACP_DB_VERSION are only defined by the pro plugin, so
	 * the free plugin must report them as unset rather than erroring.
	 *
	 * @return void
	 */
	public function test_pro_version_fields_report_unset_without_the_pro_plugin(): void {
		$fields = ( new Pro() )->get()['fields'];

		$this->assertSame( 'Unset', $fields['version']['value'] );
		$this->assertSame( 'Unset', $fields['database_version']['value'] );
	}

	/**
	 * Tests that the pro fields report the stored option and transient values.
	 *
	 * @return void
	 */
	public function test_pro_fields_report_stored_option_and_transient_values(): void {
		update_option( 'edacp_license_status', 'valid' );
		update_option( 'edacp_simplified_summary_heading', 'Summary' );
		update_option( 'edacp_ignore_user_roles', [ 'administrator', 'editor' ] );
		set_transient( 'edacp_scan_id', 'scan-123' );
		set_transient( 'edacp_scan_total', '42' );
		wp_cache_set( 'edac_table_count_accessibility_checker_global_ignores', '2' );

		$fields = ( new Pro() )->get()['fields'];

		$this->assertSame( 'valid', $fields['license_status']['value'] );
		$this->assertSame( 'Summary', $fields['simplified_sum_heading']['value'] );
		$this->assertSame( 'administrator, editor', $fields['ignore_permissions']['value'] );
		$this->assertSame( 'scan-123', $fields['scan_id']['value'] );
		$this->assertSame( 42, $fields['scan_total']['value'] );
		$this->assertSame( 2, $fields['ignores_db_table_count']['value'] );
	}

	/**
	 * Tests the dismissed permissions field when no roles are stored.
	 *
	 * @return void
	 */
	public function test_pro_ignore_permissions_reports_none_when_the_option_is_unset(): void {
		$fields = ( new Pro() )->get()['fields'];

		$this->assertSame( 'None', $fields['ignore_permissions']['value'] );
	}

	/**
	 * Tests that the pro section keeps the pro fixes and drops the is_pro flag.
	 *
	 * @return void
	 */
	public function test_pro_fixes_field_contains_only_pro_fixes(): void {
		$this->write_manager_fixes( $this->fixes_with_one_pro_entry() );

		$value = ( new Pro() )->get()['fields']['fixes']['value'];
		$fixes = $this->decode_fixes_field( $value );

		$this->assertSame( [ AddFileSizeAndTypeToLinkedFilesFix::get_slug() ], array_keys( $fixes ) );
		$this->assertArrayNotHasKey( 'is_pro', $fixes[ AddFileSizeAndTypeToLinkedFilesFix::get_slug() ] );
	}

	/**
	 * Tests that init_hooks registers the debug information filter.
	 *
	 * @return void
	 */
	public function test_information_init_hooks_registers_the_debug_information_filter(): void {
		$information = new Information();
		$information->init_hooks();

		$this->assertNotFalse( has_filter( 'debug_information', [ $information, 'get_data' ] ) );

		remove_filter( 'debug_information', [ $information, 'get_data' ] );
	}

	/**
	 * Tests that the edac sections are merged into the existing information.
	 *
	 * @return void
	 */
	public function test_information_get_data_merges_the_edac_sections_into_existing_information(): void {
		$result = ( new Information() )->get_data( [ 'server' => [ 'label' => 'Server' ] ] );

		$this->assertSame( 'Server', $result['server']['label'] );
		$this->assertSame( 'Accessibility Checker &mdash; Free', $result['edac_free']['label'] );

		// The pro and audit history sections are only added by those plugins.
		if ( defined( 'EDACP_VERSION' ) ) {
			$this->assertArrayHasKey( 'edac_pro', $result );
		} else {
			$this->assertArrayNotHasKey( 'edac_pro', $result );
		}

		if ( defined( 'EDACAH_VERSION' ) ) {
			$this->assertArrayHasKey( 'edac_audit_history', $result );
		} else {
			$this->assertArrayNotHasKey( 'edac_audit_history', $result );
		}
	}

	/**
	 * Tests that the edac_debug_information filter can add its own sections.
	 *
	 * @return void
	 */
	public function test_information_get_data_applies_the_edac_debug_information_filter(): void {
		$callback = static function ( $information ) {
			$information['edac_test'] = [ 'label' => 'Test' ];
			return $information;
		};

		add_filter( 'edac_debug_information', $callback );
		$result = ( new Information() )->get_data( [] );
		remove_filter( 'edac_debug_information', $callback );

		$this->assertArrayHasKey( 'edac_test', $result );
		$this->assertSame( 'Test', $result['edac_test']['label'] );
		$this->assertArrayHasKey( 'edac_free', $result );
	}
}
