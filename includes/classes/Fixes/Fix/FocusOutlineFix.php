<?php
/**
 * Focus Outline Fix Class
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
class FocusOutlineFix implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'focus_outline';
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

				$fields['edac_fix_focus_outline'] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Focus Outline', 'accessibility-checker' ),
					'labelledby'  => 'fix_focus_outline',
					'description' => esc_html__( 'Add outline to elements on keyboard focus.', 'accessibility-checker' ),
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
		if ( ! get_option( 'edac_fix_focus_outline', false ) ) {
			return;
		}

		// Adds the tabindex removal data to be used in JS if necessary.
		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data['focus_outline'] = [
					'enabled' => true,
				];
				return $data;
			}
		);
	}
}
