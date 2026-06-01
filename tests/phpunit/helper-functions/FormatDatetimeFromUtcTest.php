<?php
/**
 * Tests for the edac_format_datetime_from_utc helper.
 *
 * @package Accessibility_Checker
 * @since 1.37.0
 */

/**
 * Tests for edac_format_datetime_from_utc.
 *
 * @covers ::edac_format_datetime_from_utc
 * @since 1.37.0
 */
class FormatDatetimeFromUtcTest extends WP_UnitTestCase {

	/**
	 * Stored date format option.
	 *
	 * @var string
	 */
	protected $original_date_format;

	/**
	 * Stored time format option.
	 *
	 * @var string
	 */
	protected $original_time_format;

	/**
	 * Stored timezone string option.
	 *
	 * @var string
	 */
	protected $original_timezone_string;

	/**
	 * Set up test fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_date_format     = get_option( 'date_format' );
		$this->original_time_format     = get_option( 'time_format' );
		$this->original_timezone_string = get_option( 'timezone_string' );
	}

	/**
	 * Tear down test fixture.
	 */
	public function tearDown(): void {
		update_option( 'date_format', $this->original_date_format );
		update_option( 'time_format', $this->original_time_format );
		update_option( 'timezone_string', $this->original_timezone_string );

		parent::tearDown();
	}

	/**
	 * Tests the edac_format_datetime_from_utc function with valid input.
	 *
	 * @dataProvider provider_valid_datetime_values
	 *
	 * @param string $timezone        Timezone string.
	 * @param string $input_datetime  UTC datetime string.
	 * @param string $expected_output Expected formatted datetime string.
	 */
	public function test_edac_format_datetime_from_utc_formats_output( string $timezone, string $input_datetime, string $expected_output ): void {
		update_option( 'date_format', 'Y-m-d' );
		update_option( 'time_format', 'H:i' );
		update_option( 'timezone_string', $timezone );

		$formatted = edac_format_datetime_from_utc( $input_datetime );

		$this->assertSame( $expected_output, $formatted );
	}

	/**
	 * Data provider for valid datetime values.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_valid_datetime_values(): array {
		return [
			'New York (DST)'      => [ 'America/New_York', '2024-07-01 12:00:00', '2024-07-01 08:00' ],
			'New York (Standard)' => [ 'America/New_York', '2024-01-01 12:00:00', '2024-01-01 07:00' ],
			'London (DST)'        => [ 'Europe/London', '2024-07-01 12:00:00', '2024-07-01 13:00' ],
			'London (Standard)'   => [ 'Europe/London', '2024-01-01 12:00:00', '2024-01-01 12:00' ],
		];
	}

	/**
	 * Tests invalid values return an empty string.
	 *
	 * @dataProvider provider_invalid_datetime_values
	 *
	 * @param string $value Invalid datetime value.
	 */
	public function test_edac_format_datetime_from_utc_invalid_values( string $value ): void {
		$this->assertSame( '', edac_format_datetime_from_utc( $value ) );
	}

	/**
	 * Data provider for invalid datetime values.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_invalid_datetime_values(): array {
		return [
			'empty string'           => [ '' ],
			'zero datetime'          => [ '0000-00-00 00:00:00' ],
			'non-date string'        => [ 'not a date' ],
			'invalid month datetime' => [ '2024-13-01 00:00:00' ],
		];
	}
}
