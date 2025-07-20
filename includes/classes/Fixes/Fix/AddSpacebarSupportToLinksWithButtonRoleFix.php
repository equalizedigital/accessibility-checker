<?php
/**
 * Fix for adding spacebar support to links with role="button".
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Fix for adding spacebar support to links with role="button".
 *
 * @since 1.16.0
 */
class AddSpacebarSupportToLinksWithButtonRoleFix implements FixInterface {

	/**
	 * The slug for the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'add_spacebar_support_to_links_with_button_role';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return sprintf(
			/* translators: %1$s is a html role attribute with value of 'button' */
			__( 'Add spacebar handling to links with %1$s', 'accessibility-checker' ),
			'role="button"'
		);
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Add Spacebar Support to Links converted to Buttons', 'accessibility-checker' );
	}

	/**
	 * The type for the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'none';
	}

	/**
	 * Register anything needed for the fix.
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

		$fields[ 'edac_fix_' . $this->get_slug() ] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Add Spacebar Support to Links with Button Role', 'accessibility-checker' ),
			'labelledby'  => 'add_spacebar_support_to_links_with_button_role',
			'description' => esc_html__( 'Ensure links with role="button" respond to spacebar keypress for better accessibility.', 'accessibility-checker' ),
			'upsell'      => isset( $this->is_pro ) && $this->is_pro ? false : true,
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 0000,
		];

		return $fields;
	}

	/**
	 * Run the fix.
	 */
	public function run(): void {
		// Intentionally left empty.
	}
}
