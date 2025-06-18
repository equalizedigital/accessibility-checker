<?php
/**
 * Inserts rule data about a post to the database
 *
 * @since 1.10.0
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class for inserting rule data into the database
 *
 * @since 1.10.0
 */
class Insert_Rule_Data {

	/**
	 * Insert rule data into database
	 *
	 * @since 1.10.0
	 *
	 * @param object $post     The post object. Must have a valid ID.
	 * @param string $rule     The rule.
	 * @param string $ruletype The rule type.
	 * @param string $rule_obj The object.
	 *
	 * @return void|int|\WP_Error The ID of the inserted record, void if no
	 * record was inserted or a WP_Error if the insert failed.
	 */
	public function insert( object $post, string $rule, string $ruletype, string $rule_obj ) {

		if ( ! isset( $post->ID, $post->post_type )
			|| empty( $rule )
			|| empty( $ruletype )
			|| empty( $rule_obj )
		) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// set up rule data array.
		$rule_data = [
			'postid'        => $post->ID,
			'siteid'        => get_current_blog_id(),
			'type'          => $post->post_type,
			'rule'          => $rule,
			'ruletype'      => $ruletype,
			'object'        => esc_attr( $rule_obj ),
			'recordcheck'   => 1,
			'user'          => get_current_user_id(),
			'ignre'         => 0,
			'ignre_user'    => null,
			'ignre_date'    => null,
			'ignre_comment' => null,
			'ignre_global'  => 0,
		];

		// return if revision.
		if ( 'revision' === $rule_data['type'] ) {
			return;
		}

		// Check if exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT postid, ignre FROM %i where type = %s and postid = %d and rule = %s and object = %s and siteid = %d',
				$table_name,
				$rule_data['type'],
				$rule_data['postid'],
				$rule_data['rule'],
				$rule_data['object'],
				$rule_data['siteid']
			),
			ARRAY_A
		);

		// Loop existing records.
		if ( $results ) {
			foreach ( $results as $row ) {

				// if being ignored, don't overwrite value.
				if ( true === (bool) $row['ignre'] ) {
					$rule_data['ignre'] = 1;
				}

				// update existing record.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
				$wpdb->query(
					$wpdb->prepare(
						'UPDATE %i SET recordcheck = %d, ignre = %d  WHERE siteid = %d and postid = %d and rule = %s and object = %s and type = %s',
						$table_name,
						1,
						$rule_data['ignre'],
						$rule_data['siteid'],
						$rule_data['postid'],
						$rule_data['rule'],
						$rule_data['object'],
						$rule_data['type']
					)
				);

			}
		}

		// Insert new records.
		if ( ! $results ) {

			/**
			 * Filter the rule data before inserting it into the database.
			 *
			 * This data will be sanitized after the filter is applied.
			 *
			 * @since 1.4.0
			 *
			 * @param array $rule_data The rule data.
			 */
			$rule_data = apply_filters( 'edac_filter_insert_rule_data', $rule_data );

			// Sanitize rule data since it is filtered, and we can't be sure
			// the data is still as valid as it was when it was first set.
			// Sanitize the filtered data.
			$rule_data_sanitized = [
				'postid'        => absint( $rule_data['postid'] ),
				'siteid'        => absint( $rule_data['siteid'] ),
				'type'          => sanitize_text_field( $rule_data['type'] ),
				'rule'          => sanitize_text_field( $rule_data['rule'] ),
				'ruletype'      => sanitize_text_field( $rule_data['ruletype'] ),
				'object'        => esc_attr( $rule_data['object'] ),
				'recordcheck'   => absint( $rule_data['recordcheck'] ),
				'user'          => absint( $rule_data['user'] ),
				'ignre'         => absint( $rule_data['ignre'] ),
				'ignre_user'    => isset( $rule_data['ignre_user'] ) ? absint( $rule_data['ignre_user'] ) : null,
				'ignre_date'    => isset( $rule_data['ignre_date'] ) ? sanitize_text_field( $rule_data['ignre_date'] ) : null,
				'ignre_comment' => isset( $rule_data['ignre_comment'] ) ? sanitize_text_field( $rule_data['ignre_comment'] ) : null,
				'ignre_global'  => absint( $rule_data['ignre_global'] ),
			];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using direct query for adding data to database.
			$wpdb->insert( $table_name, $rule_data_sanitized );

			// Return insert id or error.
			return $wpdb->insert_id;
		}
	}
}
