<?php
/**
 * Remove Text Justification Fix Class.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forces justified text blocks to use left alignment.
 *
 * @since 1.30.0
 */
class RemoveTextJustificationFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'remove_text_justification';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Remove Text Justification', 'accessibility-checker' );
	}

	/**
	 * The fancyname for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Left-align Justified Text', 'accessibility-checker' );
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
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Force Left Aligned Text', 'accessibility-checker' ),
			'labelledby'  => 'force_left_aligned_text',
			'description' => esc_html__( 'Replace justified text alignment with left alignment for long text content.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 1980,
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
				/**
				 * Filters the selector used for removing text justification.
				 *
				 * @since 1.30.0
				 *
				 * @hook edac_fix_remove_text_justification_target
				 *
				 * @param string $target_selector The selector to find justified text elements.
				 *
				 * @return string Modified selector.
				 */
				$target_selector = apply_filters(
					'edac_fix_remove_text_justification_target',
					'p, span, small, strong, b, i, em, h1, h2, h3, h4, h5, h6, a, label, button, th, td, li, div, blockquote, address, cite, q, s, sub, sup, u, del, caption, dt, dd, figcaption, summary, data, time'
				);

				$data[ $this->get_slug() ] = [
					'enabled' => true,
					'target'  => $target_selector,
				];
				return $data;
			}
		);
	}
}
