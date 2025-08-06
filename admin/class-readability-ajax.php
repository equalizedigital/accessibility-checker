<?php
/**
 * Class file for readability ajax requests
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class that handles readability-related ajax requests.
 */
class Readability_Ajax {

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
		add_action( 'wp_ajax_edac_readability_ajax', [ $this, 'readability' ] );
	}

	/**
	 * Readability Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the post ID was not specified
	 *  - '-3' means that there isn't any readability data to return
	 */
	public function readability() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$post_id                     = (int) $_REQUEST['post_id'];
		$html                        = '';
		$simplified_summary          = get_post_meta( $post_id, '_edac_simplified_summary', true ) ? get_post_meta( $post_id, '_edac_simplified_summary', true ) : '';
		$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );
		$content_post                = get_post( $post_id );
		$content                     = $content_post->post_content;
		$content                     = apply_filters( 'the_content', $content );

		/**
		 * Filter the content used for reading grade readability analysis.
		 *
		 * @since 1.4.0
		 *
		 * @param string $content The content to be filtered.
		 * @param int    $post_id The post ID.
		 */
		$content = apply_filters( 'edac_filter_readability_content', $content, $post_id );
		$content = wp_filter_nohtml_kses( $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		// get readability metadata and determine if a simplified summary is required.
		$edac_summary           = get_post_meta( $post_id, '_edac_summary', true );
		$post_grade_readability = ( isset( $edac_summary['readability'] ) ) ? $edac_summary['readability'] : 0;
		$post_grade             = (int) filter_var( $post_grade_readability, FILTER_SANITIZE_NUMBER_INT );
		$post_grade_failed      = ( $post_grade < 9 ) ? false : true;

		$simplified_summary_grade = 0;
		if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
			$text_statistics          = new \DaveChild\TextStatistics\TextStatistics();
			$simplified_summary_grade = (int) floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) );
		}

		$simplified_summary_grade_failed = ( $simplified_summary_grade > 9 ) ? true : false;
		$simplified_summary_prompt       = get_option( 'edac_simplified_summary_prompt' );

		$html .= '<ul class="edac-readability-list">';

		$html .= '<li class="edac-readability-list-item edac-readability-grade-level">
		<span class="edac-readability-list-item-icon dashicons ' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
		<p class="edac-readability-list-item-title">Post Reading Grade Level: <strong class="' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . ( ( 0 === $post_grade ) ? 'None' : $post_grade_readability ) . '</strong><br /></p>';
		if ( $post_grade_failed ) {
			$html .= '<p class="edac-readability-list-item-description">Your post has a reading level higher than 9th grade. Web Content Accessibility Guidelines (WCAG) at the AAA level require a simplified summary of your post that is 9th grade or below.</p>';
		} elseif ( 0 === $post_grade ) {
			$html .= '<p class="edac-readability-list-item-description">Your post does not contain enough content to calculate its reading level.</p>';
		} else {
			$html .= '<p class="edac-readability-list-item-description">A simplified summary is not necessary when content reading level is 9th grade or below. Choose when to prompt for a simplified summary on the settings page.</p>';
		}
		$html .= '</li>';

		if ( $post_grade_failed ) {

			if ( $simplified_summary && 'none' !== $simplified_summary_prompt ) {
				$html .= '<li class="edac-readability-list-item edac-readability-summary-grade-level">
					<span class="edac-readability-list-item-icon dashicons ' . ( ( $simplified_summary_grade_failed ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
					<p class="edac-readability-list-item-title">Simplified Summary Reading Grade Level: <strong class="' . ( ( $simplified_summary_grade_failed ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . edac_ordinal( $simplified_summary_grade ) . '</strong></p>
					<p class="edac-readability-list-item-description">Your simplified summary has a reading level ' . ( ( $simplified_summary_grade_failed ) ? 'higher' : 'lower' ) . ' than 9th grade.</p>
				</li>';
			}

			if ( 'none' === $simplified_summary_prompt ) {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/warning-icon-yellow.png" alt="" width="22"></span>
					<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
						<p class="edac-readability-list-item-description">Your Prompt for Simplified Summary is set to "never." If you would like the simplified summary to be displayed automatically, you can change this on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
				</li>';

			} elseif ( 'none' !== $simplified_summary_position ) {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon dashicons dashicons-saved"></span>
					<p class="edac-readability-list-item-title">Simplified summary is being automatically inserted <strong>' . $simplified_summary_position . ' the content</strong>.</p>
						<p class="edac-readability-list-item-description">Set where the Simplified Summary is inserted into the content on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
				</li>';

			} else {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/warning-icon-yellow.png" alt="" width="22"></span>
					<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
						<p class="edac-readability-list-item-description">Your Simplified Summary location is set to "manually" which requires a function be added to your page template. If you would like the simplified summary to be displayed automatically, you can change this on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
				</li>';

			}
		}

		$html .= '</ul>';

		if ( ( $post_grade_failed || 'always' === $simplified_summary_prompt ) && ( 'none' !== $simplified_summary_prompt ) ) {
			$html .=
				'</form>
			<form action="/" class="edac-readability-simplified-summary">
				<label for="edac-readability-text">Simplified Summary</label>
				<textarea name="" id="edac-readability-text" cols="30" rows="10">' . $simplified_summary . '</textarea>
				<input type="submit" value="Submit">
			</form>';
		}

		$html .= '<span class="dashicons dashicons-info"></span><a href="' . esc_url( edac_link_wrapper( 'https://a11ychecker.com/help3265', 'wordpress-general', 'content-analysis', false ) ) . '" target="_blank">Learn more about improving readability and simplified summary requirements</a>';

		if ( ! $html ) {

			$error = new \WP_Error( '-3', __( 'No readability data to return', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $html ) );
	}
}
