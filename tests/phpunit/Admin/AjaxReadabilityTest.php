<?php
/**
 * AJAX readability behavior tests.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Ajax;

/**
 * Tests for the AJAX readability response.
 */
class AjaxReadabilityTest extends WP_Ajax_UnitTestCase {

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
		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$post_id  = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_content' => '<p>Content for AJAX readability tests.</p>',
			]
		);
	}

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$admin_id );

		$this->ajax = new Ajax();
		add_action( 'wp_ajax_edac_readability_ajax', [ $this->ajax, 'readability' ] );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		remove_action( 'wp_ajax_edac_readability_ajax', [ $this->ajax, 'readability' ] );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Verify that only reading levels above ninth grade fail.
	 *
	 * @dataProvider data_readability_grade_threshold
	 *
	 * @param string $readability   Stored readability label.
	 * @param string $expected_text Expected response text.
	 * @param string $expected_css  Expected result CSS class.
	 */
	public function test_readability_uses_above_ninth_grade_threshold( string $readability, string $expected_text, string $expected_css ) {
		update_post_meta(
			self::$post_id,
			'_edac_summary',
			[ 'readability' => $readability ]
		);

		$_POST['nonce']   = wp_create_nonce( 'ajax-nonce' );
		$_POST['post_id'] = self::$post_id;

		try {
			$this->_handleAjax( 'edac_readability_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );

		$html = json_decode( $response['data'] );
		$this->assertIsString( $html );
		$this->assertStringContainsString( $expected_text, $html );
		$this->assertStringContainsString( $expected_css, $html );
	}

	/**
	 * Data provider for the ninth-grade boundary.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public static function data_readability_grade_threshold(): array {
		return [
			'grade 9 passes' => [
				'9th Grade',
				'A simplified summary is not necessary when content reading level is 9th grade or below.',
				'passed-text-color',
			],
			'grade 10 fails' => [
				'10th Grade',
				'Your post has a reading level higher than 9th grade.',
				'failed-text-color',
			],
		];
	}
}
