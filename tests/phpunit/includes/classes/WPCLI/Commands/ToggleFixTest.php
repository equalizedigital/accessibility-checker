<?php
/**
 * Test the ToggleFix command.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\Mocks\Mock_WP_CLI;
use EqualizeDigital\AccessibilityChecker\WPCLI\Command\ToggleFix;

/**
 * Test cases to verify that the ToggleFix command toggles fixes correctly.
 */
class ToggleFixTest extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var ToggleFix
	 */
	protected $toggle_fix;

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
		$this->toggle_fix = new ToggleFix( $wp_cli );

		// Reset and load the FixesManager so fixes are available.
		$reflection = new ReflectionClass( FixesManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		FixesManager::get_instance()->register_fixes();

		parent::setUp();
	}

	/**
	 * Reset the FixesManager singleton and clean up options after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( 'edac_fix_add_skip_link' );
		delete_option( 'edac_fix_remove_tabindex' );
		delete_option( 'edac_fix_focus_outline' );

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
		$this->assertEquals( 'accessibility-checker toggle-fix', ToggleFix::get_name() );
	}

	/**
	 * Test the get_shortname method returns the correct short command name.
	 *
	 * @return void
	 */
	public function test_get_shortname_returns_correct_short_command_name() {
		$this->assertEquals( 'edac toggle-fix', ToggleFix::get_shortname() );
	}

	/**
	 * Test the get_args method returns an array with the expected structure.
	 *
	 * @return void
	 */
	public function test_get_args_returns_correct_structure() {
		$args = ToggleFix::get_args();

		$this->assertIsArray( $args );
		$this->assertArrayHasKey( 'synopsis', $args );

		$synopsis_by_name = array_column( $args['synopsis'], null, 'name' );
		$this->assertArrayHasKey( 'slug', $synopsis_by_name );
		$this->assertArrayHasKey( 'enable', $synopsis_by_name );
		$this->assertArrayHasKey( 'disable', $synopsis_by_name );

		$this->assertEquals( 'positional', $synopsis_by_name['slug']['type'] );
		$this->assertFalse( $synopsis_by_name['slug']['optional'] );
		$this->assertEquals( 'flag', $synopsis_by_name['enable']['type'] );
		$this->assertEquals( 'flag', $synopsis_by_name['disable']['type'] );
	}

	/**
	 * Test the command errors when no slug is provided.
	 *
	 * @return void
	 */
	public function test_command_errors_when_no_slug_provided() {
		ob_start();
		$this->toggle_fix->__invoke( [], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Error: No fix slug provided.', $output );
	}

	/**
	 * Test the command errors when a non-existent slug is provided.
	 *
	 * @return void
	 */
	public function test_command_errors_when_fix_not_found() {
		ob_start();
		$this->toggle_fix->__invoke( [ 'non_existent_fix_slug' ], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Error: Fix &quot;non_existent_fix_slug&quot; not found', $output );
	}

	/**
	 * Test that the command toggles a disabled fix to enabled.
	 *
	 * @return void
	 */
	public function test_command_toggles_disabled_fix_to_enabled() {
		delete_option( 'edac_fix_remove_tabindex' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'remove_tabindex' ], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertStringContainsString( 'enabled', $output );
		$this->assertEquals( '1', get_option( 'edac_fix_remove_tabindex' ) );
	}

	/**
	 * Test that the command toggles an enabled fix to disabled.
	 *
	 * @return void
	 */
	public function test_command_toggles_enabled_fix_to_disabled() {
		update_option( 'edac_fix_remove_tabindex', '1' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'remove_tabindex' ], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertStringContainsString( 'disabled', $output );
		$this->assertEquals( '0', get_option( 'edac_fix_remove_tabindex' ) );
	}

	/**
	 * Test that --enable flag explicitly enables a fix.
	 *
	 * @return void
	 */
	public function test_enable_flag_enables_fix() {
		delete_option( 'edac_fix_remove_tabindex' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'remove_tabindex' ], [ 'enable' => true ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertStringContainsString( 'enabled', $output );
		$this->assertEquals( '1', get_option( 'edac_fix_remove_tabindex' ) );
	}

	/**
	 * Test that --enable flag is idempotent (already-enabled fix stays enabled).
	 *
	 * @return void
	 */
	public function test_enable_flag_is_idempotent() {
		update_option( 'edac_fix_remove_tabindex', '1' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'remove_tabindex' ], [ 'enable' => true ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertStringContainsString( 'enabled', $output );
		$this->assertEquals( '1', get_option( 'edac_fix_remove_tabindex' ) );
	}

	/**
	 * Test that --disable flag explicitly disables a fix.
	 *
	 * @return void
	 */
	public function test_disable_flag_disables_fix() {
		update_option( 'edac_fix_remove_tabindex', '1' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'remove_tabindex' ], [ 'disable' => true ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertStringContainsString( 'disabled', $output );
		$this->assertEquals( '0', get_option( 'edac_fix_remove_tabindex' ) );
	}

	/**
	 * Test that --disable flag is idempotent (already-disabled fix stays disabled).
	 *
	 * @return void
	 */
	public function test_disable_flag_is_idempotent() {
		delete_option( 'edac_fix_remove_tabindex' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'remove_tabindex' ], [ 'disable' => true ] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertStringContainsString( 'disabled', $output );
		$this->assertEquals( '0', get_option( 'edac_fix_remove_tabindex' ) );
	}

	/**
	 * Test that --enable takes precedence over --disable when both flags are set.
	 *
	 * @return void
	 */
	public function test_enable_takes_precedence_over_disable() {
		delete_option( 'edac_fix_remove_tabindex' );

		ob_start();
		$this->toggle_fix->__invoke(
			[ 'remove_tabindex' ],
			[
				'enable'  => true,
				'disable' => true,
			]
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertStringContainsString( 'enabled', $output );
		$this->assertEquals( '1', get_option( 'edac_fix_remove_tabindex' ) );
	}

	/**
	 * Test that the command errors when an upsell (pro-only) fix slug is provided.
	 *
	 * @return void
	 */
	public function test_command_errors_for_upsell_fix() {
		ob_start();
		$this->toggle_fix->__invoke( [ 'add_label_to_unlabelled_form_fields' ], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Error: ', $output );
		$this->assertStringContainsString( 'pro version', $output );
	}

	/**
	 * Test that the skip_link fix toggles all of its main checkboxes.
	 *
	 * The skip_link fix uses 'edac_fix_add_skip_link' as its main option key.
	 *
	 * @return void
	 */
	public function test_toggle_skip_link_fix_uses_correct_option_key() {
		delete_option( 'edac_fix_add_skip_link' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'skip_link' ], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertEquals( '1', get_option( 'edac_fix_add_skip_link' ) );
	}

	/**
	 * Test that the comment-search-label fix toggles both of its checkboxes.
	 *
	 * @return void
	 */
	public function test_toggle_comment_search_label_fix_toggles_all_checkboxes() {
		delete_option( 'edac_fix_comment_label' );
		delete_option( 'edac_fix_search_label' );

		ob_start();
		$this->toggle_fix->__invoke( [ 'comment-search-label' ], [] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Success: ', $output );
		$this->assertEquals( '1', get_option( 'edac_fix_comment_label' ) );
		$this->assertEquals( '1', get_option( 'edac_fix_search_label' ) );

		// Clean up.
		delete_option( 'edac_fix_comment_label' );
		delete_option( 'edac_fix_search_label' );
	}
}
