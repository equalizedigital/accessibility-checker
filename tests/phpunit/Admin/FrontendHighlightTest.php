<?php
/**
 * Test class for Frontend Highlight functionality.
 *
 * @package EDAC\Tests\Admin
 * @since 1.0.0
 */

namespace EDAC\Tests\Admin;

use EDAC\Admin\Frontend_Highlight;
use EDAC\Admin\Helpers;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey;
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
		Monkey\setUp();
		$this->instance = new Frontend_Highlight();
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test that init_hooks adds the ajax action.
	 *
	 * @return void
	 */
	public function testInitHooksAddsAjaxAction() {
		Monkey\Actions\expects( 'add_action' )
			->once()
			->with( 'wp_ajax_edac_frontend_highlight_ajax', [ $this->instance, 'ajax' ] );
		
		Monkey\Filters\expectApplied( 'edac_filter_frontend_highlighter_visibility' )
			->andReturn( false );

		$this->instance->init_hooks();
	}

	/**
	 * Test that init_hooks adds nopriv ajax action when filter returns true.
	 *
	 * @return void
	 */
	public function testInitHooksAddsNoprivAjaxActionWhenFilterReturnsTrue() {
		Monkey\Actions\expects( 'add_action' )
			->once()
			->with( 'wp_ajax_edac_frontend_highlight_ajax', [ $this->instance, 'ajax' ] );
		Monkey\Actions\expects( 'add_action' )
			->once()
			->with( 'wp_ajax_nopriv_edac_frontend_highlight_ajax', [ $this->instance, 'ajax' ] );
		Monkey\Filters\expectApplied( 'edac_filter_frontend_highlighter_visibility' )
			->andReturn( true );

		$this->instance->init_hooks();
	}
	
	/**
	 * Test that init_hooks does not add nopriv ajax action when filter returns false.
	 *
	 * @return void
	 */
	public function testInitHooksDoesNotAddNoprivAjaxActionWhenFilterReturnsFalse() {
		Monkey\Actions\expects( 'add_action' )
			->once()
			->with( 'wp_ajax_edac_frontend_highlight_ajax', [ $this->instance, 'ajax' ] );
		Monkey\Actions\expects( 'add_action' )
			->never()
			->with( 'wp_ajax_nopriv_edac_frontend_highlight_ajax' );
		Monkey\Filters\expectApplied( 'edac_filter_frontend_highlighter_visibility' )
			->andReturn( false );

		$this->instance->init_hooks();
	}

	/**
	 * Test that get_issues returns null when no results found.
	 *
	 * @return void
	 */
	public function testGetIssuesReturnsNullWhenNoResults() {
		$expected_query = 'SELECT * FROM wp_accessibility_checker';
		
		Monkey\Functions\expect( 'get_current_blog_id' )
			->once()
			->andReturn( 1 );

		// Mock WordPress database functions, including serialization.
		Monkey\Functions\expect( 'maybe_serialize' )
			->andReturnUsing(
				function ( $data ) {
					return $data;
				} 
			);
		
		// Mock database escape functionality.
		Monkey\Functions\when( '_real_escape' )->alias(
			function ( $text ) {
				return addslashes( $text );
			}
		);
		
		// Mock database query results.
		Monkey\Functions\expect( 'get_results' )
			->once()
			->with( $expected_query )
			->andReturn( null );

		$result = $this->instance->get_issues( 1 );
		$this->assertNull( $result );
	}

	/**
	 * Test that get_issues filters results.
	 *
	 * @return void
	 */
	public function testGetIssuesFiltersResults() {
		$mock_results = [
			[
				'rule'     => 'test_rule1',
				'ignre'    => false,
				'object'   => 'obj1',
				'ruletype' => 'error',
			],
		];

		$expected_query = 'SELECT * FROM wp_accessibility_checker';
		
		Monkey\Functions\expect( 'get_current_blog_id' )
			->once()
			->andReturn( 1 );

		// Mock WordPress database functionality.
		Monkey\Functions\expect( 'maybe_serialize' )
			->andReturnUsing(
				function ( $data ) {
					return $data;
				} 
			);
		
		// Mock database escape functionality.
		Monkey\Functions\when( '_real_escape' )->alias(
			function ( $text ) {
				return addslashes( $text );
			}
		);
		
		// Mock database query execution.
		Monkey\Functions\expect( 'get_results' )
			->once()
			->with( $expected_query )
			->andReturn( $mock_results );

		// Set up Helpers class mock.
		$helpers_mock = Mockery::mock( 'alias:EDAC\Admin\Helpers' );
		$helpers_mock->shouldReceive( 'filter_results_to_only_active_rules' )
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
		Monkey\Functions\expect( 'sanitize_text_field' )
			->once()
			->andReturn( 'bad_nonce' );
		Monkey\Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'bad_nonce', 'ajax-nonce' )
			->andReturn( false );
		Monkey\Functions\expect( 'wp_send_json_error' )
			->once()
			->with(
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
		Monkey\Functions\expect( 'sanitize_text_field' )
			->once()
			->andReturn( 'good_nonce' );
		Monkey\Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'good_nonce', 'ajax-nonce' )
			->andReturn( true );
		Monkey\Functions\expect( 'wp_send_json_error' )
			->once()
			->with(
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
		Monkey\Functions\expect( 'sanitize_text_field' )
			->once()
			->andReturn( 'good_nonce' );
		Monkey\Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'good_nonce', 'ajax-nonce' )
			->andReturn( true );

		$mock_instance = Mockery::mock( Frontend_Highlight::class )->makePartial();
		$mock_instance->shouldAllowMockingProtectedMethods();
		$mock_instance->shouldReceive( 'get_issues' )
			->once()
			->with( 123 )
			->andReturn( null );
		
		Monkey\Functions\expect( 'wp_send_json_error' )
			->once()
			->with(
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
		Monkey\Functions\expect( 'sanitize_text_field' )
			->once()
			->andReturn( 'good_nonce' );
		Monkey\Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'good_nonce', 'ajax-nonce' )
			->andReturn( true );

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
		$mock_instance->shouldReceive( 'get_issues' )
			->once()
			->with( 123 )
			->andReturn( $mock_issues_data );

		Monkey\Functions\expect( 'edac_register_rules' )
			->once()
			->andReturn(
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

		Monkey\Functions\expect( 'edac_filter_by_value' )
			->once()
			->andReturnUsing(
				function ( $items, $key, $value ) {
					foreach ( $items as $item ) {
						if ( isset( $item[ $key ] ) && $item[ $key ] === $value ) {
							return [ $item ];
						}
					}
					return [];
				}
			);
		Monkey\Functions\expect( 'edac_documentation_link' )
			->once()
			->andReturn( 'http://example.com/doc' );
		Monkey\Functions\expect( 'html_entity_decode' )
			->once()
			->andReturnArgument( 0 );
		Monkey\Functions\expect( 'esc_html' )
			->once()
			->andReturnArgument( 0 );
		
		$fixes_manager_mock = Mockery::mock( 'alias:EqualizeDigital\AccessibilityChecker\Fixes\FixesManager' );
		$fixes_manager_mock->shouldReceive( 'get_instance' )
			->andReturnSelf();
		$fixes_manager_mock->shouldReceive( 'get_fix' )
			->andReturn( null );

		Monkey\Functions\expect( 'wp_send_json_success' )
			->once();

		$mock_instance->ajax();
	}
}
