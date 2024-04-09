<?php
/**
 * Data handler for scan summary with back compat support.
 *
 * This class will ideally be removable after enough time has passed or enough
 * updated versions have been released where we can be confident that all data
 * in users systems has been updated to be stored in the singular root key.
 *
 * @since 1.11.0
 *
 * @package accessibility-checker
 */

namespace EDAC\Admin\Data\Post_Meta;

use EDAC\Admin\Data\Interface_Data;

/**
 * Handle scan summary data in a backwards compatible way.
 *
 * @since 1.11.0
 */
class Scan_Summary_Back_Compat extends Scan_Summary implements Interface_Data {

	/**
	 * Scan summary data
	 *
	 * @var array
	 */
	protected array $summary;

	/**
	 * Get scan summary data
	 *
	 * @param string $key Optional. Key to get specific data.
	 *
	 * @return array|string
	 */
	public function get( string $key = '' ) {
		if ( ! empty( $key ) ) {
			return $this->summary[ $key ] ?? $this->back_compat_get( $key );
		}

		return parent::get( $key );
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
	 * @param mixed  $data Data to save.
	 * @param string $key Optional. Key to save specific data.
	 *
	 * @return void
	 */
	public function save( $data, string $key = '' ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- some implementations may need a key.
		parent::save( $data, $key );
		if ( empty( $key ) ) {
			return;
		}
		$back_compat_key = $this->get_key_for_back_compat( $key );
		if ( $back_compat_key !== $key ) {
			delete_post_meta( $this->post_id, $back_compat_key );
		}
	}

	/**
	 * Delete scan summary data
	 *
	 * @param string $key Optional. Key to delete specific data.
	 *
	 * @return void
	 */
	public function delete( string $key = '' ): void {
		parent::delete( $key );
		foreach ( $this->column_sort_keys as $column ) {
			// delete the old keys for back compat.
			delete_post_meta(
				$this->post_id,
				$this->get_key_for_back_compat(
					$this->root_key . '_' . $column
				)
			);
		}
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
			'post_checked'            => '_edac_post_checked',
			'post_checked_time_js'    => '_edac_post_checked_js',
			'issue_density'           => '_edac_issue_density',
		);
		return $back_compat_keys[ $key ] ?? $key;
	}
}
