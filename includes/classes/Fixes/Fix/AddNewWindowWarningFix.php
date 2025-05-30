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
class AddNewWindowWarningFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'new_window_warning';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add warning when link opens new tab/window', 'accessibility-checker' );
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
			'label'       => esc_html__( 'Add Label To Links That Open A New Tab/Window', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'add_new_window_warning',
			'description' => sprintf(
			// translators: %1%s: A <code> tag containing target="_blank".
				esc_html__( 'Add a label and icon to links with %1$s informing users they will open a new tab/window. %2$sNote: This setting will have no effect if the "Block Links Opening New Windows" fix is enabled.%3$s', 'accessibility-checker' ),
				'<code>target="_blank"</code>',
				'<br><strong>',
				'</strong>'
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

		// unregister the anww script if it's present, this fix supercedes it.
		if ( class_exists( '\ANWW' ) && defined( 'ANWW_VERSION' ) ) {
			add_action(
				'wp_enqueue_scripts',
				function () {
					wp_deregister_script( 'anww' );
					wp_deregister_style( 'anww' );
				},
				PHP_INT_MAX
			);
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

		add_action( 'wp_head', [ $this, 'add_styles' ] );
	}

	/**
	 * Add the styles for the new window warning.
	 */
	public function add_styles() {
		$font_url = EDAC_PLUGIN_URL . 'assets/fonts';
		?>
		<style id="edac-nww">
			@font-face {
				font-family: 'anww';
				src:  url('<?php echo esc_url( $font_url ); ?>/anww.eot?7msg3d');
				src:  url('<?php echo esc_url( $font_url ); ?>/anww.eot?7msg3d#iefix') format('embedded-opentype'),
				url('<?php echo esc_url( $font_url ); ?>/anww.ttf?7msg3d') format('truetype'),
				url('<?php echo esc_url( $font_url ); ?>/anww.woff?7msg3d') format('woff'),
				url('<?php echo esc_url( $font_url ); ?>/anww.svg?7msg3d#anww') format('svg');
				font-weight: normal;
				font-style: normal;
				font-display: block;
			}

			:root {
				--font-base: 'anww', sans-serif;
				--icon-size: 0.75em;
			}

			.edac-nww-external-link-icon {
				font: normal normal normal 1em var(--font-base) !important;
				speak: never;
				text-transform: none;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}

			.edac-nww-external-link-icon:before {
				content: " \e900";
				font-size: var(--icon-size);
			}

			.edac-nww-external-link-icon.elementor-button-link-content:before {
				vertical-align: middle;
			}
		</style>
		<?php
	}
}
