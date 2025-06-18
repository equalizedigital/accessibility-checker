<?php
/**
 * Focus Outline Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Handles adding a focus outline to focusable elements.
 *
 * @since 1.16.0
 */
class FocusOutlineFix implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'focus_outline';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add Focus Outlines', 'accessibility-checker' );
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
	 * Registers the settings field for the focus outline fix.
	 *
	 * @return void
	 */
	public function register(): void {

		add_filter(
			'edac_filter_fixes_settings_sections',
			function ( $sections ) {
				$sections['focus_outline'] = [
					'title'       => esc_html__( 'Focus Outline', 'accessibility-checker' ),
					'description' => esc_html__( 'Add an outline to elements when they receive keyboard focus.', 'accessibility-checker' ),
					'callback'    => [ $this, 'focus_outline_section_callback' ],
				];

				return $sections;
			}
		);

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
		$fields['edac_fix_focus_outline'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Add Focus Outline', 'accessibility-checker' ),
			'labelledby'  => 'fix_focus_outline',
			'description' => esc_html__( 'Add an outline to elements when they receive keyboard focus.', 'accessibility-checker' ),
			'section'     => 'focus_outline',
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 8495,
		];

		return $fields;
	}

	/**
	 * Executes the focus outline fix on the frontend.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_focus_outline', false ) ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'css' ] );
	}

	/**
	 * Callback for the focus outline section.
	 *
	 * @return void
	 */
	public function focus_outline_section_callback() {
		?>
		<p><?php esc_html_e( 'Settings related to enhancing focus outlines for better keyboard accessibility.', 'accessibility-checker' ); ?></p>
		<?php
	}

	/**
	 * Outputs the CSS for the focus outline fix.
	 *
	 * @return void
	 */
	public function css() {
		?>
		<style id="edac-fix-focus-outline">
			:focus {
				outline: revert !important;
				outline-offset: revert !important;
			}
		</style>
		<?php
	}
}
