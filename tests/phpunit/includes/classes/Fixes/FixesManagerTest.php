<?php
/**
 * Test class for FixesManager.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the FixesManager class.
 */
class FixesManagerTest extends WP_UnitTestCase {

	/**
	 * Setup the test environment by resetting the instance before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		// Reset the instance before each test.
		$reflection = new ReflectionClass( FixesManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Tear down the test environment by closing Mockery.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		Mockery::close();
	}

	/**
	 * Test that the instance retuns an empty array when no fixes are registered.
	 *
	 * @return void
	 */
	public function test_get_fixes_settings_returns_empty_array_when_no_fixes() {
		$fixes_manager = FixesManager::get_instance();
		$this->assertEmpty( $fixes_manager->get_fixes_settings() );
	}

	/**
	 * Test that the instance returns the correct structure when fixes are registered.
	 *
	 * @return void
	 */
	public function test_get_fixes_settings_returns_correct_structure() {
		$fix_mock = Mockery::mock( 'EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddFileSizeAndTypeToLinkedFilesFix' );
		$fix_mock->shouldReceive( 'get_slug' )->andReturn( 'mock_fix' );
		$fix_mock->shouldReceive( 'get_fields_array' )->andReturn(
			[
				'field1' => [ 'default' => 'value1' ],
				'field2' => [ 'default' => 'value2' ],
			]
		);
		$fix_mock->is_pro = true;

		$fixes_manager  = FixesManager::get_instance();
		$reflection     = new ReflectionClass( $fixes_manager );
		$fixes_property = $reflection->getProperty( 'fixes' );
		$fixes_property->setAccessible( true );
		$fixes_property->setValue( $fixes_manager, [ 'mock_fix' => $fix_mock ] );

		$expected = [
			'mock_fix' => [
				'fields' => [
					'field1' => 'value1',
					'field2' => 'value2',
				],
				'is_pro' => true,
			],
		];

		$this->assertEquals( $expected, $fixes_manager->get_fixes_settings() );
	}

	/**
	 * Test that the instance returns the default values when options aren't set.
	 *
	 * @return void
	 */
	public function test_get_fixes_settings_uses_default_values() {
		$fix_mock = Mockery::mock( 'EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddFileSizeAndTypeToLinkedFilesFix' );
		$fix_mock->shouldReceive( 'get_slug' )->andReturn( 'mock_fix' );
		$fix_mock->shouldReceive( 'get_fields_array' )->andReturn(
			[
				'field1' => [ 'default' => 'default_value1' ],
				'field2' => [ 'default' => 'default_value2' ],
			]
		);

		$fixes_manager  = FixesManager::get_instance();
		$reflection     = new ReflectionClass( $fixes_manager );
		$fixes_property = $reflection->getProperty( 'fixes' );
		$fixes_property->setAccessible( true );
		$fixes_property->setValue( $fixes_manager, [ 'mock_fix' => $fix_mock ] );

		$expected = [
			'mock_fix' => [
				'fields' => [
					'field1' => 'default_value1',
					'field2' => 'default_value2',
				],
				'is_pro' => false,
			],
		];

		$this->assertEquals( $expected, $fixes_manager->get_fixes_settings() );
	}

	/**
	 * Test that scalar filter output does not trigger warnings during registration.
	 *
	 * @return void
	 */
	public function test_register_fixes_handles_scalar_filter_output() {
		$callback = static function () {
			return 123;
		};
		add_filter( 'edac_filter_fixes', $callback );

		$fixes_manager = FixesManager::get_instance();

		$fixes_manager->register_fixes();
		remove_filter( 'edac_filter_fixes', $callback );

		$this->assertEmpty( $fixes_manager->get_fixes_settings() );
	}

	/**
	 * Test that current and legacy REST routes share the same configuration.
	 *
	 * @return void
	 */
	public function test_register_rest_routes_preserves_legacy_namespace() {
		$registered_routes = rest_get_server()->get_routes();
		$expected_routes   = [
			'/fixes'                               => 'GET',
			'/fixes/update'                        => 'POST',
			'/fix-fields/(?P<slug>[a-zA-Z0-9_-]+)' => 'GET',
		];

		foreach ( $expected_routes as $route => $method ) {
			$current_route = '/accessibility-checker/v1' . $route;
			$legacy_route  = '/edac/v1' . $route;

			$this->assertArrayHasKey( $current_route, $registered_routes );
			$this->assertArrayHasKey( $legacy_route, $registered_routes );

			$current_handler = $registered_routes[ $current_route ][0];
			$legacy_handler  = $registered_routes[ $legacy_route ][0];

			$this->assertArrayHasKey( $method, $current_handler['methods'] );
			$this->assertArrayHasKey( $method, $legacy_handler['methods'] );
			$this->assertTrue( is_callable( $current_handler['callback'] ) );
			$this->assertTrue( is_callable( $legacy_handler['callback'] ) );
			$this->assertSame( $current_handler['callback'], $legacy_handler['callback'] );
			$this->assertSame( $current_handler['permission_callback'], $legacy_handler['permission_callback'] );
		}
	}

	/**
	 * Test that authorized requests receive the same fixes payload from both namespaces.
	 *
	 * @return void
	 */
	public function test_fixes_routes_return_the_same_response() {
		rest_get_server();

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		try {
			$current_response = rest_do_request( '/accessibility-checker/v1/fixes' );
			$legacy_response  = rest_do_request( '/edac/v1/fixes' );

			$this->assertSame( 200, $current_response->get_status() );
			$this->assertSame( 200, $legacy_response->get_status() );
			$this->assertSame( $current_response->get_data(), $legacy_response->get_data() );
		} finally {
			wp_set_current_user( 0 );
			wp_delete_user( $admin_id );
		}
	}
}
