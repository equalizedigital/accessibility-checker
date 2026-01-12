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
	 */
	public function test_edac_format_datetime_from_utc_formats_output(): void {
		update_option( 'date_format', 'Y-m-d' );
		update_option( 'time_format', 'H:i' );
		update_option( 'timezone_string', 'America/New_York' );

		$formatted = edac_format_datetime_from_utc( '2024-07-01 12:00:00' );

		$this->assertSame( '2024-07-01 08:00', $formatted );
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
