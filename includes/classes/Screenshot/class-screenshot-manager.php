<?php
/**
 * Class file for managing accessibility violation screenshots.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc\Screenshot;

/**
 * Class that manages screenshots of accessibility violations.
 */
class Screenshot_Manager {

	/**
	 * The uploads directory information.
	 *
	 * @var array
	 */
	private $upload_dir;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->upload_dir = wp_upload_dir();
	}

	/**
	 * Initialize the screenshots directory.
	 *
	 * @return string|WP_Error Path to screenshots directory or WP_Error if creation failed.
	 */
	public function init_screenshots_directory() {
		$screenshots_path = $this->upload_dir['basedir'] . '/accessibility-screenshots';
		
		// Create screenshots directory if it doesn't exist.
		if ( ! file_exists( $screenshots_path ) ) {
			if ( ! wp_mkdir_p( $screenshots_path ) ) {
				return new \WP_Error( 'failed_create_dir', __( 'Failed to create screenshots directory', 'accessibility-checker' ) );
			}
		}

		// Create .htaccess to protect the directory.
		$htaccess_file = $screenshots_path . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "Options -Indexes\nDeny from all";
			if ( ! file_put_contents( $htaccess_file, $htaccess_content ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
				return new \WP_Error( 'failed_create_htaccess', __( 'Failed to create .htaccess file', 'accessibility-checker' ) );
			}
		}

		return $screenshots_path;
	}

	/**
	 * Save a screenshot from base64 data.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $violation_id The violation ID.
	 * @param string $base64_data The base64 encoded screenshot data.
	 * @return string|WP_Error Path to saved screenshot or WP_Error if save failed.
	 */
	public function save_screenshot( $post_id, $violation_id, $base64_data ) {
		$screenshots_path = $this->init_screenshots_directory();
		
		if ( is_wp_error( $screenshots_path ) ) {
			return $screenshots_path;
		}

		// Decode base64 data.
		$image_data = base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $base64_data ) );
		
		if ( ! $image_data ) {
			return new \WP_Error( 'invalid_base64', __( 'Invalid base64 image data', 'accessibility-checker' ) );
		}

		// Generate unique filename.
		$filename = sprintf( 'violation-%d-%s-%s.png', $post_id, $violation_id, uniqid() );
		$filepath = $screenshots_path . '/' . $filename;

		if ( ! file_put_contents( $filepath, $image_data ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			return new \WP_Error( 'failed_save_screenshot', __( 'Failed to save screenshot', 'accessibility-checker' ) );
		}

		return str_replace( $this->upload_dir['basedir'], $this->upload_dir['baseurl'], $filepath );
	}

	/**
	 * Delete a screenshot.
	 *
	 * @param string $screenshot_path Path to the screenshot.
	 * @return bool True if deleted successfully, false otherwise.
	 */
	public function delete_screenshot( $screenshot_path ) {
		$file_path = str_replace( $this->upload_dir['baseurl'], $this->upload_dir['basedir'], $screenshot_path );
		if ( file_exists( $file_path ) ) {
			return unlink( $file_path ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
		}
		return false;
	}
}
