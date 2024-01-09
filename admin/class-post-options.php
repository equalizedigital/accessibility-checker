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
class Post_Options {

	/**
	 * The default values.
	 *
	 * @var array [name => value]
	 */
	private $default_values = array(
		'_edac_issue_density'          => '',
		'_edac_post_checked'           => false,
		'_edac_post_checked_js'        => false,
		'_edac_summary'                => '',
		'_edac_summary_contrast_error' => '',
		'_edac_summary_errors'         => '',
		'_edac_summary_ignored'        => '',
		'_edac_summary_passed_tests'   => '',
		'_edac_summary_warnings'       => '',
		'edac_anww_update_post_meta'   => false,
		'link_blank'                   => false,
	);
	
	/**
	 * The variable type for the stored value.
	 *
	 * @var array [name => string|number|bool|array] defaults to string if empty.
	 */
	private $casts = array(
		'_edac_issue_density'          => 'number',
		'_edac_post_checked'           => 'bool',
		'_edac_post_checked_js'        => 'bool',
		'_edac_summary'                => '',
		'_edac_summary_contrast_error' => 'number',
		'_edac_summary_errors'         => 'number',
		'_edac_summary_ignored'        => 'number',
		'_edac_summary_passed_tests'   => 'number',
		'_edac_summary_warnings'       => 'number',
		'edac_anww_update_post_meta'   => 'bool',
		'link_blank'                   => 'bool',
	);
	

	/**
	 * The id of the post to which these options are associated.
	 *
	 * @var integer 
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
	private $options_list = array();

	/**
	 * Class constructor.
	 *
	 * @param [integer] $post_id           The id of the post to which these options are associated.
	 * @param [string]  $options_list_name  Name of the WordPress option we're using.
	 * @param [array]   $default_values     Default values for the options in the list.
	 * @param [array]   $casts              Data type for the options in the list. Defaults to string.
	 */
	public function __construct( $post_id, $options_list_name, $default_values = null, $casts = null ) {
		
		$this->post_id           = $post_id;
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
	 * Fill list with either the passed array or from the values stored in WordPress. 
	 *
	 * @param [array] $options_list Array of values to load into the list.
	 * @return void
	 */
	public function fill( $options_list = null ) {
	
		if ( is_null( $options_list ) ) {
			// Load from WordPress.
			$options_list = get_post_meta( $this->post_id, $this->options_list_name );
		}
	
		if ( is_array( $options_list ) && ! empty( $options_list ) ) {

			$keys = array_keys( $options_list );
			if ( ! is_string( $keys[0] ) ) {
				$options_list = $options_list[0];
			}
		
			$options_list = array_merge( $this->default_values, $options_list );
		} else {
			$options_list = $this->default_values;
		}
		
		
		foreach ( $options_list as $name => $value ) {

			$cast_value                  = $this->cast( $name, $value );
			$this->options_list[ $name ] = $cast_value;

		}
	}


	/**
	 * Returns the value from the list. If the value doesn't exist, returns null.
	 *
	 * @param string $name of the value to return.
	 * @return mixed 
	 */
	public function get( $name ) {

		if ( array_key_exists( $name, $this->options_list ) ) {
			return $this->options_list[ $name ];            
		} else {
			return null;
		}
	}

	/**
	 * Sets the value in the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of the list item.
	 * @param [mixed]  $value The value of the list item.
	 * @return boolean True if the value was updated.
	 */
	public function set( $name, $value ) {
		
		$sanitized_value             = $this->cast( $name, $value );
		$this->options_list[ $name ] = $sanitized_value;
		
		return update_option( $this->options_list_name, $this->options_list );
	}

	/**
	 * Remove the value from the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of list item.
	 * @return boolean True if the value was deleted.
	 */
	public function delete( $name ) {

		if ( array_key_exists( $name, $this->options_list ) ) {
			unset( $this->options_list[ $name ] );
			
			return update_post_meta( $this->post_id, $this->options_list_name, $this->options_list );
		}
	}

	/**
	 * Remove all values from the list then deletes the option in the WP database.
	 *
	 * @return void
	 */
	public function delete_all() {
		$this->options_list = array();
		delete_option( $this->options_list_name );
	}

	/**
	 * Gets the name of the list. This is the WordPress option's name.
	 *
	 * @return string
	 */
	public function list_name() {
		return $this->options_list_name;
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
	private function cast( $name, $value ) {
		
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

			default:
				return (string) $value;

		}
	}
}
