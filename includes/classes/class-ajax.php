<?php
/**
 * Class file for admin notices
 *
 * @package Accessibility_Checker
 */

namespace EDAC;

/**
 * Class that handles ajax requests.
 */
class Ajax {

	public function __construct() {
	}

	public function init_hooks() {
		add_action( 'wp_ajax_edac_summary_ajax', array( $this, 'edac_summary_ajax' ) );
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
	function edac_summary_ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new WP_Error( '-2', 'The post ID was not set' );
			wp_send_json_error( $error );

		}

		$html            = array();
		$html['content'] = '';

		// password check.
		if ( boolval( get_option( 'edac_password_protected' ) ) === true ) {
			$admin_notices              = new \EDAC\Admin_Notices();
			$notice_text                = $admin_notices->edac_password_protected_notice_text();
			$html['password_protected'] = $notice_text;
			$html['content']           .= '<div class="edac-summary-notice">' . $notice_text . '</div>';
		}

		$post_id                   = intval( $_REQUEST['post_id'] );
		$summary                   = edac_summary( $post_id );
		$simplified_summary_text   = '';
		$simplified_summary_prompt = get_option( 'edac_simplified_summary_prompt' );

		$simplified_summary_text = esc_html__( 'A Simplified summary has not been included for this content.', 'accessibility-checker' );
		if ( 'none' !== $simplified_summary_prompt ) {
			if ( $summary['content_grade'] <= 9 ) {
				$simplified_summary_text = esc_html__( 'Your content has a reading level at or below 9th grade and does not require a simplified summary.', 'accessibility-checker' );
			} elseif ( $summary['simplified_summary'] ) {
				$simplified_summary_text = esc_html__( 'A Simplified summary has been included for this content.', 'accessibility-checker' );
			}
		}

		$html['content'] .= '<div class="edac-summary-total">';

		$html['content'] .= '<div class="edac-summary-total-progress-circle ' . ( ( $summary['passed_tests'] > 50 ) ? ' over50' : '' ) . '">
			<div class="edac-summary-total-progress-circle-label">
				<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
				<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
			</div>
			<div class="left-half-clipper">
				<div class="first50-bar"></div>
				<div class="value-bar" style="transform: rotate(' . $summary['passed_tests'] * 3.6 . 'deg);"></div>
			</div>
		</div>';

		$html['content'] .= '<div class="edac-summary-total-mobile">
			<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
			<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
			<div class="edac-summary-total-mobile-bar"><span style="width:' . ( $summary['passed_tests'] ) . '%;"></span></div>
		</div>';

		$html['content'] .= '</div>';

		$html['content'] .= '
		<div class="edac-summary-stats">
			<div class="edac-summary-stat edac-summary-errors' . ( ( $summary['errors'] > 0 ) ? ' has-errors' : '' ) . '">
				<div class="edac-panel-number">
					' . $summary['errors'] . '
				</div>
				<div class="edac-panel-number-label">Error' . ( ( 1 !== $summary['errors'] ) ? 's' : '' ) . '</div>
			</div>
			<div class="edac-summary-stat edac-summary-contrast' . ( ( $summary['contrast_errors'] > 0 ) ? ' has-errors' : '' ) . '">
				<div class="edac-panel-number">
					' . $summary['contrast_errors'] . '
				</div>
				<div class="edac-panel-number-label">Contrast Error' . ( ( 1 !== $summary['contrast_errors'] ) ? 's' : '' ) . '</div>
			</div>
			<div class="edac-summary-stat edac-summary-warnings' . ( ( $summary['warnings'] > 0 ) ? ' has-warning' : '' ) . '">
				<div class="edac-panel-number">
					' . $summary['warnings'] . '
				</div>
				<div class="edac-panel-number-label">Warning' . ( ( 1 !== $summary['warnings'] ) ? 's' : '' ) . '</div>
			</div>
			<div class="edac-summary-stat edac-summary-ignored">
				<div class="edac-panel-number">
					' . $summary['ignored'] . '
				</div>
				<div class="edac-panel-number-label">Ignored Item' . ( ( 1 !== $summary['ignored'] ) ? 's' : '' ) . '</div>
			</div>
		</div>
		<div class="edac-summary-readability">
			<div class="edac-summary-readability-level">
				<div><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/readability icon navy.png" alt="" width="54"></div>
				<div class="edac-panel-number' . ( ( (int) $summary['readability'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">
					' . $summary['readability'] . '
				</div>
				<div class="edac-panel-number-label' . ( ( (int) $summary['readability'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">Reading <br />Level</div>
			</div>
			<div class="edac-summary-readability-summary">
				<div class="edac-summary-readability-summary-icon' . ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['readability'] <= 9 ) ? ' active' : '' ) . '"></div>
				<div class="edac-summary-readability-summary-text' . ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['readability'] <= 9 ) ? ' active' : '' ) . '">' . $simplified_summary_text . '</div>
			</div>
		</div>
		<div class="edac-summary-disclaimer"><small>* True accessibility requires manual testing in addition to automated scans. <a href="https://a11ychecker.com/help4280">Learn how to manually test for accessibility</a>.</small></div>
		';

		if ( ! $html ) {

			$error = new WP_Error( '-3', 'No summary to return' );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $html ) );
	}

}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	$ajax = new \EDAC\Ajax();
	$ajax->init_hooks();
}