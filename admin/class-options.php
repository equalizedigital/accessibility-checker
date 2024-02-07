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
 * Note: This class is a Singleton.
 * TODO: Implement a DI Container so we can avoid the use of Singletons.
 */
class Options {

	/**
	 * Name of the WordPress option we're using.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'edac';
	
	const DATATYPE_NUMBER  = 'number';
	const DATATYPE_STRING  = 'string';
	const DATATYPE_ARRAY   = 'array';
	const DATATYPE_BOOLEAN = 'boolean';
	const DATATYPE_URL     = 'url';
	


	const ITEMS = array(
		'legacy_options_migrated'              => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'accessibility_policy_page'            => array(
			'datatype' => self::DATATYPE_URL,
			'default'  => '',
		),
		'activation_date'                      => array(
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'add_footer_accessibility_statement'   => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'anww_update_post_meta'                => array(
			'datatype' => self::DATATYPE_STRING,
			'default'  => '',
		),
		'black_friday_2023_notice_dismiss'     => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'db_version'                           => array(
			'datatype' => self::DATATYPE_STRING,
			'default'  => '',
		),
		'delete_data'                          => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'gaad_notice_dismiss'                  => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'include_accessibility_statement_link' => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'local_loopback'                       => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'password_protected'                   => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'password_protected_notice_dismiss'    => array(
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'post_types'                           => array(
			'datatype' => self::DATATYPE_ARRAY,
			'default'  => array( 'post', 'page' ),
		),
		'simplified_summary_position'          => array(
			'datatype' => self::DATATYPE_STRING,
			'default'  => 'after',
		),
		'simplified_summary_prompt'            => array(
			'datatype' => self::DATATYPE_STRING,
			'default'  => 'when required',
		),
		'review_notice'                        => array(
			'datatype' => self::DATATYPE_STRING,
			'default'  => '',
		),
	);

	/**
	 * Mapping of legacy option names to new option names.
	 * Note: Originally these were stored as separate options in the database
	 * and we're migrating them to be stored in a single option. 
	 * 
	 * @var array
	 */ 
	const LEGACY_NAMES_MAPPING = array(
		'edac_accessibility_policy_page'            => 'accessibility_policy_page',
		'edac_activation_date'                      => 'activation_date',
		'edac_add_footer_accessibility_statement'   => 'add_footer_accessibility_statement',
		'edac_anww_update_post_meta'                => 'anww_update_post_meta',
		'edac_black_friday_2023_notice_dismiss'     => 'black_friday_2023_notice_dismiss',
		'edac_db_version'                           => 'db_version',
		'edac_delete_data'                          => 'delete_data',
		'edac_gaad_notice_dismiss'                  => 'gaad_notice_dismiss',
		'edac_include_accessibility_statement_link' => 'include_accessibility_statement_link',
		'edac_local_loopback'                       => 'local_loopback',
		'edac_password_protected'                   => 'password_protected',
		'edac_password_protected_notice_dismiss'    => 'password_protected_notice_dismiss',
		'edac_post_types'                           => 'post_types',
		'edac_simplified_summary_position'          => 'simplified_summary_position',
		'edac_simplified_summary_prompt'            => 'simplified_summary_prompt',
		'edac_review_notice'                        => 'review_notice',

	);

	/**
	 * The instance of the class.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Array that holds the actual option values.
	 *
	 * @var array
	 */
	private static $items_list = array();

	/**
	 * A list of the default values for all the items.
	 *
	 * @var array
	 */
	public static $default_values = array();

	/**
	 * A list of the data types for all the items.
	 *
	 * @var array
	 */
	private static $data_types = array();


	/**
	 * Constructor for the class.
	 * 
	 * @return void
	 */
	private function __construct() {
	}

	/**
	 * Boot the class.
	 *
	 * @return void
	 */
	public static function boot() {  
	
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Options();

			self::$default_values = array_map(
				fn( $item ) => $item['default'],
				self::ITEMS
			);
		
			self::$data_types = array_map(
				fn( $item ) => $item['datatype'],
				self::ITEMS
			);
	
			self::fill();   
	
			if ( self::get( 'legacy_options_migrated' ) !== true ) {
				self::migrate_legacy_options();
			}
		
			self::init_hooks();


		}
	}

	/**
	 * Init hooks for handling a standard option_update (pre_update_option_) and legacy named calls to get_option, update_option and delete_option calls (backward compatibility.)
	 *
	 * @return void
	 */
	public static function init_hooks() {

		// Hook into pre_update_option_ so we can cast and validate the values before they are saved by options.php.
		add_filter( 'pre_update_option_' . self::OPTION_NAME, self::class . '::pre_update_option_hook', 10, 3 );
	
		// Hook into get_option, update_option and delete_option so we can handle those calls if they use a legacy named item.
		add_action( 'get_option', self::class . '::get_option_hook', 10, 3 );
		add_action( 'update_option', self::class . '::update_option_hook', 10, 3 );
		add_action( 'delete_option', self::class . '::delete_option_hook', 10, 1 );
	}

	/**
	 * Handle the casting and validating before options.php saves the option.
	 *
	 * @param [mixed]  $new_value The new value of the option.
	 * @param [mixed]  $old_value The old value of the option.
	 * @param [string] $name The name of the option.
	 * @return array
	 */
	public static function pre_update_option_hook( $new_value, $old_value, $name ) {
	
		if ( self::OPTION_NAME === $name ) {
			
			$items = self::$items_list;
			
			foreach ( $new_value as $key => $value ) {
				// adds the new value to the list if has an expected name.
				if ( array_key_exists( $key, self::ITEMS ) ) {
					// cast and validate the value.
					$items[ $key ] = self::cast_and_validate( $key, $value );
				}           
			}
			
			// update our list with the new values.
			self::$items_list = $items;

			return $items;

		}
	}           

	//phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	//phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	/**
	 * Return the correct value in case get_option is called directly using a legacy name item.
	 *
	 * @param [mixed]   $value The value.
	 * @param [string]  $name The meta key.
	 * @param [boolean] $single Whether to return a single value.
	 * 
	 * @return mixed
	 */
	public static function get_option_hook( $value, $name, $single ) {
	
		if ( self::OPTION_NAME === $name ) {
			return $value;
		}
	
		// Handle the other legacy options.
		$map      = self::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );

		if ( in_array( $name, $map_keys, true ) ) {
			// The call is for a legacy name, pass the value from the list.

			// Prevent a recursive loop.
			remove_action( 'get_option', self::class . '::get_option_hook', 10 );

			$value = Options::get( $map[ $name ] );
		
			// re-add the action we removed.
			add_action( 'get_option', self::class . '::get_option_hook', 10, 3 );
	
		}
	
		return $value;
	}
	//phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	//phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	

	/**
	 * Update the correct value in case update_option is called directly using a legacy name item.
	 *
	 * @param [mixed]  $name The meta key.
	 * @param [string] $old_value The old value.
	 * @param [string] $value The new value.
	 * @return boolean
	 */
	public static function update_option_hook( $name, $old_value, $value ) {

		if ( self::OPTION_NAME === $name ) {
			return;
		}

		// Handle the other legacy options.
		$map      = self::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );
	
		if ( in_array( $name, $map_keys, true ) ) {
			// This is an update to a legacy named option.

			// Prevent a recursive loop.
			remove_action( 'update_option', self::class . '::update_hook', 10 );

			if ( array_key_exists( $map[ $name ], self::$items_list ) ) {
		
				// set the list option using the non-legacy name.
				$retval = Options::set( $map[ $name ], $value );
	
				// re-add the action we removed.
				add_action( 'update_option', self::class . '::update_hook', 10, 3 );

				return $retval;

			}
		}
	}

	/**
	 * Delete the correct value in case delete_option is called directly using a legacy name item.
	 *
	 * @param [string] $name The meta key.
	 * @return boolean
	 */
	public static function delete_option_hook( $name ) {

		if ( self::OPTION_NAME === $name ) {
			return;
		}
	
		// Handle the other legacy options.
		$map      = self::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );
		
		if ( in_array( $name, $map_keys, true ) ) {
			// This is a delete to a legacy named option.
	
			// Prevent a recursive loop.
			remove_action( 'delete_option', self::class . '::delete_hook', 10 );
	
			if ( array_key_exists( $map[ $name ], self::$items_list ) ) {
			
				// set the list option using the non-legacy name.
				$retval = Options::delete( $map[ $name ] );
			
				// re-add the action we removed.
				add_action( 'delete_option', self::class . '::delete_hook', 10, 1 );
	
				return $retval;
	
			}
		}
	}
	
	/**
	 * Fill list from the values stored in WordPress. 
	 *
	 * @return void
	 */
	private static function fill() {
	
		$array = get_option( self::OPTION_NAME, array() );
		
		if ( ! is_array( $array ) ) {
			$array = array();
		}

		$items = array();
		foreach ( array_keys( self::ITEMS ) as $name ) {
			
			if ( array_key_exists( $name, $array ) ) {
				$value = $array[ $name ];
			} else {
				$value = null;
			}

	
			$items[ $name ] = self::cast_and_validate( $name, $value );
		}

		self::$items_list = $items;
	}

	/**
	 * Returns the default value for the given name.
	 *
	 * @param string $name The name of the value to return.
	 * @return mixed
	 */
	public static function default_value( $name ) {
		return self::$default_values[ $name ];
	}

	/**
	 * Returns the value from the list. If the value doesn't exist, returns null.
	 *
	 * @param string $name of the value to return.
	 * @return mixed 
	 */
	public static function get( $name ) {

		if ( array_key_exists( $name, self::$items_list ) ) {
			return self::cast_and_validate( $name, self::$items_list[ $name ] );
		} else {
			return null;
		}
	}

	/**
	 * Sets the value in the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of the list item.
	 * @param [mixed]  $value The value of the list item.
	 * @return boolean True if successful.
	 * @throws \Exception When the option is not valid.
	 */
	public static function set( $name, $value ) {

		$sanitized_value           = self::cast_and_validate( $name, $value );
		self::$items_list[ $name ] = $sanitized_value;

		// only allow setting of known options.
		if ( ! array_key_exists( $name, self::ITEMS ) ) {
			throw new \Exception( esc_html( $name . ' is not a valid option.' ), 100 );
		}

		return update_option( self::OPTION_NAME, self::$items_list );
	}

	/**
	 * Remove the value from the list then saves the list to the WP database.
	 *
	 * @param [string] $name The name of list item.
	 * @return boolean True if successful.
	 */
	public static function delete( $name ) {

		if ( array_key_exists( $name, self::$items_list ) ) {
			unset( self::$items_list[ $name ] );
			
			return update_option( self::OPTION_NAME, self::$items_list );
		}
	}

	/**
	 * Remove all values from the list then deletes the option in the WP database.
	 *
	 * @param boolean $multisite Whether to delete the option from the network.
	 * @return boolean True if successful.
	 */
	public static function delete_all( $multisite = false ) {
		self::$items_list = array();
		
		$retval = delete_option( self::OPTION_NAME );

		if ( $multisite ) {
			delete_site_option( self::OPTION_NAME );
		}

		return $retval;
	}

	/**
	 * Gets the name of the list. This is the WordPress option's name.
	 *
	 * @return string
	 */
	public static function list_name() {
		return self::OPTION_NAME;
	}

	/**
	 * Gets a list of all the list item names.
	 *
	 * @return array
	 */
	public static function names() {
		return array_keys( self::ITEMS );
	}

	/**
	 * Gets a list of all the name/value pairs in the list.
	 *
	 * @return array
	 */
	public static function as_array() {
		return self::$items_list;
	}
	
	/**
	 * If needed, migrate legacy options to use this Options class.
	 *
	 * @return void
	 */
	private static function migrate_legacy_options() {
		
		foreach ( self::LEGACY_NAMES_MAPPING as $old_name => $new_name ) {


			$value = get_option( $old_name );

			$retval = self::set( $new_name, $value );
			if ( $retval ) {
				delete_option( $old_name );
			}
		}       
		self::set( 'legacy_options_migrated', true );
	}

	
	/**
	 * Forces the value stored in the list to be of the type and value we expect.
	 *
	 * @param [string] $name Name of the list item.
	 * @param [mixed]  $value Value of the list item.
	 * @return mixed
	 */
	private static function cast_and_validate( $name, $value ) {
		
		$type = self::DATATYPE_STRING;
	
		// Cast the value to the correct type.
		if ( array_key_exists( $name, self::$data_types ) ) {
			$type = self::$data_types[ $name ];
		}
	
		switch ( $type ) {
		
			case self::DATATYPE_STRING:
				$value = (string) $value;
				break;

			case self::DATATYPE_BOOLEAN:
				$value = (bool) $value;
				break;
			
			case self::DATATYPE_NUMBER:
				$value = (float) $value;
				break;
			
			case self::DATATYPE_ARRAY:
				if ( is_array( $value ) ) {
					$value = $value;
				}
				if ( is_string( $value ) ) {
					$value = array( $value );
				}
				if ( ! $value || is_null( $value ) ) {
					$value = array();
				}
				break;
		
			case self::DATATYPE_URL:
				if ( is_string( $value ) ) {
					$value = esc_url_raw( $value );
				}
				if ( ! $value || is_null( $value ) ) {
					$value = '';
				}
				break;
		
			default:
				$value = (string) $value;

		}
	
		// Validate the value.
		switch ( $name ) {
			case 'simplified_summary_position':
				if ( ! in_array( $value, array( 'before', 'after', 'none' ), true ) ) {
					$value = self::default_value( $name );
				}

				break;

			case 'simplified_summary_prompt':
				if ( ! in_array( $value, array( 'always', 'when required', 'none' ), true ) ) {
					$value = self::default_value( $name );
				}

				break;

			case 'post_types':
				$selected_post_types = array();
		
				$all_post_types = \edac_post_types();
		
				if ( is_array( $value ) ) {
					foreach ( $value as $post_type ) {
						if ( in_array( $post_type, $all_post_types, true ) ) {
							$selected_post_types[] = $post_type;
						}
					}
				}

				$value = $selected_post_types;

				break;
		
			case 'add_footer_accessibility_statement':
				$value = (bool) $value;
				break;

			case 'delete_data':
				$value = (bool) $value;
				break;
		
		}

		return $value;
	}
}
