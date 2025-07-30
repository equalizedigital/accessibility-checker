<?php
/**
 * Simplified Summary Block.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Class that registers the Simplified Summary block.
 */
class Simplified_Summary_Block {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register the block type.
	 *
	 * @return void
	 */
	public function register_block() {
		$block_path = EDAC_PLUGIN_DIR . 'blocks/simplified-summary/block.json';

		if ( ! file_exists( $block_path ) ) {
			return;
		}

		wp_register_script(
			'edac-simplified-summary-block',
			plugins_url( 'build/simplifiedSummaryBlock.bundle.js', EDAC_PLUGIN_FILE ),
			[ 'wp-blocks', 'wp-element', 'wp-i18n' ],
			EDAC_VERSION,
			false
		);

		register_block_type(
			$block_path,
			[
				'editor_script'   => 'edac-simplified-summary-block',
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Render callback for the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 *
	 * @return string
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_id = $block->context['postId'] ?? get_the_ID();
		$summary = new Simplified_Summary();
		return $summary->simplified_summary_markup( $post_id );
	}
}
