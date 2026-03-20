<?php
/**
 * Class file for summary generator
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Class that handles summary generator
 *
 * @since 1.9.0
 */
class Summary_Generator {

	/**
	 * Identifies the post for which the summary is being generated.
	 *
	 * @var int
	 * @since 1.9.0
	 */
	private $post_id;

	/**
	 * Specifies the site ID in a multisite WordPress environment.
	 *
	 * @var int
	 * @since 1.9.0
	 */
	private $site_id;

	/**
	 * Summary_Generator constructor.
	 * Initializes the class by setting up its properties with the provided parameters.
	 *
	 * @param int $post_id The ID of the post for which the summary will be generated.
	 *            This is used to retrieve and store data related to the specific post.
	 *
	 * @since 1.9.0
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
		$this->site_id = get_current_blog_id();
	}

	/**
	 * Generates a summary of accessibility tests for a specific post.
	 * This method compiles a summary including passed tests, errors, warnings, and other relevant
	 * information by querying the database and applying logic based on the set of rules and the post's content.
	 *
	 * @return array An associative array containing the summary of accessibility checks, such as
	 *               passed tests, errors, warnings, ignored checks, contrast errors, content grade,
	 *               readability, and whether a simplified summary is enabled.
	 *
	 * @since 1.9.0
	 */
	public function generate_summary() {
		global $wpdb;

		$table_name       = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$summary          = [];
		$previous_summary = get_post_meta( $this->post_id, '_edac_summary', true );

		if ( ! $table_name ) {
			return $summary;
		}

		$rules = edac_register_rules();

		if ( defined( 'ANWW_VERSION' ) ) {
			$rules = edac_remove_element_with_value( $rules, 'slug', 'link_blank' );
		}

		$summary['passed_tests']       = $this->calculate_passed_tests( $rules );
		$summary['errors']             = $this->count_errors();
		$summary['warnings']           = $this->count_warnings();
		$summary['ignored']            = $this->count_ignored();
		$summary['contrast_errors']    = $this->count_contrast_errors();
		$summary['errors']            -= $summary['contrast_errors'];
		$summary['content_grade']      = $this->calculate_content_grade();
		$summary['readability']        = $this->get_readability( $summary );
		$summary['simplified_summary'] = (bool) ( get_post_meta( $this->post_id, '_edac_simplified_summary', true ) );

		// If issue counts haven't changed since the last save, preserve existing regression
		// data to avoid a second generate_summary() call overwriting deltas with zeros.
		if ( $this->issue_counts_match( $previous_summary, $summary ) && ! empty( $previous_summary['regression'] ) ) {
			$summary['regression'] = $previous_summary['regression'];
		} else {
			$summary['regression'] = $this->build_regression_data( $previous_summary, $summary );
		}

		$this->update_issue_density( $summary );
		$this->save_summary_meta_data( $summary );

		return $summary;
	}

	/**
	 * Checks whether issue-related counts in the current summary match a previously saved summary.
	 *
	 * @param mixed $previous_summary The previously saved summary meta.
	 * @param array $current_summary  The current summary data.
	 *
	 * @return bool True when all issue counts are identical.
	 */
	private function issue_counts_match( $previous_summary, array $current_summary ): bool {
		if ( ! is_array( $previous_summary ) ) {
			return false;
		}

		$metrics = [ 'errors', 'warnings', 'contrast_errors', 'passed_tests', 'content_grade' ];

		foreach ( $metrics as $metric ) {
			if ( absint( $previous_summary[ $metric ] ?? 0 ) !== absint( $current_summary[ $metric ] ?? 0 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build regression/trend data by comparing the current summary with the previous scan summary.
	 *
	 * @param mixed $previous_summary The previously saved summary meta.
	 * @param array $current_summary  The current summary data.
	 *
	 * @return array
	 */
	private function build_regression_data( $previous_summary, array $current_summary ) {
		$metrics = [
			'errors',
			'warnings',
			'contrast_errors',
			'passed_tests',
			'content_grade',
		];

		$deltas = [];
		foreach ( $metrics as $metric ) {
			$current_value     = absint( $current_summary[ $metric ] ?? 0 );
			$previous_value    = is_array( $previous_summary ) ? absint( $previous_summary[ $metric ] ?? 0 ) : 0;
			$deltas[ $metric ] = $current_value - $previous_value;
		}

		$regression_score = max( 0, $deltas['errors'] ) + max( 0, $deltas['warnings'] ) + max( 0, $deltas['contrast_errors'] );

		$status = 'stable';
		if ( $regression_score >= 5 || $deltas['passed_tests'] <= -10 ) {
			$status = 'declining';
		} elseif ( $regression_score > 0 || $deltas['passed_tests'] < 0 ) {
			$status = 'watch';
		} elseif ( $deltas['errors'] < 0 || $deltas['warnings'] < 0 || $deltas['contrast_errors'] < 0 || $deltas['passed_tests'] > 0 ) {
			$status = 'improving';
		}

		$has_baseline = is_array( $previous_summary ) && ! empty( $previous_summary );

		return [
			'has_baseline' => (bool) $has_baseline,
			'status'       => sanitize_key( $status ),
			'delta'        => [
				'errors'          => intval( $deltas['errors'] ),
				'warnings'        => intval( $deltas['warnings'] ),
				'contrast_errors' => intval( $deltas['contrast_errors'] ),
				'passed_tests'    => intval( $deltas['passed_tests'] ),
				'content_grade'   => intval( $deltas['content_grade'] ),
			],
			'scanned_at'   => current_time( 'mysql', true ),
		];
	}

	/**
	 * Calculates the percentage of passed tests based on the provided rules.
	 * This method queries the database to find which rules have not been violated (passed) for
	 * the current post and calculates the percentage of these passed tests.
	 *
	 * @param array $rules An array of rules against which the post's accessibility is checked.
	 * @return int The percentage of rules that have passed.
	 *
	 * @since 1.9.0
	 */
	private function calculate_passed_tests( $rules ) {
		if ( empty( $rules ) ) {
			return 0;
		}

		global $wpdb;
		$passed_count = 0;

		foreach ( $rules as $rule ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
			$count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT count(*) FROM %i where rule = %s and siteid = %d and postid = %d and ignre = %d',
					$wpdb->prefix . 'accessibility_checker',
					$rule['slug'],
					$this->site_id,
					$this->post_id,
					0
				)
			);

			// If count is zero, it means the rule has passed for this post.
			if ( 0 === (int) $count ) {
				++$passed_count;
			}
		}

		// Calculate the percentage of passed tests.
		return round( ( $passed_count / count( $rules ) ) * 100 );
	}

	/**
	 * Counts the number of errors found for the current post.
	 * This method queries the database to count the number of accessibility issues classified as 'error'.
	 *
	 * @return int The count of errors.
	 *
	 * @since 1.9.0
	 */
	private function count_errors() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
		$errors_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT count(*) FROM %i where siteid = %d and postid = %d and ruletype = %s and ignre = %d',
				$wpdb->prefix . 'accessibility_checker',
				$this->site_id,
				$this->post_id,
				'error',
				0
			)
		);

		return (int) $errors_count;
	}

	/**
	 * Counts the number of warnings found for the current post.
	 * This method queries the database to count the number of accessibility issues classified as 'warning'.
	 *
	 * @return int The count of warnings.
	 *
	 * @since 1.9.0
	 */
	private function count_warnings() {
		global $wpdb;

		$warnings_parameters = [ get_current_blog_id(), $this->post_id, 'warning', 0 ];
		$warnings_where      = 'WHERE siteid = %d and postid = %d and ruletype = %s and ignre = %d';
		if ( defined( 'ANWW_VERSION' ) ) {
			array_push( $warnings_parameters, 'link_blank' );
			$warnings_where .= ' and rule != %s';
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
		$warnings_count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				'SELECT count(*) FROM ' . $wpdb->prefix . 'accessibility_checker ' . $warnings_where,
				$warnings_parameters
			)
		);

		return (int) $warnings_count;
	}

	/**
	 * Counts the number of ignored issues for the current post.
	 * This method queries the database to count the number of accessibility issues that have been ignored.
	 *
	 * @return int The count of ignored issues.
	 *
	 * @since 1.9.0
	 */
	private function count_ignored() {
		global $wpdb;

		$ignored_parameters = [ get_current_blog_id(), $this->post_id, 1 ];
		$ignored_where      = 'WHERE siteid = %d and postid = %d and ignre = %d';
		if ( defined( 'ANWW_VERSION' ) ) {
			array_push( $ignored_parameters, 'link_blank' );
			$ignored_where .= ' and rule != %s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
		$ignored_count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared , WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				"SELECT count(*) FROM {$wpdb->prefix}accessibility_checker $ignored_where",
				$ignored_parameters
			)
		);

		return (int) $ignored_count;
	}

	/**
	 * Counts the number of contrast errors for the current post.
	 * This method queries the database to count the number of accessibility issues specifically related to color contrast failures.
	 *
	 * @return int The count of contrast errors.
	 *
	 * @since 1.9.0
	 */
	private function count_contrast_errors() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
		$contrast_errors_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT count(*) FROM %i where siteid = %d and postid = %d and rule = %s and ignre = %d',
				$wpdb->prefix . 'accessibility_checker',
				$this->site_id,
				$this->post_id,
				'color_contrast_failure',
				0
			)
		);

		return (int) $contrast_errors_count;
	}

	/**
	 * Updates the issue density metadata for the current post.
	 * This method calculates and updates the issue density based on the summary of accessibility issues
	 * and the content length of the post.
	 *
	 * @param array $summary An associative array containing the summary of accessibility checks.
	 *
	 * @since 1.9.0
	 */
	private function update_issue_density( $summary ) {
		$issue_density_array = get_post_meta( $this->post_id, '_edac_density_data', false );

		if (
			(
				is_array( $issue_density_array ) &&
				count( $issue_density_array ) > 0
			) && (
				is_array( $issue_density_array[0] ) &&
				count( $issue_density_array[0] ) > 0
			)
		) {
			$issue_count    = $summary['warnings'] + $summary['errors'] + $summary['contrast_errors'];
			$element_count  = $issue_density_array[0][0];
			$content_length = $issue_density_array[0][1];
			$issue_density  = edac_get_issue_density( $issue_count, $element_count, $content_length );

			update_post_meta( $this->post_id, '_edac_issue_density', floatval( $issue_density ) );
		} else {
			delete_post_meta( $this->post_id, '_edac_issue_density' );
		}
	}

	/**
	 * Calculates the content grade of the post's content.
	 * This method uses the Flesch-Kincaid Grade Level formula to determine the readability grade of the post's content.
	 *
	 * @return int The content grade, based on the Flesch-Kincaid Grade Level.
	 *
	 * @since 1.9.0
	 */
	private function calculate_content_grade() {
		$content_post = get_post( $this->post_id );
		if ( ! $content_post instanceof \WP_Post ) {
			return 0;
		}

		$content       = $content_post->post_content;
		$content       = wp_filter_nohtml_kses( $content );
		$content       = str_replace( ']]>', ']]&gt;', $content );
		$content_grade = 0;

		if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
			$content_grade = floor(
				( new \DaveChild\TextStatistics\TextStatistics() )->fleschKincaidGradeLevel( $content )
			);
		}

		return (int) round( $content_grade );
	}

	/**
	 * Determines the readability of the post's content based on the content grade.
	 * This method translates the content grade into a human-readable format, indicating the level of education
	 * required to understand the content.
	 *
	 * @param array $summary An associative array containing the summary of accessibility checks, including the content grade.
	 * @return string The readability level of the post's content, or 'N/A' if the content grade is 0.
	 *
	 * @since 1.9.0
	 */
	private function get_readability( $summary ) {
		return 0 === $summary['content_grade']
			? 'N/A'
			: edac_ordinal( $summary['content_grade'] );
	}

	/**
	 * Saves the summary metadata for the current post.
	 * This method saves the summary of accessibility checks as post metadata, including the number of passed tests,
	 * errors, warnings, ignored checks, contrast errors, content grade, readability, and whether a simplified summary is enabled.
	 *
	 * @param array $summary An associative array containing the summary of accessibility checks.
	 *
	 * @since 1.9.0
	 */
	private function save_summary_meta_data( $summary ) {
		update_post_meta( $this->post_id, '_edac_summary', $this->sanitize_summary_meta_data( $summary ) );
		update_post_meta( $this->post_id, '_edac_summary_passed_tests', absint( $summary['passed_tests'] ) );
		update_post_meta( $this->post_id, '_edac_summary_errors', absint( $summary['errors'] ) );
		update_post_meta( $this->post_id, '_edac_summary_warnings', absint( $summary['warnings'] ) );
		update_post_meta( $this->post_id, '_edac_summary_ignored', absint( $summary['ignored'] ) );
		update_post_meta( $this->post_id, '_edac_summary_contrast_errors', absint( $summary['contrast_errors'] ) );
		$this->update_summary_history( $summary );
	}

	/**
	 * Stores compact summary scan history used for trend/regression messaging.
	 *
	 * @param array $summary The latest summary.
	 *
	 * @return void
	 */
	private function update_summary_history( array $summary ) {
		$history   = get_post_meta( $this->post_id, '_edac_summary_history', true );
		$history   = is_array( $history ) ? $history : [];
		$timestamp = current_time( 'mysql', true );

		$new_entry = [
			'scanned_at'      => $timestamp,
			'errors'          => absint( $summary['errors'] ?? 0 ),
			'warnings'        => absint( $summary['warnings'] ?? 0 ),
			'contrast_errors' => absint( $summary['contrast_errors'] ?? 0 ),
			'passed_tests'    => absint( $summary['passed_tests'] ?? 0 ),
			'content_grade'   => absint( $summary['content_grade'] ?? 0 ),
		];

		// Skip duplicate entries when generate_summary() is called multiple times per scan.
		if ( ! empty( $history ) ) {
			$last = end( $history );
			if (
				absint( $last['errors'] ?? -1 ) === $new_entry['errors'] &&
				absint( $last['warnings'] ?? -1 ) === $new_entry['warnings'] &&
				absint( $last['contrast_errors'] ?? -1 ) === $new_entry['contrast_errors'] &&
				absint( $last['passed_tests'] ?? -1 ) === $new_entry['passed_tests'] &&
				absint( $last['content_grade'] ?? -1 ) === $new_entry['content_grade']
			) {
				return;
			}
		}

		$history[] = $new_entry;

		if ( count( $history ) > 10 ) {
			$history = array_slice( $history, -10 );
		}

		update_post_meta( $this->post_id, '_edac_summary_history', $history );
	}

	/**
	 * Sanitizes the summary metadata before saving it to the database.
	 *
	 * @param array $summary An associative array containing the summary of accessibility checks.
	 *
	 * @return array The sanitized summary metadata.
	 *
	 * @since 1.11.0
	 */
	private function sanitize_summary_meta_data( array $summary ): array {
		return [
			'passed_tests'       => absint( $summary['passed_tests'] ?? 0 ),
			'errors'             => absint( $summary['errors'] ?? 0 ),
			'warnings'           => absint( $summary['warnings'] ?? 0 ),
			'ignored'            => absint( $summary['ignored'] ?? 0 ),
			'contrast_errors'    => absint( $summary['contrast_errors'] ?? 0 ),
			'content_grade'      => absint( $summary['content_grade'] ?? 0 ),
			'readability'        => sanitize_text_field( $summary['readability'] ?? '' ),
			'simplified_summary' => filter_var( $summary['simplified_summary'] ?? false, FILTER_VALIDATE_BOOLEAN ),
			'regression'         => [
				'has_baseline' => filter_var( $summary['regression']['has_baseline'] ?? false, FILTER_VALIDATE_BOOLEAN ),
				'status'       => sanitize_key( $summary['regression']['status'] ?? 'stable' ),
				'delta'        => [
					'errors'          => intval( $summary['regression']['delta']['errors'] ?? 0 ),
					'warnings'        => intval( $summary['regression']['delta']['warnings'] ?? 0 ),
					'contrast_errors' => intval( $summary['regression']['delta']['contrast_errors'] ?? 0 ),
					'passed_tests'    => intval( $summary['regression']['delta']['passed_tests'] ?? 0 ),
					'content_grade'   => intval( $summary['regression']['delta']['content_grade'] ?? 0 ),
				],
				'scanned_at'   => sanitize_text_field( $summary['regression']['scanned_at'] ?? '' ),
			],
		];
	}
}
