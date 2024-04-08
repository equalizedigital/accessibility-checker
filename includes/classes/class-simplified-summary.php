<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Data\Post_Meta\Scan_Summary_Back_Compat;

/**
 * A class that handles the simplified summary.
 */
class Simplified_Summary {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize WordPress hooks.
	 */
	public function init_hooks() {
		add_filter( 'the_content', array( $this, 'output_simplified_summary' ) );
	}

	/**
	 * Output simplified summary
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public function output_simplified_summary( $content ) {
		$simplified_summary_prompt = get_option( 'edac_simplified_summary_prompt' );
		if ( 'none' === $simplified_summary_prompt ) {
			return $content;
		}
		$simplified_summary          = $this->simplified_summary_markup( get_the_ID() );
		$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );

		if ( $simplified_summary ) {
			if ( 'before' === $simplified_summary_position ) {
				return $simplified_summary . $content;
			}
			if ( 'after' === $simplified_summary_position ) {
				return $content . $simplified_summary;
			}
		}
		return $content;
	}

	/**
	 * Simplified summary markup
	 *
	 * @modified 1.11.0 - Use new Scan_Summary_Back_Compat class to get simplified summary text.
	 * @modified 1.11.0 - Added filter edac_filter_simplified_summary_header_element.
	 *
	 * @param int $post Post ID.
	 * @return string
	 */
	public function simplified_summary_markup( $post ) {
		$simplified_summary = ( new Scan_Summary_Back_Compat( $post ) )->get( 'simplified_summary_text' );

		if ( ! $simplified_summary ) {
			return '';
		}

		$simplified_summary_heading = apply_filters(
			'edac_filter_simplified_summary_heading',
			esc_html__( 'Simplified Summary', 'accessibility-checker' )
		);

		if ( $simplified_summary ) {
			return '<div class="edac-simplified-summary"><h2>' . wp_kses_post( $simplified_summary_heading ) . '</h2><p>' . wp_kses_post( $simplified_summary ) . '</p></div>';
		}
		return '';
	}
}
