<?php
/**
 * Tests for the edac_get_upcoming_meetups_html helper.
 *
 * @package Accessibility_Checker
 * @since 1.51.0
 */

/**
 * Tests for edac_get_upcoming_meetups_html.
 *
 * The helper renders the dashboard widget and the welcome page, so an event's
 * name, time or link reaching the markup unescaped is a front-end defect rather
 * than a formatting nit. The event list is seeded straight into the cache so
 * these tests cover the rendering and not the upstream fetch.
 *
 * @covers ::edac_get_upcoming_meetups_html
 * @since 1.51.0
 */
class UpcomingMeetupsHtmlTest extends WP_UnitTestCase {

	/**
	 * Meetup slug the fixture events are cached under.
	 *
	 * @var string
	 */
	private const MEETUP = 'equalize-digital-web-accessibility-meetup-dfw';

	/**
	 * Number of events every scenario asks for; it is part of the cache key.
	 *
	 * @var int
	 */
	private const COUNT = 2;

	/**
	 * Clear any cached meetup data left behind by another test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clear_cache();
	}

	/**
	 * Clear the cached meetup data written by a test.
	 */
	public function tearDown(): void {
		$this->clear_cache();

		parent::tearDown();
	}

	/**
	 * Delete the cached event list and its stale fallback.
	 */
	private function clear_cache(): void {
		$key = $this->cache_key();

		delete_transient( $key );
		delete_option( $key . '__stale' );
	}

	/**
	 * The transient key edac_get_upcoming_meetups_json() reads and writes.
	 *
	 * @return string
	 */
	private function cache_key(): string {
		return '_upcoming_meetups__' . self::MEETUP . '__' . self::COUNT;
	}

	/**
	 * Cache the event list the helper renders from.
	 *
	 * @param array $events The event objects to cache.
	 */
	private function seed_events( array $events ): void {
		set_transient( $this->cache_key(), $events, DAY_IN_SECONDS );
	}

	/**
	 * Build a single event object in the shape the helper expects.
	 *
	 * @param string $name The event name.
	 * @param int    $time The event start time in milliseconds.
	 * @param string $link The event url.
	 * @return object
	 */
	private function event( string $name, int $time = 1735689600000, string $link = 'https://www.meetup.com/example/events/1' ): object {
		return (object) [
			'name' => $name,
			'time' => $time,
			'link' => $link,
			'id'   => '1',
		];
	}

	/**
	 * Tests that an empty event list renders nothing at all.
	 */
	public function test_returns_empty_string_without_events(): void {
		$this->seed_events( [] );

		$this->assertSame( '', edac_get_upcoming_meetups_html( self::MEETUP, self::COUNT ) );
	}

	/**
	 * Tests the markup of one list item per event: the wrapper, the heading, the
	 * time in whole seconds and a link with a descriptive accessible name.
	 */
	public function test_renders_a_list_item_per_event(): void {
		$this->seed_events(
			[
				$this->event( 'Accessible Design 101' ),
				$this->event( 'Office Hours', 1735776000000, 'https://www.meetup.com/example/events/2' ),
			]
		);

		$html = edac_get_upcoming_meetups_html( self::MEETUP, self::COUNT );

		$this->assertStringStartsWith( '<ul class="edac-upcoming-meetup-list">', $html );
		$this->assertStringEndsWith( '</ul>', $html );
		$this->assertSame( 2, substr_count( $html, '<li class="edac-upcoming-meetup-item edac-mb-3">' ) );
		$this->assertStringContainsString( '<h3 class="edac-upcoming-meetup-item-name">Accessible Design 101</h3>', $html );
		$this->assertStringContainsString( '<div class="edac-upcoming-meetup-item-time edac-timestamp-to-local">1735689600</div>', $html );
		$this->assertStringNotContainsString( '1735689600000', $html );
		$this->assertStringContainsString( 'href="https://www.meetup.com/example/events/1"', $html );
		$this->assertStringContainsString( 'aria-label="Attend Free: Accessible Design 101 (opens in a new window)"', $html );
		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
	}

	/**
	 * Tests that the heading level argument is used on the open and close tags.
	 */
	public function test_heading_level_argument_is_used(): void {
		$this->seed_events( [ $this->event( 'Accessible Design 101' ) ] );

		$html = edac_get_upcoming_meetups_html( self::MEETUP, self::COUNT, '4' );

		$this->assertStringContainsString( '<h4 class="edac-upcoming-meetup-item-name">Accessible Design 101</h4>', $html );
		$this->assertStringNotContainsString( '<h3', $html );
	}

	/**
	 * Tests that an event name cannot inject markup into the heading or the label.
	 */
	public function test_event_name_is_escaped(): void {
		$this->seed_events( [ $this->event( '<script>alert("x")</script>' ) ] );

		$html = edac_get_upcoming_meetups_html( self::MEETUP, self::COUNT );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $html );
	}

	/**
	 * Tests that a link with a disallowed protocol is dropped.
	 */
	public function test_link_with_a_disallowed_protocol_is_dropped(): void {
		$this->seed_events( [ $this->event( 'Accessible Design 101', 1735689600000, 'javascript:alert(1)' ) ] );

		$html = edac_get_upcoming_meetups_html( self::MEETUP, self::COUNT );

		$this->assertStringContainsString( 'href=""', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
	}
}
