<?php
/**
 * Tests for the options page functions.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests;

/**
 * Test the options page helper functions.
 */
class OptionsPageTest extends \WP_UnitTestCase {

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'edacp_ignore_user_roles' );
		parent::tearDown();
	}

	/**
	 * Test that edac_user_can_ignore() returns true for administrators.
	 */
	public function test_user_can_ignore_returns_true_for_admin() {
		// Create an administrator user.
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$this->assertTrue( edac_user_can_ignore(), 'Administrators should be able to ignore issues' );
	}

	/**
	 * Test that edac_user_can_ignore() returns boolean, not array.
	 *
	 * This test verifies the bug fix where the function was returning an array
	 * from array_intersect() instead of a boolean value.
	 */
	public function test_user_can_ignore_returns_boolean() {
		// Create an editor user.
		$editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		// Set editor as allowed role.
		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean, not array or other type' );
		$this->assertTrue( $result, 'Editor with allowed role should be able to ignore' );
	}

	/**
	 * Test that edac_user_can_ignore() returns false for non-allowed roles.
	 */
	public function test_user_can_ignore_returns_false_for_non_allowed_role() {
		// Create a subscriber user.
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

		// Set editor as allowed role (subscriber is not in this list).
		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean' );
		$this->assertFalse( $result, 'Subscriber without allowed role should not be able to ignore' );
	}

	/**
	 * Test that edac_user_can_ignore() returns false when no roles are configured.
	 */
	public function test_user_can_ignore_returns_false_when_no_roles_configured() {
		// Create an editor user.
		$editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		// No roles configured.
		delete_option( 'edacp_ignore_user_roles' );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean' );
		$this->assertFalse( $result, 'Should return false when no roles are configured' );
	}

	/**
	 * Test that edac_user_can_ignore() handles multiple user roles correctly.
	 */
	public function test_user_can_ignore_with_multiple_roles() {
		// Create a user with multiple roles.
		$user_id = $this->factory->user->create( [ 'role' => 'contributor' ] );
		$user    = get_user_by( 'id', $user_id );
		$user->add_role( 'author' );
		wp_set_current_user( $user_id );

		// Set author as allowed role.
		update_option( 'edacp_ignore_user_roles', [ 'author', 'editor' ] );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean' );
		$this->assertTrue( $result, 'User with author role should be able to ignore (has one matching role)' );
	}

	/**
	 * Test that empty array intersection doesn't cause truthy return.
	 *
	 * This specifically tests the bug where an empty array from array_intersect()
	 * would be returned and evaluated as truthy in some contexts.
	 */
	public function test_empty_intersection_returns_false() {
		// Create a contributor user.
		$contributor_id = $this->factory->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $contributor_id );

		// Set different roles as allowed.
		update_option( 'edacp_ignore_user_roles', [ 'editor', 'author' ] );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean' );
		$this->assertFalse( $result, 'Empty intersection should return false, not empty array' );
		$this->assertNotEquals( [], $result, 'Should not return empty array' );
	}

	/**
	 * Test that the function handles edge case of empty user roles array.
	 */
	public function test_user_with_no_roles() {
		// Create a user and remove all roles.
		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );
		$user->remove_all_caps();
		wp_set_current_user( $user_id );

		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean' );
		$this->assertFalse( $result, 'User with no roles should not be able to ignore' );
	}

	/**
	 * Test that users without matching roles receive boolean false, not empty array.
	 *
	 * This test specifically verifies the bug fix where array_intersect() with no matches
	 * returns an empty array [], which is truthy in PHP conditionals. The bug allowed users
	 * without proper roles to bypass permission checks. The fix ensures we return strict
	 * boolean false when there are no matching roles.
	 *
	 * Bug reproduction: User with 'subscriber' role, allowed roles are ['editor', 'author']
	 * Before fix: array_intersect(['subscriber'], ['editor', 'author']) = [] (truthy!)
	 * After fix: Returns boolean false (correct)
	 */
	public function test_user_without_matching_role_gets_boolean_false_not_empty_array() {
		// Subscriber role (common low-privilege role).
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

		// Only editors and authors allowed.
		update_option( 'edacp_ignore_user_roles', [ 'editor', 'author' ] );

		$result = edac_user_can_ignore();

		// The bug would return [] which is truthy.
		// The fix returns false which is correct.
		$this->assertFalse( $result, 'Subscriber should not have permission (bug would return truthy empty array)' );
		$this->assertNotSame( [], $result, 'Should not return empty array (the bug)' );
		$this->assertSame( false, $result, 'Should be exactly boolean false' );
	}

	/**
	 * Test with empty allowed roles array.
	 */
	public function test_with_empty_allowed_roles_array() {
		$editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		// Empty array for allowed roles.
		update_option( 'edacp_ignore_user_roles', [] );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean' );
		$this->assertFalse( $result, 'Empty allowed roles should deny permission' );
	}

	/**
	 * Test with null option value.
	 */
	public function test_with_null_option_value() {
		$editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		// Option doesn't exist (get_option returns false).
		delete_option( 'edacp_ignore_user_roles' );

		$result = edac_user_can_ignore();

		$this->assertIsBool( $result, 'Function should return boolean' );
		$this->assertFalse( $result, 'Non-existent option should deny permission' );
	}

	/**
	 * Test that admin always has permission regardless of role configuration.
	 */
	public function test_admin_ignores_role_configuration() {
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		// No roles configured at all.
		delete_option( 'edacp_ignore_user_roles' );

		$result = edac_user_can_ignore();

		$this->assertTrue( $result, 'Admin should always have permission even with no role config' );

		// Empty allowed roles.
		update_option( 'edacp_ignore_user_roles', [] );
		$result = edac_user_can_ignore();

		$this->assertTrue( $result, 'Admin should always have permission even with empty role config' );
	}

	/**
	 * Test with case-sensitive role names.
	 */
	public function test_role_names_are_case_sensitive() {
		$editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		// Uppercase - should NOT match.
		update_option( 'edacp_ignore_user_roles', [ 'EDITOR' ] );

		$result = edac_user_can_ignore();

		$this->assertFalse( $result, 'Role names should be case-sensitive' );

		// Correct case - should match.
		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		$result = edac_user_can_ignore();

		$this->assertTrue( $result, 'Correct case should match' );
	}

	/**
	 * Test return type is strictly boolean, not truthy/falsy values.
	 */
	public function test_strict_boolean_return_type() {
		// Test true case.
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$result = edac_user_can_ignore();

		$this->assertSame( true, $result, 'Should return exactly boolean true, not truthy value' );
		$this->assertNotSame( 1, $result, 'Should not return integer 1' );
		$this->assertNotSame( 'true', $result, 'Should not return string' );

		// Test false case.
		$subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		$result = edac_user_can_ignore();

		$this->assertSame( false, $result, 'Should return exactly boolean false, not falsy value' );
		$this->assertNotSame( 0, $result, 'Should not return integer 0' );
		$this->assertNotSame( '', $result, 'Should not return empty string' );
		$this->assertNotSame( null, $result, 'Should not return null' );
		$this->assertNotSame( [], $result, 'Should not return empty array (the original bug)' );
	}

	/**
	 * Test with multiple matching roles.
	 */
	public function test_multiple_matching_roles() {
		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		$user    = get_user_by( 'id', $user_id );
		$user->add_role( 'author' );
		wp_set_current_user( $user_id );

		// Both roles are allowed.
		update_option( 'edacp_ignore_user_roles', [ 'editor', 'author' ] );

		$result = edac_user_can_ignore();

		$this->assertTrue( $result, 'User with multiple matching roles should have permission' );
	}

	/**
	 * Test with partial role match.
	 */
	public function test_partial_role_match() {
		$user_id = $this->factory->user->create( [ 'role' => 'contributor' ] );
		$user    = get_user_by( 'id', $user_id );
		$user->add_role( 'author' );
		wp_set_current_user( $user_id );

		// Only one of the user's roles is allowed.
		update_option( 'edacp_ignore_user_roles', [ 'author', 'editor' ] );

		$result = edac_user_can_ignore();

		$this->assertTrue( $result, 'User should have permission if ANY of their roles matches' );
	}

	/**
	 * Test that admin capability bypasses role checks but role-based permissions work for non-admins.
	 *
	 * This test verifies that:
	 * 1. Users with manage_options capability always pass (admins)
	 * 2. Users without manage_options need their role in the allowed list
	 * 3. Changing user roles properly updates their permissions
	 */
	public function test_admin_capability_bypasses_role_check_but_others_need_role_match() {
		// Create user with admin role (has manage_options capability).
		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		// Admin should pass even with no role configuration.
		delete_option( 'edacp_ignore_user_roles' );
		$this->assertTrue( edac_user_can_ignore(), 'Admin with manage_options capability should always pass' );

		// Create a separate editor user (does not have manage_options).
		$editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		// Editor should fail without role configuration.
		$this->assertFalse( edac_user_can_ignore(), 'Editor without manage_options and no role config should fail' );

		// Add editor to allowed roles.
		update_option( 'edacp_ignore_user_roles', [ 'editor' ] );

		// Editor should now pass with role in allowed list.
		$this->assertTrue( edac_user_can_ignore(), 'Editor should pass when their role is in allowed list' );

		// Verify admin still bypasses even with editor-only config.
		wp_set_current_user( $admin_id );
		$this->assertTrue( edac_user_can_ignore(), 'Admin should still bypass role checks' );
	}
}
