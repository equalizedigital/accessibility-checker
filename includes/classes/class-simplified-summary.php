<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Options;
use EDAC\Admin\Post_Options;

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
		$simplified_summary_prompt = Options::get( 'simplified_summary_prompt' );
		if ( 'none' === $simplified_summary_prompt ) {
			return $content;
		}
		$simplified_summary          = $this->simplified_summary_markup( get_the_ID() );
		$simplified_summary_position = Options::get( 'simplified_summary_position' );

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
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function simplified_summary_markup( $post_id ) {
		$post_options       = new Post_Options( $post_id );
		$simplified_summary = $post_options->get( 'simplified_summary' );
		
		if ( '' !== trim( $simplified_summary ) ) {
			$simplified_summary_heading = apply_filters(
				'edac_filter_simplified_summary_heading',
				esc_html__( 'Simplified Summary', 'accessibility-checker' )
			);
	
			return '<div class="edac-simplified-summary"><h2>' . wp_kses_post( $simplified_summary_heading ) . '</h2><p>' . wp_kses_post( $simplified_summary ) . '</p></div>';
		}
		return '';
	}
}
