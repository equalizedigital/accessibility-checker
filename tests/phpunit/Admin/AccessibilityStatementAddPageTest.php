<?php
/**
 * Tests for the accessibility statement page written on activation.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Accessibility_Statement;

/**
 * Tests for Accessibility_Statement::add_page().
 *
 * Activation seeds a draft page at /accessibility-statement/ for the user who
 * activated the plugin, so a site has somewhere to publish its policy without
 * hand-writing the whole outline. The guards matter as much as the happy path:
 * a page already at that slug must never be replaced, and a user who cannot
 * activate plugins must not have a page written on their behalf.
 *
 * @covers \EDAC\Admin\Accessibility_Statement::add_page
 */
class AccessibilityStatementAddPageTest extends WP_UnitTestCase {

	/**
	 * The slug activation writes, at the site root.
	 *
	 * @var string
	 */
	private const SLUG = 'accessibility-statement';

	/**
	 * Leaves no current user behind for the next test in the process.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Activation writes a draft page owned by the user doing the activating.
	 *
	 * @return void
	 */
	public function test_creates_a_draft_page_owned_by_the_activating_user(): void {
		$user_id = $this->user_who_can_activate_plugins();
		wp_set_current_user( $user_id );

		$this->assertNull( get_page_by_path( self::SLUG ), 'Precondition: nothing is at the slug before the call.' );

		Accessibility_Statement::add_page();

		$page = get_page_by_path( self::SLUG );

		$this->assertInstanceOf( WP_Post::class, $page );
		$this->assertSame( 'draft', $page->post_status );
		$this->assertSame( 'page', $page->post_type );
		$this->assertSame( self::SLUG, $page->post_name );
		$this->assertSame( $user_id, (int) $page->post_author );
		$this->assertSame( 'Our Commitment to Web Accessibility', $page->post_title );
	}

	/**
	 * The seeded body is the statement outline, with the unresolved parts left
	 * as bracketed placeholders for the site owner to fill in.
	 *
	 * @return void
	 */
	public function test_content_is_the_statement_outline_with_placeholders_to_fill_in(): void {
		wp_set_current_user( $this->user_who_can_activate_plugins() );

		Accessibility_Statement::add_page();

		$content = get_page_by_path( self::SLUG )->post_content;

		foreach (
			[
				'[YOUR COMPANY NAME] is committed to providing a fully accessible website experience',
				'<h2><strong>Ongoing Efforts to Ensure Accessibility</strong></h2>',
				'<a href="https://www.w3.org/TR/WCAG21/">Web Content Accessibility Guidelines (WCAG) version 2.1</a>',
				'Accessibility Features On Our Website',
				'[LIST ACCESSIBILITY FEATURES HERE]',
				'Where We\'re Improving',
				'[LIST ITEMS YOU\'RE WORKING TO FIX HERE]',
				'Accessibility Support Contact',
				'[YOUR ACCESSIBILITY EMAIL ADDRESS]',
			] as $expected
		) {
			$this->assertStringContainsString( $expected, $content );
		}
	}

	/**
	 * A user who cannot activate plugins gets no page written for them.
	 *
	 * @return void
	 */
	public function test_does_nothing_for_a_user_who_cannot_activate_plugins(): void {
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		Accessibility_Statement::add_page();

		$this->assertNull( get_page_by_path( self::SLUG ) );
	}

	/**
	 * A site that already has a page at the slug keeps its own content.
	 *
	 * @return void
	 */
	public function test_an_existing_page_at_the_slug_is_left_alone(): void {
		wp_set_current_user( $this->user_who_can_activate_plugins() );

		$existing_id = $this->factory()->post->create(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => self::SLUG,
				'post_title'   => 'Our accessibility statement',
				'post_content' => 'Hand written by the site owner.',
			]
		);

		Accessibility_Statement::add_page();

		$pages = $this->pages_at_slug();

		$this->assertCount( 1, $pages );
		$this->assertSame( $existing_id, $pages[0]->ID );
		$this->assertSame( 'Hand written by the site owner.', $pages[0]->post_content );
	}

	/**
	 * Running activation twice does not leave two pages behind.
	 *
	 * The page the first call writes is a draft, so this only holds if the
	 * existence check finds drafts as well as published pages.
	 *
	 * @return void
	 */
	public function test_a_second_call_does_not_create_a_second_page(): void {
		wp_set_current_user( $this->user_who_can_activate_plugins() );

		Accessibility_Statement::add_page();
		Accessibility_Statement::add_page();

		$this->assertCount( 1, $this->pages_at_slug() );
	}

	/**
	 * A user who really holds `activate_plugins` in this environment.
	 *
	 * On multisite that capability belongs to super admins rather than to site
	 * administrators, so a plain administrator fixture would trip the guard
	 * under test and the body would never run.
	 *
	 * @return int
	 */
	private function user_who_can_activate_plugins(): int {
		$user_id = (int) $this->factory()->user->create( [ 'role' => 'administrator' ] );

		if ( is_multisite() && function_exists( 'grant_super_admin' ) ) {
			grant_super_admin( $user_id );
		}

		return $user_id;
	}

	/**
	 * Every page sitting at the fixture slug, in any status except trash.
	 *
	 * Bounded rather than unlimited: the VIP ruleset forbids `-1` here, and all
	 * this needs to see is whether a second page appeared.
	 *
	 * @return WP_Post[]
	 */
	private function pages_at_slug(): array {
		return get_posts(
			[
				'post_type'      => 'page',
				'post_status'    => 'any',
				'name'           => self::SLUG,
				'posts_per_page' => 10,
			]
		);
	}
}
