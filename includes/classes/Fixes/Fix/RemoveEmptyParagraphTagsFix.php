<?php
/**
 * Remove Empty Paragraph Tags Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes empty paragraph tags from the frontend.
 *
 * @since 1.28.0
 */
class RemoveEmptyParagraphTagsFix implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'remove_empty_paragraph_tags';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Remove Empty Paragraph Tags', 'accessibility-checker' );
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
	 * Registers the settings field for this fix.
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
			'label'       => esc_html__( 'Remove Empty Paragraph Tags', 'accessibility-checker' ),
			'labelledby'  => 'remove_empty_paragraph_tags',
			'description' => esc_html__( 'Remove empty <p> tags from frontend output to reduce screen reader noise and prevent layout hacks.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
		];

		return $fields;
	}

	/**
	 * Executes the fix on the frontend.
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
					'enabled' => true,
				];
				return $data;
			}
		);
	}
}
