<?php
/**
 * Main rest class for the Issues API.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rest;

use EDAC\Admin\Insert_Rule_Data;
use EDAC\Admin\Issues_Query;
use EDAC\Inc\REST_Api;

/**
 * Issues API class.
 */
class Issues_API extends \WP_REST_Controller {

	/**
	 * Query options.
	 *
	 * @var array
	 */
	private $query_options = [];

	/**
	 * Query Data.
	 *
	 * @var array
	 */
	private $query_data = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->api_version = '1';
		$this->namespace   = 'accessibility-checker/v' . $this->api_version;
		$this->rest_base   = 'issues';

		global $wpdb;
		$this->table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );

		$this->query_options = [
			'siteid' => get_current_blog_id(),
			'limit'  => 500,
			'offset' => 0,
		];
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_issues' ],
					'permission_callback' => function ( $request ) {
						return REST_Api::check_token_or_nonce_and_capability_permissions_check( $request, 'manage_options' );
					},
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_issue' ],
					'permission_callback' => function ( $request ) {
						return REST_Api::check_token_or_nonce_and_capability_permissions_check( $request, 'manage_options' );
					},
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_issue' ],
					'permission_callback' => function ( $request ) {
						return REST_Api::check_token_or_nonce_and_capability_permissions_check( $request, 'manage_options' );
					},
					'args'                => [
						'context' => [
							'default' => 'view',
						],
					],
				],
				// To update an item you can use the create method, is this needed?
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_issue' ],
					'permission_callback' => function ( $request ) {
						return REST_Api::check_token_or_nonce_and_capability_permissions_check( $request, 'manage_options' );
					},
					'args'                => $this->get_update_issue_args(),
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_issue' ],
					'permission_callback' => function ( $request ) {
						return REST_Api::check_token_or_nonce_and_capability_permissions_check( $request, 'manage_options' );
					},
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/access-check',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function () {
					return new \WP_REST_Response(
						[
							'success' => true,
						],
						200
					);
				},
				'permission_callback' => function ( $request ) {
					return REST_Api::check_token_or_nonce_and_capability_permissions_check( $request, 'manage_options' );
				},
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/count',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_issues_count' ],
				'permission_callback' => function ( $request ) {
					return REST_Api::check_token_or_nonce_and_capability_permissions_check( $request, 'manage_options' );
				},
			]
		);
	}

	/**
	 * Get a collection of issues.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_issues( $request ) {
		$per_page = min( 500, max( 1, (int) ( $request->get_param( 'per_page' ) ?? 10 ) ) );
		$page     = max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) );

		$this->query_options['offset']    = ( $page - 1 ) * $per_page;
		$this->query_options['limit']     = $per_page;
		$issues                           = $this->do_issues_query( $request->get_param( 'ids' ) ?? [] );
		$this->query_data['issues_count'] = is_countable( $issues ) ? count( $issues ) : 0;

		$data = [];
		foreach ( $issues as $issue ) {
			$issue_data = $this->prepare_item_for_response( $issue, $request );
			$data[]     = $this->prepare_response_for_collection( $issue_data );
		}

		$response = new \WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', $this->query_data['issues_count'] );
		$response->header( 'X-WP-TotalPages', ceil( $this->query_data['issues_count'] / $per_page ) );

		return $response;
	}

	/**
	 * Get a collection of issues.
	 *
	 * Note: I tested using Issues_Query class for this but that class requires changes that could
	 * potentially break back compat. I'm using the following code to get the issues for now.
	 *
	 * @param array $ids List of issue IDs to get.
	 *
	 * @return array Collection of issues.
	 */
	protected function do_issues_query( $ids = [] ) {
		// Verify the ids is just an array of numbers.
		if ( ! is_array( $ids ) ) {
			$ids = [];
		}
		$ids = array_map( 'absint', $ids );

		// Count the total number of issues first. Needed to handle some pagination params.
		$this->query_data['total_issues'] = $this->count_all_issues( $ids );

		global $wpdb;
		$query = '
			SELECT * FROM `' . esc_sql( $this->table_name ) . '`
			WHERE siteid = %d
			' . ( ! empty( $ids ) ? ' AND id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')' : '' ) . '
			ORDER BY id DESC
			LIMIT %d
			OFFSET %d';

		$params = array_merge(
			[ $this->query_options['siteid'] ?? get_current_blog_id() ],
			$ids,
			[ $this->query_options['limit'] ?? 1000, $this->query_options['offset'] ?? 0 ]
		);

		return $wpdb->get_results( $wpdb->prepare( $query, ...$params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Using direct query for getting data from database, caching not required for one time operation.
	}

	/**
	 * Prepare a single issue output for response.
	 *
	 * @param array            $item    Issue object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = [
			'id'            => (int) $item->id,
			'postid'        => (int) $item->postid,
			'siteid'        => (int) $item->siteid,
			'type'          => (string) $item->type,
			'rule'          => (string) $item->rule,
			'ruletype'      => (string) $item->ruletype,
			'object'        => (string) $item->object,
			'recordcheck'   => (bool) $item->recordcheck,
			'created'       => (string) $item->created,
			'user'          => (int) $item->user,
			'ignre'         => (bool) $item->ignre,
			'ignre_global'  => (bool) $item->ignre_global,
			'ignre_user'    => isset( $item->ignre_user ) ? (int) $item->ignre_user : null,
			'ignre_date'    => isset( $item->ignre_date ) ? (string) $item->ignre_date : null,
			'ignre_comment' => isset( $item->ignre_comment ) ? (string) $item->ignre_comment : null,
		];

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );

		return $this->filter_response_by_context( $data, $context );
	}

	/**
	 * Add additional fields to the issue.
	 *
	 * @param array            $data    Issue data.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function add_additional_fields_to_object( $data, $request ) {
		$data['meta'] = [
			'links'      => [
				'self'       => rest_url( '/accessibility-checker/v1/issues/' . $data['id'] ),
				'collection' => rest_url( '/accessibility-checker/v1/issues' ),
			],
			'pagination' => [
				'page'        => (int) $request['page'] ?? 1,
				'per_page'    => (int) $request['per_page'] ?? 10,
				'total_pages' => (int) ceil(
					( $this->query_data['total_issues'] ? $this->query_data['total_issues'] : 1 )
					/ max( 1, (int) ( $request['per_page'] ?? 10 ) )
				),
			],
		];

		// Add some post data to send back in the request.
		// NOTE: maybe in future this is stored in the issue table directly instead of grabbed at request time.
		$data['post_title']     = get_the_title( $data['postid'] );
		$data['post_permalink'] = get_permalink( $data['postid'] );

		$discoverer                  = get_user_by( 'id', $data['user'] );
		$data['discoverer_username'] = $discoverer ? $discoverer->display_name : '';

		// check if there was an ignre_user set and if so get the display_name of that user.
		if ( isset( $data['ignre_user'] ) ) {
			$data['ignre_username'] = get_the_author_meta( 'display_name', $data['ignre_user'] );
		}

		return $data;
	}

	/**
	 * Filter the response data based on the context.
	 *
	 * NOTE: This method is not useful for right now but may be soon.
	 *
	 * @param array  $data    Response data to fiter.
	 * @param string $context Context defined for the response.
	 * @return array Filtered response.
	 */
	public function filter_response_by_context( $data, $context ) {
		$data['context'] = $context;
		return $data;
	}

	/**
	 * Prepare a response for insertion into a collection.
	 *
	 * @param array $response Data to insert.
	 * @return array
	 */
	public function prepare_response_for_collection( $response ) {
		return $response;
	}

	/**
	 * Get a single issue.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_issue( $request ) {
		$id    = (int) $request['id'];
		$issue = $this->do_issues_query( [ $id ] );
		if ( empty( $issue ) ) {
			return new \WP_Error( 'rest_issue_invalid_id', __( 'Invalid issue ID.', 'accessibility-checker' ), [ 'status' => 404 ] );
		}
		// Prepare the first item in the object.
		$data = $this->prepare_item_for_response( $issue[0], $request );
		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Delete a issue.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function delete_issue( $request ) {
		$id    = (int) $request['id'];
		$issue = $this->do_issues_query( [ $id ] );
		if ( empty( $issue ) ) {
			return new \WP_Error( 'rest_issue_invalid_id', __( 'Invalid issue ID.', 'accessibility-checker' ), [ 'status' => 404 ] );
		}
		global $wpdb;
		$deleted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Using direct query for getting data from database, caching not useful for a delete.
			$wpdb->prepare(
				'DELETE FROM `' . esc_sql( $this->table_name ) . '` WHERE id = %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$id
			)
		);

		if ( false === $deleted ) {
			return new \WP_Error( 'rest_issue_delete_failed', __( 'Failed to delete issue.', 'accessibility-checker' ), [ 'status' => 500 ] );
		}

		return new \WP_REST_Response( [ 'success' => true ], 204 );
	}

	/**
	 * Create an issue.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function create_issue( $request ) {
		// Issue must have all these fields in the request.
		$required_fields = [
			'postid',
			'rule',
			'ruletype',
			'object',
			'user',
		];

		// Check if all required fields are present.
		foreach ( $required_fields as $field ) {
			if ( ! array_key_exists( $field, $request ) || empty( $request[ $field ] ) ) {
				return new \WP_Error( 'rest_issue_invalid_fields', __( 'Missing required fields.', 'accessibility-checker' ), [ 'status' => 400 ] );
			}
		}

		$post = get_post( $request['postid'] );
		if ( ! $post || ! is_a( $post, '\WP_Post' ) ) {
			return new \WP_Error( 'rest_issue_invalid_post', __( 'Invalid post ID.', 'accessibility-checker' ), [ 'status' => 400 ] );
		}

		// Create a new issue.
		// NOTE: the return values of this is strange, need to find a better way to validate what happened.
		$inserted = ( new Insert_Rule_Data() )->insert( $post, $request['rule'], $request['ruletype'], $request['object'] );
		return new \WP_REST_Response(
			[
				'id' => $inserted,
			],
			! is_wp_error( $inserted ) ? 201 : 400
		);
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return [
			'context'  => [
				'default' => 'view',
			],
			'page'     => [
				'default'           => 1,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'description'       => __( 'Current page of the collection.', 'accessibility-checker' ),
			],
			'per_page' => [
				'default'           => 10,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'description'       => __( 'Maximum number of items to be returned in result set.', 'accessibility-checker' ),
			],
			'ids'      => [
				'default'     => [],
				'type'        => 'array',
				'items'       => [
					'type' => 'integer',
				],
				'description' => __( 'Optionally a list of issue IDs to get.', 'accessibility-checker' ),
			],
		];
	}

	/**
	 * Callback to get the count of issues.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_issues_count( $request ) {
		$ids = $request->get_param( 'ids' ) ?? [];
		return new \WP_REST_Response( [ 'count' => $this->count_all_issues( $ids ) ], 200 );
	}

	/**
	 * Count the total issues that we are going to get.
	 *
	 * @param array $ids The list of IDs to count.
	 *
	 * @return mixed
	 */
	public function count_all_issues( array $ids = [] ) {
		global $wpdb;
		$table = esc_sql( $this->table_name );
		return $wpdb->get_var( // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Using direct query for getting data from database, caching not required for one time operation.
			$wpdb->prepare(
				'
				SELECT COUNT(*) FROM `' . $table . '`
				WHERE siteid = %d
				' . ( ! empty( $ids ) ? ' AND id IN (' . implode( ',', array_map( 'absint', $ids ) ) . ')' : '' ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Revisit and write a prepair helper
				$this->query_options['siteid'] ?? get_current_blog_id()
			)
		); // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Update an existing issue.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_issue( \WP_REST_Request $request ) {
		global $wpdb;
		$id = (int) $request['id'];

		// Fetch the existing issue.
		$issue_exists = $this->do_issues_query( [ $id ] );
		if ( empty( $issue_exists ) ) {
			return new \WP_Error( 'rest_issue_invalid_id', __( 'Invalid issue ID.', 'accessibility-checker' ), [ 'status' => 404 ] );
		}

		$params = $request->get_params();

		$allowed_fields = [
			'postid'        => '%d',
			'type'          => '%s',
			'rule'          => '%s',
			'ruletype'      => '%s',
			'object'        => '%s',
			'recordcheck'   => '%d', // Assuming boolean stored as integer 0 or 1
			'user'          => '%d',
			'ignre'         => '%d', // Assuming boolean stored as integer 0 or 1
			'ignre_global'  => '%d', // Assuming boolean stored as integer 0 or 1
			'ignre_user'    => '%d',
			'ignre_date'    => '%s',
			'ignre_comment' => '%s',
		];

		$update_data    = [];
		$update_formats = [];

		foreach ( $allowed_fields as $field_name => $format ) {
			if ( isset( $params[ $field_name ] ) ) {
				$update_data[ $field_name ] = $params[ $field_name ];
				$update_formats[]           = $format;
			}
		}

		if ( empty( $update_data ) ) {
			return new \WP_Error( 'rest_nothing_to_update', __( 'No fields provided to update.', 'accessibility-checker' ), [ 'status' => 400 ] );
		}

		// Construct the SET part of the SQL query.
		$set_clauses = [];
		foreach ( array_keys( $update_data ) as $field_name ) {
			$set_clauses[] = '`' . esc_sql( $field_name ) . '` = ' . $allowed_fields[ $field_name ];
		}
		$set_sql = implode( ', ', $set_clauses );

		$query_values = array_values( $update_data );
		$query_values[] = $id; // For the WHERE clause.

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Using $wpdb->prepare correctly
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$this->table_name}` SET {$set_sql} WHERE `id` = %d",
				...$query_values
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $updated ) {
			return new \WP_Error( 'rest_issue_update_failed', __( 'Failed to update issue.', 'accessibility-checker' ), [ 'status' => 500 ] );
		}

		// Fetch the updated issue data.
		$updated_issue_data = $this->do_issues_query( [ $id ] );
		if ( empty( $updated_issue_data ) ) {
			// This should ideally not happen if the update was successful and ID was valid.
			return new \WP_Error( 'rest_issue_not_found_after_update', __( 'Updated issue could not be retrieved.', 'accessibility-checker' ), [ 'status' => 500 ] );
		}

		$prepared_data = $this->prepare_item_for_response( $updated_issue_data[0], $request );
		return new \WP_REST_Response( $prepared_data, 200 );
	}

	/**
	 * Get the arguments for updating an issue.
	 *
	 * @return array
	 */
	private function get_update_issue_args() {
		return [
			'postid'        => [
				'description'       => __( 'The ID of the post associated with the issue.', 'accessibility-checker' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'required'          => false,
			],
			'type'          => [
				'description'       => __( 'The type of issue (e.g., error, warning).', 'accessibility-checker' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'required'          => false,
			],
			'rule'          => [
				'description'       => __( 'The specific accessibility rule that was violated.', 'accessibility-checker' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'required'          => false,
			],
			'ruletype'      => [
				'description'       => __( 'The type of rule (e.g., EDAC, WCAG2AA).', 'accessibility-checker' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'required'          => false,
			],
			'object'        => [
				'description'       => __( 'The HTML object or element that caused the issue.', 'accessibility-checker' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field', // Or potentially 'wp_kses_post' if HTML is allowed and needs to be saved safely. 'sanitize_text_field' is safer if only plain text is expected.
				'required'          => false,
			],
			'recordcheck'   => [
				'description'       => __( 'Indicates if the record was checked.', 'accessibility-checker' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'required'          => false,
			],
			'user'          => [
				'description'       => __( 'The user ID of the person who discovered or created the issue.', 'accessibility-checker' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'required'          => false,
			],
			'ignre'         => [ // 'ignore' is a reserved keyword in PHP, so 'ignre' is used in the database.
				'description'       => __( 'Whether the issue is ignored.', 'accessibility-checker' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'required'          => false,
			],
			'ignre_global'  => [
				'description'       => __( 'Whether the issue is ignored globally.', 'accessibility-checker' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'required'          => false,
			],
			'ignre_user'    => [
				'description'       => __( 'User ID of the user who ignored the issue.', 'accessibility-checker' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'required'          => false,
			],
			'ignre_date'    => [
				'description'       => __( 'Timestamp (YYYY-MM-DD HH:MM:SS) of when the issue was ignored.', 'accessibility-checker' ),
				'type'              => 'string',
				'format'            => 'date-time',
				'sanitize_callback' => 'sanitize_text_field', // Basic sanitization.
				'validate_callback' => 'rest_validate_date_time', // Validate it's a date-time string.
				'required'          => false,
			],
			'ignre_comment' => [
				'description'       => __( 'Comment provided when ignoring the issue.', 'accessibility-checker' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'required'          => false,
			],
		];
	}
}
