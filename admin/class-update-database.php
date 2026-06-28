<?php
/**
 * Class file for updating the database.
 *
 * @package Accessibility_Checker
 * @since 1.9.0
 */

namespace EDAC\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles admin notices
 *
 * @since 1.9.0
 */
class Update_Database {

	/**
	 * Class constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize WordPress hooks
	 */
	public function init_hooks() {
		add_action( 'admin_init', [ $this, 'edac_update_database' ], 10 );
	}

	/**
	 * Create/Update database
	 *
	 * @return void
	 */
	public function edac_update_database() {

		global $wpdb;
		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) )
		) === $table_name;
		$db_version   = get_option( 'edac_db_version' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepare above, Safe variable used for table name, caching not required for one time operation.
		if ( EDAC_DB_VERSION !== $db_version || ! $table_exists ) {

			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				siteid text NOT NULL,
				type text NOT NULL,
				landmark varchar(20) NULL,
				landmark_selector text NULL,
				selector text NULL,
				ancestry text NULL,
				xpath text NULL,
				rule text NOT NULL,
				ruletype text NOT NULL,
				object mediumtext NOT NULL,
				extra_data text NULL,
				source text NULL,
				recordcheck mediumint(9) NOT NULL,
				created timestamp NOT NULL default CURRENT_TIMESTAMP,
				user bigint(20) NOT NULL,
				ignre mediumint(9) NOT NULL,
				ignre_global mediumint(9) NOT NULL,
				ignre_user bigint(20) NULL,
				ignre_date timestamp NULL,
				ignre_reason varchar(50) NULL,
				ignre_comment mediumtext NULL,
				PRIMARY KEY (id),
				KEY postid_index (postid)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Run migration for selector-based unique identifiers if upgrading from older versions.
			if ( version_compare( $db_version, '1.0.5', '<' ) ) {
				$this->migrate_to_selector_based_unique_id();
			}

			// Migrate free plugin license key to shared option key used by both free and pro.
			if ( version_compare( $db_version, '1.0.7', '<' ) ) {
				$this->migrate_license_key_to_shared_option();
			}

			// 1.0.8: Added extra_data column. dbDelta() handles ADD COLUMN automatically
			// when the column appears in the CREATE TABLE DDL above; no data migration required.

			// 1.0.9: Added source column. Backfill existing rows to 'automated' since text
			// columns cannot carry a DB-level DEFAULT in MySQL 5.7. Also grant plugin
			// capabilities here so existing users who update (rather than reactivate)
			// receive them — register_activation_hook does not fire on plugin updates.
			if ( version_compare( $db_version, '1.0.9', '<' ) ) {
				$this->migrate_source_column( $table_name );
				$this->grant_plugin_capabilities();
			}
		}

		// Update database version option.
		update_option( 'edac_db_version', sanitize_text_field( EDAC_DB_VERSION ) );
	}

	/**
	 * Migrate early testing key to the shared option used by both free and pro.
	 *
	 * Copies `edac_license_key` to `edacp_license_key` if the free key exists and the
	 * shared key is not already set, then deletes the old option.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	private function migrate_license_key_to_shared_option() {
		$migratable_key = get_option( 'edac_license_key', '' );

		if ( empty( $migratable_key ) ) {
			return;
		}

		// Only copy if the shared key is not already occupied.
		if ( empty( get_option( 'edacp_license_key', '' ) ) ) {
			update_option( 'edacp_license_key', $migratable_key );
		}

		delete_option( 'edac_license_key' );
	}

	/**
	 * Backfill existing rows so every row has source = 'automated'.
	 *
	 * The source column is declared NULL in the CREATE TABLE DDL so dbDelta can add it
	 * to existing tables without a DEFAULT. PHP always writes the value explicitly on
	 * insert, but rows created before 1.0.9 need a one-time backfill.
	 *
	 * @since x.x.x
	 * @param string $table_name The full table name including prefix.
	 * @return void
	 */
	private function migrate_source_column( string $table_name ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET source = %s WHERE source IS NULL',
				$table_name,
				'automated'
			)
		);
	}

	/**
	 * Grant plugin capabilities to their default roles.
	 *
	 * Called during the 1.0.9 migration so that users who update the plugin
	 * (rather than deactivate and reactivate) also receive the capabilities.
	 * Uses edac_get_plugin_capabilities() so that the pro plugin's caps — registered
	 * via the edac_plugin_capabilities filter at plugins_loaded — are included when
	 * this migration runs on admin_init.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function grant_plugin_capabilities(): void {
		foreach ( edac_get_plugin_capabilities() as $cap => $roles ) {
			foreach ( $roles as $role_name ) {
				$role = get_role( $role_name );
				if ( $role ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Migrate existing records to use selector-based unique identifiers.
	 *
	 * This migration handles records that were created before the selector field
	 * was used as the unique identifier. Records with NULL selectors will have
	 * a fallback identifier generated based on their ID to ensure uniqueness.
	 *
	 * @since 1.0.5
	 * @return void
	 */
	private function migrate_to_selector_based_unique_id() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Find records with NULL or empty selectors and update them with a fallback value.
		// Using the record ID ensures each record has a unique selector for backward compatibility.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration query.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET selector = CONCAT('legacy-id-', id) WHERE selector IS NULL OR selector = ''",
				$table_name
			)
		);
	}
}
