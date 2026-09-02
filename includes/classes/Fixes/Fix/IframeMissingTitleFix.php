<?php
/**
 * Iframe Missing Title Fix class.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds fallback titles to iframes when they are missing.
 *
 * @since 1.33.0
 */
class IframeMissingTitleFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'iframe_missing_title';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add Missing iframe Titles', 'accessibility-checker' );
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
			'label'       => esc_html__( 'Add Missing iframe Titles', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => '',
			'description' => esc_html__( 'Adds a generated, descriptive title to iframe elements that are missing one.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
		];

		return $fields;
	}

	/**
	 * Run the iframe title fix.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! get_option( 'edac_fix_' . $this->get_slug(), false ) ) {
			return;
		}

		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data[ $this->get_slug() ] = [
					'enabled' => get_option( 'edac_fix_' . $this->get_slug(), false ),
				];
				return $data;
			}
		);
	}
}
