<?php
/**
 * AJAX details behavior tests.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Ajax;
use EDAC\Admin\Update_Database;

/**
 * Tests for the AJAX details response.
 */
class AjaxDetailsTest extends WP_Ajax_UnitTestCase {

	/**
	 * AJAX handler under test.
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Post ID used for tests.
	 *
	 * @var int
	 */
	protected static $post_id;

	/**
	 * Create shared fixtures for this test class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		( new Update_Database() )->edac_update_database();

		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$post_id  = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_content' => '<p>Content for AJAX details tests.</p>',
			]
		);

		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clearing prior fixtures for this focused AJAX regression test.
			$table_name,
			[
				'postid' => self::$post_id,
				'siteid' => get_current_blog_id(),
				'rule'   => 'empty_link',
			]
		);
		$base_row = [
			'postid'        => self::$post_id,
			'siteid'        => get_current_blog_id(),
			'type'          => 'post',
			'rule'          => 'empty_link',
			'ruletype'      => 'error',
			'object'        => '<a></a>',
			'selector'      => 'a[href="#"]',
			'recordcheck'   => 1,
			'user'          => self::$admin_id,
			'ignre'         => 0,
			'ignre_global'  => 0,
			'ignre_user'    => null,
			'ignre_date'    => null,
			'ignre_reason'  => null,
			'ignre_comment' => null,
		];

		// First record: no image and no landmark, so both cells are empty.
		$wpdb->insert( $table_name, $base_row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for a focused AJAX regression test.

		// Second record: has an image and a landmark, so both cells have content.
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for a focused AJAX regression test.
			$table_name,
			array_merge(
				$base_row,
				[
					'object'            => '<a href="#"><img src="https://example.org/photo.jpg"></a>',
					'selector'          => 'main a[href="#"]',
					'landmark'          => 'main',
					'landmark_selector' => 'main',
				]
			)
		);
	}

	/**
	 * Clean up shared fixtures for this test class.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleaning focused AJAX regression test fixtures.
			$table_name,
			[
				'postid' => self::$post_id,
				'siteid' => get_current_blog_id(),
				'rule'   => 'empty_link',
			]
		);

		wp_delete_post( self::$post_id, true );
		wp_delete_user( self::$admin_id );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$admin_id );

		$this->ajax = new Ajax();
		add_action( 'wp_ajax_edac_details_ajax', [ $this->ajax, 'details' ] );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		remove_action( 'wp_ajax_edac_details_ajax', [ $this->ajax, 'details' ] );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Verify the expand button references the rendered rule heading.
	 *
	 * @return void
	 */
	public function test_details_expand_button_is_labelled_by_rule_heading(): void {
		$xpath    = $this->get_details_xpath();
		$document = $xpath->document;
		$heading  = $xpath->query( '//h3[@id="edac-details-rule-heading-empty_link"]' );
		$button   = $xpath->query( '//button[@aria-labelledby="edac-details-rule-heading-empty_link"]' );

		$this->assertSame( 1, $heading->length );
		$this->assertSame( 1, $button->length );
		$heading_markup = $document->saveHTML( $heading->item( 0 ) );
		$heading_text   = preg_replace( '/\s+/', ' ', $heading->item( 0 )->textContent );

		$this->assertIsString( $heading_text );
		$this->assertStringContainsString( '2', $heading_text );
		$this->assertStringContainsString( 'total', $heading_markup );
		$this->assertStringContainsString( 'Critical', $heading_text );
	}

	/**
	 * Verify each issue cell has a screen reader only heading for its column.
	 *
	 * @return void
	 */
	public function test_details_record_cells_have_screen_reader_headings(): void {
		$xpath   = $this->get_details_xpath();
		$expects = [
			'edac-details-rule-records-record-object'   => 'Affected Code',
			'edac-details-rule-records-record-image'    => 'Image',
			'edac-details-rule-records-record-landmark' => 'Landmark',
			'edac-details-rule-records-record-actions'  => 'Actions',
		];

		// The visual labels row uses the same column names, in the same order.
		$labels = $xpath->query( '//div[@class="edac-details-rule-records-labels-label"]' );
		$this->assertSame( count( $expects ), $labels->length );
		foreach ( array_values( $expects ) as $index => $label ) {
			$this->assertSame( $label, trim( $labels->item( $index )->textContent ) );
		}

		foreach ( $expects as $cell_class => $label ) {
			$cells = $xpath->query( $this->cell_query( $cell_class ) );
			$this->assertSame( 2, $cells->length, $cell_class );

			foreach ( $cells as $cell ) {
				$headings = $xpath->query( './h5[@class="screen-reader-text"]', $cell );
				$this->assertSame( 1, $headings->length, $cell_class );
				$this->assertSame( $label, trim( $headings->item( 0 )->textContent ) );
			}
		}

		// Each Issue ID heading is followed by its record with all four field headings.
		$records = $xpath->query( '//h4[@class="screen-reader-text"]/following-sibling::div[1][@class="edac-details-rule-records-record"]' );
		$this->assertSame( 2, $records->length );
		foreach ( $records as $record ) {
			$this->assertSame( count( $expects ), $xpath->query( './/h5[@class="screen-reader-text"]', $record )->length );
		}
	}

	/**
	 * Verify empty Image and Landmark cells say "None" to screen readers, and filled cells do not.
	 *
	 * @return void
	 */
	public function test_details_empty_cells_have_screen_reader_none_text(): void {
		$xpath = $this->get_details_xpath();
		$none  = './span[@class="screen-reader-text"][normalize-space()="None"]';

		$image_cells    = $xpath->query( $this->cell_query( 'edac-details-rule-records-record-image' ) );
		$landmark_cells = $xpath->query( $this->cell_query( 'edac-details-rule-records-record-landmark' ) );
		$this->assertSame( 2, $image_cells->length );
		$this->assertSame( 2, $landmark_cells->length );

		$empty_cells  = 0;
		$filled_cells = 0;
		foreach ( [ $image_cells, $landmark_cells ] as $cells ) {
			foreach ( $cells as $cell ) {
				$has_content = $xpath->query( './img | ./a', $cell )->length > 0;
				$none_count  = $xpath->query( $none, $cell )->length;

				$this->assertSame( $has_content ? 0 : 1, $none_count );
				$has_content ? ++$filled_cells : ++$empty_cells;
			}
		}

		// One record has no image or landmark, the other has both.
		$this->assertSame( 2, $empty_cells );
		$this->assertSame( 2, $filled_cells );
	}

	/**
	 * Build an XPath query that matches record cells with the given class.
	 *
	 * @param string $cell_class Cell class name.
	 * @return string
	 */
	private function cell_query( string $cell_class ): string {
		return '//div[contains(concat(" ", normalize-space(@class), " "), " ' . $cell_class . ' ")]';
	}

	/**
	 * Run the details AJAX request and return an XPath for the returned HTML.
	 *
	 * @return DOMXPath
	 */
	private function get_details_xpath(): DOMXPath {
		$_POST['nonce']   = wp_create_nonce( 'ajax-nonce' );
		$_POST['post_id'] = self::$post_id;

		try {
			$this->_handleAjax( 'edac_details_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );

		$encoded_html = $response['data'];
		$this->assertIsString( $encoded_html );
		$this->assertJson( $encoded_html );

		$html = json_decode( $encoded_html, true );
		$this->assertIsString( $html );

		$document              = new DOMDocument();
		$internal_errors_state = libxml_use_internal_errors( true );
		try {
			$document->loadHTML( '<div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $internal_errors_state );
		}

		return new DOMXPath( $document );
	}
}
