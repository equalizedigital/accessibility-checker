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
		$table_name = $wpdb->prefix . 'accessibility_checker';

		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepare above, Safe variable used for table name, caching not required for one time operation.
		if ( get_option( 'edac_db_version' ) !== EDAC_DB_VERSION || $wpdb->get_var( $query ) !== $table_name ) {

			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				siteid text NOT NULL,
				type text NOT NULL,
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
				UNIQUE KEY id (id),
				KEY postid_index (postid)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

		}

		// Update database version option.
		update_option( 'edac_db_version', sanitize_text_field( EDAC_DB_VERSION ) );
	}
}
