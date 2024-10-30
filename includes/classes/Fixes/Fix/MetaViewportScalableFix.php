<?php
/**
 * Comment Search Label Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Trys to ensure there is a meta viewport tag with the correct scalable value.
 *
 * @since 1.16.0
 */
class MetaViewportScalableFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'meta_viewport_scalable';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Make Meta Viewport Tag Scalable', 'accessibility-checker' );
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
			'edac_filter_fixes_settings_sections',
			function ( $sections ) {
				$sections['meta-viewport-scalable'] = [
					'title'       => esc_html__( 'Ensure scalable viewport', 'accessibility-checker' ),
					'description' => esc_html__( 'Make sure that the viewport tag on the page allows scaling.', 'accessibility-checker' ),
					'callback'    => [ $this, 'comment_search_label_section_callback' ],
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
		$fields[ 'edac_fix_' . $this->get_slug() ] = [
			'label'       => esc_html__( 'Make Viewport Scalable', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => '',
			'description' => esc_html__( 'Ensure the viewport tag allows for scaling, enhancing accessibility on mobile devices.', 'accessibility-checker' ),
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 8488,
		];

		return $fields;
	}

	/**
	 * Run the fix setting a scalable meta viewport tag.
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
