<?php
/**
 * Tests for edac_custom_post_types helper.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_custom_post_types.
 *
 * @covers ::edac_custom_post_types
 */
class CustomPostTypesTest extends WP_UnitTestCase {

	/**
	 * Unregister test post types after each test.
	 */
	public function tearDown(): void {
		unregister_post_type( 'edac_public_qt' );
		unregister_post_type( 'edac_public_default' );
		unregister_post_type( 'edac_not_queryable' );
		unregister_post_type( 'edac_not_public' );
		parent::tearDown();
	}

	/**
	 * Registers a public and publicly-queryable custom post type and asserts
	 * it is included in the result.
	 */
	public function test_includes_publicly_queryable_post_types(): void {
		register_post_type(
			'edac_public_qt',
			[
				'public'             => true,
				'publicly_queryable' => true,
				'label'              => 'Public Queryable',
			]
		);

		$this->assertContains( 'edac_public_qt', edac_custom_post_types() );
	}

	/**
	 * Registers a custom post type with public=true but no explicit publicly_queryable
	 * and asserts it is included — WordPress defaults publicly_queryable to true
	 * when public is true, so existing CPTs should not regress.
	 */
	public function test_includes_public_cpt_with_default_publicly_queryable(): void {
		register_post_type(
			'edac_public_default',
			[
				'public' => true,
				'label'  => 'Public Default Queryable',
			]
		);

		$this->assertContains( 'edac_public_default', edac_custom_post_types() );
	}

	/**
	 * Registers a custom post type with public=true but publicly_queryable=false
	 * and asserts it is excluded from the result.
	 */
	public function test_excludes_non_publicly_queryable_post_types(): void {
		register_post_type(
			'edac_not_queryable',
			[
				'public'             => true,
				'publicly_queryable' => false,
				'label'              => 'Not Queryable',
			]
		);

		$this->assertNotContains( 'edac_not_queryable', edac_custom_post_types() );
	}

	/**
	 * Registers a non-public custom post type and asserts it is excluded.
	 */
	public function test_excludes_non_public_post_types(): void {
		register_post_type(
			'edac_not_public',
			[
				'public' => false,
				'label'  => 'Not Public',
			]
		);

		$this->assertNotContains( 'edac_not_public', edac_custom_post_types() );
	}

	/**
	 * Asserts that built-in post types (post, page) are not returned.
	 */
	public function test_excludes_builtin_post_types(): void {
		$result = edac_custom_post_types();

		$this->assertNotContains( 'post', $result );
		$this->assertNotContains( 'page', $result );
	}
}
