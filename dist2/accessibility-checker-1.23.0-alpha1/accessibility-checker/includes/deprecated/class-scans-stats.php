<?php
/**
 * Class file for backward compatibility.
 * Passes deprecated calls to \EDAC\Scans_Stats to \EDAC\Admin\Scans_Stats.
 *
 * @package Accessibility_Checker
 */

namespace EDAC;

/**
 * Class that handles passing deprecated calls to \EDAC\Scans_Stats to \EDAC\Admin\Scans_Stats.
 */
class Scans_Stats {

	/**
	 * Enable/disable _deprecated_function() warning messages on calls.
	 *
	 * @var boolean
	 */
	const FORCE_DEPRECATION = false;
	
	/**
	 * Instance of the new class.
	 *
	 * @var class
	 */
	private $instance;

	/**
	 * Constructor
	 *
	 * @param integer $cache_time number of seconds to return the results from cache.
	 * @deprecated 1.8.0
	 */
	public function __construct( $cache_time = 60 * 60 * 24 ) {
		if ( self::FORCE_DEPRECATION ) {
			_deprecated_function( __FUNCTION__, '1.8.0' );
		}
		$this->instance = new Admin\Scans_Stats( $cache_time );
	}
	
	/**
	 * Magic method for forwarding non-static method calls
	 *
	 * @param string $method The method.
	 * @param mixed  $arguments The arguments.
	 * @deprecated 1.8.0
	 * @return function
	 */
	public function __call( $method, $arguments ) {
		if ( self::FORCE_DEPRECATION ) {
			_deprecated_function( __FUNCTION__, '1.8.0' );
		}
		return call_user_func_array( [ $this->instance, $method ], $arguments );
	}
	
	/**
	 * Magic method for forwarding static method calls
	 *
	 * @param string $method The method.
	 * @param mixed  $arguments The arguments.
	 * @deprecated 1.8.0
	 * @return function
	 */
	public static function __callStatic( $method, $arguments ) {
		if ( self::FORCE_DEPRECATION ) {
			_deprecated_function( __FUNCTION__, '1.8.0' );
		}
		return call_user_func_array( [ self::$instance, $method ], $arguments );
	}
	
	/**
	 * Magic method for forwarding property calls
	 *
	 * @param string $property The property.
	 * @deprecated 1.8.0
	 * @return function
	 */
	public function __get( $property ) {
		if ( self::FORCE_DEPRECATION ) {
			_deprecated_function( __FUNCTION__, '1.8.0' );
		}
		return $this->instance->$property;
	}
	
	/**
	 * Magic method for forwarding property calls
	 *
	 * @param string $property The property.
	 * @param mixed  $value The value.
	 * @deprecated 1.8.0
	 * @return void
	 */
	public function __set( $property, $value ) {
		if ( self::FORCE_DEPRECATION ) {
			_deprecated_function( __FUNCTION__, '1.8.0' );
		}
		$this->instance->$property = $value;
	}
}
