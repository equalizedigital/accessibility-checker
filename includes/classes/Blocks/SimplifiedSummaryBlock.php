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
 * @since 1.49.0
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
	 * The block category slug.
	 *
	 * @var string
	 */
	const CATEGORY = 'accessibility-checker';

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.49.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'register' ] );
		add_filter( 'block_categories_all', [ $this, 'register_block_category' ] );
	}

	/**
	 * Register the Accessibility Checker block category.
	 *
	 * @since 1.49.0
	 *
	 * @param array $categories The registered block categories.
	 * @return array
	 */
	public function register_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( self::CATEGORY === $category['slug'] ) {
				return $categories;
			}
		}

		$categories[] = [
			'slug'  => self::CATEGORY,
			'title' => esc_html__( 'Accessibility Checker', 'accessibility-checker' ),
			'icon'  => null,
		];

		return $categories;
	}

	/**
	 * Register the block and its editor assets.
	 *
	 * The build does not generate *.asset.php files, so the editor script is
	 * registered here with an explicit dependency list and block.json
	 * references the handle rather than a file.
	 *
	 * @since 1.49.0
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

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'edacSimplifiedSummaryBlock',
			[
				/** This filter is documented in includes/classes/class-simplified-summary.php */
				'heading' => apply_filters(
					'edac_filter_simplified_summary_heading',
					esc_html__( 'Simplified Summary', 'accessibility-checker' )
				),
			]
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
	 * deliberate act, matching edac_get_simplified_summary(). The postId
	 * context can point at a post other than the one being viewed (Query
	 * Loop), so password-protection and visibility are checked explicitly
	 * rather than relying on WP's template-level access gate.
	 *
	 * @since 1.49.0
	 *
	 * @param array         $attributes The block attributes.
	 * @param string        $content    The block content.
	 * @param WP_Block|null $block      The block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block = null ) {
		$post_id = ( $block instanceof WP_Block && isset( $block->context['postId'] ) )
			? (int) $block->context['postId']
			: (int) get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		if ( post_password_required( $post_id ) ) {
			return '';
		}

		if ( get_queried_object_id() !== $post_id && ! is_post_publicly_viewable( $post_id ) ) {
			return '';
		}

		$markup = ( new Simplified_Summary() )->simplified_summary_markup( $post_id );

		if ( ! $markup ) {
			return '';
		}

		// The wrapper carries the block supports output (margin classes/styles).
		// get_block_wrapper_attributes() requires an active block render; guard
		// against direct calls to this callback outside of one.
		$wrapper_attributes = null !== \WP_Block_Supports::$block_to_render
			? get_block_wrapper_attributes()
			: 'class="wp-block-edac-simplified-summary"';

		return sprintf( '<div %s>%s</div>', $wrapper_attributes, $markup );
	}
}
