<?php
/**
 * Test the ListFixes command.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\ListFixes;

/**
 * Test cases to verify that the ListFixes command lists fixes correctly.
 */
class ListFixesTest extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var ListFixes
	 */
	protected $list_fixes;

	/**
	 * Sets up the mock, injects it into the class under test, and loads fixes.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		if ( ! defined( 'WP_CLI' ) ) {
			define( 'WP_CLI', true );
		}

		$wp_cli           = new Mock_WP_CLI();
		$this->list_fixes = new ListFixes( $wp_cli );

		// Reset and load the FixesManager so fixes are available.
		$reflection = new ReflectionClass( FixesManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		FixesManager::get_instance()->register_fixes();

		parent::setUp();
	}

	/**
	 * Reset the FixesManager singleton after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$reflection = new ReflectionClass( FixesManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * Test the get_name method returns the correct command name.
	 *
	 * @return void
	 */
	public function test_get_name_returns_correct_command_name() {
		$this->assertEquals( 'accessibility-checker list-fixes', ListFixes::get_name() );
	}

	/**
	 * Test the get_shortname method returns the correct short command name.
	 *
	 * @return void
	 */
	public function test_get_shortname_returns_correct_short_command_name() {
		$this->assertEquals( 'edac list-fixes', ListFixes::get_shortname() );
	}

	/**
	 * Test the get_args method returns an array.
	 *
	 * @return void
	 */
	public function test_get_args_returns_array() {
		$this->assertIsArray( ListFixes::get_args() );
	}

	/**
	 * Test the command outputs a success message with JSON data.
	 *
	 * @return void
	 */
	public function test_command_outputs_success_with_json_list() {
		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringStartsWith( 'Success: ', $output );

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );

		$this->assertIsArray( $items );
		$this->assertNotEmpty( $items );
	}

	/**
	 * Test that each listed fix has the expected keys.
	 *
	 * @return void
	 */
	public function test_each_fix_has_expected_keys() {
		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );

		foreach ( $items as $item ) {
			$this->assertArrayHasKey( 'slug', $item );
			$this->assertArrayHasKey( 'name', $item );
			$this->assertArrayHasKey( 'status', $item );
			$this->assertArrayHasKey( 'type', $item );
		}
	}

	/**
	 * Test that each fix's status is either 'enabled' or 'disabled'.
	 *
	 * @return void
	 */
	public function test_each_fix_status_is_valid() {
		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );

		foreach ( $items as $item ) {
			$this->assertContains( $item['status'], [ 'enabled', 'disabled' ] );
		}
	}

	/**
	 * Test that each fix's type is either 'free' or 'pro'.
	 *
	 * @return void
	 */
	public function test_each_fix_type_is_valid() {
		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );

		foreach ( $items as $item ) {
			$this->assertContains( $item['type'], [ 'free', 'pro' ] );
		}
	}

	/**
	 * Test that the skip_link fix appears in the list (a known free fix).
	 *
	 * @return void
	 */
	public function test_known_free_fix_appears_in_list() {
		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );
		$slugs     = array_column( $items, 'slug' );

		$this->assertContains( 'skip_link', $slugs );
	}

	/**
	 * Test that a fix enabled via option shows as 'enabled' in the list.
	 *
	 * @return void
	 */
	public function test_enabled_fix_shows_as_enabled_in_list() {
		update_option( 'edac_fix_add_skip_link', '1' );

		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );

		$skip_link = null;
		foreach ( $items as $item ) {
			if ( 'skip_link' === $item['slug'] ) {
				$skip_link = $item;
				break;
			}
		}

		$this->assertNotNull( $skip_link );
		$this->assertEquals( 'enabled', $skip_link['status'] );

		delete_option( 'edac_fix_add_skip_link' );
	}

	/**
	 * Test that a fix disabled via option shows as 'disabled' in the list.
	 *
	 * @return void
	 */
	public function test_disabled_fix_shows_as_disabled_in_list() {
		delete_option( 'edac_fix_add_skip_link' );

		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );

		$skip_link = null;
		foreach ( $items as $item ) {
			if ( 'skip_link' === $item['slug'] ) {
				$skip_link = $item;
				break;
			}
		}

		$this->assertNotNull( $skip_link );
		$this->assertEquals( 'disabled', $skip_link['status'] );
	}

	/**
	 * Test that upsell (pro-only) fixes are excluded from the list when not using pro.
	 *
	 * @return void
	 */
	public function test_upsell_fixes_are_excluded_from_list() {
		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$json_part = substr( $output, strlen( 'Success: ' ) );
		$items     = json_decode( html_entity_decode( $json_part ), true );
		$slugs     = array_column( $items, 'slug' );

		// add_label_to_unlabelled_form_fields is a pro-only (upsell) fix in the free version.
		$this->assertNotContains( 'add_label_to_unlabelled_form_fields', $slugs );
	}

	/**
	 * Test that the command outputs a warning when no fixes are available.
	 *
	 * @return void
	 */
	public function test_command_outputs_warning_when_no_fixes_found() {
		// Override filter to return no fixes.
		$callback = static function () {
			return [];
		};
		add_filter( 'edac_filter_fixes', $callback );

		// Reset and reload with empty fixes.
		$reflection = new ReflectionClass( FixesManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		FixesManager::get_instance()->register_fixes();

		ob_start();
		$this->list_fixes->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Warning: No fixes found.', $output );

		remove_filter( 'edac_filter_fixes', $callback );
	}
}
