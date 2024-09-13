<?php
/**
 * Fix for blocking PDF uploads.
 *
 * @since 1.16.0
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Fix for blocking PDF uploads.
 *
 * @since 1.16.0
 */
class BlockPDFUploadsFix implements FixInterface {

	/**
	 * Get the slug for the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'block_pdf_uploads';
	}

	/**
	 * Get the type for the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'none';
	}

	/**
	 * Register setting.
	 */
	public function register(): void {
		// Add the settings field for the fix.
		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {
				$fields['edac_fix_block_pdf_uploads'] = [
					'label'       => esc_html__( 'Block PDF Uploads', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'block_pdf_uploads',
					// translators: %1$s: a code tag with the capability name.
					'description' => sprintf( __( 'Restricts PDF uploads for users without the %1$s capability (allowed for admins by default).', 'accessibility-checker' ), '<code>edac_upload_pdf</code>' ),
					'upsell'      => isset( $this->is_pro ) && $this->is_pro ? false : true,
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix.
	 */
	public function run() {
		// Intentionally empty - this run method should be implemented in an extension class.
	}
}
