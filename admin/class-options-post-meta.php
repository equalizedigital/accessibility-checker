<?php
/**
 * Class file for managing options
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class that handles WP post meta options for the plugin.
 */
class Options_Post_Meta {

	/**
	 * The default values.
	 *
	 * @var array [name => value]
	 */
	private $default_values = array(
		'test' => 'value',
	);
	

	/**
	 * The variable type for the stored value.
	 *
	 * @var array [name => string|bool|array|json]
	 */
	private $casts = array();
	
	/**
	 * Active instance of this class (singleton).
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * ID of the WordPress post we're using.
	 *
	 * @var string
	 */
	private $post_id;
	
	/**
	 * Name of the WordPress option we're using.
	 *
	 * @var string
	 */
	private $options_list_name;
	
	/**
	 * Array that holds the actual option values.
	 *
	 * @var array
	 */
	public $options_list = array();

	/**
	 * Class constructor.
	 *
	 * @param [integer] $post_id            ID of the WordPress post we're using.
	 * @param [string]  $options_list_name  Name of the WordPress option we're using.
	 * @param [array]   $default_values     Default values for the options in the list.
	 * @param [array]   $casts              Data type for the options in the list. Defaults to string.
	 */
	private function __construct( $post_id, $options_list_name, $default_values = null, $casts = null ) {
		
		$this->post_id = $post_id;

		$this->options_list_name = $options_list_name;

		if ( ! is_null( $default_values ) ) {
			$this->default_values = $default_values;
		}
	
		if ( ! is_null( $casts ) ) {
			$this->casts = $casts;
		}
		
		$this->fill();
	}

	/**
	 * Singleton to get or create an instance of this class.
	 *
	 * @param [integer] $post_id            ID of the WordPress post we're using.
	 * @param [string]  $options_list_name  Name of the WordPress option we're using.
	 * @param [array]   $default_values     Default values for the options in the list.
	 * @param [array]   $casts              Data type for the options in the list. Defaults to string.
	 * @return Options_Post_Meta;
	 */
	public static function instance( $post_id = null, $options_list_name = null, $default_values = null, $casts = null ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Options_Post_Meta( $post_id, $options_list_name, $default_values, $casts );
		}

		return self::$instance;
	}

	/**
	 * Fill list with either the passed array or from the values stored in WordPress. 
	 *
	 * @param [array] $options_list Array of values to load into the list.
	 * @return void
	 */
	public function fill( $options_list = null ) {
	
		if ( is_null( $options_list ) ) {
			// Load from WordPress.
			$options_list = get_post_meta( $this->post_id, $this->options_list_name, null );
		}
		
	
		if ( ! is_array( $options_list ) ) {
			if ( ! is_null( $options_list ) ) {
				$options_list = array( $options_list );
			} else {
				$options_list = array();
			}
		} 

		
		$options_list = array_merge( $this->default_values, $options_list );
		
		foreach ( $options_list as $name => $value ) {

			$cast_value                  = $this->transform_for_set( $name, $value );
			$this->options_list[ $name ] = $cast_value;

		}
	}


	/**
	 * Returns the value from the list.
	 *
	 * @param string $name of the value to return.
	 * @throws \Exception If the name doesn't exist in the list.
	 * @return mixed 
	 */
	public function get( $name ) {

		if ( array_key_exists( $name, $this->options_list ) ) {

			$value = $this->options_list[ $name ];

			return $this->transform_for_get( $name, $value );
			
		} else {

			throw new \Exception( esc_html( $name . ' is not a valid option.' ) );
		}
	}

	/**
	 * Sets the value in the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of the list item.
	 * @param [mixed]  $value The value of the list item.
	 * @return void
	 */
	public function set( $name, $value ) {
		
		$sanitized_value             = $this->transform_for_set( $name, $value );
		$this->options_list[ $name ] = $sanitized_value;
		
		update_post_meta( $this->post_id, $this->options_list_name, $this->options_list );
	}

	/**
	 * Remove the value from the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of list item.
	 * @return void
	 */
	public function delete( $name ) {

		if ( array_key_exists( $name, $this->options_list ) ) {
			unset( $this->options_list[ $name ] );
			
			update_post_meta( $this->post_id, $this->options_list_name, $this->options_list );
		}
	}

	/**
	 * Remove all values from the list then deletes the option in the WP database.
	 *
	 * @return void
	 */
	public function delete_all() {
		$this->options_list_name = array();
		delete_post_meta( $this->post_id, $this->options_list_name );
	}

	/**
	 * Gets a list of all the list item names.
	 *
	 * @return array
	 */
	public function names() {
		return array_keys( $this->options_list );
	}

	/**
	 * Gets a list of all the name/value pairs in the list.
	 *
	 * @return array
	 */
	public function as_array() {
		return $this->options_list;
	}

	/**
	 * Forces the value stored in the list to be of the type that we expect.
	 *
	 * @param [string] $name Name of the list item.
	 * @param [mixed]  $value Value of the list item.
	 * @throws \Exception When cast fails.
	 * @return mixed
	 */
	private function transform_for_set( $name, $value ) {
		
		$type = $this->casts[ $name ];

		switch ( $type ) {
		
			case 'bool':
				return (bool) $value;

			case 'number':
				return (float) $value;
			
			case 'array':
				if ( is_array( $value ) ) {
					return $value;
				}
				throw new \Exception( esc_html( $name . ' cannot be cast to array.' ) );

			case 'json':
				return wp_json_encode( $value );
				
			default:
				return (string) $value;

		}
	}


	/**
	 * Forces the returned value to be of the type that we expect.
	 *
	 * @param [string] $name Name of the list item.
	 * @param [mixed]  $value Value of the list item.
	 * @throws \Exception When the item doesn't exist in the list.
	 * @return mixed
	 */
	private function transform_for_get( $name, $value ) {
		
		if ( ! array_key_exists( $name, $this->casts ) ) {
			throw new \Exception( esc_html( $name . ' is not a valid option.' ) );
		}
		
		$type = $this->casts[ $name ];

		switch ( $type ) {
	
			case 'json': 
				// transforms json to an array.
				return json_decode( (string) $value, true );
	
			default:
				return $value;
				
		}
	}
}
