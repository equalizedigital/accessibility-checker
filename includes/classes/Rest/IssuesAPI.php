<?php
/**
 * Main rest class for the Issues API.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rest;

use EDAC\Admin\Insert_Rule_Data;
use EDAC\Admin\Issues_Query;

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
			'limit'  => 1000,
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
					'permission_callback' => [ $this, 'get_issues_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_issue' ],
					'permission_callback' => [ $this, 'modify_issue_permissions_check' ],
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
					'permission_callback' => [ $this, 'get_issues_permissions_check' ],
					'args'                => [
						'context' => [
							'default' => 'view',
						],
					],
				],
				// To update an item you can use the create method, is this needed?
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'create_issue' ],
					'permission_callback' => [ $this, 'modify_issue_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_issue' ],
					'permission_callback' => [ $this, 'modify_issue_permissions_check' ],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/access-check',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function () {
					// Returns a 200 response to indicate the API can be accesed.
					return new \WP_REST_Response( [ 'success' => true ], 200 );
				},
				'permission_callback' => [ $this, 'get_issues_permissions_check' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/count',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_issues_count' ],
				'permission_callback' => [ $this, 'get_issues_permissions_check' ],
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
		$per_page = (int) $request->get_param( 'per_page' ) ?? 10;
		$page     = (int) $request->get_param( 'page' ) ?? 1;

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

		// Count the total number of issues first. Needed to handle some pagination params.
		$this->query_data['total_issues'] = $this->count_all_issues( $ids );

		global $wpdb;
		$query = '
			SELECT * FROM ' . $this->table_name . '
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
	 * Check if a given request has access to get items.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_issues_permissions_check( $request ) {
		$token = $request->get_header( 'Authorization' );
		if ( method_exists( 'EDAC\Inc\REST_Api', 'api_token_verify' ) && $token ) {
			$token       = str_replace( 'Bearer ', '', $token );
			$valid_token = \EDAC\Inc\REST_Api::api_token_verify( $token );
			if ( ! $valid_token ) {
				return new \WP_Error( 'rest_forbidden', __( 'Invalid token.', 'accessibility-checker' ), [ 'status' => 401 ] );
			}
		} elseif ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ) ?? $request->get_param( 'nonce' ), 'wp_rest' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'accessibility-checker' ), [ 'status' => 401 ] );
		}
		return true;
	}

	/**
	 * Prepare a single issue output for response.
	 *
	 * @param array            $item    Issue object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data    = [
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
				'total_pages' => (int) ceil( $this->query_data['total_issues'] ? $this->query_data['total_issues'] : 1 / (int) $request['per_page'] ?? 10 ),
			],
		];
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
		// Prepair the first item in the object.
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
		$deleted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for deleting data from database, caching not required for one time operation.
			$wpdb->prepare( 'DELETE FROM %i WHERE id = %d', $this->table_name, $id )
		);

		if ( false === $deleted ) {
			return new \WP_Error( 'rest_issue_delete_failed', __( 'Failed to delete issue.', 'accessibility-checker' ), [ 'status' => 500 ] );
		}

		return new \WP_REST_Response( [ 'success' => true ], 204 );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|boolean
	 */
	public function modify_issue_permissions_check( $request ) {
		// Note: Handle permissions for creating issues here.
		// For now, just return the same permissions as viewing.
		return $this->get_issues_permissions_check( $request );
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
			if ( empty( $request[ $field ] ) ) {
				return new \WP_Error( 'rest_issue_invalid_fields', __( 'Missing required fields.', 'accessibility-checker' ), [ 'status' => 400 ] );
			}
		}

		$post = get_post( $request['postid'] );
		if ( ! $post || ! is_a( $post, '\WP_Post' ) ) {
			return new \WP_Error( 'rest_issue_invalid_post', __( 'Invalid post ID.', 'accessibility-checker' ), [ 'status' => 400 ] );
		}

		// Create a new issue.
		// NOTE: the return values of this is strange, need to find a better way to validate what happened.
		$inserted = ( new Insert_Rule_Data() )->insert( $post, $request['rule'], $request['ruletype'], $request['object'], $request['user'] );
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
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_issues_count( $request ) {
		$ids = $request->get_param( 'ids' ) ?? [];
		return new \WP_REST_Response( [ 'count' => $this->count_all_issues( $ids ) ], 200 );
	}

	/**
	 * Count the total issues that we are going to get.
	 *
	 * @param array $ids
	 *
	 * @return mixed
	 */
	public function count_all_issues( array $ids = [] ) {
		global $wpdb;
		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Using direct query for getting data from database, caching not required for one time operation.
			$wpdb->prepare(
				'
				SELECT COUNT(*) FROM %i
				WHERE siteid = %d
				' . ( ! empty( $ids ) ? ' AND id IN (' . implode( ',', array_map( 'absint', $ids ) ) . ')' : '' ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Revisit and write a prepair helper
				$this->table_name,
				$this->query_options['siteid'] ?? get_current_blog_id()
			)
		);
	}
}
