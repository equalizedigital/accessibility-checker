<?php
/**
 * Class file for backward compatibility.
 * Passes deprecated calls to \EDAC\Issues_Query to \EDAC\Admin\Issues_Query.
 *
 * @package Accessibility_Checker
 */

namespace EDAC;

/**
 * Class that handles passing deprecated calls to \EDAC\Issues_Query to \EDAC\Admin\Issues_Query.
 */
class Issues_Query {

	const FLAG_EXCLUDE_IGNORED        = Admin\Issues_Query::FLAG_EXCLUDE_IGNORED;
	const FLAG_INCLUDE_IGNORED        = Admin\Issues_Query::FLAG_INCLUDE_IGNORED;
	const FLAG_ONLY_IGNORED           = Admin\Issues_Query::FLAG_ONLY_IGNORED;
	const FLAG_INCLUDE_ALL_POST_TYPES = Admin\Issues_Query::FLAG_INCLUDE_ALL_POST_TYPES; 

	const RULETYPE_WARNING        = Admin\Issues_Query::RULETYPE_WARNING;
	const RULETYPE_ERROR          = Admin\Issues_Query::RULETYPE_ERROR;
	const RULETYPE_COLOR_CONTRAST = Admin\Issues_Query::RULETYPE_COLOR_CONTRAST;

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
	 * @param array   $filter .
	 * @param integer $record_limit .
	 * @param integer $flags .
	 * @deprecated 1.8.0
	 */
	public function __construct( $filter = [], $record_limit = 100000, $flags = self::FLAG_EXCLUDE_IGNORED ) {
		if ( self::FORCE_DEPRECATION ) {
			_deprecated_function( __FUNCTION__, '1.8.0' );
		}
		$this->instance = new Admin\Issues_Query( $filter, $record_limit, $flags );
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
