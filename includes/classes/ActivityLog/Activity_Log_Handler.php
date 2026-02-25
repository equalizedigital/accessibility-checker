<?php
/**
 * Activity Log Handler class.
 *
 * @package Accessibility_Checker
 * @since 1.36.0
 */

namespace EqualizeDigital\AccessibilityChecker\ActivityLog;

/**
 * Handles hooking into plugin actions to log activities.
 *
 * @since 1.36.0
 */
class Activity_Log_Handler {

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.36.0
	 */
	public function init() {
		add_action( 'edac_after_post_scan', [ $this, 'log_post_scan' ], 10, 2 );
		add_action( 'edac_before_clear_issues', [ $this, 'log_clear_issues' ], 10, 1 );
		add_action( 'edac_after_ignore_issue', [ $this, 'log_ignore_issue' ], 10, 3 );
	}

	/**
	 * Log post scan activity.
	 *
	 * @since 1.36.0
	 *
	 * @param int $post_id The post ID.
	 */
	public function log_post_scan( int $post_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Get error count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Needed for activity log.
		$errors = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE siteid = %d AND postid = %d AND ruletype = %s AND ignre = %d',
				$table_name,
				get_current_blog_id(),
				$post_id,
				'error',
				0
			)
		);

		// Get warning count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Needed for activity log.
		$warnings = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE siteid = %d AND postid = %d AND ruletype = %s AND ignre = %d',
				$table_name,
				get_current_blog_id(),
				$post_id,
				'warning',
				0
			)
		);

		$post_title = get_the_title( $post_id );
		if ( empty( $post_title ) ) {
			$post_title = sprintf(
				/* translators: %d: Post ID. */
				__( 'Post #%d', 'accessibility-checker' ),
				$post_id
			);
		}

		$message = sprintf(
			/* translators: %1$s: Post title, %2$d: Number of errors, %3$d: Number of warnings. */
			__( 'Scanned "%1$s": %2$d errors, %3$d warnings found', 'accessibility-checker' ),
			$post_title,
			$errors,
			$warnings
		);

		Logger::log(
			'post_scan',
			$message,
			[
				'post_id' => $post_id,
			]
		);
	}

	/**
	 * Log clear issues activity.
	 *
	 * @since 1.36.0
	 *
	 * @param int $post_id The post ID.
	 */
	public function log_clear_issues( int $post_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Get count of issues before deletion.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Needed for activity log.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE postid = %d AND siteid = %d',
				$table_name,
				$post_id,
				get_current_blog_id()
			)
		);

		$post_title = get_the_title( $post_id );
		if ( empty( $post_title ) ) {
			$post_title = sprintf(
				/* translators: %d: Post ID. */
				__( 'Post #%d', 'accessibility-checker' ),
				$post_id
			);
		}

		$message = sprintf(
			/* translators: %1$d: Number of issues cleared, %2$s: Post title. */
			__( 'Cleared %1$d issues from "%2$s"', 'accessibility-checker' ),
			$count,
			$post_title
		);

		Logger::log(
			'clear_issues',
			$message,
			[
				'post_id' => $post_id,
			]
		);
	}

	/**
	 * Log ignore issue activity.
	 *
	 * @since 1.36.0
	 *
	 * @param array  $ids           Array of issue IDs.
	 * @param string $action        The action (enable or disable).
	 * @param int    $ignore_global Whether this is a global ignore.
	 */
	public function log_ignore_issue( array $ids, string $action, int $ignore_global ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Get details for the first issue to use in the log message.
		if ( ! empty( $ids ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Needed for activity log.
			$issue = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id, postid, rule, ruletype FROM %i WHERE id = %d',
					$table_name,
					$ids[0]
				),
				ARRAY_A
			);

			if ( $issue ) {
				$post_title = get_the_title( $issue['postid'] );
				if ( empty( $post_title ) ) {
					$post_title = sprintf(
						/* translators: %d: Post ID. */
						__( 'Post #%d', 'accessibility-checker' ),
						$issue['postid']
					);
				}

				$rule_name = $this->get_rule_name( $issue['rule'] );

				if ( 'enable' === $action ) {
					if ( 1 === $ignore_global ) {
						$message = sprintf(
							/* translators: %1$s: Rule name, %2$d: Number of issues. */
							__( 'Globally ignored %1$s (%2$d issues)', 'accessibility-checker' ),
							$rule_name,
							count( $ids )
						);
					} else {
						$message = sprintf(
							/* translators: %1$s: Rule name, %2$s: Post title, %3$d: Number of issues. */
							__( 'Ignored %1$s in "%2$s" (%3$d issues)', 'accessibility-checker' ),
							$rule_name,
							$post_title,
							count( $ids )
						);
					}
				} else {
					$message = sprintf(
						/* translators: %1$s: Rule name, %2$s: Post title, %3$d: Number of issues. */
						__( 'Unignored %1$s in "%2$s" (%3$d issues)', 'accessibility-checker' ),
						$rule_name,
						$post_title,
						count( $ids )
					);
				}

				// Log each issue individually.
				foreach ( $ids as $id ) {
					Logger::log(
						'ignore_issue',
						$message,
						[
							'post_id'   => $issue['postid'],
							'issue_id'  => $id,
							'rule_type' => $issue['ruletype'],
						]
					);
				}
			}
		}
	}

	/**
	 * Get human-readable rule name.
	 *
	 * @since 1.36.0
	 *
	 * @param string $rule_slug The rule slug.
	 * @return string The rule name.
	 */
	private function get_rule_name( string $rule_slug ): string {
		// Get rule names from wcag data.
		$rules = edac_register_rules();
		
		if ( isset( $rules[ $rule_slug ] ) && isset( $rules[ $rule_slug ]['title'] ) ) {
			return $rules[ $rule_slug ]['title'];
		}

		// Fallback to slug if rule name not found.
		return $rule_slug;
	}
}
