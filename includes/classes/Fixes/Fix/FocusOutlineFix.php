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
					'description' => esc_html__( 'Adds an outline to elements when they receive keyboard focus.', 'accessibility-checker' ),
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
					'description' => esc_html__( 'Adds an outline to elements when they receive keyboard focus.', 'accessibility-checker' ),
					'section'     => 'focus_outline',
				];

				$fields['edac_fix_focus_outline_color'] = [
					'type'              => 'color',
					'label'             => esc_html__( 'Focus Outline Color', 'accessibility-checker' ),
					'labelledby'        => 'fix_focus_outline_color',
					'description'       => sprintf(
						// translators: %1$s: a color code wrapped in a <code> tag.
						__( 'Sets the color for the focus outline. Default is %1$s.', 'accessibility-checker' ),
						'<code>#005FCC</code>' 
					),
					'sanitize_callback' => 'sanitize_hex_color',
					'section'           => 'focus_outline',
					'condition'         => 'edac_fix_focus_outline',
					'default'           => '#005FCC',
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
		<p><?php esc_html_e( 'Settings related to enhancing focus outlines for better keyboard accessibility.', 'accessibility-checker' ); ?></p>
		<?php
	}

	/**
	 * Outputs the CSS for the focus outline fix.
	 *
	 * @return void
	 */
	public function css() {
		$styles = '';

		$focus_color_option = get_option( 'edac_fix_focus_outline_color', false );
		$color              = $focus_color_option ? '#' . sanitize_hex_color_no_hash( $focus_color_option ) : '#005FCC';

		$styles .= "
		:focus {
			outline: 2px solid $color !important;
			outline-offset: 2px !important;
			box-shadow: 0 0 0 3px white !important; /* Adds a white outline outside the color outline */
		}
		";

		?>
		<style id="edac-fix-focus-outline">
			<?php echo esc_attr( $styles ); ?>
		</style>
		<?php
	}
}
