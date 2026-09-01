<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_filter( 'the_content', [ $this, 'output_simplified_summary' ] );
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
		if ( $this->is_manually_placed( get_the_ID() ) ) {
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
	 * Check whether the simplified summary has been manually placed for a post.
	 *
	 * Detects the edac/simplified-summary block or [edac_simplified_summary]
	 * shortcode in the post content, and the block in the current block theme
	 * template. Blocks nested inside template parts or synced patterns cannot
	 * be detected by has_block(); the filter below is the escape hatch for
	 * those cases.
	 *
	 * @since 1.49.0
	 *
	 * @param int|\WP_Post|null $post Post ID or post object.
	 * @return bool
	 */
	public function is_manually_placed( $post = null ): bool {
		$manually_placed = false;

		$post = get_post( $post );
		if ( $post instanceof \WP_Post ) {
			if (
				has_block( 'edac/simplified-summary', $post ) ||
				has_shortcode( (string) $post->post_content, 'edac_simplified_summary' )
			) {
				$manually_placed = true;
			}
		}

		// Set by WordPress when rendering a block theme template.
		global $_wp_current_template_content;
		if (
			! $manually_placed &&
			! empty( $_wp_current_template_content ) &&
			(
				has_block( 'edac/simplified-summary', $_wp_current_template_content ) ||
				has_shortcode( $_wp_current_template_content, 'edac_simplified_summary' )
			)
		) {
			$manually_placed = true;
		}

		/**
		 * Filter whether the simplified summary is manually placed, which
		 * suppresses the automatic insertion on the_content.
		 *
		 * @since 1.49.0
		 *
		 * @param bool           $manually_placed Whether the summary is manually placed.
		 * @param \WP_Post|null  $post            The post being checked.
		 */
		return apply_filters( 'edac_filter_simplified_summary_is_manually_placed', $manually_placed, $post );
	}

	/**
	 * Simplified summary markup
	 *
	 * @param int $post Post ID.
	 * @return string
	 */
	public function simplified_summary_markup( $post ) {
		$simplified_summary = get_post_meta( $post, '_edac_simplified_summary', true )
			? get_post_meta( $post, '_edac_simplified_summary', true )
			: '';

		/**
		 * Filter the heading that gets output before the simplified summary inside an <h2> tag.
		 *
		 * @since 1.4.0
		 *
		 * @param string $simplified_summary_heading The simplified summary heading.
		 */
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
