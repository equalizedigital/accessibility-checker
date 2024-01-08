<?php
/**
 * Class file for managing options
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

//phpcs:disable Generic.Commenting.Todo.TaskFound

/**
 * Class that handles WP options for the plugin.
 * Note: The instance of this class is a Singleton.
 * TODO: Implement a DI Container so we can avoid the use of Singletons.
 */
class Options {

	/**
	 * Name of the WordPress option we're using.
	 *
	 * @var string
	 */
	const OPTIONS_LIST_NAME = 'edac';
	
	/**
	 * The default values.
	 *
	 * @var array [name => value]
	 */
	const DEFAULT_VALUES = array(
		'edac_accessibility_policy_page'            => '',
		'edac_activation_date'                      => 0,
		'edac_add_footer_accessibility_statement'   => '',
		'edac_anww_update_post_meta'                => '',
		'edac_black_friday_2023_notice_dismiss'     => '',
		'edac_db_version'                           => '',
		'edac_delete_data'                          => '',
		'edac_gaad_notice_dismiss'                  => '',
		'edac_include_accessibility_statement_link' => '',
		'edac_local_loopback'                       => false,
		'edac_password_protected'                   => '',
		'edac_password_protected_notice_dismiss'    => '',
		'edac_post_types'                           => array(),
		'edac_simplified_summary_position'          => '',
		'edac_simplified_summary_prompt'            => '',
	);
	
	/**
	 * The variable type for the stored value.
	 *
	 * @var array [name => string|number|bool|array] defaults to string if empty.
	 */
	const CASTS = array(
		'edac_accessibility_policy_page'            => '',
		'edac_activation_date'                      => 'number',
		'edac_add_footer_accessibility_statement'   => '',
		'edac_anww_update_post_meta'                => '',
		'edac_black_friday_2023_notice_dismiss'     => 'bool',
		'edac_db_version'                           => '',
		'edac_delete_data'                          => 'bool',
		'edac_gaad_notice_dismiss'                  => 'bool',
		'edac_include_accessibility_statement_link' => 'bool',
		'edac_local_loopback'                       => 'bool',
		'edac_password_protected'                   => 'bool',
		'edac_password_protected_notice_dismiss'    => 'bool',
		'edac_post_types'                           => 'array',
		'edac_simplified_summary_position'          => '',
		'edac_simplified_summary_prompt'            => '',  
	);
	

	/**
	 * Active instance of this class (singleton).
	 *
	 * @var object
	 */
	private static $instance;

	
	/**
	 * Array that holds the actual option values.
	 *
	 * @var array
	 */
	private $options_list = array();

	/**
	 * Constructor for the class.
	 * 
	 * @return void
	 */
	private function __construct() {
		$this->fill();
	}

	/**
	 * Singleton instance of this class.
	 *
	 * @return Options class instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Options();
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
			$options_list = get_option( self::OPTIONS_LIST_NAME );
		}
	
		if ( is_array( $options_list ) && ! empty( $options_list ) ) {

			$keys = array_keys( $options_list );
			if ( ! is_string( $keys[0] ) ) {
				$options_list = $options_list[0];
			}
		
			$options_list = array_merge( self::DEFAULT_VALUES, $options_list );
		} else {
			$options_list = self::DEFAULT_VALUES;
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
	 * @return void
	 */
	public function set( $name, $value ) {
		
		$sanitized_value             = $this->cast( $name, $value );
		$this->options_list[ $name ] = $sanitized_value;
		
		update_option( self::OPTIONS_LIST_NAME, $this->options_list );
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
			
			update_option( self::OPTIONS_LIST_NAME, $this->options_list );
		}
	}

	/**
	 * Remove all values from the list then deletes the option in the WP database.
	 *
	 * @return void
	 */
	public function delete_all() {
		$this->options_list = array();
		delete_option( self::OPTIONS_LIST_NAME );
	}

	/**
	 * Gets the name of the list. This is the WordPress option's name.
	 *
	 * @return string
	 */
	public function list_name() {
		return self::OPTIONS_LIST_NAME;
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
		
		$type = self::CASTS[ $name ];

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
