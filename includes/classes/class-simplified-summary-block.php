<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * A class that handles the simplified summary Gutenberg block.
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
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register the simplified summary block.
	 */
	public function register_block() {
		register_block_type(
			EDAC_PLUGIN_DIR . 'src/blocks/simplified-summary/block.json',
			[
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Render the simplified summary block.
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content    The block content.
	 * @param WP_Block $block      The block instance.
	 * @return string
	 */
	public function render_block( $attributes, $content, $block ) {
		// Get the post ID from block context or current post
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID();
		
		if ( ! $post_id ) {
			return '';
		}

		// Check if manual placement is enabled
		$simplified_summary_position = get_option( 'edac_simplified_summary_position', 'after' );
		if ( 'none' !== $simplified_summary_position ) {
			return '';
		}

		// Get the simplified summary instance and use its markup method
		$simplified_summary_instance = new Simplified_Summary();
		$markup = $simplified_summary_instance->simplified_summary_markup( $post_id );

		return $markup;
	}
}