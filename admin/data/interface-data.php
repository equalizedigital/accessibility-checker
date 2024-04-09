<?php
/**
 * Interface for data classes
 *
 * @since 1.11.0
 *
 * @package accessibility-checker
 */

namespace EDAC\Admin\Data;

/**
 * Interface for data classes
 */
interface Interface_Data {

	/**
	 * Get data
	 *
	 * @since 1.11.0
	 *
	 * @param string $key Optional. Key to get specific data.
	 *
	 * @return mixed
	 */
	public function get( string $key = '' );

	/**
	 * Save data
	 *
	 * @since 1.11.0
	 *
	 * @param mixed  $data Data to save.
	 * @param string $key Optional. Key to save specific data.
	 */
	public function save( $data, string $key = '' );

	/**
	 * Delete data
	 *
	 * @since 1.11.0
	 *
	 * @param string $key Optional. Key to delete specific data.
	 */
	public function delete( string $key = '' );
}
