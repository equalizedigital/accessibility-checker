<?php
/**
 * Tests for the Update_Database upgrade migrations.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Update_Database;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;

/**
 * Tests for the migrations Update_Database::edac_update_database() runs when the
 * recorded database version is older than the version the plugin ships.
 *
 * @covers \EDAC\Admin\Update_Database::edac_update_database
 * @covers \EDAC\Admin\Update_Database::migrate_license_key_to_shared_option
 * @covers \EDAC\Admin\Update_Database::migrate_to_selector_based_unique_id
 */
class UpdateDatabaseMigrationsTest extends WP_UnitTestCase {

	/**
	 * The issues table the selector migration writes to.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Options the migrations read and write.
	 *
	 * @var string[]
	 */
	private $migration_options = [ 'edac_license_key', 'edacp_license_key', 'edac_db_version' ];

	/**
	 * Creates the issues table and clears the state the migrations read.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wpdb;

		$this->table_name = $wpdb->prefix . 'accessibility_checker';

		// Create the table through the plugin's own update routine so the real
		// schema - including the selector column the migration writes - exists.
		DatabaseHelpers::create_table();

		$this->clear_migration_options();
		$this->clear_issue_rows();
	}

	/**
	 * Drops the issues table and clears the options a test left behind.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		DatabaseHelpers::drop_table();

		$this->clear_migration_options();

		parent::tearDown();
	}

	/**
	 * Tests a legacy license key is copied to the shared option and removed.
	 *
	 * @return void
	 */
	public function test_license_key_migration_copies_the_legacy_key_to_the_shared_option(): void {
		update_option( 'edac_license_key', 'legacy-key-123' );

		$this->run_migration( 'migrate_license_key_to_shared_option' );

		$this->assertSame( 'legacy-key-123', get_option( 'edacp_license_key' ) );
		$this->assertFalse( get_option( 'edac_license_key' ) );
	}

	/**
	 * Tests an occupied shared option is kept but the legacy option is still removed.
	 *
	 * @return void
	 */
	public function test_license_key_migration_does_not_overwrite_an_existing_shared_key(): void {
		update_option( 'edac_license_key', 'legacy-key-123' );
		update_option( 'edacp_license_key', 'already-there' );

		$this->run_migration( 'migrate_license_key_to_shared_option' );

		$this->assertSame( 'already-there', get_option( 'edacp_license_key' ) );
		$this->assertFalse( get_option( 'edac_license_key' ) );
	}

	/**
	 * Tests an empty legacy key is treated as no key at all.
	 *
	 * @dataProvider provider_empty_legacy_keys
	 *
	 * @param string $legacy_key A stored value that empties to false.
	 */
	public function test_license_key_migration_does_nothing_without_a_usable_legacy_key( string $legacy_key ): void {
		update_option( 'edac_license_key', $legacy_key );
		update_option( 'edacp_license_key', 'already-there' );

		$this->run_migration( 'migrate_license_key_to_shared_option' );

		$this->assertSame( 'already-there', get_option( 'edacp_license_key' ) );
		$this->assertSame( $legacy_key, get_option( 'edac_license_key', 'option-was-removed' ) );
	}

	/**
	 * Data provider for legacy key values that are dropped by empty().
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_empty_legacy_keys(): array {
		return [
			'empty string' => [ '' ],
			'zero string'  => [ '0' ],
		];
	}

	/**
	 * Tests rows without a selector get a unique legacy identifier.
	 *
	 * @return void
	 */
	public function test_selector_migration_stamps_legacy_ids_on_rows_without_a_selector(): void {
		$null_selector_id  = $this->insert_issue_row( null );
		$empty_selector_id = $this->insert_issue_row( '' );
		$kept_selector_id  = $this->insert_issue_row( 'h1 > a' );

		$this->run_migration( 'migrate_to_selector_based_unique_id' );

		$this->assertSame( 'legacy-id-' . $null_selector_id, $this->stored_selector( $null_selector_id ) );
		$this->assertSame( 'legacy-id-' . $empty_selector_id, $this->stored_selector( $empty_selector_id ) );
		$this->assertSame( 'h1 > a', $this->stored_selector( $kept_selector_id ) );
	}

	/**
	 * Tests running the selector migration again leaves stamped selectors alone.
	 *
	 * The migration is not version-guarded on its own, so a second run has to be
	 * a no-op for rows it has already given a selector to.
	 *
	 * @return void
	 */
	public function test_selector_migration_is_idempotent(): void {
		$row_id = $this->insert_issue_row( null );

		$this->run_migration( 'migrate_to_selector_based_unique_id' );
		$after_first_run = $this->stored_selector( $row_id );

		$this->run_migration( 'migrate_to_selector_based_unique_id' );

		$this->assertSame( 'legacy-id-' . $row_id, $after_first_run );
		$this->assertSame( $after_first_run, $this->stored_selector( $row_id ) );
	}

	/**
	 * Tests the stored db version decides which migrations run and that the
	 * version option is brought up to date.
	 *
	 * @dataProvider provider_stored_db_versions
	 *
	 * @param string $stored_version      The version recorded in the db version option.
	 * @param bool   $expect_selector     Whether the selector migration should run.
	 * @param bool   $expect_license_copy Whether the license key migration should run.
	 */
	public function test_update_database_runs_the_migrations_for_older_versions( string $stored_version, bool $expect_selector, bool $expect_license_copy ): void {
		$row_id = $this->insert_issue_row( null );

		update_option( 'edac_license_key', 'legacy-key-123' );
		update_option( 'edac_db_version', $stored_version );

		( new Update_Database() )->edac_update_database();

		if ( $expect_selector ) {
			$this->assertSame( 'legacy-id-' . $row_id, $this->stored_selector( $row_id ) );
		} else {
			$this->assertNull( $this->stored_selector( $row_id ) );
		}

		if ( $expect_license_copy ) {
			$this->assertSame( 'legacy-key-123', get_option( 'edacp_license_key' ) );
			$this->assertFalse( get_option( 'edac_license_key' ) );
		} else {
			$this->assertSame( 'legacy-key-123', get_option( 'edac_license_key' ) );
			$this->assertFalse( get_option( 'edacp_license_key' ) );
		}

		$this->assertSame( EDAC_DB_VERSION, get_option( 'edac_db_version' ) );
	}

	/**
	 * Data provider for stored database versions around the migration boundaries.
	 *
	 * @return array<string, array{0: string, 1: bool, 2: bool}>
	 */
	public function provider_stored_db_versions(): array {
		return [
			'older than both migrations'  => [ '1.0.4', true, true ],
			'selector migration applied'  => [ '1.0.5', false, true ],
			'still short of the license'  => [ '1.0.6', false, true ],
			'current version, no migrate' => [ '1.0.7', false, false ],
			'downgraded from a newer db'  => [ '1.0.8', false, false ],
		];
	}

	/**
	 * Runs one of the class's private migration methods.
	 *
	 * @param string $method The private method to invoke.
	 * @return void
	 */
	private function run_migration( string $method ): void {
		$reflection = new ReflectionMethod( Update_Database::class, $method );

		// setAccessible() is a no-op from PHP 8.1 and deprecated on 8.5.
		if ( PHP_VERSION_ID < 80100 ) {
			$reflection->setAccessible( true );
		}

		$reflection->invoke( new Update_Database() );
	}

	/**
	 * Inserts an issue row with the given selector.
	 *
	 * @param string|null $selector The selector to store, or null for a NULL selector.
	 * @return int The inserted row ID.
	 */
	private function insert_issue_row( $selector = null ): int {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture on a test-owned table.
			$this->table_name,
			[
				'postid'       => 1,
				'siteid'       => (string) get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'empty_paragraph_tag',
				'ruletype'     => 'warning',
				'object'       => '<p></p>',
				'recordcheck'  => 1,
				'user'         => 0,
				'ignre'        => 0,
				'ignre_global' => 0,
				'selector'     => $selector,
			]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Returns the selector stored for a row.
	 *
	 * @param int $id The row ID.
	 * @return string|null
	 */
	private function stored_selector( int $id ) {
		global $wpdb;

		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test-only read of a test-owned table.
			$wpdb->prepare( 'SELECT selector FROM %i WHERE id = %d', $this->table_name, $id )
		);
	}

	/**
	 * Empties the issues table.
	 *
	 * Other test classes use this same table and not all of them drop it
	 * (FrontendHighlightAjaxTest leaves rows behind), so start from empty.
	 *
	 * @return void
	 */
	private function clear_issue_rows(): void {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Resetting a test-owned table.
			$wpdb->prepare( 'DELETE FROM %i', $this->table_name )
		);
	}

	/**
	 * Removes the options the migrations read and write.
	 *
	 * @return void
	 */
	private function clear_migration_options(): void {
		foreach ( $this->migration_options as $option ) {
			delete_option( $option );
		}
	}
}
