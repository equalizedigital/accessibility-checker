<?php
/**
 * Tests for post statuses sanitization in options-page.php.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_sanitize_post_statuses().
 */
class OptionsPagePostStatusesTest extends WP_UnitTestCase {

	/**
	 * Create the custom table that delete_status_posts() needs.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'accessibility_checker';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				siteid bigint(20) NOT NULL,
				type text NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;"
		);
	}

	/**
	 * Drop the custom table and clean up filters/options after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB

		remove_all_filters( 'edac_scannable_post_statuses' );
		delete_option( 'edac_post_statuses' );
		parent::tear_down();
	}

	/**
	 * Verify stored statuses are returned unchanged when filter is active.
	 *
	 * @return void
	 */
	public function test_returns_stored_statuses_when_filter_is_active(): void {
		update_option( 'edac_post_statuses', [ 'draft', 'private' ] );

		add_filter(
			'edac_scannable_post_statuses',
			static function () {
				return [ 'publish' ];
			}
		);

		$result = edac_sanitize_post_statuses( [ 'publish', 'future', 'pending' ] );

		$this->assertSame( [ 'draft', 'private' ], $result );
	}

	/**
	 * Verify defaults are returned when filter is active and no stored option exists.
	 *
	 * @return void
	 */
	public function test_returns_defaults_when_filter_is_active_and_no_stored_option(): void {
		delete_option( 'edac_post_statuses' );

		add_filter(
			'edac_scannable_post_statuses',
			static function () {
				return [ 'publish' ];
			}
		);

		$result = edac_sanitize_post_statuses( [ 'publish' ] );

		$this->assertSame( [ 'publish', 'future', 'draft', 'pending', 'private' ], $result );
	}

	/**
	 * Verify submitted values are sanitized against allowed statuses when unfiltered.
	 *
	 * @return void
	 */
	public function test_sanitizes_selected_statuses_when_no_filter_is_active(): void {
		update_option( 'edac_post_statuses', [ 'publish', 'future', 'draft', 'pending', 'private' ] );

		$result = edac_sanitize_post_statuses( [ 'draft', 'trash', 'private', 'invalid' ] );

		$this->assertSame( [ 'draft', 'private' ], $result );
	}

	/**
	 * Verify that a filter returning an empty array is treated as no filter and
	 * sanitization proceeds normally against the submitted values.
	 *
	 * This guards against the regression where an empty-array return from the
	 * filter was incorrectly treated as an active override and caused the stored
	 * option to be returned unchanged instead of sanitizing the submitted input.
	 *
	 * @return void
	 */
	public function test_returns_stored_statuses_when_filter_returns_empty_array(): void {
		update_option( 'edac_post_statuses', [ 'publish', 'draft' ] );

		add_filter(
			'edac_scannable_post_statuses',
			static function () {
				return [];
			}
		);

		$result = edac_sanitize_post_statuses( [ 'draft', 'private' ] );

		// Filter returns empty array so it is not considered active — submitted
		// values should be sanitized normally against the allowed list.
		$this->assertSame( [ 'draft', 'private' ], $result );
	}
}
