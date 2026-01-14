<?php
/**
 * REST API sidebar data behavior tests.
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\REST_Api;
use EDAC\Admin\Update_Database;

/**
 * Tests for REST_Api sidebar data helpers.
 *
 * @group rest
 */
class RestApiSidebarDataTest extends WP_UnitTestCase {
	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Post ID used for tests.
	 *
	 * @var int
	 */
	protected static $post_id;

	/**
	 * REST server instance for dispatching requests.
	 *
	 * @var WP_REST_Server|null
	 */
	private $server;

	/**
	 * Added filter callback reference for cleanup.
	 *
	 * @var callable|null
	 */
	private $rules_filter;

	/**
	 * Create shared fixtures for this test class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		update_option( 'edac_post_types', [ 'post' ] );

		// Ensure plugin DB table exists for tests.
		( new Update_Database() )->edac_update_database();

		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$post_id  = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Sidebar Data Post',
				'post_content' => '<p>Content for sidebar data tests.</p>',
			]
		);
	}

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		// Silence duplicate block registration notices triggered across tests.
		$this->setExpectedIncorrectUsage( 'WP_Block_Bindings_Registry::register' );
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );

		do_action( 'init' );
		do_action( 'rest_api_init' );
		$this->server = rest_get_server();
		wp_set_current_user( self::$admin_id );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		if ( $table_name ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table_name, [ 'postid' => self::$post_id ], [ '%d' ] );
		}

		if ( $this->rules_filter ) {
			remove_filter( 'edac_filter_register_rules', $this->rules_filter );
			$this->rules_filter = null;
		}

		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Verify get_summary_data returns defaults when meta is missing.
	 */
	public function test_get_summary_data_returns_defaults() {
		$api      = new REST_Api();
		$method   = $this->get_private_method( $api, 'get_summary_data' );
		$summary  = $method->invoke( $api, self::$post_id );
		$expected = [
			'passed_tests'    => 0,
			'errors'          => 0,
			'contrast_errors' => 0,
			'warnings'        => 0,
			'ignored'         => 0,
			'readability'     => 0,
		];

		$this->assertSame( $expected, $summary );
	}

	/**
	 * Verify get_summary_data returns stored meta when available.
	 */
	public function test_get_summary_data_uses_meta() {
		$meta = [
			'passed_tests'    => 5,
			'errors'          => 2,
			'contrast_errors' => 1,
			'warnings'        => 3,
			'ignored'         => 4,
			'readability'     => 7,
		];
		update_post_meta( self::$post_id, '_edac_summary', $meta );

		$api    = new REST_Api();
		$method = $this->get_private_method( $api, 'get_summary_data' );
		$result = $method->invoke( $api, self::$post_id );

		$this->assertSame( $meta, $result );
	}

	/**
	 * Ensure get_details_data returns counts and passes rules without rows.
	 */
	public function test_get_details_data_counts_and_passed_rules() {
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$this->assertNotNull( $table_name, 'Expected accessibility_checker table to exist for tests.' );

		list( $error_rule, $warning_rule ) = $this->get_sample_rules();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'siteid'        => get_current_blog_id(),
				'rule'          => $error_rule['slug'],
				'postid'        => self::$post_id,
				'object'        => '<div></div>',
				'ruletype'      => 'error',
				'ignre'         => 0,
				'ignre_user'    => null,
				'ignre_date'    => null,
				'ignre_comment' => null,
			],
			[
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
			]
		);

		$api    = new REST_Api();
		$method = $this->get_private_method( $api, 'get_details_data' );
		$data   = $method->invoke( $api, self::$post_id );

		$this->assertArrayHasKey( 'errors', $data );
		$this->assertArrayHasKey( 'warnings', $data );
		$this->assertArrayHasKey( 'passed', $data );

		$this->assertNotEmpty( $data['errors'] );
		$first_error = $data['errors'][0];
		$this->assertSame( $error_rule['slug'], $first_error['slug'] );
		$this->assertSame( 1, $first_error['count'] );

		if ( $warning_rule ) {
			$passed_slugs = wp_list_pluck( $data['passed'], 'slug' );
			$this->assertContains( $warning_rule['slug'], $passed_slugs );
		}
	}

	/**
	 * Ensure process_rules_for_details ignores rows marked as ignored.
	 */
	public function test_process_rules_for_details_skips_ignored() {
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$this->assertNotNull( $table_name );

		list( $error_rule ) = $this->get_sample_rules();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'siteid'        => get_current_blog_id(),
				'rule'          => $error_rule['slug'],
				'postid'        => self::$post_id,
				'object'        => '<span></span>',
				'ruletype'      => 'error',
				'ignre'         => 1,
				'ignre_user'    => null,
				'ignre_date'    => null,
				'ignre_comment' => null,
			],
			[
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
			]
		);

		$passed_rules = [];
		$api          = new REST_Api();
		$method       = $this->get_private_method( $api, 'process_rules_for_details' );

		$rules  = [ $error_rule ];
		$args   = [ $rules, self::$post_id, $table_name, get_current_blog_id(), &$passed_rules ];
		$result = $method->invokeArgs( $api, $args );

		$this->assertSame( [], $result, 'Ignored rows should not produce error entries.' );
		$this->assertCount( 1, $passed_rules, 'Rule should be considered passed when only ignored rows exist.' );
	}

	/**
	 * Verify sidebar-data endpoint returns combined data structure.
	 */
	public function test_get_sidebar_data_endpoint_returns_payload() {
		$this->mock_rules(
			[
				[
					'slug'      => 'test_error',
					'rule_type' => 'error',
				],
			]
		);

		$request = new WP_REST_Request( 'GET', '/accessibility-checker/v1/sidebar-data/' . self::$post_id );
		$request->set_param( 'id', self::$post_id );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'summary', $data['data'] );
		$this->assertArrayHasKey( 'details', $data['data'] );
		$this->assertArrayHasKey( 'readability', $data['data'] );
		$this->assertSame( self::$post_id, $data['data']['post_id'] );
	}

	/**
	 * Helper to expose private methods via reflection.
	 *
	 * @param object $instance Class instance.
	 * @param string $method   Method name.
	 *
	 * @return \ReflectionMethod
	 */
	private function get_private_method( $instance, $method ) {
		$ref = new \ReflectionMethod( $instance, $method );
		$ref->setAccessible( true );
		return $ref;
	}

	/**
	 * Helper to mock rule registry via filter.
	 *
	 * @param array $rules Rules to return from edac_register_rules.
	 * @return void
	 */
	private function mock_rules( array $rules ) {
		$this->rules_filter = function () use ( $rules ) {
			return $rules;
		};
		add_filter( 'edac_filter_register_rules', $this->rules_filter, 10, 0 );
	}

	/**
	 * Helper to fetch a sample error and warning rule from the registry.
	 *
	 * @return array{0:array,1:?array}
	 */
	private function get_sample_rules(): array {
		$rules        = edac_register_rules();
		$error_rule   = null;
		$warning_rule = null;

		foreach ( $rules as $rule ) {
			if ( 'error' === ( $rule['rule_type'] ?? null ) && ! $error_rule ) {
				$error_rule = $rule;
			}
			if ( 'warning' === ( $rule['rule_type'] ?? null ) && ! $warning_rule ) {
				$warning_rule = $rule;
			}
			if ( $error_rule && $warning_rule ) {
				break;
			}
		}

		// Ensure we at least have one error rule for tests.
		$this->assertNotNull( $error_rule, 'Expected at least one error rule.' );

		return [ $error_rule, $warning_rule ];
	}
}
