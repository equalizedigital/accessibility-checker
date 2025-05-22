<?php
/**
 * Test class for Frontend Highlight functionality.
 *
 * @package EDAC\Tests\Admin
 * @since 1.0.0
 */

namespace EDAC\Tests\Admin;

use EDAC\Admin\Frontend_Highlight;
use EDAC\Admin\Helpers; // Assuming Helpers class is in this namespace.
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use BrainMonkey\Functions;
use BrainMonkey\Actions;
use BrainMonkey\Filters;
use Mockery;

/**
 * Test class for EDAC\Admin\Frontend_Highlight.
 *
 * @since 1.0.0
 */
class FrontendHighlightTest extends TestCase {
	/**
	 * Instance of Frontend_Highlight class.
	 *
	 * @var Frontend_Highlight
	 */
	protected $instance;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Initialize mock instance before any database operations.
		$this->instance = new Frontend_Highlight();
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close(); // Important for cleaning up Mockery expectations.
		parent::tearDown();
	}

	/**
	 * Test that init_hooks adds the ajax action.
	 *
	 * @return void
	 */
	public function testInitHooksAddsAjaxAction() {
		Actions\expectAdded( 'wp_ajax_edac_frontend_highlight_ajax' )->with( [ $this->instance, 'ajax' ] );
		Filters\when( 'edac_filter_frontend_highlighter_visibility' )->justReturn( false );

		$this->instance->init_hooks();
	}

	/**
	 * Test that init_hooks adds nopriv ajax action when filter returns true.
	 *
	 * @return void
	 */
	public function testInitHooksAddsNoprivAjaxActionWhenFilterReturnsTrue() {
		Actions\expectAdded( 'wp_ajax_edac_frontend_highlight_ajax' )->with( [ $this->instance, 'ajax' ] );
		Actions\expectAdded( 'wp_ajax_nopriv_edac_frontend_highlight_ajax' )->with( [ $this->instance, 'ajax' ] );
		Filters\when( 'edac_filter_frontend_highlighter_visibility' )->justReturn( true );

		$this->instance->init_hooks();
	}
	
	/**
	 * Test that init_hooks does not add nopriv ajax action when filter returns false.
	 *
	 * @return void
	 */
	public function testInitHooksDoesNotAddNoprivAjaxActionWhenFilterReturnsFalse() {
		Actions\expectAdded( 'wp_ajax_edac_frontend_highlight_ajax' )->with( [ $this->instance, 'ajax' ] );
		Actions\expectNotAdded( 'wp_ajax_nopriv_edac_frontend_highlight_ajax' );
		Filters\when( 'edac_filter_frontend_highlighter_visibility' )->justReturn( false );

		$this->instance->init_hooks();
	}

	/**
	 * Test that get_issues returns null when no results found.
	 *
	 * @return void
	 */
	public function testGetIssuesReturnsNullWhenNoResults() {
		$wpdb_mock         = Mockery::mock( 'WPDB' );
		$wpdb_mock->prefix = 'wp_';
		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT * FROM wp_accessibility_checker' );
		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn( null );
		
		// Use Mockery's aliasing to mock global $wpdb.
		Mockery::mock( 'alias:WPDB' )->shouldReceive( 'getInstance' )->andReturn( $wpdb_mock );
		
		Functions\when( 'get_current_blog_id' )->justReturn( 1 );

		$this->assertNull( $this->instance->get_issues( 1 ) );
	}

	/**
	 * Test that get_issues filters results.
	 *
	 * @return void
	 */
	public function testGetIssuesFiltersResults() {
		$wpdb_mock         = Mockery::mock( 'WPDB' );
		$wpdb_mock->prefix = 'wp_';
		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT * FROM wp_accessibility_checker' );
		$mock_results = [
			[
				'rule'     => 'test_rule1',
				'ignre'    => false,
				'object'   => 'obj1',
				'ruletype' => 'error',
			],
		];
		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn( $mock_results );
		
		// Use Mockery's aliasing to mock global $wpdb.
		Mockery::mock( 'alias:WPDB' )->shouldReceive( 'getInstance' )->andReturn( $wpdb_mock );
		
		Functions\when( 'get_current_blog_id' )->justReturn( 1 );

		// Mock the Helpers class static method.
		Mockery::mock( 'alias:EDAC\Admin\Helpers' );
		Helpers::shouldReceive( 'filter_results_to_only_active_rules' )
			->once()
			->with( $mock_results )
			->andReturn( [ [ 'filtered_rule' => 'yes' ] ] );

		$result = $this->instance->get_issues( 1 );
		$this->assertEquals( [ [ 'filtered_rule' => 'yes' ] ], $result );
	}
	
	/**
	 * Test that ajax fails when nonce verification fails.
	 *
	 * @return void
	 */
	public function testAjaxNonceFailure() {
		$_REQUEST['nonce'] = 'bad_nonce';
		Functions\when( 'sanitize_text_field' )->justReturn( 'bad_nonce' );
		Functions\when( 'wp_verify_nonce' )->once()->with( 'bad_nonce', 'ajax-nonce' )->andReturn( false );
		Functions\expect( 'wp_send_json_error' )->once()->with(
			Mockery::on(
				function ( $error ) {
					return $error instanceof \WP_Error && $error->get_error_code() === '-1';
				}
			)
		);

		$this->instance->ajax();
	}

	/**
	 * Test that ajax fails when post_id is missing.
	 *
	 * @return void
	 */
	public function testAjaxMissingPostId() {
		unset( $_REQUEST['post_id'] ); 
		$_REQUEST['nonce'] = 'good_nonce';
		Functions\when( 'sanitize_text_field' )->justReturn( 'good_nonce' );
		Functions\when( 'wp_verify_nonce' )->once()->with( 'good_nonce', 'ajax-nonce' )->andReturn( true );
		Functions\expect( 'wp_send_json_error' )->once()->with(
			Mockery::on(
				function ( $error ) {
					return $error instanceof \WP_Error && $error->get_error_code() === '-2';
				}
			)
		);

		$this->instance->ajax();
	}

	/**
	 * Test that ajax fails when get_issues returns no results.
	 *
	 * @return void
	 */
	public function testAjaxGetIssuesReturnsNoResults() {
		$_REQUEST['nonce']   = 'good_nonce';
		$_REQUEST['post_id'] = 123;
		Functions\when( 'sanitize_text_field' )->justReturn( 'good_nonce' );
		Functions\when( 'wp_verify_nonce' )->once()->with( 'good_nonce', 'ajax-nonce' )->andReturn( true );

		$mock_instance = Mockery::mock( Frontend_Highlight::class )->makePartial();
		$mock_instance->shouldAllowMockingProtectedMethods();
		$mock_instance->shouldReceive( 'get_issues' )->once()->with( 123 )->andReturn( null );
		
		Functions\expect( 'wp_send_json_error' )->once()->with(
			Mockery::on(
				function ( $error ) {
					return $error instanceof \WP_Error && $error->get_error_code() === '-3';
				}
			)
		);

		$mock_instance->ajax();
	}
	
	/**
	 * Test the basic success path of the ajax request.
	 *
	 * @return void
	 */
	public function testAjaxSuccessPathBasic() {
		$_REQUEST['nonce']   = 'good_nonce';
		$_REQUEST['post_id'] = 123;
		Functions\when( 'sanitize_text_field' )->justReturn( 'good_nonce' );
		Functions\when( 'wp_verify_nonce' )->once()->with( 'good_nonce', 'ajax-nonce' )->andReturn( true );

		$mock_issues_data = [
			[
				'rule'     => 'mock_rule',
				'ignre'    => false,
				'object'   => '<p>Test</p>',
				'ruletype' => 'error',
				'id'       => 1,
			],
		];
		
		$mock_instance = Mockery::mock( Frontend_Highlight::class )->makePartial();
		$mock_instance->shouldAllowMockingProtectedMethods();
		$mock_instance->shouldReceive( 'get_issues' )->once()->with( 123 )->andReturn( $mock_issues_data );

		Functions\when( 'edac_register_rules' )->justReturn(
			[
				[
					'slug'      => 'mock_rule',
					'title'     => 'Mock Rule',
					'summary'   => 'Summary',
					'rule_type' => 'error',
					'fixes'     => [],
				],
			]
		);

		Functions\when( 'edac_filter_by_value' )->andReturnUsing(
			function ( $items, $key, $value ) {
				foreach ( $items as $item ) {
					if ( isset( $item[ $key ] ) && $item[ $key ] === $value ) {
						return [ $item ]; // Return as array of items like original function.
					}
				}
				return [];
			}
		);
		Functions\when( 'edac_documentation_link' )->justReturn( 'http://example.com/doc' );
		Functions\when( 'html_entity_decode' )->justReturnArgument( 0 );
		Functions\when( 'esc_html' )->justReturnArgument( 0 );
		
		$fixes_manager_mock = Mockery::mock( 'alias:EqualizeDigital\AccessibilityChecker\Fixes\FixesManager' );
		$fixes_manager_mock->shouldReceive( 'get_instance' )->andReturnSelf();
		$fixes_manager_mock->shouldReceive( 'get_fix' )->andReturn( null );

		Functions\expect( 'wp_send_json_success' )->once();

		$mock_instance->ajax();
	}
}
