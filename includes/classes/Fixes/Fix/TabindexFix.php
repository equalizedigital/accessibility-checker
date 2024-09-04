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
class TabindexFix implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'remove_tabindex';
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

				$fields['edac_fix_remove_tabindex'] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Remove Tab Index', 'accessibility-checker' ),
					'labelledby'  => 'remove_tabindex',
					'description' => esc_html__( 'Remove tabindex from focusable elements.', 'accessibility-checker' ),
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
		if ( ! get_option( 'edac_fix_remove_tabindex', false ) ) {
			return;
		}

		// Adds the tabindex removal data to be used in JS if necessary.
		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data['tabindex'] = [
					'enabled' => true,
				];
				return $data;
			}
		);
	}
}
