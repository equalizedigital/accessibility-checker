<?php
/**
 * Iframe Missing Title Fix Class.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds descriptive title attributes to iframes missing one.
 *
 * @since 1.28.0
 */
class IframeMissingTitleFix implements FixInterface {
	/**
	 * The slug for the fix.
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
		return __( 'Add Missing iFrame Titles', 'accessibility-checker' );
	}

	/**
	 * The type for the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'frontend';
	}

	/**
	 * Register settings fields.
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
	 * Get the settings fields for this fix.
	 *
	 * @param array $fields Existing fields.
	 *
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {
		$fields[ 'edac_fix_' . $this->get_slug() ] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Add Missing iFrame Titles', 'accessibility-checker' ),
			'labelledby'  => $this->get_slug(),
			'description' => esc_html__( 'Add default title attributes to iframe elements when a title is missing.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 1953,
		];

		return $fields;
	}

	/**
	 * Run the fix.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_' . $this->get_slug(), false ) ) {
			return;
		}

		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data[ $this->get_slug() ] = [
					'enabled'        => true,
					'fallback_title' => esc_html__( 'Embedded content', 'accessibility-checker' ),
				];
				return $data;
			}
		);
	}
}
