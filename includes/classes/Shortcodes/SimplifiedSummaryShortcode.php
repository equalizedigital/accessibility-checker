<?php
/**
 * Registers the simplified summary shortcode.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Shortcodes;

use EDAC\Inc\Simplified_Summary;

/**
 * Registers and renders the [edac_simplified_summary] shortcode.
 *
 * Lets users output the simplified summary manually in classic content or
 * shortcode-aware areas. Accepts an optional post_id attribute and falls
 * back to the current post.
 *
 * @since 1.49.0
 */
class SimplifiedSummaryShortcode {

	/**
	 * The shortcode tag.
	 *
	 * @var string
	 */
	const SHORTCODE = 'edac_simplified_summary';

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.49.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_shortcode( self::SHORTCODE, [ $this, 'render' ] );
	}

	/**
	 * Render the shortcode.
	 *
	 * Renders regardless of the edac_simplified_summary_prompt option because
	 * manual placement is a deliberate act, matching edac_get_simplified_summary().
	 * An explicit post_id must reference a publicly viewable, unprotected post
	 * so the shortcode cannot expose summaries of draft, private, or
	 * password-protected posts.
	 *
	 * @since 1.49.0
	 *
	 * @param array|string $atts The shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[ 'post_id' => 0 ],
			$atts,
			self::SHORTCODE
		);

		$explicit_post_id = absint( $atts['post_id'] );

		if ( $explicit_post_id && ( ! is_post_publicly_viewable( $explicit_post_id ) || post_password_required( $explicit_post_id ) ) ) {
			return '';
		}

		$post_id = $explicit_post_id ? $explicit_post_id : (int) get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		return ( new Simplified_Summary() )->simplified_summary_markup( $post_id );
	}
}
