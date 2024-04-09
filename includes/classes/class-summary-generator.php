<?php
/**
 * Class file for summary generator
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Data\Post_Meta\{ Scan_Summary, Scan_Summary_Back_Compat };

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

		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$summary    = array();

		if ( ! $table_name ) {
			return $summary;
		}

		$rules = edac_register_rules();

		if ( defined( 'EDAC_ANWW_ACTIVE' ) && EDAC_ANWW_ACTIVE ) {
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
		$summary['simplified_summary'] = (bool) ( ( new Scan_Summary_Back_Compat( $this->post_id ) )->get( 'simplified_summary_text' ) );
		$this->update_issue_density( $summary );
		$this->save_summary_meta_data( $summary );

		return $summary;
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

		$warnings_parameters = array( get_current_blog_id(), $this->post_id, 'warning', 0 );
		$warnings_where      = 'WHERE siteid = siteid = %d and postid = %d and ruletype = %s and ignre = %d';
		if ( EDAC_ANWW_ACTIVE ) {
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

		$ignored_parameters = array( get_current_blog_id(), $this->post_id, 1 );
		$ignored_where      = 'WHERE siteid = %d and postid = %d and ignre = %d';
		if ( EDAC_ANWW_ACTIVE ) {
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
		$issue_density_array = get_post_meta( $this->post_id, '_edac_density_data' );

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

			( new Scan_Summary_Back_Compat( $this->post_id ) )->save( 'issue_density', floatval( $issue_density ) );
		} else {
			( new Scan_Summary_Back_Compat( $this->post_id ) )->delete( 'issue_density' );
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
		$content_post  = get_post( $this->post_id );
		$content       = $content_post->post_content;
		$content       = wp_filter_nohtml_kses( $content );
		$content       = str_replace( ']]>', ']]&gt;', $content );
		$content_grade = 0;

		if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
			$content_grade = floor(
				( new \DaveChild\TextStatistics\TextStatistics() )->fleschKincaidGradeLevel( $content )
			);
		}

		return $content_grade;
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
	 * @return void
	 *
	 * @since 1.9.0
	 * @modified 1.11.0 - Swapped to using Scan_Summary class to save the summary.
	 */
	private function save_summary_meta_data( array $summary ): void {
		( new Scan_Summary( $this->post_id ) )->save( $summary );
	}
}
