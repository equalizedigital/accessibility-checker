<?php
/**
 * Fix for adding file size and type to linked files.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Fix for adding file size and type to linked files.
 *
 * @since 1.16.0
 */
class AddFileSizeAndTypeToLinkedFilesFix implements FixInterface {

	/**
	 * The slug for the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'add_file_size_and_type_to_linked_files';
	}

	/**
	 * The type for the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'none';
	}

	/**
	 * Register anything needed for the fix.
	 */
	public function register(): void {
		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {
				$fields[ 'edac_fix_' . $this->get_slug() ] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Add File Size & Type To Links', 'accessibility-checker' ),
					'labelledby'  => 'add_file_size_and_type_to_linked_files',
					'description' => esc_html__( 'Adds the file size and type to linked files that may trigger a download.', 'accessibility-checker' ),
					'upsell'      => isset( $this->is_pro ) && $this->is_pro ? false : true,
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix.
	 */
	public function run(): void {
		// Intentionally left empty.
	}
}