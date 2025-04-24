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
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Remove Tabindex from Focusable Elements', 'accessibility-checker' );
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
			[ $this, 'get_fields_array' ]
		);
	}

	/**
	 * Returns the settings fields for the tabindex removal fix.
	 *
	 * @param array $fields The array of fields that are already registered, if any.
	 *
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {
		$fields['edac_fix_remove_tabindex'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Remove Tab Index', 'accessibility-checker' ),
			'labelledby'  => 'remove_tabindex',
			// translators: %1$s: a attribute name wrapped in a <code> tag.
			'description' => sprintf( __( 'Remove the %1$s attribute from focusable elements.', 'accessibility-checker' ), '<code>tabindex</code>' ),
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 8496,
		];

		return $fields;
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
