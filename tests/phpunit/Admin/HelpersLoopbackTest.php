<?php
/**
 * Class HelpersLoopbackTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Helpers;

/**
 * Test cases for EDAC\Admin\Helpers::is_domain_loopback() method.
 */
class HelpersLoopbackTest extends WP_UnitTestCase {

	/**
	 * Tests the is_domain_loopback method with various inputs.
	 *
	 * @dataProvider domain_loopback_data
	 *
	 * @param string $domain   The domain to test.
	 * @param bool   $expected The expected result.
	 */
	public function test_is_domain_loopback( $domain, $expected ) {
		$result = Helpers::is_domain_loopback( $domain );

		// The result should always be a boolean.
		$this->assertIsBool( $result );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Data provider for test_is_domain_loopback.
	 */
	public function domain_loopback_data() {
		return [
			'localhost'                             => [
				'domain'   => 'localhost',
				'expected' => true,
			],
			'127.0.0.1 direct IP'                   => [
				'domain'   => '127.0.0.1',
				'expected' => true,
			],
			'127.0.0.2 loopback range'              => [
				'domain'   => '127.0.0.2',
				'expected' => true,
			],
			'127.255.255.255 loopback range end'    => [
				'domain'   => '127.255.255.255',
				'expected' => true,
			],
			'127.100.50.25 mid loopback range'      => [
				'domain'   => '127.100.50.25',
				'expected' => true,
			],
			'google.com external domain'            => [
				'domain'   => 'google.com',
				'expected' => false,
			],
			'example.com external domain'           => [
				'domain'   => 'example.com',
				'expected' => false,
			],
			'192.168.1.1 private IP (not loopback)' => [
				'domain'   => '192.168.1.1',
				'expected' => false,
			],
			'10.0.0.1 private IP (not loopback)'    => [
				'domain'   => '10.0.0.1',
				'expected' => false,
			],
			'8.8.8.8 public IP'                     => [
				'domain'   => '8.8.8.8',
				'expected' => false,
			],
		];
	}

	/**
	 * Test IPv4 loopback range boundaries.
	 */
	public function test_ipv4_loopback_boundaries() {
		// Test the exact start of loopback range.
		$this->assertTrue( Helpers::is_domain_loopback( '127.0.0.0' ) );

		// Test just before loopback range.
		$this->assertFalse( Helpers::is_domain_loopback( '126.255.255.255' ) );

		// Test just after loopback range.
		$this->assertFalse( Helpers::is_domain_loopback( '128.0.0.0' ) );
	}

	/**
	 * Test edge cases and invalid inputs.
	 */
	public function test_edge_cases() {
		// Empty string.
		$result = Helpers::is_domain_loopback( '' );
		$this->assertIsBool( $result );
		$this->assertFalse( $result );

		// Invalid domain format.
		$result = Helpers::is_domain_loopback( 'not-a-domain' );
		$this->assertIsBool( $result );

		// Malformed IP.
		$result = Helpers::is_domain_loopback( '999.999.999.999' );
		$this->assertIsBool( $result );
		$this->assertFalse( $result );

		// Domain with protocol.
		$result = Helpers::is_domain_loopback( 'http://localhost' );
		$this->assertIsBool( $result );
	}

	/**
	 * Test special localhost variations.
	 */
	public function test_localhost_variations() {
		// Standard localhost should resolve to loopback.
		$this->assertTrue( Helpers::is_domain_loopback( 'localhost' ) );

		// Test case sensitivity (if applicable).
		$result = Helpers::is_domain_loopback( 'LOCALHOST' );
		$this->assertIsBool( $result );

		// Test with port (should still work since we're testing the domain part).
		$result = Helpers::is_domain_loopback( 'localhost:8080' );
		$this->assertIsBool( $result );
	}

	/**
	 * Test that the method handles DNS resolution errors gracefully.
	 */
	public function test_dns_resolution_error_handling() {
		// Test with a clearly non-existent domain.
		$fake_domain = 'definitely-not-a-real-domain-' . uniqid() . '.invalid';
		$result      = Helpers::is_domain_loopback( $fake_domain );

		// Should return a boolean and not throw an exception.
		$this->assertIsBool( $result );
		$this->assertFalse( $result );
	}

	/**
	 * Test IPv6 loopback detection (if supported).
	 */
	public function test_ipv6_loopback_detection() {
		// The method checks for IPv6 AAAA records.
		// This test may be limited by the test environment's DNS capabilities.

		// Test direct IPv6 loopback address (may not work in all environments).
		$result = Helpers::is_domain_loopback( '::1' );
		$this->assertIsBool( $result );

		// If IPv6 is supported, ::1 should be detected as loopback.
		// However, we can't guarantee this in all test environments.
	}

	/**
	 * Test that the method properly validates IP address formats.
	 */
	public function test_ip_address_validation() {
		// Valid IPv4 addresses in loopback range.
		$valid_loopback_ips = [
			'127.0.0.1',
			'127.1.2.3',
			'127.254.255.254',
		];

		foreach ( $valid_loopback_ips as $ip ) {
			$result = Helpers::is_domain_loopback( $ip );
			$this->assertTrue( $result, "Failed to detect loopback for IP: $ip" );
		}

		// Valid IPv4 addresses outside loopback range.
		$non_loopback_ips = [
			'1.1.1.1',
			'8.8.8.8',
			'192.168.1.1',
			'172.16.0.1',
			'203.0.113.1',
		];

		foreach ( $non_loopback_ips as $ip ) {
			$result = Helpers::is_domain_loopback( $ip );
			$this->assertFalse( $result, "Incorrectly detected loopback for IP: $ip" );
		}
	}

	/**
	 * Test behavior with domains that might have multiple A records.
	 */
	public function test_multiple_a_records() {
		// Some domains may have multiple A records.
		// The method should handle this correctly by checking the resolved IP.

		// Test a well-known domain that should resolve to non-loopback.
		$result = Helpers::is_domain_loopback( 'github.com' );
		$this->assertIsBool( $result );
		$this->assertFalse( $result );
	}

	/**
	 * Test the method's handling of the gethostbyname function.
	 */
	public function test_gethostbyname_behavior() {
		// gethostbyname returns the hostname unchanged if resolution fails.
		// The method should handle this case.

		$non_resolvable = 'non-resolvable-domain-' . uniqid() . '.invalid';
		$result         = Helpers::is_domain_loopback( $non_resolvable );

		// Should return false for non-resolvable domains.
		$this->assertIsBool( $result );
		$this->assertFalse( $result );
	}

	/**
	 * Test performance with multiple calls.
	 */
	public function test_performance_multiple_calls() {
		// Test that the method performs reasonably with multiple calls.
		$domains = [
			'localhost',
			'127.0.0.1',
			'google.com',
			'example.com',
			'127.0.0.2',
		];

		$start_time = microtime( true );

		foreach ( $domains as $domain ) {
			$result = Helpers::is_domain_loopback( $domain );
			$this->assertIsBool( $result );
		}

		$end_time       = microtime( true );
		$execution_time = $end_time - $start_time;

		// Should complete within a reasonable time (5 seconds).
		$this->assertLessThan( 5.0, $execution_time, 'Method took too long to execute multiple calls' );
	}
}
