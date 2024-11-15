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
class FixesManagerTest extends TestCase {

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
		$fix_mock = $this->createMock( FixInterface::class );
		$fix_mock->method( 'get_fields_array' )->willReturn(
			[
				'field1' => [ 'default' => 'value1' ],
				'field2' => [ 'default' => 'value2' ],
			]
		);
		$fix_mock->method( 'get_slug' )->willReturn( 'mock_fix' );
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
		$fix_mock = $this->createMock( FixInterface::class );
		$fix_mock->method( 'get_fields_array' )->willReturn(
			[
				'field1' => [ 'default' => 'default_value1' ],
				'field2' => [ 'default' => 'default_value2' ],
			]
		);
		$fix_mock->method( 'get_slug' )->willReturn( 'mock_fix' );

		$fixes_manager = FixesManager::get_instance();
		$reflection    = new ReflectionClass( $fixes_manager );
		$fixes_propert = $reflection->getProperty( 'fixes' );
		$fixes_propert->setAccessible( true );
		$fixes_propert->setValue( $fixes_manager, [ 'mock_fix' => $fix_mock ] );

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
}
