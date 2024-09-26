<?php
/**
 * Fix missing or empty page titles.
 *
 * @package accessibility-checker-pro
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Allows the user to add a title to the page <title> tag if empty or missing.
 *
 * @since 1.9.0
 */
class AddMissingOrEmptyPageTitleFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'missing_or_empty_page_title';
	}

	/**
	 * The type of the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'none';
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

				$fields['edac_fix_add_missing_or_empty_page_title'] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Add Missing Page Title', 'accessibility-checker' ),
					'labelledby'  => 'add_missing_or_empty_page_title',
					// translators: %1$s: a code tag with a title tag.
					'description' => sprintf( __( 'Adds a %1$s tag to the page if it\'s missing or empty.', 'accessibility-checker' ), '<code>&lt;title&gt;</code>' ),
					'upsell'      => isset( $this->is_pro ) && $this->is_pro ? false : true,
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix for adding a missing or empty page title.
	 *
	 * @return void
	 */
	public function run() {
		// Intentionally left empty.
	}
}