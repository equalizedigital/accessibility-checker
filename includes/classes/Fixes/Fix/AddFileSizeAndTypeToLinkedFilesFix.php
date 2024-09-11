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
 * @since 1.9.0
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
		return 'frontend';
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
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix.
	 */
	public function run(): void {
		if ( ! get_option( 'edac_fix_', $this->get_slug(), false ) ) {
			return;
		}

		add_filter( 'the_content', [ $this, 'add_file_size_and_type_to_linked_files' ] );
	}

	/**
	 * Add file size and type to linked files.
	 *
	 * @param string $content The content.
	 *
	 * @return string
	 */
	public function add_file_size_and_type_to_linked_files( string $content ): string {
		$pattern = '/(<a\s[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>)([^<]+)<\/a>/';
		preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$file  = $match[2];
			$label = $match[3];

			$file_type = wp_check_filetype( $file );

			if ( ! $file_type['ext'] ) {
				continue;
			}

			// Check if the file exists in the media library.
			if ( function_exists( 'wpcom_vip_attachment_url_to_postid' ) ) {
				$attachment_id = wpcom_vip_attachment_url_to_postid( $file );
			} else {
				$attachment_id = attachment_url_to_postid( $file ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- this is for non-VIP sites.
			}

			if ( $attachment_id ) {
				$file_path = get_attached_file( $attachment_id );
				$file_size = size_format( filesize( $file_path ), 2 );
			} else {
				// Check if the file is local to this site or external.
				$site_url = site_url();
				if ( strpos( $file, $site_url ) === false ) {
					// external file, just rely on the extension for the label.
					$label  .= ' (' . $file_type['ext'] . ')';
					$content = str_replace( $match[0], $match[1] . $label . '</a>', $content );
					continue;
				}

				$file_path = ABSPATH . str_replace( site_url(), '', $file );
				// Check if the file exists in the uploads directory.
				if ( file_exists( $file_path ) ) {
					$file_size = size_format( filesize( $file_path ), 2 );
					$label    .= ' (' . $file_size . ', ' . $file_type['ext'] . ')';
					$content   = str_replace( $match[0], '<a href="' . $file . '">' . $label . '</a>', $content );
				}
			}

			if ( file_exists( $file_path ) ) {

				$label  .= ' (' . $file_size . ', ' . $file_type['ext'] . ')';
				$content = str_replace( $match[0], $match[1] . $label . '</a>', $content );
			}
		}

		return $content;
	}
}
