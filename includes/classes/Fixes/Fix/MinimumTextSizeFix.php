<?php
/**
 * Minimum Text Size Fix.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforces a minimum text size on frontend content.
 *
 * @since 1.16.0
 */
class MinimumTextSizeFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'minimum_text_size';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Enforce Minimum Text Size', 'accessibility-checker' );
	}

	/**
	 * The fancyname for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Increase Small Text', 'accessibility-checker' );
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
		$fields['edac_fix_minimum_text_size'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Enforce Minimum Text Size', 'accessibility-checker' ),
			'labelledby'  => 'minimum_text_size',
			'description' => esc_html__( 'Increase text smaller than the minimum size to improve readability.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 1975,
		];

		$fields['edac_fix_minimum_text_size_px'] = [
			'type'        => 'text',
			'label'       => esc_html__( 'Minimum Text Size (px)', 'accessibility-checker' ),
			'labelledby'  => 'minimum_text_size_px',
			'description' => esc_html__( 'Set the minimum font size in pixels. Values below 10 default to 10.', 'accessibility-checker' ),
			'default'     => '10',
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 1975,
		];

		return $fields;
	}

	/**
	 * Run the fix for minimum text size.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! get_option( 'edac_fix_minimum_text_size', false ) ) {
			return;
		}

		$min_size = max( 10, absint( get_option( 'edac_fix_minimum_text_size_px', 10 ) ) );

		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) use ( $min_size ) {
				$data[ $this->get_slug() ] = [
					'enabled'  => true,
					'min_size' => $min_size,
				];
				return $data;
			}
		);
	}
}
