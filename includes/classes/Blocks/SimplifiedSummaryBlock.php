<?php
/**
 * Registers the simplified summary block.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Blocks;

use EDAC\Inc\Simplified_Summary;
use WP_Block;

/**
 * Registers and renders the edac/simplified-summary dynamic block.
 *
 * Lets users place the simplified summary manually in post content or in a
 * block theme (FSE) template instead of relying on the automatic insertion
 * that runs on the_content.
 *
 * @since 1.xx.x
 */
class SimplifiedSummaryBlock {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'edac/simplified-summary';

	/**
	 * The editor script handle referenced from block.json.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'edac-simplified-summary-block';

	/**
	 * The editor style handle referenced from block.json.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'edac-simplified-summary-block-editor';

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register the block and its editor assets.
	 *
	 * The build does not generate *.asset.php files, so the editor script is
	 * registered here with an explicit dependency list and block.json
	 * references the handle rather than a file.
	 *
	 * @since 1.xx.x
	 *
	 * @return void
	 */
	public function register() {
		if ( \WP_Block_Type_Registry::get_instance()->is_registered( self::BLOCK_NAME ) ) {
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/simplifiedSummaryBlock.bundle.js',
			[ 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor' ],
			EDAC_VERSION,
			true
		);

		wp_set_script_translations(
			self::SCRIPT_HANDLE,
			'accessibility-checker',
			plugin_dir_path( EDAC_PLUGIN_FILE ) . 'languages'
		);

		wp_register_style(
			self::STYLE_HANDLE,
			plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/css/simplifiedSummaryBlock.css',
			[],
			EDAC_VERSION
		);

		register_block_type(
			EDAC_PLUGIN_DIR . 'includes/blocks/simplified-summary',
			[ 'render_callback' => [ $this, 'render' ] ]
		);
	}

	/**
	 * Render the block on the front end.
	 *
	 * Uses the postId block context when available (FSE templates, Query
	 * Loop) and falls back to the global post. Renders regardless of the
	 * edac_simplified_summary_prompt option because manual placement is a
	 * deliberate act, matching edac_get_simplified_summary().
	 *
	 * @since 1.xx.x
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content    The block content.
	 * @param WP_Block $block      The block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] )
			? (int) $block->context['postId']
			: (int) get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		return ( new Simplified_Summary() )->simplified_summary_markup( $post_id );
	}
}
