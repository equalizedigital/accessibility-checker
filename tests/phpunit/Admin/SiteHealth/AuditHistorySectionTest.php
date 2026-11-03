<?php
/**
 * Tests for the Site Health audit history section.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\SiteHealth\Audit_History;
use EDAC\Admin\SiteHealth\Information;

/**
 * Tests for Audit_History::get() and the version gate that registers it.
 *
 * Tools -> Site Health -> Info only shows the "Accessibility Checker &mdash;
 * Audit History" section when the Audit History add-on is active, because the
 * section's version field reads the EDACAH_VERSION constant that the add-on
 * defines. Both halves of that contract are pinned here: the fields the
 * section emits, and the registration gate in Information::get_edac_data().
 *
 * @covers \EDAC\Admin\SiteHealth\Audit_History::get
 * @covers \EDAC\Admin\SiteHealth\Information::get_edac_data
 */
class AuditHistorySectionTest extends WP_UnitTestCase {

	/**
	 * The cache key the section's table count field reads through.
	 *
	 * @var string
	 */
	private const TABLE_COUNT_CACHE_KEY = 'edac_table_count_accessibility_checker_audit_history';

	/**
	 * Stands in for the Audit History add-on, which is what defines the constant.
	 *
	 * The free plugin never defines EDACAH_VERSION on its own, so without this the
	 * section would not be registered at all and there would be nothing to assert.
	 * Constants cannot be undefined again, so the define is guarded and every
	 * expectation below is written to hold either way.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! defined( 'EDACAH_VERSION' ) ) {
			define( 'EDACAH_VERSION', '1.4.2' );
		}
	}

	/**
	 * Seeds the count the section reports, so no query runs against the add-on's table.
	 *
	 * The audit history table is created by the add-on rather than by the free
	 * plugin, so the helper's cache is primed here instead of letting the lookup
	 * fall through to the database.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		wp_cache_set( self::TABLE_COUNT_CACHE_KEY, '12' );
	}

	/**
	 * Leaves no cached count or filtered section behind for the next test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		wp_cache_delete( self::TABLE_COUNT_CACHE_KEY );
		remove_all_filters( 'edac_debug_information' );

		parent::tearDown();
	}

	/**
	 * Tests the label and the field keys of the section.
	 *
	 * @return void
	 */
	public function test_section_has_the_expected_label_and_field_keys(): void {
		$section = ( new Audit_History() )->get();

		$this->assertSame( 'Accessibility Checker &mdash; Audit History', $section['label'] );
		$this->assertSame( [ 'version', 'ignores_db_table_count' ], array_keys( $section['fields'] ) );
	}

	/**
	 * Tests that the version field reports the add-on's version constant.
	 *
	 * @return void
	 */
	public function test_version_field_reports_the_addon_version(): void {
		$fields = ( new Audit_History() )->get()['fields'];

		$this->assertSame( 'Version', $fields['version']['label'] );
		$this->assertSame( EDACAH_VERSION, $fields['version']['value'] );
	}

	/**
	 * Tests that the table count field reports the cached count without casting it.
	 *
	 * The free and pro sections both wrap this helper's value in absint(); this
	 * section does not, so whatever the object cache hands back is what reaches
	 * Site Health. Pinned as it behaves today - if the cast is added later this
	 * expectation has to move with it.
	 *
	 * @return void
	 */
	public function test_table_count_field_reports_the_cached_count_uncast(): void {
		$fields = ( new Audit_History() )->get()['fields'];

		$this->assertSame( 'DB Table Count', $fields['ignores_db_table_count']['label'] );
		$this->assertSame( '12', $fields['ignores_db_table_count']['value'] );
	}

	/**
	 * Tests that the info section registers the audit history collector.
	 *
	 * The section is added on top of the free one rather than replacing it, so a
	 * site reading its debug information sees both.
	 *
	 * @return void
	 */
	public function test_information_registers_the_audit_history_collector(): void {
		$result = ( new Information() )->get_data( [] );

		$this->assertArrayHasKey( 'edac_free', $result );
		$this->assertArrayHasKey( 'edac_audit_history', $result );
		$this->assertSame( ( new Audit_History() )->get(), $result['edac_audit_history'] );
	}
}
