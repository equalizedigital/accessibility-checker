<?php
/**
 * Class file for managing post options
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class that handles WP post options (meta) for the plugin.
 * 
 * This class provides a standard way to handle the edac data we store for a post. ie:
 *  - Uses a single interface to set/get/delete the data items.
 *  - Lets us define how each data item is stored (grouped into a single record or as separate records).
 *  - Lets us set the default values for grouped items.
 *  - Insures data is of the datatype and value we expect.
 *  - Handles backward compatibility for legacy _edac_summary data.
 *  - Handles backward compatibility when user is running an older version of Pro or Audit that directly accesses options with get_post_metadata/update_post_metadata/delete_post_metadata. 
 */
class Post_Options {

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
	
	

	const ITEMS = array(
		'issue_density'          => array(
			'grouped'  => true,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'issue_density_elements' => array(
			'grouped'  => true,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'issue_density_strlen'   => array(
			'grouped'  => true,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'simplified_summary'     => array(
			'grouped'  => true,
			'datatype' => self::DATATYPE_STRING,
			'default'  => '',
		),
		'readability'            => array(
			'grouped'  => true,
			'datatype' => self::DATATYPE_STRING,
			'default'  => '',
		),
		'anww_update_post_meta'  => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'link_blank'             => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
	
		'contrast_errors'        => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'errors'                 => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'ignored'                => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'passed_tests'           => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'warnings'               => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_NUMBER,
			'default'  => 0,
		),
		'post_checked'           => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
		'post_checked_js'        => array(
			'grouped'  => false,
			'datatype' => self::DATATYPE_BOOLEAN,
			'default'  => false,
		),
	);

	// This is a mapping of the legacy named options to the new named options.
	// We need to handle backward compatibility for legacy named options, ie:
	// get_post_metadata/update_post_metadata/delete_post_metadata must get/update/delete the correct item.
	const LEGACY_NAMES_MAPPING = array(
		// These are stored in a grouped meta record.   
		'_edac_issue_density'           => 'issue_density',
		'_edac_simplified_summary'      => 'simplified_summary',
	
		// These are stored as separate meta records.   
		'edac_anww_update_post_meta'    => 'anww_update_post_meta',
		'_edac_summary_contrast_errors' => 'contrast_errors',
		'_edac_summary_errors'          => 'errors',
		'_edac_summary_ignored'         => 'ignored',
		'_edac_summary_passed_tests'    => 'passed_tests',
		'_edac_summary_warnings'        => 'warnings',
		'_edac_post_checked'            => 'post_checked',
		'_edac_post_checked_js'         => 'post_checked_js',
	);
	

	/**
	 * Id of the post we are working with.
	 *
	 * @var integer
	 */
	private $post_id;

	/**
	 * A list of the grouped items.
	 *
	 * @var array
	 */
	public $grouped_items = array();

	/**
	 * A list of the ungrouped items.
	 *
	 * @var array
	 */
	public $ungrouped_items = array();

	/**
	 * A list of the default values for the grouped items.
	 *
	 * @var array
	 */
	public $grouped_default_values = array();

	/**
	 * A list of the data types for all the items.
	 *
	 * @var array
	 */
	private $data_types = array();

	/**
	 * Array that holds the actual option values.
	 *
	 * @var array
	 */
	private $grouped_items_list = array();

	/**
	 * Constructor for the class.
	 * 
	 * @param integer $post_id The post id we are working with.
	 * @return void
	 */
	public function __construct( $post_id ) {

		
		$this->grouped_items = array_filter( 
			self::ITEMS, 
			fn( $item ) => true === $item['grouped']
		);
	
		$this->ungrouped_items = array_filter( 
			self::ITEMS, 
			fn( $item ) => false === $item['grouped']
		);
	
		$this->grouped_default_values = array_map(
			fn( $item ) => $item['default'],
			$this->grouped_items
		);
	
		$this->data_types = array_map(
			fn( $item ) => $item['datatype'],
			self::ITEMS
		);
	
		$this->post_id = $post_id;
		$this->fill_grouped_items();
	}

	/**
	 * Init the hooks.
	 *
	 * @return void
	 */
	public static function init_hooks() {
		
		// See: https://www.ibenic.com/hook-wordpress-metadata/ .
		
		// Handles determining if post metadata should be added to the database.
		add_filter( 'add_post_metadata', self::class . '::filter_add_post_metadata', 10, 3 );
		add_filter( 'update_post_metadata', self::class . '::filter_update_post_metadata', 10, 4 );
		
		// Handles logic we want to run after after post metadata has been added.
		add_action( 'added_post_meta', self::class . '::on_added_post_meta', 10, 3 );
		add_action( 'updated_post_meta', self::class . '::on_updated_post_meta', 10, 4 );
		
		// Handles modifying post metadata read from the database.
		add_filter( 'get_post_metadata', self::class . '::get_post_metadata', 10, 4 );       
		
		// Handles logic we want to run when the post metadata is deleted.
		add_action( 'delete_post_metadata', self::class . '::delete_post_metadata', 10, 3 );
	}

	public static function disable_hooks() {
		
		// Handles determining if post metadata should be added to the database.
		remove_filter( 'add_post_metadata', self::class . '::filter_add_post_metadata', 10 );
		remove_filter( 'update_post_metadata', self::class . '::filter_update_post_metadata', 10 );
		
		// Handles logic we want to run after after post metadata has been added.
		remove_action( 'added_post_meta', self::class . '::on_added_post_meta', 10 );
		remove_action( 'updated_post_meta', self::class . '::on_updated_post_meta', 10 );
		
		// Handles modifying post metadata read from the database.
		remove_filter( 'get_post_metadata', self::class . '::get_post_metadata', 10 );       
		
		// Handles logic we want to run when the post metadata is deleted.
		remove_action( 'delete_post_metadata', self::class . '::delete_post_metadata', 10 );
	}

	public static function filter_add_post_metadata( $post_id, $name, $value ) {
		return self::filter_add_or_update_post_metadata( $post_id, $name, $value );
	}

	public static function filter_update_post_metadata( $meta_id, $post_id, $name, $value ) {
		return self::filter_add_or_update_post_metadata( $post_id, $name, $value );
	}
	
	public static function filter_add_or_update_post_metadata( $post_id, $name, $value ) {

		if ( null !== self::filter_edac_summary_metadata( $post_id, $name, $value ) ) {
			return false;
		}
		if ( null !== self::filter_legacy_named_metadata( $post_id, $name, $value ) ) {
			return false;
		}

		return null;
	}

	public static function filter_edac_summary_metadata( $post_id, $name, $value ) {
		
		// Special case for legacy _edac_summary b/c it was stored as an array.
		if ( '_edac_summary' === $name ) {
		
			self::disable_hooks();

			// Read the values from legacy _edac_summary array and write to the list.
			$keys         = array( 'passed_tests', 'errors', 'contrast_errors', 'warnings', 'ignored' );
			$post_options = new Post_Options( $post_id );
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $value ) ) {
					$post_options->set( $key, $value[ $key ] );
				}
			}   

			self::init_hooks();
		
			// Don't write the legacy _edac_summary to the database.
			return true;
		}
	
		return null;
	}

	public static function filter_legacy_named_metadata( $post_id, $name, $value ) {

		$legacy_names = array_keys( self::LEGACY_NAMES_MAPPING );
			
		if ( in_array( $name, $legacy_names, true ) ) {
			// This is an update to a legacy named option.
		
			$grouped_items = array_filter( 
				self::ITEMS, 
				fn( $item ) => true === $item['grouped']
			);
			
			if ( array_key_exists( self::LEGACY_NAMES_MAPPING[ $name ], $grouped_items ) ) {

				// This is an update for a grouped item.
	
				self::disable_hooks();

				// set the list option using the non-legacy name.
				$post_options = new Post_Options( $post_id );
				$post_options->set( self::LEGACY_NAMES_MAPPING[ $name ], $value );

				self::init_hooks();

				// Don't write this legacy named meta to the database.
				return false;
		
			
			} else {
	
				$ungrouped_items = array_filter( 
					self::ITEMS, 
					fn( $item ) => false === $item['grouped']
				);
				
				if ( array_key_exists( self::LEGACY_NAMES_MAPPING[ $name ], $ungrouped_items ) ) {

					// This is an update for an ungrouped item.

					self::disable_hooks();

					update_post_meta( $post_id, self::OPTION_NAME . '_' . self::LEGACY_NAMES_MAPPING[ $name ], $value );

					self::init_hooks();

					if ( ! metadata_exists( 'post', $post_id, self::LEGACY_NAMES_MAPPING[ $name ] ) ) {
						// This legacy name meta already exists in this post, so leave it in place.
						return null;
					}

					// Don't write this legacy named meta to the database.
					return false;
							
				}           
			}       
		}

		return null;
	}

	public static function on_added_post_meta( $post_id, $name, $value ) {
		self::on_added_or_updated_post_meta( $post_id, $name, $value );
	}

	public static function on_updated_post_meta( $meta_id, $post_id, $name, $value ) {
		self::on_added_or_updated_post_meta( $post_id, $name, $value );
	}

	public static function on_added_or_updated_post_meta( $post_id, $name, $value ) {
		
		// Special case for post_checked_js. We need to copy to _post_checked_js as it is used as a named field in sql for Pro.
		if ( 'edac_post_checked_js' === $name ) {
		
			// save a copy to _edac_post_checked_js.
			self::disable_hooks();
			update_post_meta( $post_id, '_edac_post_checked_js', $value );
			self::init_hooks();
		}
	}

	public static function get_post_metadata( $check, $post_id, $name, $value ) {
	
		$value = null;

		if ( '_edac_summary' === $name ) {
			// special case for legacy _edac_summary b/c it was stored as an array.

			self::disable_hooks();
	
			$post_options = new Post_Options( $post_id );

			$value = array(
				'passed_tests'    => $post_options->get( 'passed_tests' ),
				'errors'          => $post_options->get( 'errors' ),
				'contrast_errors' => $post_options->get( 'contrast_errors' ),
				'warnings'        => $post_options->get( 'warnings' ),
				'ignored'         => $post_options->get( 'ignored' ),
			);
			
			self::init_hooks();

			return $value;
		}
		/*
		// Handle the legacy options.
		$legacy_names = array_keys( self::LEGACY_NAMES_MAPPING );

		
		if ( in_array( $name, $legacy_names, true ) ) {
			die(self::LEGACY_NAMES_MAPPING[ $name ]);
			die('1234');
			self::disable_hooks();

			$post_options = new Post_Options( $post_id );
			$value        = $post_options->get( self::LEGACY_NAMES_MAPPING[ $name ] );

			self::init_hooks();
	
		} 
		*/
		return $value;
	}

	/*
	 * If there is a legacy delete_post_meta call for one of our items, delete the correct value from the list.
	 *
	 * @param [mixed]   $meta_ids The meta ids.
	 * @param [integer] $post_id The post ID.
	 * @param [string]  $name The key.
	 * @param [mixed]   $value The value to delete.
	 * @return boolean
	 */
	public static function delete_post_metadata( $check, $post_id, $name ) {
		
		if ( self::OPTION_NAME === $name ) {
			return;
		}
	
		// special case for legacy _edac_summary b/c it was stored as an array.
		if ( '_edac_summary' === $name ) {

		
			self::disable_hooks();

			$keys         = array( 'passed_tests', 'errors', 'contrast_errors', 'warnings', 'ignored' );
			$retval       = true;
			$post_options = new Post_Options( $post_id );
			
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $value ) ) {
		
					$result = $post_options->delete( $key );
					if ( false === $result ) {
						$retval = false;
					}
				}
			}

			$post_options->delete( $name );

			self::init_hooks();
			
			return $retval;
			
		}


		// Handle the other legacy options.
		$map      = self::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );

		if ( in_array( $name, $map_keys, true ) ) {
			// The call is for a legacy name.

			// This function does not support the $meta_value parameter.
			if ( '' !== $value ) {
				return false;
			}

			self::disable_hooks();
			$post_options = new Post_Options( $post_id );
			$retval       = $post_options->delete( $map[ $name ] );

			self::enable_hooks();

			return $retval;
		}
	}

	/**
	 * Fill list with the group item values stored in WordPress. 
	 *
	 * @return void
	 */
	public function fill_grouped_items() {
	
		$grouped_items_list = get_post_meta( $this->post_id, self::OPTION_NAME, true );
		if ( ! is_array( $grouped_items_list ) ) {
			$grouped_items_list = array_fill_keys( array_keys( $this->grouped_items ), null );
		}
	
		foreach ( $this->grouped_items as $name => $value ) {
			if ( array_key_exists( $name, $grouped_items_list ) ) {
				$cast_value                        = $this->cast_and_validate( $name, $grouped_items_list[ $name ] );
				$this->grouped_items_list[ $name ] = $cast_value;
			} else {
				$this->grouped_items_list[ $name ] = $this->default_value( $name );
			}
		}
	}


	/**
	 * Returns the default value for a given item.
	 *
	 * @param string $name The name of the item.
	 * @return mixed
	 */
	public function default_value( $name ) {
		return $this->grouped_default_values[ $name ];
	}

	/**
	 * Returns the legacy name for a given item or false if there is no legacy name.
	 *
	 * @param string $name The name of the item.
	 * @return mixed
	 */
	public function legacy_name( $name ) {
		return array_search( $name, self::LEGACY_NAMES_MAPPING, true );
	}

	/**
	 * Returns the datatype for a given item.
	 *
	 * @param string $name The name of the item.
	 * @return mixed
	 */
	public function data_type( $name ) {
		return $this->data_types[ $name ];
	}


	/**
	 * Returns the value from the list.
	 *
	 * @param string  $name of the value to return.
	 * @param boolean $single Whether to return a single value.
	 * @return mixed 
	 */
	public function get( $name, $single = true ) {

		if ( array_key_exists( $name, $this->grouped_items ) ) {
			// non-legacy named grouped item.
			$this->fill_grouped_items(); // in case the list has been updated by update hook or another process.
			$value           = $this->grouped_items_list[ $name ];  
			$sanitized_value = $this->cast_and_validate( $name, $value );
			return $sanitized_value;

		} elseif ( metadata_exists( 'post', $this->post_id, self::OPTION_NAME . '_' . $name ) ) {
				// non-legacy named upgrouped item.
				$value           = get_post_meta( $this->post_id, self::OPTION_NAME . '_' . $name, $single );
				$sanitized_value = $this->cast_and_validate( $name, $value );
				return $sanitized_value;
		} else {
			// legacy named ungrouped item.
			self::disable_hooks();
			if ( metadata_exists( 'post', $this->post_id, $name ) ) {
				self::init_hooks();
				return get_post_meta( $this->post_id, $name, $single );
			}
			self::init_hooks();

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
	public function set( $name, $value ) {
	
		if ( ! array_key_exists( $name, self::ITEMS ) ) {
			throw new \Exception( esc_html( $name . ' is not a valid option.' ) );
		}

		
		if ( array_key_exists( $name, $this->grouped_items ) ) {

			// This is a grouped item.

			self::disable_hooks();

			$sanitized_value                   = $this->cast_and_validate( $name, $value );
			$this->grouped_items_list[ $name ] = $sanitized_value;
			$retval_1                          = update_post_meta( $this->post_id, self::OPTION_NAME, $this->grouped_items_list );
	
			$retval_2 = true;
			$key      = array_search( $name, self::LEGACY_NAMES_MAPPING, true );

			if ( metadata_exists( 'post', $this->post_id, $key ) ) {
				$retval_2 = update_post_meta( $this->post_id, $key, $value );
			}
		
			self::init_hooks();
			
			return ( true === $retval_1 ) && ( true === $retval_2 );
		
		
		} elseif ( array_key_exists( $name, $this->ungrouped_items ) ) {
				// this is not a grouped item, so use the standard update_post_meta.

				self::disable_hooks();
			
				$retval_1 = update_post_meta( $this->post_id, self::OPTION_NAME . '_' . $name, $value );
		
				// Check if this item has a non-grouped legacy named wp option. If so, update it for backward compatibility.
				$retval_2 = true;
				$key      = array_search( $name, self::LEGACY_NAMES_MAPPING, true );
	
			if ( metadata_exists( 'post', $this->post_id, $key ) ) {
				$retval_2 = update_post_meta( $this->post_id, $key, $value );
			}
			
				self::init_hooks();
			
				return ( true === $retval_1 ) && ( true === $retval_2 );
		}       
	}

	/**
	 * Remove the value from the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of list item.
	 * @return boolean True if successful.
	 */
	public function delete( $name ) {

		if ( array_key_exists( $name, $this->grouped_items ) ) {
		
			self::disable_hooks();

			update_post_meta( $this->post_id, self::OPTION_NAME, $this->grouped_items_list );
			
			self::init_hooks();

			return true;
		
		} else {
			// this is not a grouped item, so use the standard delete_post_meta.
			self::disable_hooks();

			$retval_1 = delete_post_meta( $this->post_id, self::OPTION_NAME . '_' . $name );
		
			// Check if this item has a non-grouped legacy named wp option. If so, delete it for backward compatibility.
			$retval_2 = true;
			$key      = array_search( $name, self::LEGACY_NAMES_MAPPING, true );
			if ( metadata_exists( 'post', $this->post_id, $key ) ) {
				$retval_2 = delete_post_meta( $this->post_id, $key );
			}

			self::init_hooks();

			return ( true === $retval_1 ) && ( true === $retval_2 );
		}
	}

	/**
	 * Remove all values from the list then delete the option in the WP database.
	 *
	 * @return boolean True if successful.
	 */
	public function delete_all() {
		$this->grouped_items_list = array();

		self::disable_hooks();

		$retval = delete_post_meta( $this->post_id, self::OPTION_NAME );

		self::init_hooks();

		return $retval;
	}
	
	/**
	 * Gets a list of all the name/value pairs in the list.
	 *
	 * @return array
	 */
	public function as_array() {
		$this->fill_grouped_items();
		return $this->grouped_items_list;
	}
	
	/**
	 * Forces the value stored in the list to be of the type and value we expect.
	 *
	 * @param [string] $name Name of the list item.
	 * @param [mixed]  $value Value of the list item.
	 * @throws \Exception When cast fails.
	 * @return mixed
	 */
	public function cast_and_validate( $name, $value ) {
		
		$type = $this->data_types[ $name ];
		switch ( $type ) {
		
			case self::DATATYPE_STRING:
				return (string) $value;

			case self::DATATYPE_BOOLEAN:
				return (bool) $value;
			
			case self::DATATYPE_NUMBER:
				return (float) $value;
			
			case self::DATATYPE_ARRAY:
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
		
			default:
				return (string) $value;

		}
	}
}
