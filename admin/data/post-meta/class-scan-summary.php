<?php
/**
 * Data handler for scan summary
 *
 * @since 1.11.0
 *
 * @package accessibility-checker
 */

namespace EDAC\Admin\Data\Post_Meta;

use EDAC\Admin\Data\Data_Interface;

/**
 * Handle scan summary data
 *
 * @since 1.11.0
 */
class Scan_Summary implements Data_Interface {
	const SUMMARY_KEY = '_edac_summary';
	/**
	 * Scan summary data
	 *
	 * @var array
	 */
	private array $summary;

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Column sort keys to store as individual post_meta values
	 *
	 * @var string[]
	 */
	private array $column_sort_keys;

	/**
	 * Set up the post ID and colum sort keys.
	 *
	 * @param int $post_id Post ID.
	 */
	public function __construct( int $post_id = 0 ) {
		$this->post_id          = $post_id;
		$this->column_sort_keys = array( 'passed', 'error', 'warning', 'ignored', 'contrast_errors' );
	}

	/**
	 * Get scan summary data
	 *
	 * @param string $key Optional. Key to get specific data.
	 *
	 * @return array|string
	 */
	public function get( string $key = '' ) {
		if ( ! $this->summary ) {
			$this->summary = get_post_meta( $this->post_id, self::SUMMARY_KEY, true );
		}

		if ( ! empty( $key ) ) {
			return $this->summary[ $key ] ?? '';
		}

		return $this->summary;
	}

	/**
	 * Save scan summary data
	 *
	 * Also saves the keys we want to sort by as individual post_meta values.
	 *
	 * @param mixed  $data Data to save.
	 * @param string $key Optional. Key to save specific data.
	 *
	 * @return void
	 */
	public function save( $data, string $key = '' ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- some implementations may need a key.
		$this->summary = $this->sanitize_summary(
			array_merge( $this->summary, $data )
		);
		update_post_meta( $this->post_id, self::SUMMARY_KEY, $this->summary );

		// save the keys we want to sort by as individual post_meta values.
		foreach ( $this->column_sort_keys as $column ) {
			$value_to_store = $this->summary[ $column ] ?? 0;
			update_post_meta( $this->post_id, self::SUMMARY_KEY . '_' . $column, $value_to_store );
		}
	}

	/**
	 * Delete scan summary data
	 *
	 * @param string $key Optional. Key to delete specific data.
	 *
	 * @return void
	 */
	public function delete( string $key = '' ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- some implementations may need a key.
		delete_post_meta( $this->post_id, self::SUMMARY_KEY );
		foreach ( $this->column_sort_keys as $column ) {
			delete_post_meta( $this->post_id, self::SUMMARY_KEY . '_' . $column );
		}
	}

	/**
	 * Sanitizes the summary metadata.
	 *
	 * @param array $summary An associative array containing the summary of accessibility checks.
	 *
	 * @return array The sanitized summary metadata.
	 *
	 * @since 1.11.0
	 */
	private function sanitize_summary( array $summary ): array {
		return array(
			'passed_tests'            => absint( $summary['passed_tests'] ?? 0 ),
			'errors'                  => absint( $summary['errors'] ?? 0 ),
			'warnings'                => absint( $summary['warnings'] ?? 0 ),
			'ignored'                 => absint( $summary['ignored'] ?? 0 ),
			'contrast_errors'         => absint( $summary['contrast_errors'] ?? 0 ),
			'content_grade'           => absint( $summary['content_grade'] ?? 0 ),
			'readability'             => sanitize_text_field( $summary['readability'] ?? '' ),
			'simplified_summary'      => filter_var( // I would like to rename this to 'simplified_summary_required'.
				$summary['simplified_summary'] ?? false,
				FILTER_VALIDATE_BOOLEAN
			),
			'simplified_summary_text' => wp_kses_post( $summary['simplified_summary_text'] ?? '' ),
		);
	}

	/**
	 * For back compat with older data that was stored as individual post_meta values.
	 *
	 * This method should be removed after the next major release, or after a year, whichever comes first.
	 *
	 * @since 1.11.0
	 *
	 * @param string $key The key to get.
	 *
	 * @return mixed
	 */
	private function back_compat_get( string $key ) {
		$maybe_back_compat_key = $this->get_key_for_back_compat( $key );
		$meta_from_old_key     = get_post_meta( $this->post_id, $maybe_back_compat_key, true );
		return $meta_from_old_key ?? '';
	}

	/**
	 * Remap new keys to old keys to preserve back compat with older data.
	 *
	 * @param string $key - a key to check for a back compat equivalent.
	 *
	 * @return string - the back compat key if it exists, otherwise the original key.
	 */
	private function get_key_for_back_compat( string $key ): string {
		$back_compat_keys = array(
			'simplified_summary_text' => '_edac_simplified_summary',
		);
		return $back_compat_keys[ $key ] ?? $key;
	}
}
