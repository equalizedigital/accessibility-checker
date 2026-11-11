<?php
/**
 * Tests for the effective license context.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\LicenseContext;

/**
 * Test cases for fallback-aware license context resolution.
 *
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\LicenseContext
 */
class LicenseContextTest extends WP_UnitTestCase {

	/**
	 * Option names the context reads.
	 *
	 * @var string[]
	 */
	private $options = [
		'edac_license_status',
		'edacp_license_status',
		'edac_site_id',
		'edac_fallback_active',
	];

	/**
	 * Clear the options the context reads.
	 */
	public function setUp(): void {
		parent::setUp();
		array_map( 'delete_option', $this->options );
	}

	/**
	 * Clear the options the context reads.
	 */
	public function tearDown(): void {
		array_map( 'delete_option', $this->options );
		parent::tearDown();
	}

	/**
	 * Valid Pro remains authoritative when it is installed and connected.
	 */
	public function test_resolve_uses_pro_when_pro_is_valid() {
		$context = LicenseContext::resolve( true, 'valid', 'valid', 'site-123', false );

		$this->assertTrue( $context['has_pro_plugin'] );
		$this->assertTrue( $context['is_pro'] );
		$this->assertSame( 'valid', $context['status'] );
		$this->assertTrue( $context['is_connected'] );
	}

	/**
	 * Falls back to free state when Pro is installed but no longer valid.
	 */
	public function test_resolve_falls_back_to_free_when_pro_is_invalid() {
		$context = LicenseContext::resolve( true, 'expired', 'valid', 'site-123', false );

		$this->assertTrue( $context['has_pro_plugin'] );
		$this->assertFalse( $context['is_pro'] );
		$this->assertSame( 'valid', $context['status'] );
		$this->assertTrue( $context['is_connected'] );
	}

	/**
	 * The fallback marker does not disconnect the site when Free is valid and the site remains enrolled.
	 */
	public function test_resolve_keeps_free_fallback_connected_when_site_id_exists() {
		$context = LicenseContext::resolve( true, 'expired', 'valid', 'site-123', true );

		$this->assertFalse( $context['is_pro'] );
		$this->assertSame( 'valid', $context['status'] );
		$this->assertTrue( $context['is_connected'] );
	}

	/**
	 * A site with no site ID is neither connected nor registered, even with a valid status.
	 */
	public function test_resolve_requires_a_site_id_to_be_connected() {
		$context = LicenseContext::resolve( false, '', 'valid', '', false );

		$this->assertFalse( $context['is_connected'] );
		$this->assertFalse( $context['is_registered'] );
	}

	/**
	 * Reads the stored license options and resolves them.
	 *
	 * @dataProvider provider_stored_license_options
	 *
	 * @param array  $options               Option name/value pairs to store before reading the context.
	 * @param bool   $expected_is_pro       Expected effective Pro authority.
	 * @param string $expected_status       Expected effective license status.
	 * @param bool   $expected_is_connected Expected effective connection state.
	 */
	public function test_get_current_reads_the_stored_license_options( array $options, bool $expected_is_pro, string $expected_status, bool $expected_is_connected ) {
		foreach ( $options as $option_name => $option_value ) {
			update_option( $option_name, $option_value );
		}

		$context = LicenseContext::get_current();

		$this->assertSame( $expected_is_pro, $context['is_pro'] );
		$this->assertSame( $expected_status, $context['status'] );
		$this->assertSame( $expected_is_connected, $context['is_connected'] );
	}

	/**
	 * Stored license option combinations and the context they resolve to on a
	 * site running the free plugin alone.
	 *
	 * @return array[]
	 */
	public function provider_stored_license_options() {
		return [
			'free license connected'        => [
				[
					'edac_license_status' => 'valid',
					'edac_site_id'        => 'site-123',
				],
				false,
				'valid',
				true,
			],
			'pro status stored without pro' => [
				[ 'edacp_license_status' => 'valid' ],
				false,
				'',
				false,
			],
			'nothing stored'                => [ [], false, '', false ],
		];
	}
}
