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
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add Underlines to all non-nav Links', 'accessibility-checker' );
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Underline Links', 'accessibility-checker' );
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
		$fields['edac_fix_force_link_underline'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Force Link Underline', 'accessibility-checker' ),
			'labelledby'  => 'force_link_underline',
			'description' => esc_html__( 'Ensure that non-navigation links are underlined.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 8489,
		];

		return $fields;
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
