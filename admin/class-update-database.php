<?php
/**
 * Class file for updating the database.
 *
 * @package Accessibility_Checker
 * @since 1.9.0
 */

namespace EDAC\Admin;

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
				recordcheck mediumint(9) NOT NULL,
				created timestamp NOT NULL default CURRENT_TIMESTAMP,
				user bigint(20) NOT NULL,
				ignre mediumint(9) NOT NULL,
				ignre_global mediumint(9) NOT NULL,
				ignre_user bigint(20) NULL,
				ignre_date timestamp NULL,
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
		}

		// Update database version option.
		update_option( 'edac_db_version', sanitize_text_field( EDAC_DB_VERSION ) );
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
