<?php
/**
 * Helper methods for database operations to support tests.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\TestHelpers;

use EDAC\Admin\Update_Database;
use WP_Post;

/**
 * Some helper methods for database operations useful for testing.
 */
class DatabaseHelpers {

	/**
	 * Create the table for the plugin.
	 *
	 * Used for setup before tests.
	 *
	 * @return void
	 */
	public static function create_table() {
		( new Update_Database() )->edac_update_database();
	}
	/**
	 * Insert a record to the database for a given post.
	 *
	 * @param WP_Post $post The post to insert the record for.
	 *
	 * @return void
	 */
	public static function insert_test_issue_to_db( WP_Post $post ): void {

		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->insert( // phpcs:ignore WordPress.DB -- using direct query for testing.
			$table_name,
			[
				'postid'        => $post->ID,
				'siteid'        => get_current_blog_id(),
				'type'          => $post->post_type,
				'rule'          => 'empty_paragraph_tag',
				'ruletype'      => 'warning',
				'object'        => '<p></p>',
				'recordcheck'   => 1,
				'user'          => get_current_user_id(),
				'ignre'         => 0,
				'ignre_user'    => null,
				'ignre_date'    => null,
				'ignre_comment' => null,
				'ignre_global'  => 0,
			]
		);
	}

	/**
	 * Drops the table for the plugin if it exists.
	 *
	 * Used for cleanup after tests.
	 *
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name ); // phpcs:ignore WordPress.DB -- query for a unit test.
	}
}
