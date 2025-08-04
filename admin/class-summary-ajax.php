<?php
/**
 * Class file for summary ajax requests
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Inc\Summary_Generator;

/**
 * Class that handles summary-related ajax requests.
 */
class Summary_Ajax {

	/**
	 * Constructor function for the class.
	 */
	public function __construct() {
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_edac_summary_ajax', [ $this, 'summary' ] );
		add_action( 'wp_ajax_edac_update_simplified_summary', [ $this, 'simplified_summary' ] );
	}

	/**
	 * Summary Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the post ID was not specified
	 *  - '-3' means that there isn't any summary data to return
	 */
	public function summary() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$html            = [];
		$html['content'] = '';


		$post_id                   = (int) $_REQUEST['post_id'];
		$summary                   = ( new Summary_Generator( $post_id ) )->generate_summary();
		$simplified_summary_text   = '';
		$simplified_summary_prompt = get_option( 'edac_simplified_summary_prompt' );
		$simplified_summary        = get_post_meta( $post_id, '_edac_simplified_summary', true ) ? get_post_meta( $post_id, '_edac_simplified_summary', true ) : '';

		$simplified_summary_grade = 0;
		if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
			$text_statistics          = new \DaveChild\TextStatistics\TextStatistics();
			$simplified_summary_grade = (int) floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) );
		}
		$simplified_summary_grade_failed = ( $simplified_summary_grade > 9 ) ? true : false;

		$simplified_summary_text = esc_html__( 'A Simplified summary has not been included for this content.', 'accessibility-checker' );
		if ( 'none' !== $simplified_summary_prompt ) {
			if ( $summary['content_grade'] <= 9 ) {
				$simplified_summary_text = esc_html__( 'Your content has a reading level at or below 9th grade and does not require a simplified summary.', 'accessibility-checker' );
			} elseif ( $summary['simplified_summary'] ) {
				if ( $simplified_summary_grade_failed ) {
					$simplified_summary_text = esc_html__( 'The reading level of the simplified summary is too high.', 'accessibility-checker' );
				} else {
					$simplified_summary_text = esc_html__( 'A simplified summary has been included for this content.', 'accessibility-checker' );
				}
			}
		}

		$html['content'] .= '<ul class="edac-summary-grid">';

			$html['content'] .= '<li class="edac-summary-total" aria-label="' . $summary['passed_tests'] . '% Passed Tests">';

				$html['content'] .= '<div class="edac-summary-total-progress-circle ' . ( ( $summary['passed_tests'] > 50 ) ? ' over50' : '' ) . '">
					<div class="edac-summary-total-progress-circle-label">
						<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
						<div class="edac-panel-number-label">Passed Tests<sup><a href="#edac-summary-disclaimer" aria-label="About passed tests.">*</a></sup></div>
					</div>
					<div class="left-half-clipper">
						<div class="first50-bar"></div>
						<div class="value-bar" style="transform: rotate(' . $summary['passed_tests'] * 3.6 . 'deg);"></div>
					</div>
				</div>';

				$html['content'] .= '<div class="edac-summary-total-mobile">
					<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
					<div class="edac-panel-number-label">Passed Tests<sup><a href="#edac-summary-disclaimer" aria-label="About passed tests.">*</a></sup></div>
					<div class="edac-summary-total-mobile-bar"><span style="width:' . ( $summary['passed_tests'] ) . '%;"></span></div>
				</div>';

			$html['content'] .= '</li>';

			$html['content'] .= '
				' . edac_generate_summary_stat(
				'edac-summary-errors',
				$summary['errors'],
				/* translators: %s: Number of errors */
					sprintf( _n( '%s Error', '%s Errors', $summary['errors'], 'accessibility-checker' ), $summary['errors'] )
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-contrast',
				$summary['contrast_errors'],
				/* translators: %s: Number of contrast errors */
					sprintf( _n( '%s Contrast Error', '%s Contrast Errors', $summary['contrast_errors'], 'accessibility-checker' ), $summary['contrast_errors'] )
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-warnings',
				$summary['warnings'],
				/* translators: %s: Number of warnings */
					sprintf( _n( '%s Warning', '%s Warnings', $summary['warnings'], 'accessibility-checker' ), $summary['warnings'] )
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-ignored',
				$summary['ignored'],
				/* translators: %s: Number of ignored items */
					sprintf( _n( '%s Ignored Item', '%s Ignored Items', $summary['ignored'], 'accessibility-checker' ), $summary['ignored'] )
			) . '

		</ul>
		<div class="edac-summary-readability">
			<div class="edac-summary-readability-level">
				<div><img src="' . EDAC_PLUGIN_URL . 'assets/images/readability-icon-navy.png" alt="" width="54"></div>
				<div class="edac-panel-number' . ( ( (int) $summary['content_grade'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">
					' . $summary['readability'] . '
				</div>
				<div class="edac-panel-number-label' . ( ( (int) $summary['readability'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">Reading <br />Level</div>
			</div>
			<div class="edac-summary-readability-summary">
				<div class="edac-summary-readability-summary-icon' . ( ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['content_grade'] <= 9 ) && ! $simplified_summary_grade_failed ) ? ' active' : '' ) . '"></div>
				<div class="edac-summary-readability-summary-text' . ( ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['content_grade'] <= 9 ) && ! $simplified_summary_grade_failed ) ? ' active' : '' ) . '">' . $simplified_summary_text . '</div>
			</div>
		</div>
		';

		$html['content'] .= '<div class="edac-summary-disclaimer" id="edac-summary-disclaimer"><small>' . PHP_EOL;
		$html['content'] .= sprintf(
			'* True accessibility requires manual testing in addition to automated scans. %1$sLearn how to manually test for accessibility%2$s.',
			'<a href="' . esc_url(
				edac_generate_link_type(
					[
						'utm_campaign' => 'dashboard-widget',
						'utm_content'  => 'how-to-manually-check',
					],
					'help',
					[ 'help_id' => 4280 ]
				)
			) . '">',
			'</a>'
		) . PHP_EOL;
		$html['content'] .= '</small></div>' . PHP_EOL;

		if ( ! $html ) {

			$error = new \WP_Error( '-3', __( 'No summary to return', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $html ) );
	}

	/**
	 * Update simplified summary
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the post ID was not specified
	 *  - '-3' means that the summary was not specified
	 */
	public function simplified_summary() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['summary'] ) ) {

			$error = new \WP_Error( '-3', __( 'The summary was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$post_id = (int) $_REQUEST['post_id'];
		update_post_meta(
			$post_id,
			'_edac_simplified_summary',
			sanitize_text_field( $_REQUEST['summary'] )
		);

		$edac_simplified_summary = get_post_meta( $post_id, '_edac_simplified_summary', $single = true );
		$simplified_summary      = $edac_simplified_summary ? $edac_simplified_summary : '';

		wp_send_json_success( wp_json_encode( $simplified_summary ) );
	}
}
