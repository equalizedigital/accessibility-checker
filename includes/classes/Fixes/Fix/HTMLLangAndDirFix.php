<?php
/**
 * Skip Link Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Allows the user to add the post title to their read more links.
 *
 * @since 1.16.0
 */
class HTMLLangAndDirFix implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'lang_and_dir';
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
	 * Registers everything needed for the lang and dir attributes on the html element.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {

				$fields['edac_fix_add_lang_and_dir'] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Add "lang" and "dir" attribributes', 'accessibility-checker' ),
					'labelledby'  => 'add_read_more_title',
					'description' => esc_html__( 'Add Site Language and text direction to the HTML element.', 'accessibility-checker' ),
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix for adding the lang and dir attributes to the html element.
	 *
	 * This incudes a filter to add them via php but also binds some data that can be used in
	 * JS for themes that do not have the language_attributes function in the correct place.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_add_lang_and_dir', false ) ) {
			return;
		}

		// Add the lang and dir attributes to the html element via filter.
		add_filter( 'language_attributes', [ $this, 'maybe_add_lang_and_dir' ] );

		// Some themes may not have the language_attributes() function where it's meant to be so add so some JS is also
		// added that can add the attributes if they are still missing.
		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data['lang_and_dir'] = [
					'enabled' => true,
					'lang'    => get_bloginfo( 'language' ),
					'dir'     => is_rtl() ? 'rtl' : 'ltr',
				];
				return $data;
			}
		);
	}

	/**
	 * Add the lang and dir attributes to the html element.
	 *
	 * @param string $output The language attributes.
	 * @return string
	 */
	public function maybe_add_lang_and_dir( $output ): string {
		$language  = get_bloginfo( 'language' );
		$direction = is_rtl() ? 'rtl' : 'ltr';

		$additional_atts = '';

		if ( strpos( $output, 'lang=' ) === false ) {
			$additional_atts = ' lang="' . esc_attr( $language ) . '"';
		}

		if ( strpos( $output, 'dir=' ) === false ) {
			$additional_atts .= ' dir="' . esc_attr( $direction ) . '"';
		}

		return $output . $additional_atts;
	}
}
