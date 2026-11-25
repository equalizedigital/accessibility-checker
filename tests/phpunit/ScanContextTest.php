<?php
/**
 * PHPUnit tests for the context printed on page scan requests.
 *
 * @package Accessibility_Checker\Tests
 */

use EDAC\Inc\Enqueue_Frontend;

/**
 * Class ScanContextTest
 */
class ScanContextTest extends WP_UnitTestCase {

	/**
	 * Leave no scan flag or enqueued handle behind.
	 */
	public function tear_down() {
		unset( $_GET['edac_pageScanner'] );
		wp_dequeue_script( 'edac-scan-context' );
		wp_deregister_script( 'edac-scan-context' );
		parent::tear_down();
	}

	/**
	 * The localized data printed for the handle.
	 *
	 * @return string
	 */
	private function printed_data(): string {
		return (string) wp_scripts()->get_data( 'edac-scan-context', 'data' );
	}

	/**
	 * A page scan request of a post gets that post's ID.
	 */
	public function test_a_scan_request_names_the_post_shown(): void {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		$_GET['edac_pageScanner'] = '1';

		Enqueue_Frontend::maybe_enqueue_scan_context();

		$this->assertTrue( wp_script_is( 'edac-scan-context', 'enqueued' ) );
		$this->assertStringContainsString( '"postId":"' . $post_id . '"', $this->printed_data() );
		$this->assertStringContainsString( '"is404":""', $this->printed_data() );
	}

	/**
	 * A page scan request that lands on WordPress's not-found page says so.
	 */
	public function test_a_scan_request_for_a_missing_page_says_it_is_a_404(): void {
		$this->go_to( home_url( '/no-such-page-' . wp_generate_password( 6, false ) . '/' ) );
		$_GET['edac_pageScanner'] = '1';

		Enqueue_Frontend::maybe_enqueue_scan_context();

		$this->assertStringContainsString( '"is404":"1"', $this->printed_data() );
		$this->assertStringContainsString( '"postId":"0"', $this->printed_data() );
	}

	/**
	 * Nothing is added for a visitor.
	 */
	public function test_an_ordinary_request_gets_no_scan_context(): void {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		Enqueue_Frontend::maybe_enqueue_scan_context();

		$this->assertFalse( wp_script_is( 'edac-scan-context', 'enqueued' ) );
	}
}
