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
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Block PDF Uploads', 'accessibility-checker' );
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
		$fields['edac_fix_block_pdf_uploads'] = [
			'label'       => esc_html__( 'Block PDF Uploads', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'block_pdf_uploads',
			// translators: %1$s: a code tag with the capability name.
			'description' => sprintf( __( 'Restrict PDF uploads for users without the %1$s capability (allowed for admins by default).', 'accessibility-checker' ), '<code>edac_upload_pdf</code>' ),
			'upsell'      => isset( $this->is_pro ) && $this->is_pro ? false : true,
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 8486,
		];

		return $fields;
	}

	/**
	 * Run the fix.
	 */
	public function run() {
		// Intentionally empty - this run method should be implemented in an extension class.
	}
}
