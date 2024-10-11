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
		return 'prevent_links_opening_new_windows';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Prevent Links Opening New Windows', 'accessibility-checker' );
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
			'label'       => esc_html__( 'Block Links Opening New Windows', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'prevent_links_opening_in_new_windows',
			'description' => sprintf(
			// translators: %1%s: A <code> tag containing target="_blank".
				esc_html__( 'Prevent links from opening in a new window or tab by removing %1$s.', 'accessibility-checker' ),
				'<code>target="_blank"</code>'
			),
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 8493,
		];

		return $fields;
	}

	/**
	 * Run the fix for adding the comment and search form labels.
	 */
	public function run(): void {
		if ( ! get_option( 'edac_fix_' . $this->get_slug(), false ) ) {
			return;
		}

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
