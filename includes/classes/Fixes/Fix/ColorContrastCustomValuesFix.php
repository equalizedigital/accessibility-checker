<?php
/**
 * Color Contrast Custom Values Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows users to apply custom foreground/background color overrides
 * to a selector when color contrast issues are detected.
 *
 * @since 1.39.0
 */
class ColorContrastCustomValuesFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'color_contrast_custom_values';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Custom Color Contrast Override', 'accessibility-checker' );
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
	 * Registers fields for the custom contrast override fix.
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
	 * Get the settings fields for this fix.
	 *
	 * @param array $fields Existing settings fields.
	 *
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {
		$fields['edac_fix_color_contrast_custom_values_enabled'] = [
			'label'       => esc_html__( 'Enable Custom Color Contrast Override', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'color_contrast_custom_values_enabled',
			'description' => esc_html__( 'Apply custom text and background colors to matching elements site-wide.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 1983,
		];

		$fields['edac_fix_color_contrast_custom_values_selector'] = [
			'label'             => esc_html__( 'CSS Selector', 'accessibility-checker' ),
			'type'              => 'text',
			'labelledby'        => 'color_contrast_custom_values_selector',
			'description'       => esc_html__( 'Enter a CSS selector to target affected elements (example: .entry-content a, .entry-content p).', 'accessibility-checker' ),
			'sanitize_callback' => 'sanitize_text_field',
			'fix_slug'          => $this->get_slug(),
		];

		$fields['edac_fix_color_contrast_custom_values_text_color'] = [
			'label'             => esc_html__( 'Text Color (hex)', 'accessibility-checker' ),
			'type'              => 'text',
			'labelledby'        => 'color_contrast_custom_values_text_color',
			'description'       => esc_html__( 'Enter a hex color value like #111111.', 'accessibility-checker' ),
			'sanitize_callback' => 'sanitize_text_field',
			'fix_slug'          => $this->get_slug(),
		];

		$fields['edac_fix_color_contrast_custom_values_background_color'] = [
			'label'             => esc_html__( 'Background Color (hex)', 'accessibility-checker' ),
			'type'              => 'text',
			'labelledby'        => 'color_contrast_custom_values_background_color',
			'description'       => esc_html__( 'Enter a hex color value like #FFFFFF.', 'accessibility-checker' ),
			'sanitize_callback' => 'sanitize_text_field',
			'fix_slug'          => $this->get_slug(),
		];

		return $fields;
	}

	/**
	 * Run the custom override fix.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! get_option( 'edac_fix_color_contrast_custom_values_enabled', false ) ) {
			return;
		}

		$selector = $this->sanitize_selector( get_option( 'edac_fix_color_contrast_custom_values_selector', '' ) );
		if ( '' === $selector ) {
			return;
		}

		$text_color       = sanitize_hex_color( (string) get_option( 'edac_fix_color_contrast_custom_values_text_color', '' ) );
		$background_color = sanitize_hex_color( (string) get_option( 'edac_fix_color_contrast_custom_values_background_color', '' ) );

		if ( ! $text_color && ! $background_color ) {
			return;
		}

		add_action(
			'wp_head',
			function () use ( $selector, $text_color, $background_color ) {
				$rules = [];

				if ( $text_color ) {
					$rules[] = 'color: ' . $text_color . ' !important';
				}
				if ( $background_color ) {
					$rules[] = 'background-color: ' . $background_color . ' !important';
				}

				if ( empty( $rules ) ) {
					return;
				}
				?>
				<style id="edac-fix-color-contrast-custom-values">
					<?php echo esc_html( $selector ); ?> {
						<?php echo esc_html( implode( '; ', $rules ) ); ?>;
					}
				</style>
				<?php
			}
		);
	}

	/**
	 * Sanitizes user-provided CSS selector input.
	 *
	 * @param string $selector CSS selector string.
	 *
	 * @return string
	 */
	private function sanitize_selector( string $selector ): string {
		$selector = sanitize_text_field( $selector );
		$selector = str_replace( [ '{', '}', ';' ], '', $selector );
		$selector = preg_replace( '/[^a-zA-Z0-9#\.\,\-_:\s>\+\~\*\[\]\(\)=\"\'\|\^\$]/', '', $selector );

		return trim( $selector );
	}
}
