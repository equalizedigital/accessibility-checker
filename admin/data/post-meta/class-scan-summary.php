<?php
/**
 * Data handler for scan summary
 *
 * @since 1.11.0
 *
 * @package accessibility-checker
 */

namespace EDAC\Admin\Data\Post_Meta;

use EDAC\Admin\Data\Abstract_Data;
use EDAC\Admin\Data\Interface_Data;

/**
 * Handle scan summary data
 *
 * @since 1.11.0
 */
class Scan_Summary extends Abstract_Data implements Interface_Data {
	const KEY = 'summary';

	/**
	 * Storage key
	 *
	 * @var string
	 */
	public string $root_key = self::PREFIX . self::KEY;

	/**
	 * Scan summary data
	 *
	 * @var array
	 */
	protected array $summary;

	/**
	 * Post ID
	 *
	 * @var int
	 */
	public int $post_id;

	/**
	 * Column sort keys to store as individual post_meta values
	 *
	 * @var string[]
	 */
	public array $column_sort_keys;

	/**
	 * Set up the post ID and colum sort keys.
	 *
	 * @param int $post_id Post ID.
	 */
	public function __construct( int $post_id = 0 ) {
		$this->post_id          = $post_id;
		$this->column_sort_keys = array( 'passed', 'error', 'warning', 'ignored', 'contrast_errors' );

		// There is no reason to create an instance of this class without wanting this data.
		$summary       = get_post_meta( $this->post_id, $this->root_key, true );
		$this->summary = ! empty( $summary ) ? $summary : array();
	}

	/**
	 * Get scan summary data
	 *
	 * @param string $key Optional. Key to get specific data.
	 *
	 * @return array|string
	 */
	public function get( string $key = '' ) {
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
	 * It may be confusing to have the data paramiter before the key parameter but this
	 * is done so that data can be passed without a key to save the entire summary. The
	 * key just allows a chunk to be saved.
	 *
	 * @param mixed  $data Data to save. This is expected to be an associative array if a key is not passed.
	 * @param string $key Optional. Key to save specific data.
	 *
	 * @return void
	 */
	public function save( $data, string $key = '' ): void {
		if ( empty( $key ) && ! is_array( $data ) ) {
			// this is a fail, we may need to bubble a WP_Error back up.
			return;
		}
		$this->summary = $this->sanitize_summary(
			// If a key is passed, only update that key.
			array_merge(
				$this->summary,
				empty( $key ) ? $data : array( $key => $data )
			)
		);
		update_post_meta( $this->post_id, $this->root_key, $this->summary );

		// save the keys we want to sort by as individual post_meta values.
		foreach ( $this->column_sort_keys as $column ) {
			$value_to_store = $this->summary[ $column ] ?? 0;
			update_post_meta(
				$this->post_id,
				$this->root_key . '_' . $column,
				$value_to_store
			);
		}
	}

	/**
	 * Delete scan summary data
	 *
	 * @param string $key Optional. Key to delete specific data.
	 *
	 * @return void
	 */
	public function delete( string $key = '' ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- some implementations may need a key for back compat.
		if ( ! empty( $key ) ) {
			unset( $this->summary[ $key ] );
			$this->save( $this->summary );
			return;
		}
		delete_post_meta( $this->post_id, $this->root_key );
		foreach ( $this->column_sort_keys as $column ) {
			delete_post_meta(
				$this->post_id,
				$this->root_key . '_' . $column
			);
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
	public function sanitize_summary( array $summary ): array {
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
}
