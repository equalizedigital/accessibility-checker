<?php
/**
 * Empty Paragraph Tag Fix Class.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles removing empty paragraph tags on the frontend.
 *
 * @since 1.16.0
 */
class EmptyParagraphTagFix implements FixInterface {
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
	 * Registers the settings field for removing empty paragraph tags.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter(
			'edac_filter_fixes_settings_fields',
			[ $this, 'get_fields_array' ]
		);
	}

	/**
	 * Returns the settings field for removing empty paragraph tags.
	 *
	 * @param array $fields Existing fields.
	 *
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {
		$fields['edac_fix_remove_empty_paragraph_tags'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Remove Empty Paragraph Tags', 'accessibility-checker' ),
			'labelledby'  => 'remove_empty_paragraph_tags',
			// translators: %s is <code>&lt;p&gt;</code>.
			'description' => sprintf( __( 'Remove empty %s tags from the page output.', 'accessibility-checker' ), '<code>&lt;p&gt;</code>' ),
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 7870,
		];

		return $fields;
	}

	/**
	 * Executes the empty paragraph tag fix on the frontend.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_remove_empty_paragraph_tags', false ) ) {
			return;
		}

		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data['empty_paragraph_tag'] = [
					'enabled' => true,
				];
				return $data;
			}
		);
	}
}
