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
 * @since 1.16.0
 */
class AddMissingOrEmptyPageTitleFix implements FixInterface {

	/**
	 * Whether the pro version is active.
	 *
	 * @var bool
	 */
	public $is_pro = false;

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'missing_or_empty_page_title';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add Missing or Empty Page Titles', 'accessibility-checker' );
	}

	/**
	 * The fancyname for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Set Page HTML Titles', 'accessibility-checker' );
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
			[ $this, 'get_fields_array' ],
		);
	}

	/**
	 * Get the settings fields for the fix.
	 *
	 * @param array $fields The array of fields that are already registered, if any.
	 *
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {
		$fields['edac_fix_add_missing_or_empty_page_title'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Add Missing Page Title', 'accessibility-checker' ),
			'labelledby'  => 'add_missing_or_empty_page_title',
			// translators: %1$s: a code tag with a title tag.
			'description' => sprintf( __( 'Add a %1$s tag to the page if it\'s missing or empty.', 'accessibility-checker' ), '<code>&lt;title&gt;</code>' ),
			'upsell'      => isset( $this->is_pro ) && $this->is_pro ? false : true,
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 8490,
		];

		return $fields;
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
