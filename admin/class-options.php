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
	const OPTIONS_LIST_NAME = 'edac';
	
	/**
	 * The default values.
	 *
	 * @var array [name => value]
	 */
	const DEFAULT_VALUES = array(
		'accessibility_policy_page'            => '',
		'activation_date'                      => 0,
		'add_footer_accessibility_statement'   => '',
		'anww_update_post_meta'                => '',
		'black_friday_2023_notice_dismiss'     => false,
		'db_version'                           => '',
		'delete_data'                          => '',
		'gaad_notice_dismiss'                  => '',
		'include_accessibility_statement_link' => '',
		'local_loopback'                       => false,
		'password_protected'                   => '',
		'password_protected_notice_dismiss'    => '',
		'post_types'                           => array( 'post', 'page' ),
		'simplified_summary_position'          => 'after',
		'simplified_summary_prompt'            => 'when required',
		'review_notice'                        => '',
	);
	
	/**
	 * The variable type for the stored value.
	 *
	 * @var array [name => string|number|bool|array,url] defaults to string if empty.
	 */
	const CASTS = array(
		'accessibility_policy_page'            => 'url',
		'activation_date'                      => 'number',
		'add_footer_accessibility_statement'   => 'bool',
		'anww_update_post_meta'                => '',
		'black_friday_2023_notice_dismiss'     => 'bool',
		'db_version'                           => '',
		'delete_data'                          => 'bool',
		'gaad_notice_dismiss'                  => 'bool',
		'include_accessibility_statement_link' => 'bool',
		'local_loopback'                       => 'bool',
		'password_protected'                   => 'bool',
		'password_protected_notice_dismiss'    => 'bool',
		'post_types'                           => 'array',
		'simplified_summary_position'          => '',
		'simplified_summary_prompt'            => '', 
		'review_notice'                        => '',
	);
	
	const LEGACY_OPTION_NAMES_MAPPING = array(
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
	 * Array that holds the actual option values.
	 *
	 * @var array
	 */
	private static $options_list = array();

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
		self::fill();   
	}

	
	/**
	 * Fill list with either the passed array or from the values stored in WordPress. 
	 *
	 * @param [array] $options_list Array of values to load into the list.
	 * @return void
	 */
	public static function fill( $options_list = null ) {
	
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

			$cast_value                  = self::cast_and_validate( $name, $value );
			self::$options_list[ $name ] = $cast_value;

		}
	}


	/**
	 * Returns the value from the list. If the value doesn't exist, returns null.
	 *
	 * @param string $name of the value to return.
	 * @return mixed 
	 */
	public static function get( $name ) {

		if ( array_key_exists( $name, self::$options_list ) ) {
			return self::$options_list[ $name ];            
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
	 */
	public static function set( $name, $value ) {
		$sanitized_value             = self::cast_and_validate( $name, $value );
		self::$options_list[ $name ] = $sanitized_value;
		update_option( self::OPTIONS_LIST_NAME, self::$options_list );
		return false;
	}

	/**
	 * Remove the value from the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of list item.
	 * @return boolean True if successful.
	 */
	public static function delete( $name ) {

		if ( array_key_exists( $name, self::$options_list ) ) {
			unset( self::$options_list[ $name ] );
			
			return update_option( self::OPTIONS_LIST_NAME, self::$options_list );
		}
	}

	/**
	 * Remove all values from the list then deletes the option in the WP database.
	 *
	 * @param boolean $multisite Whether to delete the option from the network.
	 * @return boolean True if successful.
	 */
	public static function delete_all( $multisite = false ) {
		self::$options_list = array();
		
		$retval = delete_option( self::OPTIONS_LIST_NAME );

		if ( $multisite ) {
			delete_site_option( self::OPTIONS_LIST_NAME );
		}

		return $retval;
	}

	/**
	 * Gets the name of the list. This is the WordPress option's name.
	 *
	 * @return string
	 */
	public static function list_name() {
		return self::OPTIONS_LIST_NAME;
	}

	/**
	 * Gets a list of all the list item names.
	 *
	 * @return array
	 */
	public static function names() {
		return array_keys( self::$options_list );
	}

	/**
	 * Gets a list of all the name/value pairs in the list.
	 *
	 * @return array
	 */
	public static function as_array() {
		return self::$options_list;
	}
	
	/**
	 * If needed, migrate legacy options to use this Options class.
	 *
	 * @return void
	 */
	public static function maybe_migrate_legacy_options() {
		
		if ( get_option( self::LEGACY_OPTION_NAMES_MAPPING[0] ) ) {
	
			// Legacy options exist. Migrate them.
			foreach ( self::LEGACY_OPTION_NAMES_MAPPING as $old_name => $new_name ) {
				$value = get_option( $old_name );
				self::set( $new_name, $value );
			}       
		}       
	
	
		foreach ( self::LEGACY_OPTION_NAMES_MAPPING as $old_name => $new_name ) {
	
			// TODO remove this.
			// trigger an exception when a legacy option is read so we can find and fix.
			add_filter(
				'pre_option_' . $old_name,
				function ( $legacy_value, $legacy_name ) {
					throw new \Exception( esc_html( 'Legacy option "' . $legacy_name . '" is being read.' ) );
				},
				10,
				2
			);

		}
	}

	
	/**
	 * Forces the value stored in the list to be of the type and value we expect.
	 *
	 * @param [string] $name Name of the list item.
	 * @param [mixed]  $value Value of the list item.
	 * @throws \Exception When cast fails.
	 * @return mixed
	 */
	private static function cast_and_validate( $name, $value ) {
		

		switch ( $name ) {
			case 'simplified_summary_position':
				if ( ! in_array( $value, array( 'before', 'after', 'none' ), true ) ) {
					$value = self::DEFAULT_VALUES[ $name ];
				}

				break;

			case 'simplified_summary_prompt':
				if ( ! in_array( $value, array( 'always', 'when required', 'none' ), true ) ) {
					$value = self::DEFAULT_VALUES[ $name ];
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


		

		$type = self::CASTS[ $name ];
		switch ( $type ) {
		
			case 'string':
				return (string) $value;

			case 'bool':
				return (bool) $value;
			
			case 'number':
				return (float) $value;
			
			case 'array':
				if ( is_array( $value ) ) {
					return $value;
				}
				if ( is_string( $value ) ) {
					return array( $value );
				}
				if ( ! $value || is_null( $value ) ) {
					return array();
				}
				throw new \Exception( esc_html( $name . ' cannot be cast to array.' ) );

			case 'url':
				if ( is_string( $value ) ) {
					return esc_url_raw( $value );
				}
				if ( ! $value || is_null( $value ) ) {
					return '';
				}
				throw new \Exception( esc_html( $name . ' cannot be cast to url.' ) );
	
			default:
				return (string) $value;

		}
	}
}
