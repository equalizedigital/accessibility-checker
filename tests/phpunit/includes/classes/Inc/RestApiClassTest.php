<?php
/**
 * @group rest
 * Tests for EDAC\Inc\REST_Api class logic.
 *
 * Framework: PHPUnit with WordPress (WP_UnitTestCase)
 */

use EDAC\Inc\REST_Api;

/**
 * Class RestApiClassTest
 */
class RestApiClassTest extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var REST_Api
	 */
	protected $api;

	public function setUp(): void {
		parent::setUp();
		$this->api = new REST_Api();
	}

	/* ==========================================================
	 * filter_js_validation_html()
	 * ========================================================== */

	public function test_filter_html_appends_selector_for_empty_paragraph_tag() {
		$html       = '<p></p>';
		$rule_id    = 'empty_paragraph_tag';
		$violation  = [ 'selector' => [ '.entry-content p:nth-child(2)' ] ];

		$result = $this->api->filter_js_validation_html( $html, $rule_id, $violation );

		$this->assertStringContainsString('// {{ .entry-content p:nth-child(2) }}', $result);
	}

	public function test_filter_html_does_not_append_when_selector_missing() {
		$html       = '<p></p>';
		$rule_id    = 'empty_paragraph_tag';
		$violation  = [ 'selector' => [] ];

		$result = $this->api->filter_js_validation_html( $html, $rule_id, $violation );

		$this->assertSame( $html, $result );
	}

	public function test_filter_html_trims_full_document_for_html_has_lang_and_document_title() {
		$doc_html = '<!doctype html><html lang="en"><head><title>My Title</title></head><body><h1>Hi</h1></body></html>';
		$violation = [ 'selector' => [ 'html' ] ];

		$r1 = $this->api->filter_js_validation_html( $doc_html, 'html-has-lang', $violation );
		$r2 = $this->api->filter_js_validation_html( $doc_html, 'document-title', $violation );

		$this->assertSame('<html lang="en">...</html>', $r1);
		$this->assertSame('<html lang="en">...</html>', $r2);
	}

	public function test_filter_html_leaves_other_rules_unchanged() {
		$html = '<div>Content</div>';
		$violation = [ 'selector' => [ 'div' ] ];

		$result = $this->api->filter_js_validation_html( $html, 'color-contrast', $violation );

		$this->assertSame( $html, $result );
	}

	/* ==========================================================
	 * clear_issues_for_post()
	 * ========================================================== */

	public function test_clear_issues_requires_id() {
		$request = new class() implements ArrayAccess {
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetExists($o): bool {
				(void) $o;
				return false;
			}
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetGet($o): mixed {
				(void) $o;
				return null;
			}
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetSet($_offset, $_value): void {
				(void) $_offset;
				(void) $_value;
			}
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetUnset($_offset): void {
				(void) $_offset;
			}
			public function get_json_params(): array { return []; }
		};

		$response = $this->api->clear_issues_for_post( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'The ID is required to be passed.', $response->get_data()['message'] );
	}

	public function test_clear_issues_invalid_post_without_skip_flag_returns_400() {
		$request = new class() implements ArrayAccess {
			private $data = [ 'id' => 987 ];
			public function offsetExists($o): bool { return array_key_exists($o, $this->data); }
			public function offsetGet($o): mixed { return $this->data[$o] ?? null; }
			public function offsetSet($o, $v): void { $this->data[$o] = $v; }
			public function offsetUnset($o): void { unset($this->data[$o]); }
			public function get_json_params(): array { return []; }
		};

		$response = $this->api->clear_issues_for_post( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'The post is not valid.', $response->get_data()['message'] );
	}

	public function test_clear_issues_with_skip_flag_and_flush_returns_success() {
		$post_id = self::factory()->post->create( [ 'post_type' => 'post' ] );

		$request = new class($post_id) implements ArrayAccess {
			private $data;
			public function __construct($post_id){ $this->data = [ 'id' => $post_id ]; }
			public function offsetExists($o): bool { return array_key_exists($o, $this->data); }
			public function offsetGet($o): mixed { return $this->data[$o] ?? null; }
			public function offsetSet($o, $v): void { $this->data[$o] = $v; }
			public function offsetUnset($o): void { unset($this->data[$o]); }
			public function get_json_params(): array { return [ 'skip_post_exists_check' => true, 'flush' => true ]; }
		};

		$response = $this->api->clear_issues_for_post( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['flushed'] );
		$this->assertSame( $post_id, $data['id'] );
	}

	/* ==========================================================
	 * set_post_scan_results()
	 * ========================================================== */

	public function test_set_post_scan_results_requires_violations_param() {
		$post_id = self::factory()->post->create( [ 'post_type' => 'post' ] );

		$request = new class($post_id) implements ArrayAccess {
			private $data;
			public function __construct($post_id){ $this->data = [ 'id' => $post_id ]; }
			public function offsetExists($o): bool { return array_key_exists($o, $this->data); }
			public function offsetGet($o): mixed { return $this->data[$o] ?? null; }
			public function offsetSet($o, $v): void { $this->data[$o] = $v; }
			public function offsetUnset($o): void { unset($this->data[$o]); }
			public function get_json_params(): array { return []; }
		};

		$response = $this->api->set_post_scan_results( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'A required parameter is missing.', $response->get_data()['message'] );
	}

	public function test_set_post_scan_results_invalid_post_returns_400() {
		$request = new class() implements ArrayAccess {
			private $data = [ 'id' => 999999, 'violations' => [] ];
			public function offsetExists($o): bool { return array_key_exists($o, $this->data); }
			public function offsetGet($o): mixed { return $this->data[$o] ?? null; }
			public function offsetSet($o, $v): void { $this->data[$o] = $v; }
			public function offsetUnset($o): void { unset($this->data[$o]); }
			public function get_json_params(): array { return []; }
		};

		$response = $this->api->set_post_scan_results( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'The post is not valid.', $response->get_data()['message'] );
	}

	public function test_set_post_scan_results_happy_path_saves_meta_and_returns_success() {
		$post_id = self::factory()->post->create( [ 'post_type' => 'post' ] );

		$violations = [
			[
				'ruleId'   => 'color-contrast',
				'html'     => '<div style="color:#ccc;background:#ccc">text</div>',
				'impact'   => 'warning',
				'selector' => [ '.x' ],
			],
		];

		$request = new class($post_id, $violations) implements ArrayAccess {
			private $data;
			public function __construct($post_id, $violations){
				$this->data = [
					'id'              => $post_id,
					'violations'      => $violations,
					'densityMetrics'  => [ 'elementCount' => 10, 'contentLength' => 1000 ],
				];
			}
			public function offsetExists($o): bool { return array_key_exists($o, $this->data); }
			public function offsetGet($o): mixed { return $this->data[$o] ?? null; }
			public function offsetSet($o, $v): void { $this->data[$o] = $v; }
			public function offsetUnset($o): void { unset($this->data[$o]); }
			public function get_json_params(): array { return []; }
		};

		$response = $this->api->set_post_scan_results( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( $post_id, $data['id'] );
		$this->assertIsInt( $data['timestamp'] );

		// Verify meta updated
		$this->assertSame( [10, 1000], get_post_meta( $post_id, '_edac_density_data', true ) );
		$this->assertNotEmpty( get_post_meta( $post_id, '_edac_post_checked_js', true ) );
	}

	/* ==========================================================
	 * get_scans_stats(), get_scans_stats_by_post_type(), get_scans_stats_by_post_types()
	 * ========================================================== */

	public function test_get_scans_stats_returns_success_and_array() {
		$response = $this->api->get_scans_stats();

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['stats'] );
	}

	public function test_get_scans_stats_by_post_type_missing_slug_returns_400() {
		$request = new class() implements ArrayAccess {
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetExists($o): bool {
				(void) $o;
				return false;
			}
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetGet($o): mixed {
				(void) $o;
				return null;
			}
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetSet($_offset, $_value): void {
				(void) $_offset;
				(void) $_value;
			}
			/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
			public function offsetUnset($_offset): void {
				(void) $_offset;
			}
			public function get_json_params(): array { return []; }
		};

		$response = $this->api->get_scans_stats_by_post_type( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'A required parameter is missing.', $response->get_data()['message'] );
	}

	public function test_get_scans_stats_by_post_type_valid_slug_returns_success() {
		$request = new class() implements ArrayAccess {
			private $data = [ 'slug' => 'post' ];
			public function offsetExists($o): bool { return array_key_exists($o, $this->data); }
			public function offsetGet($o): mixed { return $this->data[$o] ?? null; }
			public function offsetSet($o, $v): void { $this->data[$o] = $v; }
			public function offsetUnset($o): void { unset($this->data[$o]); }
			public function get_json_params(): array { return []; }
		};

		$response = $this->api->get_scans_stats_by_post_type( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['stats'] );
	}

	public function test_get_scans_stats_by_post_types_returns_success_and_map() {
		$response = $this->api->get_scans_stats_by_post_types( new class() {} );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['stats'] );
	}

	/* ==========================================================
	 * get_site_summary()
	 * ========================================================== */

	public function test_get_site_summary_respects_clear_cache_param() {
		$request = new class() {
			public function get_param( $key ) {
				return ( 'clearCache' === $key ) ? true : null;
			}
		};

		$response = $this->api->get_site_summary( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['stats'] );
	}
}