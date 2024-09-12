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
					'description' => esc_html__( 'Add a skip link to all of your site pages.', 'accessibility-checker' ),
					'callback'    => [ $this, 'focus_outline_section_callback' ],
				];

				return $sections;
			}
		);

		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {

				$fields['edac_fix_focus_outline'] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Focus Outline', 'accessibility-checker' ),
					'labelledby'  => 'fix_focus_outline',
					'description' => esc_html__( 'Add outline to elements on keyboard focus.', 'accessibility-checker' ),
					'section'     => 'focus_outline',
				];

				return $fields;
			}
		);
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
		<p><?php esc_html_e( 'Settings related to the focus outline fixes.', 'accessibility-checker' ); ?></p>
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
