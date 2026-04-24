<?php
/**
 * Test cases for the Admin body class behavior.
 *
 * @package accessibility-checker
 */

use EDAC\Admin\Admin;
use EDAC\Admin\Meta_Boxes;

/**
 * Tests for Admin::sr_only_admin_body_class().
 */
class AdminBodyClassTest extends WP_UnitTestCase {

	/**
	 * Admin instance under test.
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Current test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up an admin instance with a current user.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->user_id );

		$this->admin = new Admin( $this->createMock( Meta_Boxes::class ) );
	}

	/**
	 * Clean up state.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_user_meta( $this->user_id, 'show_sr_text_in_editor' );
		wp_set_current_user( 0 );

		unset( $this->admin, $this->user_id );

		parent::tearDown();
	}

	/**
	 * Test that the sr-only body class is appended when the preference is enabled.
	 *
	 * @return void
	 */
	public function testSrOnlyAdminBodyClassAppendsClassWhenPreferenceEnabled() {
		update_user_meta( $this->user_id, 'show_sr_text_in_editor', true );

		$result = $this->admin->sr_only_admin_body_class( 'existing-class' );

		$this->assertSame( 'existing-class sr-only-show-always', $result );
	}

	/**
	 * Test that the sr-only body class is unchanged when the preference is disabled.
	 *
	 * @return void
	 */
	public function testSrOnlyAdminBodyClassLeavesClassesUnchangedWhenPreferenceDisabled() {
		update_user_meta( $this->user_id, 'show_sr_text_in_editor', false );

		$result = $this->admin->sr_only_admin_body_class( 'existing-class' );

		$this->assertSame( 'existing-class', $result );
		$this->assertStringNotContainsString( 'sr-only-show-always', $result );
	}

	/**
	 * Test that the sr-only body class is unchanged when the preference has not been set.
	 *
	 * @return void
	 */
	public function testSrOnlyAdminBodyClassLeavesClassesUnchangedWhenPreferenceMissing() {
		delete_user_meta( $this->user_id, 'show_sr_text_in_editor' );

		$result = $this->admin->sr_only_admin_body_class( 'existing-class' );

		$this->assertSame( 'existing-class', $result );
		$this->assertStringNotContainsString( 'sr-only-show-always', $result );
	}

	/**
	 * Test that sr-only body class is not duplicated when already present.
	 *
	 * @return void
	 */
	public function testSrOnlyAdminBodyClassDoesNotDuplicateExistingClass() {
		update_user_meta( $this->user_id, 'show_sr_text_in_editor', true );

		$result = $this->admin->sr_only_admin_body_class( 'existing-class sr-only-show-always' );

		$this->assertSame( 'existing-class sr-only-show-always', $result );
		$this->assertSame( 1, preg_match_all( '/\bsr-only-show-always\b/', $result ) );
	}

	/**
	 * Test that sr-only body class is appended without leading whitespace for empty classes.
	 *
	 * @return void
	 */
	public function testSrOnlyAdminBodyClassAppendsWithoutLeadingWhitespaceWhenClassesEmpty() {
		update_user_meta( $this->user_id, 'show_sr_text_in_editor', true );

		$result = $this->admin->sr_only_admin_body_class( '' );

		$this->assertSame( 'sr-only-show-always', $result );
	}
}
