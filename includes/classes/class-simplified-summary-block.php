<?php
/**
 * Simplified Summary Block class file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Class for handling the Simplified Summary WordPress block.
 */
class Simplified_Summary_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize WordPress hooks.
	 */
	public function init_hooks() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
	}

	/**
	 * Register the simplified summary block.
	 *
	 * @return void
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'accessibility-checker/simplified-summary',
			array(
				'editor_script'   => 'edac-simplified-summary-block',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'postId' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'showHeading' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'headingLevel' => array(
						'type'    => 'number',
						'default' => 2,
					),
					'customHeading' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_script(
			'edac-simplified-summary-block',
			plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/simplified-summary-block.bundle.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			EDAC_VERSION,
			true
		);

		wp_enqueue_style(
			'edac-simplified-summary-block',
			plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/css/simplified-summary-block.css',
			array(),
			EDAC_VERSION
		);

		wp_localize_script(
			'edac-simplified-summary-block',
			'edacSimplifiedSummaryBlock',
			array(
				'postId' => get_the_ID(),
			)
		);
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles() {
		wp_enqueue_style(
			'edac-simplified-summary-frontend',
			plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/css/simplified-summary-block.css',
			array(),
			EDAC_VERSION
		);
	}

	/**
	 * Render the simplified summary block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		// Ensure attributes is an array and has default values
		$attributes = wp_parse_args( $attributes, array(
			'postId' => 0,
			'showHeading' => true,
			'headingLevel' => 2,
			'customHeading' => '',
		) );

		$post_id = ! empty( $attributes['postId'] ) ? intval( $attributes['postId'] ) : get_the_ID();
		
		if ( ! $post_id ) {
			return '<div class="edac-simplified-summary-error"><p>' . esc_html__( 'No post ID available for simplified summary.', 'accessibility-checker' ) . '</p></div>';
		}

		// Check if the Simplified_Summary class exists
		if ( ! class_exists( 'EDAC\Inc\Simplified_Summary' ) ) {
			return '<div class="edac-simplified-summary-error"><p>' . esc_html__( 'Simplified Summary functionality is not available.', 'accessibility-checker' ) . '</p></div>';
		}

		try {
			$simplified_summary = new Simplified_Summary();
			$markup = $simplified_summary->simplified_summary_markup( $post_id );

			// If no markup was generated, return empty string (not null or undefined)
			if ( empty( $markup ) ) {
				return '';
			}

			// If custom settings are provided, modify the markup
			if ( ! $attributes['showHeading'] || ! empty( $attributes['customHeading'] ) || intval( $attributes['headingLevel'] ) !== 2 ) {
				$markup = $this->modify_markup( $markup, $attributes );
			}

			return $markup;

		} catch ( Exception $e ) {
			// Log the error for debugging
			error_log( 'Simplified Summary Block Error: ' . $e->getMessage() );
			return '<div class="edac-simplified-summary-error"><p>' . esc_html__( 'Error loading simplified summary.', 'accessibility-checker' ) . '</p></div>';
		}
	}

	/**
	 * Modify the simplified summary markup based on block attributes.
	 *
	 * @param string $markup The original markup.
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	private function modify_markup( $markup, $attributes ) {
		// Validate input
		if ( empty( $markup ) || ! is_string( $markup ) ) {
			return '';
		}

		// Ensure attributes are properly set
		$attributes = wp_parse_args( $attributes, array(
			'showHeading' => true,
			'headingLevel' => 2,
			'customHeading' => '',
		) );

		// Extract the content between <p> tags
		preg_match( '/<p>(.*?)<\/p>/', $markup, $matches );
		$content = isset( $matches[1] ) ? $matches[1] : '';

		if ( ! $content ) {
			return $markup; // Return original markup if we can't parse it
		}

		$output = '<div class="edac-simplified-summary">';

		// Handle heading display
		if ( $attributes['showHeading'] ) {
			$heading_level = max( 1, min( 6, intval( $attributes['headingLevel'] ) ) );
			$heading_text = ! empty( $attributes['customHeading'] ) 
				? esc_html( $attributes['customHeading'] )
				: esc_html__( 'Simplified Summary', 'accessibility-checker' );
			
			$output .= sprintf( '<h%d>%s</h%d>', $heading_level, $heading_text, $heading_level );
		}

		$output .= '<p>' . wp_kses_post( $content ) . '</p>';
		$output .= '</div>';

		return $output;
	}
}
