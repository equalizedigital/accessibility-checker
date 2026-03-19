<?php
/**
 * Tests for the PublishBlocker class.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\PublishBlocker;

/**
 * Test suite for PublishBlocker.
 */
class PublishBlockerTest extends WP_UnitTestCase {

	/**
	 * The ID of a test post created for each test.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Set up shared test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a test post with a saved _edac_summary so it appears scanned.
		$this->post_id = $this->factory()->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'draft',
			]
		);
		update_post_meta(
			$this->post_id,
			'_edac_summary',
			[
				'passed_tests'    => 10,
				'errors'          => 3,
				'contrast_errors' => 1,
				'warnings'        => 2,
				'ignored'         => 0,
				'readability'     => 0,
			]
		);

		// Configure sensible defaults for a hard-block scenario with errors.
		update_option( 'edac_block_publish', 1 );
		update_option( 'edac_block_publish_mode', 'hard' );
		update_option( 'edac_block_publish_on_errors', 1 );
		update_option( 'edac_block_publish_on_warnings', 0 );
		update_option( 'edac_block_publish_post_types', [ 'post' ] );
		update_option( 'edac_block_publish_roles', [ 'editor', 'author' ] );
		update_option( 'edac_block_publish_bypass_cap', '' );

		// Log in as an author (a blocked role).
		$user_id = $this->factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $user_id );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		delete_option( 'edac_block_publish' );
		delete_option( 'edac_block_publish_mode' );
		delete_option( 'edac_block_publish_on_errors' );
		delete_option( 'edac_block_publish_on_warnings' );
		delete_option( 'edac_block_publish_post_types' );
		delete_option( 'edac_block_publish_roles' );
		delete_option( 'edac_block_publish_bypass_cap' );
		wp_set_current_user( 0 );
	}

	/**
	 * Returns the post data array used by maybe_block_publish tests.
	 *
	 * @param string $post_type   Post type.
	 * @param string $post_status Target post status.
	 * @return array
	 */
	private function make_post_data( string $post_type = 'post', string $post_status = 'publish' ): array {
		return [
			'post_type'   => $post_type,
			'post_status' => $post_status,
		];
	}

	/**
	 * Returns the postarr array used by maybe_block_publish tests.
	 *
	 * @param int    $post_id         The post ID.
	 * @param string $original_status The original post status before save.
	 * @return array
	 */
	private function make_postarr( int $post_id, string $original_status = 'draft' ): array {
		return [
			'ID'                   => $post_id,
			'original_post_status' => $original_status,
		];
	}

	// -----------------------------------------------------------------------
	// maybe_block_publish — hard mode enforcement
	// -----------------------------------------------------------------------

	/**
	 * Hard mode with errors should revert publish status to draft.
	 */
	public function test_hard_mode_with_errors_reverts_status() {
		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertNotEquals( 'publish', $result['post_status'] );
		$this->assertEquals( 'draft', $result['post_status'] );
	}

	/**
	 * Hard mode should restore original status (pending) when blocking.
	 */
	public function test_hard_mode_reverts_to_original_status_when_pending() {
		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id, 'pending' );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'pending', $result['post_status'] );
	}

	/**
	 * Soft mode should not touch post status even when issues exist.
	 */
	public function test_soft_mode_does_not_revert_status() {
		update_option( 'edac_block_publish_mode', 'soft' );

		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'publish', $result['post_status'] );
	}

	/**
	 * Feature disabled should allow publish without blocking.
	 */
	public function test_feature_disabled_allows_publish() {
		update_option( 'edac_block_publish', 0 );

		// Reinitialise: init() won't register the filter when option is off.
		$blocker = new PublishBlocker();
		$blocker->init();

		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id );

		// Directly test the method — the filter is never added, but the method
		// should still return correctly if called independently.
		$result = $blocker->maybe_block_publish( $data, $postarr );
		$this->assertEquals( 'publish', $result['post_status'] );
	}

	/**
	 * Post type not in block list should allow publish.
	 */
	public function test_post_type_not_in_list_allows_publish() {
		update_option( 'edac_block_publish_post_types', [ 'page' ] ); // 'post' not included.

		$blocker = new PublishBlocker();
		$data    = $this->make_post_data( 'post' );
		$postarr = $this->make_postarr( $this->post_id );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'publish', $result['post_status'] );
	}

	/**
	 * A post that has never been scanned (empty summary) should be allowed to publish.
	 */
	public function test_never_scanned_post_allows_publish() {
		$unscanned_post_id = $this->factory()->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'draft',
			]
		);

		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $unscanned_post_id );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'publish', $result['post_status'] );
	}

	/**
	 * A new post (ID = 0) should always be allowed to publish.
	 */
	public function test_new_post_with_no_id_allows_publish() {
		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = [
			'ID'                   => 0,
			'original_post_status' => 'new',
		];

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'publish', $result['post_status'] );
	}

	/**
	 * Non-publish status changes pass through untouched.
	 */
	public function test_non_publish_status_passes_through() {
		$blocker = new PublishBlocker();
		$data    = $this->make_post_data( 'post', 'draft' );
		$postarr = $this->make_postarr( $this->post_id );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'draft', $result['post_status'] );
	}

	/**
	 * When only warnings exist and block-on-errors is set (not warnings), publish is allowed.
	 */
	public function test_no_block_when_issue_type_not_configured() {
		// Errors exist but block-on-errors is off; only block-on-warnings is on.
		update_option( 'edac_block_publish_on_errors', 0 );
		update_option( 'edac_block_publish_on_warnings', 0 );

		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'publish', $result['post_status'] );
	}

	/**
	 * Post with only warnings is blocked when block-on-warnings is enabled.
	 */
	public function test_warnings_only_blocks_when_configured() {
		update_option( 'edac_block_publish_on_errors', 0 );
		update_option( 'edac_block_publish_on_warnings', 1 );

		// Post with only warnings.
		update_post_meta(
			$this->post_id,
			'_edac_summary',
			[
				'errors'          => 0,
				'contrast_errors' => 0,
				'warnings'        => 5,
			]
		);

		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id );

		$result = $blocker->maybe_block_publish( $data, $postarr );

		$this->assertEquals( 'draft', $result['post_status'] );
	}

	// -----------------------------------------------------------------------
	// user_can_bypass
	// -----------------------------------------------------------------------

	/**
	 * Administrator is not in the blocked roles list, so they can bypass.
	 */
	public function test_admin_not_in_blocked_roles_can_bypass() {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		// 'administrator' is not in the blocked roles list.
		$this->assertTrue( PublishBlocker::user_can_bypass() );
	}

	/**
	 * Author in blocked roles cannot bypass.
	 */
	public function test_blocked_role_cannot_bypass() {
		// Author is already set up as the current user in setUp() and 'author'
		// is in the blocked roles list.
		$this->assertFalse( PublishBlocker::user_can_bypass() );
	}

	/**
	 * User with bypass capability can bypass even if their role is blocked.
	 */
	public function test_user_with_bypass_capability_can_bypass() {
		update_option( 'edac_block_publish_bypass_cap', 'edac_bypass_publish_block' );

		// Grant the capability to the current user (author).
		$user = wp_get_current_user();
		$user->add_cap( 'edac_bypass_publish_block' );

		$this->assertTrue( PublishBlocker::user_can_bypass() );

		// Clean up.
		$user->remove_cap( 'edac_bypass_publish_block' );
	}

	/**
	 * When no roles are configured, everybody can bypass (no enforcement).
	 */
	public function test_empty_roles_list_allows_bypass_for_everyone() {
		update_option( 'edac_block_publish_roles', [] );

		$this->assertTrue( PublishBlocker::user_can_bypass() );
	}

	/**
	 * The edac_user_can_bypass_publish_block filter can override bypass.
	 */
	public function test_bypass_filter_can_override() {
		// Author is in the blocked list — normally cannot bypass.
		add_filter( 'edac_user_can_bypass_publish_block', '__return_true' );

		$this->assertTrue( PublishBlocker::user_can_bypass() );

		remove_filter( 'edac_user_can_bypass_publish_block', '__return_true' );
	}

	// -----------------------------------------------------------------------
	// Transient notice
	// -----------------------------------------------------------------------

	/**
	 * A transient is set when publish is hard-blocked.
	 */
	public function test_transient_is_set_when_publish_blocked() {
		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id );

		$blocker->maybe_block_publish( $data, $postarr );

		$transient = get_transient( PublishBlocker::TRANSIENT_PREFIX . get_current_user_id() );
		$this->assertIsArray( $transient );
		$this->assertArrayHasKey( 'error_count', $transient );
		$this->assertEquals( 4, $transient['error_count'] ); // 3 errors + 1 contrast.
	}

	/**
	 * No transient is set when publish is allowed (no blocking issues).
	 */
	public function test_no_transient_when_no_blocking_issues() {
		update_option( 'edac_block_publish_on_errors', 0 );
		update_option( 'edac_block_publish_on_warnings', 0 );

		$blocker = new PublishBlocker();
		$data    = $this->make_post_data();
		$postarr = $this->make_postarr( $this->post_id );

		$blocker->maybe_block_publish( $data, $postarr );

		$transient = get_transient( PublishBlocker::TRANSIENT_PREFIX . get_current_user_id() );
		$this->assertFalse( $transient );
	}
}
