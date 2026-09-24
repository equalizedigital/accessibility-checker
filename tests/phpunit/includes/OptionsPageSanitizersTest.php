<?php
/**
 * Tests for the sanitize callbacks defined in includes/options-page.php.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for the edac_sanitize_* settings callbacks.
 *
 * @covers ::edac_sanitize_scan_speed
 * @covers ::edac_sanitize_simplified_summary_position
 * @covers ::edac_sanitize_frontend_highlighter_position
 * @covers ::edac_sanitize_simplified_summary_prompt
 * @covers ::edac_sanitize_accessibility_policy_page
 * @covers ::edac_sanitize_pro_scan_speed
 * @covers ::edac_sanitize_pro_checkbox
 * @covers ::edac_sanitize_pro_archive_scanning
 * @covers ::edac_sanitize_pro_taxonomy_terms
 * @covers ::edac_sanitize_pro_summary_heading
 */
class OptionsPageSanitizersTest extends WP_UnitTestCase {

	/**
	 * Options set by the pro-wrapper tests, cleaned up on teardown.
	 *
	 * @var string[]
	 */
	private $options_to_clean = [
		'edacp_full_site_scan_speed',
		'edacp_enable_archive_scanning',
		'edacp_scan_all_taxonomies',
		'edacp_simplified_summary_heading',
		'edacp_test_checkbox',
	];

	/**
	 * Remove any options the pro-wrapper tests may have set.
	 */
	public function tearDown(): void {
		foreach ( $this->options_to_clean as $option ) {
			delete_option( $option );
		}

		parent::tearDown();
	}

	/**
	 * Known scan speed values are returned unchanged.
	 *
	 * @dataProvider provider_valid_scan_speeds
	 *
	 * @param string $speed A valid speed value.
	 */
	public function test_edac_sanitize_scan_speed_allows_known_values( string $speed ): void {
		$this->assertSame( $speed, edac_sanitize_scan_speed( $speed ) );
	}

	/**
	 * Data provider for valid scan speeds.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_valid_scan_speeds(): array {
		return [
			'250ms'   => [ '250' ],
			'1000ms'  => [ '1000' ],
			'5000ms'  => [ '5000' ],
			'30000ms' => [ '30000' ],
		];
	}

	/**
	 * Anything outside the whitelist falls back to the 1000ms default.
	 *
	 * Note the comparison is strict, so the integer 250 is not accepted.
	 *
	 * @dataProvider provider_invalid_scan_speeds
	 *
	 * @param mixed $speed An invalid speed value.
	 */
	public function test_edac_sanitize_scan_speed_falls_back_to_default( $speed ): void {
		$this->assertSame( '1000', edac_sanitize_scan_speed( $speed ) );
	}

	/**
	 * Data provider for invalid scan speeds.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_invalid_scan_speeds(): array {
		return [
			'empty string'   => [ '' ],
			'unknown number' => [ '100' ],
			'non numeric'    => [ 'fast' ],
			'integer 250'    => [ 250 ],
			'null'           => [ null ],
		];
	}

	/**
	 * Valid simplified summary positions pass through, anything else is null.
	 *
	 * @dataProvider provider_simplified_summary_positions
	 *
	 * @param mixed       $input    Input value.
	 * @param string|null $expected Expected result.
	 */
	public function test_edac_sanitize_simplified_summary_position( $input, $expected ): void {
		$this->assertSame( $expected, edac_sanitize_simplified_summary_position( $input ) );
	}

	/**
	 * Data provider for simplified summary positions.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_simplified_summary_positions(): array {
		return [
			'before'       => [ 'before', 'before' ],
			'after'        => [ 'after', 'after' ],
			'none'         => [ 'none', 'none' ],
			'unknown'      => [ 'top', null ],
			'empty string' => [ '', null ],
		];
	}

	/**
	 * Frontend highlighter position only allows right/left, defaulting to right.
	 *
	 * @dataProvider provider_frontend_highlighter_positions
	 *
	 * @param string $input    Input value.
	 * @param string $expected Expected result.
	 */
	public function test_edac_sanitize_frontend_highlighter_position( string $input, string $expected ): void {
		$this->assertSame( $expected, edac_sanitize_frontend_highlighter_position( $input ) );
	}

	/**
	 * Data provider for frontend highlighter positions.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_frontend_highlighter_positions(): array {
		return [
			'right'        => [ 'right', 'right' ],
			'left'         => [ 'left', 'left' ],
			'unknown'      => [ 'top', 'right' ],
			'empty string' => [ '', 'right' ],
		];
	}

	/**
	 * Valid summary prompts pass through, anything else is null.
	 *
	 * @dataProvider provider_simplified_summary_prompts
	 *
	 * @param mixed       $input    Input value.
	 * @param string|null $expected Expected result.
	 */
	public function test_edac_sanitize_simplified_summary_prompt( $input, $expected ): void {
		$this->assertSame( $expected, edac_sanitize_simplified_summary_prompt( $input ) );
	}

	/**
	 * Data provider for simplified summary prompts.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_simplified_summary_prompts(): array {
		return [
			'when required' => [ 'when required', 'when required' ],
			'always'        => [ 'always', 'always' ],
			'none'          => [ 'none', 'none' ],
			'unknown'       => [ 'never', null ],
			'empty string'  => [ '', null ],
		];
	}

	/**
	 * A non-empty policy page is run through esc_url, falsy input returns null.
	 *
	 * @dataProvider provider_accessibility_policy_pages
	 *
	 * @param mixed       $input    Input value.
	 * @param string|null $expected Expected result.
	 */
	public function test_edac_sanitize_accessibility_policy_page( $input, $expected ): void {
		$this->assertSame( $expected, edac_sanitize_accessibility_policy_page( $input ) );
	}

	/**
	 * Data provider for accessibility policy page values.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_accessibility_policy_pages(): array {
		return [
			'plain url'         => [ 'https://example.com/policy', 'https://example.com/policy' ],
			'ampersand escaped' => [ 'https://example.com/?a=1&b=2', 'https://example.com/?a=1&#038;b=2' ],
			'empty string'      => [ '', null ],
			'null'              => [ null, null ],
		];
	}

	/**
	 * With Pro inactive the scan speed wrapper preserves the stored option.
	 */
	public function test_edac_sanitize_pro_scan_speed_preserves_value_when_not_pro(): void {
		if ( edac_is_pro() ) {
			$this->markTestSkipped( 'Pro constants are defined in this environment.' );
		}

		$this->assertSame( '1000', edac_sanitize_pro_scan_speed( '250' ) );

		update_option( 'edacp_full_site_scan_speed', '5000' );
		$this->assertSame( '5000', edac_sanitize_pro_scan_speed( '250' ) );
	}

	/**
	 * With Pro inactive the checkbox wrapper returns the stored option value.
	 */
	public function test_edac_sanitize_pro_checkbox_preserves_value_when_not_pro(): void {
		if ( edac_is_pro() ) {
			$this->markTestSkipped( 'Pro constants are defined in this environment.' );
		}

		$this->assertSame( 0, edac_sanitize_pro_checkbox( '1', 'edacp_test_checkbox' ) );

		update_option( 'edacp_test_checkbox', 1 );
		$result = edac_sanitize_pro_checkbox( '1', 'edacp_test_checkbox' );
		$this->assertSame( 1, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * The pro-save action only fires when Pro is active, so nothing is dispatched here.
	 */
	public function test_edac_sanitize_pro_checkbox_does_not_dispatch_action_when_not_pro(): void {
		if ( edac_is_pro() ) {
			$this->markTestSkipped( 'Pro constants are defined in this environment.' );
		}

		$before = did_action( 'edac_pro_setting_saving_checkbox' );

		edac_sanitize_pro_checkbox( '1', 'edacp_test_checkbox' );

		$this->assertSame( $before, did_action( 'edac_pro_setting_saving_checkbox' ) );
	}

	/**
	 * The archive scanning wrapper reads its own option when Pro is inactive.
	 */
	public function test_edac_sanitize_pro_archive_scanning_preserves_value_when_not_pro(): void {
		if ( edac_is_pro() ) {
			$this->markTestSkipped( 'Pro constants are defined in this environment.' );
		}

		update_option( 'edacp_enable_archive_scanning', 1 );
		$this->assertSame( 1, edac_sanitize_pro_archive_scanning( '1' ) );
	}

	/**
	 * The taxonomy terms wrapper reads its own option when Pro is inactive.
	 */
	public function test_edac_sanitize_pro_taxonomy_terms_preserves_value_when_not_pro(): void {
		if ( edac_is_pro() ) {
			$this->markTestSkipped( 'Pro constants are defined in this environment.' );
		}

		update_option( 'edacp_scan_all_taxonomies', 1 );
		$this->assertSame( 1, edac_sanitize_pro_taxonomy_terms( '1' ) );
	}

	/**
	 * The summary heading wrapper falls back to the default heading when Pro is inactive.
	 */
	public function test_edac_sanitize_pro_summary_heading_preserves_value_when_not_pro(): void {
		if ( edac_is_pro() ) {
			$this->markTestSkipped( 'Pro constants are defined in this environment.' );
		}

		$this->assertSame(
			'Simplified Summary',
			edac_sanitize_pro_summary_heading( 'Custom heading' )
		);

		update_option( 'edacp_simplified_summary_heading', 'Custom heading' );
		$this->assertSame(
			'Custom heading',
			edac_sanitize_pro_summary_heading( 'Ignored' )
		);
	}
}
