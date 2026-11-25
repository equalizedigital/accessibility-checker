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
		// A post ID that does not exist is a 404 whatever the permalink structure is.
		$this->go_to( home_url( '/?p=999999999' ) );
		$_GET['edac_pageScanner'] = '1';

		Enqueue_Frontend::maybe_enqueue_scan_context();

		$this->assertStringContainsString( '"is404":"1"', $this->printed_data() );
		$this->assertStringContainsString( '"postId":"0"', $this->printed_data() );
	}

	/**
	 * The page that shows the latest posts names itself, not the first post of the list.
	 */
	public function test_the_posts_page_names_itself(): void {
		$posts_page = self::factory()->post->create( [ 'post_type' => 'page' ] );
		self::factory()->post->create();
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $posts_page );
		$this->go_to( get_permalink( $posts_page ) );
		$_GET['edac_pageScanner'] = '1';

		Enqueue_Frontend::maybe_enqueue_scan_context();

		$this->assertStringContainsString( '"postId":"' . $posts_page . '"', $this->printed_data() );
		$this->assertStringContainsString( '"virtualItemId":"0"', $this->printed_data() );

		delete_option( 'page_for_posts' );
		update_option( 'show_on_front', 'posts' );
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

	/**
	 * Saving scan results for a post whose status is no longer scanned is refused when the request is from a full site scan.
	 */
	public function test_a_full_site_scan_save_for_an_unscanned_status_is_refused(): void {
		// Posts are one of the scanned post types, so only the status can make the save be refused.
		update_option( 'edac_post_types', [ 'post' ] );
		$post_id = self::factory()->post->create( [ 'post_status' => 'trash' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/accessibility-checker/v1/post-scan-results/' . $post_id );
		$request->set_param( 'violations', [] );
		$request->set_param( 'edacpScanToken', 'lease-token' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 409, $response->get_status() );
	}
}
