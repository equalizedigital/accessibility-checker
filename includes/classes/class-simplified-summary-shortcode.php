<?php
/**
 * Simplified Summary Shortcode class file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Class for handling the Simplified Summary shortcode.
 */
class Simplified_Summary_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize WordPress hooks.
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'register_shortcode' ] );
	}

	/**
	 * Register the simplified summary shortcode.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'edac_simplified_summary', [ $this, 'render_shortcode' ] );
		add_shortcode( 'accessibility_summary', [ $this, 'render_shortcode' ] ); // Alternative shortcode name
	}

	/**
	 * Render the simplified summary shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'post_id'        => 0,
				'show_heading'   => 'true',
				'heading_level'  => 2,
				'custom_heading' => '',
				'class'          => '',
			],
			$atts,
			'edac_simplified_summary'
		);

		// Sanitize attributes
		$post_id = intval( $atts['post_id'] );
		$show_heading = filter_var( $atts['show_heading'], FILTER_VALIDATE_BOOLEAN );
		$heading_level = max( 1, min( 6, intval( $atts['heading_level'] ) ) );
		$custom_heading = sanitize_text_field( $atts['custom_heading'] );
		$css_class = sanitize_html_class( $atts['class'] );

		// Use current post ID if not specified
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return '';
		}

		// Get the simplified summary content
		$simplified_summary_text = get_post_meta( $post_id, '_edac_simplified_summary', true );
		
		if ( ! $simplified_summary_text ) {
			return '';
		}

		// Apply heading filter if custom heading is not provided
		if ( empty( $custom_heading ) ) {
			/**
			 * Filter the heading that gets output before the simplified summary.
			 *
			 * @since 1.4.0
			 *
			 * @param string $simplified_summary_heading The simplified summary heading.
			 */
			$custom_heading = apply_filters(
				'edac_filter_simplified_summary_heading',
				esc_html__( 'Simplified Summary', 'accessibility-checker' )
			);
		}

		// Build the output
		$css_classes = 'edac-simplified-summary';
		if ( ! empty( $css_class ) ) {
			$css_classes .= ' ' . $css_class;
		}

		$output = '<div class="' . esc_attr( $css_classes ) . '">';

		if ( $show_heading ) {
			$output .= sprintf(
				'<h%d>%s</h%d>',
				$heading_level,
				wp_kses_post( $custom_heading ),
				$heading_level
			);
		}

		$output .= '<p>' . wp_kses_post( $simplified_summary_text ) . '</p>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get shortcode usage information.
	 *
	 * @return array
	 */
	public static function get_shortcode_info() {
		return [
			'shortcode' => '[edac_simplified_summary]',
			'alternative' => '[accessibility_summary]',
			'attributes' => [
				'post_id' => [
					'description' => __( 'The ID of the post to display the summary for. Leave empty to use current post.', 'accessibility-checker' ),
					'type' => 'number',
					'default' => 0,
				],
				'show_heading' => [
					'description' => __( 'Whether to show the heading above the summary.', 'accessibility-checker' ),
					'type' => 'boolean',
					'default' => 'true',
				],
				'heading_level' => [
					'description' => __( 'The heading level to use (1-6).', 'accessibility-checker' ),
					'type' => 'number',
					'default' => 2,
				],
				'custom_heading' => [
					'description' => __( 'Custom heading text to use instead of the default.', 'accessibility-checker' ),
					'type' => 'string',
					'default' => '',
				],
				'class' => [
					'description' => __( 'Additional CSS class to add to the wrapper div.', 'accessibility-checker' ),
					'type' => 'string',
					'default' => '',
				],
			],
			'examples' => [
				'[edac_simplified_summary]',
				'[edac_simplified_summary post_id="123"]',
				'[edac_simplified_summary show_heading="false"]',
				'[edac_simplified_summary heading_level="3" custom_heading="Easy to Read Summary"]',
				'[accessibility_summary class="my-custom-class"]',
			],
		];
	}
}
