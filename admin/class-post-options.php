<?php
/**
 * Class file for managing post options
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

//phpcs:disable Generic.Commenting.Todo.TaskFound

/**
 * Class that handles WP post options for the plugin.
 */
class Post_Options {

	/**
	 * Name of the WordPress option we're using.
	 *
	 * @var string
	 */
	const OPTIONS_LIST_NAME = 'edac';
	
	/**
	 * The variable type for the stored value.
	 *
	 * @var array [name => string|number|bool|array,url] defaults to string if empty.
	 */
	const CASTS = array(
		'issue_density'          => 'number',
		'issue_density_elements' => 'number',
		'issue_density_strlen'   => 'number',
		'post_checked'           => 'bool',
		'post_checked_js'        => 'bool',
		'simplified_summary'     => '',
		'readability'            => '',
		'contrast_errors'        => 'number',
		'errors'                 => 'number',
		'ignored'                => 'number',
		'passed_tests'           => 'number',
		'warnings'               => 'number',
		'anww_update_post_meta'  => 'bool',
		'link_blank'             => 'bool',
	);

	/**
	 * The default values.
	 *
	 * @var array [name => value]
	 */
	const DEFAULT_VALUES = array(
		'issue_density'          => 0,
		'issue_density_elements' => 0,
		'issue_density_strlen'   => 0,
		'post_checked'           => false,
		'post_checked_js'        => false,
		'summary'                => '',
		'simplified_summary'     => '',
		'readability'            => '',
		'contrast_error'         => 0,
		'errors'                 => 0,
		'ignored'                => 0,
		'passed_tests'           => 0,
		'warnings'               => 0,
		'anww_update_post_meta'  => false,
		'link_blank'             => false,
	);
		
	const LEGACY_OPTION_NAMES_MAPPING = array(
		'_edac_post_checked'            => 'post_checked',
		'_edac_issue_density'           => 'issue_density',
		'_edac_post_checked_js'         => 'post_checked_js',
		'_edac_simplified_summary'      => 'simplified_summary',
		'_edac_summary_contrast_errors' => 'contrast_errors',
		'_edac_summary_errors'          => 'errors',
		'_edac_summary_ignored'         => 'ignored',
		'_edac_summary_passed_tests'    => 'passed_tests',
		'_edac_summary_warnings'        => 'warnings',
		'edac_anww_update_post_meta'    => 'anww_update_post_meta',
	);

	/**
	 * Id of the post we are working with.
	 *
	 * @var integer
	 */
	private $post_id;

	/**
	 * Array that holds the actual option values.
	 *
	 * @var array
	 */
	private $options_list = array();

	/**
	 * Constructor for the class.
	 * 
	 * @param integer $post_id The post id we are working with.
	 * @return void
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
		$this->maybe_migrate_legacy_options();
		$this->fill();
	}

	/**
	 * Boot the class.
	 *
	 * @return void
	 */
	public static function init_hooks() {
		self::handle_legacy_get_post_meta_calls();  
	}

	/**
	 * If there is a legacy get_post_meta call for one of our items, return the correct value from the list.
	 *
	 * @return mixed
	 */
	private static function handle_legacy_get_post_meta_calls() {
		$names = array_keys( self::LEGACY_OPTION_NAMES_MAPPING );
		$map   = self::LEGACY_OPTION_NAMES_MAPPING;

		add_filter(
			'get_post_metadata',
			function ( $metadata, $post_id, $name, $single ) use( $names, $map ) {
				
				// special case for _edac_summary b/c it was stored as an array.
				if ( '_edac_summary' === $name ) {
					$post_options = new Post_Options( $post_id );
		
					$metadata = array(
						'passed_tests'    => $post_options->get( 'passed_tests' ),
						'errors'          => $post_options->get( 'errors' ),
						'contrast_errors' => $post_options->get( 'contrast_errors' ),
						'warnings'        => $post_options->get( 'warnings' ),
						'ignored'         => $post_options->get( 'ignored' ),
					);
					
					if ( $single ) {
						return array( $metadata );
					} else {
						return $metadata;
			
					}
				}
	
				if ( in_array( $name, $names, true ) ) {
					$post_options = new Post_Options( $post_id );
					$metadata     = $post_options->get( $map[ $name ] );
				}
			
				return $metadata;
			},
			PHP_INT_MAX,
			4
		);
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
			$options_list = get_post_meta( $this->post_id, self::OPTIONS_LIST_NAME );
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

			// only allow setting of known options.
			if ( array_key_exists( $name, self::CASTS ) ) {
				$cast_value                  = $this->cast_and_validate( $name, $value );
				$this->options_list[ $name ] = $cast_value;
			}       
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
	 * @return boolean True if successful.
	 * @throws \Exception When cast fails.
	 */
	public function set( $name, $value ) {
		
		// only allow setting of known options.
		if ( ! array_key_exists( $name, self::CASTS ) ) {
			throw new \Exception( esc_html( $name . ' is not a valid post option.' ) );
		}

		$sanitized_value             = $this->cast_and_validate( $name, $value );
		$this->options_list[ $name ] = $sanitized_value;
		return update_post_meta( $this->post_id, self::OPTIONS_LIST_NAME, $this->options_list );
	}

	/**
	 * Remove the value from the list then saves the entire list in the WP database.
	 *
	 * @param [string] $name The name of list item.
	 * @return boolean True if successful.
	 */
	public function delete( $name ) {

		if ( array_key_exists( $name, $this->options_list ) ) {
			unset( $this->options_list[ $name ] );
			
			return update_post_meta( $this->post_id, self::OPTIONS_LIST_NAME, $this->options_list );
		}
	}

	/**
	 * Remove all values from the list then deletes the option in the WP database.
	 *
	 * @return boolean True if successful.
	 */
	public function delete_all() {
		$this->options_list = array();
		
		return delete_post_meta( $this->post_id, self::OPTIONS_LIST_NAME );
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
	 * If needed, migrate legacy options to use this Options class.
	 *
	 * @return void
	 */
	private function maybe_migrate_legacy_options() {
		
		$first_key = key( self::LEGACY_OPTION_NAMES_MAPPING );

		if ( get_option( $first_key ) ) {

			// Legacy options exist. Migrate them.
			foreach ( self::LEGACY_OPTION_NAMES_MAPPING as $old_name => $new_name ) {
				$value  = get_post_meta( $this->post_id, $old_name );
				$retval = $this->set( $new_name, $value );
				if ( $retval ) {
					delete_post_meta( $this->post_id, $old_name );
				}
			}       
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
	private function cast_and_validate( $name, $value ) {
		
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
