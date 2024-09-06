<?php
/**
 * Tabindex Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Handles the removal of tabindex attributes from focusable elements.
 *
 * @since 1.16.0
 */
class LinkUnderline implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'link_underline';
	}

	/**
	 * The type of the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'frontend';
	}

	/**
	 * Registers the settings field for the tabindex removal fix.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {

				$fields['edac_fix_force_link_underline'] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Force Link Underline', 'accessibility-checker' ),
					'labelledby'  => 'force_link_underline',
					'description' => esc_html__( 'Force underline on non-navigation links.', 'accessibility-checker' ),
				];

				return $fields;
			}
		);
	}

	/**
	 * Executes the tabindex removal fix on the frontend.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_force_link_underline', false ) ) {
			return;
		}

		// Adds the tabindex removal data to be used in JS if necessary.
		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				/**
				 * Filters the target element selector for forcing underlines.
				 *
				 * This filter allows customization of the target elements that should have 
				 * forced underlines applied. By default, the selector is set to 'a'.
				 *
				 * @since 1.16.0
				 *
				 * @hook edac_fix_underline_target
				 *
				 * @param string $el The target element selector. Default is 'a'.
				 *
				 * @return string Modified target element selector.
				 */
				$target = apply_filters( 'edac_fix_underline_target', 'a' );

				$data['underline'] = [
					'enabled' => true,
					'target'  => $target,
				];
				return $data;
			}
		);
	}
}
