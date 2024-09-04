<?php
/**
 * Prevents links from opening in new windows.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Prevents links from opening in new windows.
 *
 * @since 1.16.0
 */
class PreventLinksOpeningNewWindowFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'prevent-links-opening-new-windows';
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
	 * Registers everything needed for the fix.
	 *
	 * @return void
	 */
	public function register(): void {

		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {
				$fields['edac_fix_prevent_links_opening_in_new_windows'] = [
					'label'       => esc_html__( 'Links Opening New Windows', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'prevent_links_opening_in_new_windows',
					'description' => esc_html__( 'Prevents links from opening in a new window.', 'accessibility-checker' ),
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix for adding the comment and search form labels.
	 */
	public function run(): void {
		if ( ! get_option( 'edac_fix_prevent_links_opening_in_new_windows', false ) ) {
			return;
		}

		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data['prevent_links_opening_in_new_window'] = [
					'enabled' => true,
				];
				return $data;
			}
		);
	}
}
