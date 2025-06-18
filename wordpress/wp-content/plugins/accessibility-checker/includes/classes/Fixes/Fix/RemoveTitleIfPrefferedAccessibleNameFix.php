<?php
/**
 * Accessible Name Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Finds elements with a preferred accessible name.
 *
 * @since 1.16.0
 */
class RemoveTitleIfPrefferedAccessibleNameFix implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'remove_title_if_preferred_accessible_name';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Prefer Accessible Label Attribute', 'accessibility-checker' );
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Remove Unnecessary Title Attributes', 'accessibility-checker' );
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
	 * Registers the settings field for the accessible name fix.
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
		$fields[ 'edac_fix_' . $this->get_slug() ] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Remove Title Attributes', 'accessibility-checker' ),
			'labelledby'  => 'accessible_name',
			// translators: %1$s: a attribute name wrapped in a <code> tag.
			'description' => sprintf( __( 'Remove %1$s attributes from elements that already have a preferred accessible name.', 'accessibility-checker' ), '<code>title</code>' ),
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 8494,
		];

		return $fields;
	}

	/**
	 * Outputs the feature flag for the fix on the frontend for JS to use.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_' . $this->get_slug(), false ) ) {
			return;
		}

		// Adds the accessible name data to be used in JS if necessary.
		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data[ $this->get_slug() ] = [
					'enabled' => true,
				];
				return $data;
			}
		);
	}
}
