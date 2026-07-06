<?php
/**
 * Class UpcomingMeetupsJsonTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_get_upcoming_meetups_json() function.
 */
class UpcomingMeetupsJsonTest extends WP_UnitTestCase {

	/**
	 * Cache key for the current test meetup.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Stale cache key for the current test meetup.
	 *
	 * @var string
	 */
	private $stale_key;

	/**
	 * Set up each test by clearing transients and stale options.
	 */
	public function set_up() {
		parent::set_up();
		$this->key       = '_upcoming_meetups__test-meetup__1';
		$this->stale_key = $this->key . '__stale';
		delete_transient( $this->key );
		delete_option( $this->stale_key );
	}

	/**
	 * Build a mock pre_http_request filter that returns the given body and status code.
	 *
	 * @param string $body        Response body.
	 * @param int    $status_code HTTP status code.
	 * @return Closure
	 */
	private function mock_http_response( $body, $status_code = 200 ) {
		return function () use ( $body, $status_code ) {
			return [
				'headers'  => [],
				'body'     => $body,
				'response' => [
					'code'    => $status_code,
					'message' => 'OK',
				],
			];
		};
	}

	/**
	 * Build a valid GraphQL JSON response body from an array of event arrays.
	 *
	 * Each event array should have keys: title, dateTime, eventUrl, id.
	 *
	 * @param array $events List of event data arrays.
	 * @return string JSON-encoded response body.
	 */
	private function build_valid_response_body( $events ) {
		$edges = array_map(
			function ( $event ) {
				return [ 'node' => $event ];
			},
			$events
		);

		return wp_json_encode(
			[
				'data' => [
					'groupByUrlname' => [
						'events' => [
							'totalCount' => count( $events ),
							'edges'      => $edges,
						],
					],
				],
			]
		);
	}

	/**
	 * Helper to call the function under test with a mocked HTTP response, converting
	 * PHP warnings into test failures via ErrorException.
	 *
	 * @param string  $meetup Meetup slug.
	 * @param int     $count  Number of events.
	 * @param Closure $filter pre_http_request filter (may be null to skip mocking HTTP).
	 * @return array
	 */
	private function call_with_error_handler( $meetup, $count, $filter = null ) {
		if ( $filter ) {
			add_filter( 'pre_http_request', $filter, 10, 3 );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Needed to convert warnings into test failures.
		set_error_handler(
			function ( $severity, $message, $file, $line ) {
				throw new \ErrorException( $message, 0, $severity, $file, $line ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Rethrowing error as exception for testing.
			}
		);

		try {
			$result = edac_get_upcoming_meetups_json( $meetup, $count );
		} finally {
			restore_error_handler();
			if ( $filter ) {
				remove_filter( 'pre_http_request', $filter, 10 );
			}
		}

		return $result;
	}

	/**
	 * Ensure empty meetup name returns an empty array.
	 */
	public function test_returns_empty_array_for_empty_meetup() {
		$this->assertSame( [], edac_get_upcoming_meetups_json( '', 1 ) );
	}

	/**
	 * Ensure non-string meetup returns an empty array.
	 */
	public function test_returns_empty_array_for_non_string_meetup() {
		$this->assertSame( [], edac_get_upcoming_meetups_json( 123, 1 ) );
	}

	/**
	 * Ensure null meetup returns an empty array.
	 */
	public function test_returns_empty_array_for_null_meetup() {
		$this->assertSame( [], edac_get_upcoming_meetups_json( null, 1 ) );
	}

	/**
	 * Ensure cached transient is returned without making an HTTP request.
	 */
	public function test_returns_cached_transient_when_available() {
		$cached = [
			(object) [
				'name' => 'Cached Event',
				'time' => 1700000000000,
				'link' => 'https://example.com',
				'id'   => '123',
			],
		];
		set_transient( $this->key, $cached, DAY_IN_SECONDS );

		$result = edac_get_upcoming_meetups_json( 'test-meetup', 1 );

		$this->assertEquals( $cached, $result );
	}

	/**
	 * Ensure a non-array cached transient returns an empty array.
	 */
	public function test_returns_empty_array_for_non_array_cached_transient() {
		set_transient( $this->key, 'not-an-array', DAY_IN_SECONDS );

		$result = edac_get_upcoming_meetups_json( 'test-meetup', 1 );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure a valid API response is parsed into the expected event objects.
	 */
	public function test_parses_valid_api_response() {
		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Test Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://www.meetup.com/test-meetup/events/123/',
					'id'       => 'evt-123',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Test Event', $result[0]->name );
		$this->assertSame( 'https://www.meetup.com/test-meetup/events/123/', $result[0]->link );
		$this->assertSame( 'evt-123', $result[0]->id );
		$this->assertSame( strtotime( '2026-03-01T10:00:00-05:00' ) * 1000, $result[0]->time );
	}

	/**
	 * Ensure the transient and stale option are set after a successful response.
	 */
	public function test_sets_transient_and_stale_option_on_success() {
		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Cache Event',
					'dateTime' => '2026-04-01T12:00:00-05:00',
					'eventUrl' => 'https://www.meetup.com/test-meetup/events/456/',
					'id'       => 'evt-456',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		$this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertNotFalse( get_transient( $this->key ) );
		$this->assertNotFalse( get_option( $this->stale_key ) );
	}

	/**
	 * Ensure invalid JSON responses do not trigger notices.
	 */
	public function test_handles_invalid_json_body() {
		$filter = $this->mock_http_response( 'not json' );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure a WP_Error HTTP response returns empty array.
	 */
	public function test_returns_empty_array_on_wp_error() {
		$filter = function () {
			return new \WP_Error( 'http_request_failed', 'Connection timed out' );
		};

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure a non-200 HTTP status returns empty array.
	 */
	public function test_returns_empty_array_on_non_200_status() {
		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Should Not Appear',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-999',
				],
			]
		);
		$filter = $this->mock_http_response( $body, 500 );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure edges with a missing node are skipped.
	 */
	public function test_skips_edges_with_missing_node() {
		$body = wp_json_encode(
			[
				'data' => [
					'groupByUrlname' => [
						'events' => [
							'totalCount' => 1,
							'edges'      => [
								[ 'not_node' => 'irrelevant' ],
							],
						],
					],
				],
			]
		);

		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure edges with incomplete event fields are skipped.
	 *
	 * @dataProvider provide_incomplete_event_fields
	 *
	 * @param array $event Incomplete event data.
	 */
	public function test_skips_events_with_missing_required_fields( $event ) {
		$body   = $this->build_valid_response_body( [ $event ] );
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Data provider for incomplete event fields.
	 *
	 * @return array
	 */
	public function provide_incomplete_event_fields() {
		return [
			'missing title'    => [
				[
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-1',
				],
			],
			'missing dateTime' => [
				[
					'title'    => 'Event',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-1',
				],
			],
			'missing eventUrl' => [
				[
					'title'    => 'Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'id'       => 'evt-1',
				],
			],
			'missing id'       => [
				[
					'title'    => 'Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
				],
			],
		];
	}

	/**
	 * Ensure events with an unparseable dateTime are skipped.
	 */
	public function test_skips_events_with_invalid_datetime() {
		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Bad Date Event',
					'dateTime' => 'not-a-date',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-bad',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure stale data is served when the API fails and stale option exists.
	 */
	public function test_serves_stale_data_when_api_fails() {
		$stale = [
			(object) [
				'name' => 'Stale Event',
				'time' => 1700000000000,
				'link' => 'https://example.com/stale',
				'id'   => 'stale-1',
			],
		];
		update_option( $this->stale_key, $stale, false );

		$filter = function () {
			return new \WP_Error( 'http_request_failed', 'Timeout' );
		};

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertEquals( $stale, $result );
		// Verify the stale data is also cached in a short-lived transient.
		$this->assertNotFalse( get_transient( $this->key ) );
	}

	/**
	 * Ensure empty array is returned when API fails and no stale data exists.
	 */
	public function test_returns_empty_array_when_api_fails_and_no_stale_data() {
		$filter = function () {
			return new \WP_Error( 'http_request_failed', 'Timeout' );
		};

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure response with missing nested data structure returns empty array.
	 */
	public function test_returns_empty_array_for_response_missing_nested_structure() {
		$body   = wp_json_encode( [ 'data' => [ 'groupByUrlname' => null ] ] );
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure multiple valid events are all returned.
	 */
	public function test_parses_multiple_valid_events() {
		$body = $this->build_valid_response_body(
			[
				[
					'title'    => 'Event One',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com/1',
					'id'       => 'evt-1',
				],
				[
					'title'    => 'Event Two',
					'dateTime' => '2026-04-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com/2',
					'id'       => 'evt-2',
				],
			]
		);

		// Need a key that matches count=2.
		$key = '_upcoming_meetups__test-meetup__2';
		delete_transient( $key );
		delete_option( $key . '__stale' );

		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 2, $filter );

		$this->assertCount( 2, $result );
		$this->assertSame( 'Event One', $result[0]->name );
		$this->assertSame( 'Event Two', $result[1]->name );
	}

	/**
	 * Ensure valid events are returned even when mixed with invalid ones.
	 */
	public function test_returns_only_valid_events_from_mixed_edges() {
		$body = wp_json_encode(
			[
				'data' => [
					'groupByUrlname' => [
						'events' => [
							'totalCount' => 3,
							'edges'      => [
								[
									'node' => [
										'title'    => 'Valid Event',
										'dateTime' => '2026-03-01T10:00:00-05:00',
										'eventUrl' => 'https://example.com/valid',
										'id'       => 'evt-valid',
									],
								],
								[ 'not_node' => 'missing node' ],
								[
									'node' => [
										'title'    => '',
										'dateTime' => '2026-03-02T10:00:00-05:00',
										'eventUrl' => 'https://example.com/empty-title',
										'id'       => 'evt-empty',
									],
								],
							],
						],
					],
				],
			]
		);

		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Valid Event', $result[0]->name );
	}

	/**
	 * Ensure count is clamped to minimum of 1.
	 */
	public function test_count_is_clamped_to_minimum_of_one() {
		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Clamped Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-clamp',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		// Passing 0 should be clamped to 1; the key should use count=1.
		$result = $this->call_with_error_handler( 'test-meetup', 0, $filter );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Clamped Event', $result[0]->name );
	}

	/**
	 * Ensure the event time is stored in milliseconds.
	 */
	public function test_event_time_is_in_milliseconds() {
		$date_string = '2026-06-15T14:30:00-04:00';
		$body        = $this->build_valid_response_body(
			[
				[
					'title'    => 'Millis Event',
					'dateTime' => $date_string,
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-ms',
				],
			]
		);
		$filter      = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$expected_ms = strtotime( $date_string ) * 1000;
		$this->assertSame( $expected_ms, $result[0]->time );
	}

	/**
	 * Ensure count is clamped to a maximum of 25.
	 */
	public function test_count_is_clamped_to_maximum_of_twenty_five() {
		$key = '_upcoming_meetups__test-meetup__25';
		delete_transient( $key );
		delete_option( $key . '__stale' );

		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Max Count Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-max',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		// Passing 100 should be clamped to 25; the cache key should use count=25.
		$result = $this->call_with_error_handler( 'test-meetup', 100, $filter );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Max Count Event', $result[0]->name );
		$this->assertNotFalse( get_transient( $key ) );
	}

	/**
	 * Ensure negative count is clamped to 1.
	 */
	public function test_negative_count_is_clamped_to_one() {
		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Negative Count Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-neg',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		// Passing -5 should be clamped to 1; the key should use count=1.
		$result = $this->call_with_error_handler( 'test-meetup', -5, $filter );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Negative Count Event', $result[0]->name );
	}

	/**
	 * Ensure boolean true meetup returns an empty array since it is not a string.
	 */
	public function test_returns_empty_array_for_boolean_meetup() {
		$this->assertSame( [], edac_get_upcoming_meetups_json( true, 1 ) );
	}

	/**
	 * Ensure array meetup returns an empty array.
	 */
	public function test_returns_empty_array_for_array_meetup() {
		$this->assertSame( [], edac_get_upcoming_meetups_json( [ 'test-meetup' ], 1 ) );
	}

	/**
	 * Ensure the meetup name is sanitized for the cache key via sanitize_title.
	 */
	public function test_meetup_name_is_sanitized_for_cache_key() {
		$sanitized = sanitize_title( 'Test Meetup!!' );
		$key       = '_upcoming_meetups__' . $sanitized . '__1';
		delete_transient( $key );
		delete_option( $key . '__stale' );

		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Sanitized Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-san',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'Test Meetup!!', 1, $filter );

		$this->assertCount( 1, $result );
		$this->assertNotFalse( get_transient( $key ) );
	}

	/**
	 * Ensure a valid response with an empty edges array returns an empty array.
	 */
	public function test_returns_empty_array_for_empty_edges() {
		$body = wp_json_encode(
			[
				'data' => [
					'groupByUrlname' => [
						'events' => [
							'totalCount' => 0,
							'edges'      => [],
						],
					],
				],
			]
		);

		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure stale option that is not an array is not served as fallback.
	 */
	public function test_returns_empty_array_when_stale_is_not_array() {
		update_option( $this->stale_key, 'not-an-array', false );

		$filter = function () {
			return new \WP_Error( 'http_request_failed', 'Timeout' );
		};

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure an empty stale array is not served as fallback.
	 */
	public function test_returns_empty_array_when_stale_is_empty_array() {
		update_option( $this->stale_key, [], false );

		$filter = function () {
			return new \WP_Error( 'http_request_failed', 'Timeout' );
		};

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure the default count parameter (5) is used when count is not provided.
	 */
	public function test_uses_default_count_of_five() {
		$key = '_upcoming_meetups__test-meetup__5';
		delete_transient( $key );
		delete_option( $key . '__stale' );

		$events = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$events[] = [
				'title'    => 'Default Count Event ' . $i,
				'dateTime' => '2026-03-0' . $i . 'T10:00:00-05:00',
				'eventUrl' => 'https://example.com/' . $i,
				'id'       => 'evt-def-' . $i,
			];
		}

		$body   = $this->build_valid_response_body( $events );
		$filter = $this->mock_http_response( $body );

		add_filter( 'pre_http_request', $filter, 10, 3 );

		$result = edac_get_upcoming_meetups_json( 'test-meetup' );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertCount( 5, $result );
		$this->assertNotFalse( get_transient( $key ) );
	}

	/**
	 * Ensure that when a cached transient exists, no HTTP request is made.
	 */
	public function test_cached_transient_prevents_http_request() {
		$cached = [
			(object) [
				'name' => 'Cached',
				'time' => 1700000000000,
				'link' => 'https://example.com',
				'id'   => 'cached-1',
			],
		];
		set_transient( $this->key, $cached, DAY_IN_SECONDS );

		$http_called = false;
		$filter      = function () use ( &$http_called ) {
			$http_called = true;
			return [
				'headers'  => [],
				'body'     => '',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$result = edac_get_upcoming_meetups_json( 'test-meetup', 1 );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertFalse( $http_called, 'HTTP request should not be made when transient is cached.' );
		$this->assertEquals( $cached, $result );
	}

	/**
	 * Ensure that stale data served on API failure is cached with a short-lived transient.
	 */
	public function test_stale_fallback_sets_short_lived_transient() {
		$stale = [
			(object) [
				'name' => 'Stale Short TTL',
				'time' => 1700000000000,
				'link' => 'https://example.com/stale',
				'id'   => 'stale-ttl',
			],
		];
		update_option( $this->stale_key, $stale, false );

		$filter = function () {
			return new \WP_Error( 'http_request_failed', 'Timeout' );
		};

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertEquals( $stale, $result );
		// The transient should now be set so a subsequent call returns cached data.
		$this->assertNotFalse( get_transient( $this->key ) );
		$this->assertEquals( $stale, get_transient( $this->key ) );
	}

	/**
	 * Ensure a non-200 status falls back to stale data when available.
	 */
	public function test_non_200_status_serves_stale_data() {
		$stale = [
			(object) [
				'name' => 'Stale on 500',
				'time' => 1700000000000,
				'link' => 'https://example.com/stale500',
				'id'   => 'stale-500',
			],
		];
		update_option( $this->stale_key, $stale, false );

		$filter = $this->mock_http_response( '{}', 500 );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertEquals( $stale, $result );
	}

	/**
	 * Ensure a response body with a top-level array (not object) returns empty array.
	 */
	public function test_returns_empty_array_for_json_array_body() {
		$body   = wp_json_encode( [ 'not an object structure' ] );
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure a response with edges containing null values is handled safely.
	 */
	public function test_handles_null_node_in_edges() {
		$body = wp_json_encode(
			[
				'data' => [
					'groupByUrlname' => [
						'events' => [
							'totalCount' => 1,
							'edges'      => [
								[ 'node' => null ],
							],
						],
					],
				],
			]
		);

		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure event properties are cast to strings.
	 */
	public function test_event_properties_are_cast_to_strings() {
		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 12345,
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 67890,
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertCount( 1, $result );
		$this->assertIsString( $result[0]->name );
		$this->assertSame( '12345', $result[0]->name );
		$this->assertIsString( $result[0]->link );
		$this->assertIsString( $result[0]->id );
		$this->assertSame( '67890', $result[0]->id );
	}

	/**
	 * Ensure successful response updates stale option to latest data.
	 */
	public function test_successful_response_updates_stale_option() {
		// Pre-set stale data with old values.
		$old_stale = [
			(object) [
				'name' => 'Old Stale Event',
				'time' => 1600000000000,
				'link' => 'https://example.com/old',
				'id'   => 'old-1',
			],
		];
		update_option( $this->stale_key, $old_stale, false );

		$body   = $this->build_valid_response_body(
			[
				[
					'title'    => 'Fresh Event',
					'dateTime' => '2026-05-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com/fresh',
					'id'       => 'evt-fresh',
				],
			]
		);
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Fresh Event', $result[0]->name );

		// The stale option should now contain the fresh data, not the old stale data.
		$updated_stale = get_option( $this->stale_key );
		$this->assertIsArray( $updated_stale );
		$this->assertSame( 'Fresh Event', $updated_stale[0]->name );
	}

	/**
	 * Ensure response with data key but missing groupByUrlname returns empty array.
	 */
	public function test_returns_empty_array_when_group_by_urlname_missing() {
		$body   = wp_json_encode( [ 'data' => [ 'other' => 'value' ] ] );
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure response with events key but missing edges returns empty array.
	 */
	public function test_returns_empty_array_when_edges_missing() {
		$body = wp_json_encode(
			[
				'data' => [
					'groupByUrlname' => [
						'events' => [
							'totalCount' => 0,
						],
					],
				],
			]
		);

		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Ensure events with empty string fields are skipped.
	 *
	 * @dataProvider provide_empty_string_fields
	 *
	 * @param array $event Event data with an empty string field.
	 */
	public function test_skips_events_with_empty_string_fields( $event ) {
		$body   = $this->build_valid_response_body( [ $event ] );
		$filter = $this->mock_http_response( $body );

		$result = $this->call_with_error_handler( 'test-meetup', 1, $filter );

		$this->assertSame( [], $result );
	}

	/**
	 * Data provider for events with empty string fields.
	 *
	 * @return array
	 */
	public function provide_empty_string_fields() {
		return [
			'empty title'    => [
				[
					'title'    => '',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-1',
				],
			],
			'empty dateTime' => [
				[
					'title'    => 'Event',
					'dateTime' => '',
					'eventUrl' => 'https://example.com',
					'id'       => 'evt-1',
				],
			],
			'empty eventUrl' => [
				[
					'title'    => 'Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => '',
					'id'       => 'evt-1',
				],
			],
			'empty id'       => [
				[
					'title'    => 'Event',
					'dateTime' => '2026-03-01T10:00:00-05:00',
					'eventUrl' => 'https://example.com',
					'id'       => '',
				],
			],
		];
	}
}
