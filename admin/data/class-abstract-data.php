<?php
/**
 * Abstract data class
 *
 * @since 1.11.0
 *
 * @package accessibility-checker
 */

namespace EDAC\Admin\Data;

/**
 * Abstract data class showing the basic structure and requirements for a data class.
 */
abstract class Abstract_Data implements Interface_Data {
	const PREFIX = 'edac_';
	const KEY    = '';

	/**
	 * Get data
	 *
	 * @since 1.11.0
	 *
	 * @param string $key Optional. Key to get specific data.
	 *
	 * @return mixed
	 */
	abstract public function get( string $key = '' );

	/**
	 * Save data
	 *
	 * @since 1.11.0
	 *
	 * @param mixed  $data Data to save.
	 * @param string $key Optional. Key to save specific data.
	 */
	abstract public function save( $data, string $key = '' );

	/**
	 * Delete data
	 *
	 * @since 1.11.0
	 *
	 * @param string $key Optional. Key to delete specific data.
	 */
	abstract public function delete( string $key = '' );
}
