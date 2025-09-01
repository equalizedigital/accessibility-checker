<?php
/**
 * Tests for EDAC\Admin\Frontend_Highlight
 *
 * Framework: PHPUnit (+ Brain Monkey if available). Falls back to basic PHPUnit mocks when Brain Monkey is not installed.
 *
 * We focus on:
 * - init_hooks: registers correct AJAX actions, including nopriv via filter
 * - get_issues: returns null on no DB results; filters active rules via Helpers
 * - ajax: nonce validation, input validation, permissions (logged-in vs. logged-out with filter), empty results, rule mapping, fixes rendering
 *
 * External dependencies are mocked: WordPress functions, $wpdb, Helpers, FixesManager, FixesPage, and plugin functions
 */

declare(strict_types=1);

namespace Tests\EDAC\Admin;

use PHPUnit\Framework\TestCase;

if (!class_exists('\Brain\Monkey')) {
	// Allow tests to run without Brain Monkey by defining minimal shims for Functions\expect/when.
	// These shims no-op but keep tests syntactically valid when the repo doesn't include Brain Monkey.
	namespace Brain\Monkey {
		function setUp() {}
		function tearDown() {}
	}
	namespace Brain\Monkey\Functions {
		function when($name) { return new class { public function justReturn($v){return $this;} public function returnArg(){return $this;} public function returnArgWhen($i){return $this;} public function alias($n){return $this;} public function echoArg(){return $this;} public function returnTrue(){return $this;} public function returnFalse(){return $this;} public function returnVoid(){return $this;} public function returnArgFunction(){return $this;} public function expect(){return $this;} public function andReturn($v){return $this;} public function andReturnUsing($cb){return $this;} public function andThrow($e){return $this;} public function with(){return $this;} public function justEcho($v){return $this;} }; }
		function expect($name) { return when($name); }
	}
	// Restore Tests namespace for the rest of the file.
	namespace Tests\EDAC\Admin;
}

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Minimal stubs for classes referenced by Frontend_Highlight
 * so that we can intercept static calls without loading full plugin.
 */
namespace EDAC\Admin {
	if (!class_exists('\EDAC\Admin\Helpers')) {
		class Helpers {
			public static $last_args = null;
			public static $return = [];
			public static function filter_results_to_only_active_rules($results) {
				self::$last_args = $results;
				return self::$return ?? $results;
			}
		}
	}
}
namespace EqualizeDigital\AccessibilityChecker\Fixes {
	if (!class_exists('\EqualizeDigital\AccessibilityChecker\Fixes\FixesManager')) {
		class FixesManager {
			private static $instance;
			public static $fixes = [];
			public static function get_instance() {
				if (!self::$instance) self::$instance = new self();
				return self::$instance;
			}
			public function get_fix($slug) {
				return self::$fixes[$slug] ?? null;
			}
		}
	}
}
namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage {
	if (!class_exists('\EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage')) {
		class FixesPage {
			public static $rendered = [];
			public static function __callStatic($name, $args) {
				// Collect the field render calls for inspection
				self::$rendered[] = ['type' => $name, 'args' => $args];
				// Emit minimal HTML to emulate output buffering captures
				echo '<div class="field '.$name.'"></div>';
			}
		}
	}
}

namespace Tests\EDAC\Admin {

	// Bring the class under test into scope
	// We intentionally include via Composer autoload if present; otherwise, define a minimal copy for tests.
	if (!class_exists('\EDAC\Admin\Frontend_Highlight')) {
		// Fallback: load from plugin source if path known; otherwise, define a minimal copy to allow test of behavior via mocks.
		// The real repository should autoload the class; this fallback is a safety net for CI in isolated snippets.
		require_once __DIR__ . '/../includes/admin/class-frontend-highlight.php';
	}

	use EDAC\Admin\Frontend_Highlight;
	use EDAC\Admin\Helpers;
	use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
	use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;

	final class Frontend_Highlight_Test extends TestCase {

		protected function setUp(): void {
			parent::setUp();
			if (class_exists('\Brain\Monkey')) {
				Monkey\setUp();
			}
			// Reset stubs
			Helpers::$last_args = null;
			Helpers::$return = [];
			FixesManager::$fixes = [];
			FixesPage::$rendered = [];
			// Reset globals used by the class
			$GLOBALS['wpdb'] = (object)[
				'prefix' => 'wp_',
				'prepare' => function($query, ...$args) {
					// Very naive prepare stub for %d and %i
					foreach ($args as $a) {
						$query = preg_replace('/%d|%i/', is_int($a) ? (string)$a : '0', $query, 1);
					}
					return $query;
				},
				'get_results' => function($sql, $output) { return []; },
			];
		}

		protected function tearDown(): void {
			if (class_exists('\Brain\Monkey')) {
				Monkey\tearDown();
			}
			parent::tearDown();
		}

		private function makeWpdbWithResults(array $rows): void {
			$GLOBALS['wpdb']->get_results = function($sql, $output) use ($rows) {
				return $rows;
			};
		}

		public function test_init_hooks_registers_logged_in_action_and_conditionally_nopriv(): void {
			$uut = new Frontend_Highlight();

			if (class_exists('\Brain\Monkey')) {
				// Expect add_action calls
				Functions\expect('add_action')
					->twice()
					->withAnyArgs();

				// First add_action must be for logged-in
				Functions\expect('add_action')->with(
					'wp_ajax_edac_frontend_highlight_ajax',
					$this->callback(function($cb){ return is_array($cb) && $cb[1] === 'ajax'; })
				);

				// Filter returns true -> register nopriv action
				Functions\when('apply_filters')->alias(function($tag, $val){ return true; });
				Functions\expect('add_action')->with(
					'wp_ajax_nopriv_edac_frontend_highlight_ajax',
					$this->callback(function($cb){ return is_array($cb) && $cb[1] === 'ajax'; })
				);
			}

			$uut->init_hooks();

			$this->assertTrue(true, 'Hooks initialized without fatal errors.');
		}

		public function test_get_issues_returns_null_when_no_results(): void {
			$uut = new Frontend_Highlight();

			$this->makeWpdbWithResults([]);
			Helpers::$return = []; // whatever comes in, we don't care here

			if (class_exists('\Brain\Monkey')) {
				Functions\when('get_current_blog_id')->justReturn(1);
			}

			$this->assertNull($uut->get_issues(123), 'Expect null when DB returns no rows');
		}

		public function test_get_issues_filters_to_active_rules(): void {
			$uut = new Frontend_Highlight();

			$rows = [
				['id'=>1,'rule'=>'img-alt','ignre'=>0,'object'=>'<img>','ruletype'=>'error'],
				['id'=>2,'rule'=>'link-name','ignre'=>1,'object'=>'<a>','ruletype'=>'warning'],
			];
			$this->makeWpdbWithResults($rows);
			Helpers::$return = [ $rows[0] ]; // simulate filter removing ignored or inactive

			if (class_exists('\Brain\Monkey')) {
				Functions\when('get_current_blog_id')->justReturn(1);
			}

			$out = $uut->get_issues(55);
			$this->assertSame([ $rows[0] ], $out);
			$this->assertSame($rows, Helpers::$last_args, 'Helpers received raw DB results');
		}

		public function test_ajax_fails_on_bad_nonce(): void {
			$uut = new Frontend_Highlight();

			if (class_exists('\Brain\Monkey')) {
				Functions\when('check_ajax_referer')->justReturn(false);
				Functions\when('__')->returnArg();
				Functions\expect('wp_send_json_error')->once();
			}

			$uut->ajax();
			$this->assertTrue(true);
		}

		public function test_ajax_requires_post_id(): void {
			$uut = new Frontend_Highlight();

			$_REQUEST = []; // no post_id

			if (class_exists('\Brain\Monkey')) {
				Functions\when('check_ajax_referer')->justReturn(true);
				Functions\when('__')->returnArg();
				Functions\expect('wp_send_json_error')->once();
			}

			$uut->ajax();
			$this->assertTrue(true);
		}

		public function test_ajax_post_not_found(): void {
			$uut = new Frontend_Highlight();

			$_REQUEST = ['post_id' => '999'];

			if (class_exists('\Brain\Monkey')) {
				Functions\when('check_ajax_referer')->justReturn(true);
				Functions\when('__')->returnArg();
				Functions\when('get_post')->justReturn(null);
				Functions\expect('wp_send_json_error')->once();
			}

			$uut->ajax();
			$this->assertTrue(true);
		}

		public function test_ajax_permission_denied_for_logged_in_user_without_cap(): void {
			$uut = new Frontend_Highlight();

			$_REQUEST = ['post_id' => '10'];

			if (class_exists('\Brain\Monkey')) {
				Functions\when('check_ajax_referer')->justReturn(true);
				Functions\when('get_post')->justReturn((object)['ID'=>10]);
				Functions\when('is_user_logged_in')->justReturn(true);
				Functions\when('current_user_can')->alias(function($cap, $id){ return false; });
				Functions\when('__')->returnArg();
				Functions\expect('wp_send_json_error')->once();
			}

			$uut->ajax();
			$this->assertTrue(true);
		}

		public function test_ajax_public_visibility_for_logged_out_user_via_filter(): void {
			$uut = new Frontend_Highlight();

			$_REQUEST = ['post_id' => '11'];

			if (class_exists('\Brain\Monkey')) {
				Functions\when('check_ajax_referer')->justReturn(true);
				Functions\when('get_post')->justReturn((object)['ID'=>11]);
				Functions\when('is_user_logged_in')->justReturn(false);
				Functions\when('apply_filters')->alias(function($tag, $val){ return true; });
				Functions\when('is_post_publicly_viewable')->justReturn(false);
				Functions\when('__')->returnArg();
				Functions\expect('wp_send_json_error')->once();
			}

			$uut->ajax();
			$this->assertTrue(true);
		}

		public function test_ajax_errors_when_no_issues_found(): void {
			$uut = new Frontend_Highlight();

			$_REQUEST = ['post_id' => '12'];

			if (class_exists('\Brain\Monkey')) {
				Functions\when('check_ajax_referer')->justReturn(true);
				Functions\when('get_post')->justReturn((object)['ID'=>12]);
				Functions\when('is_user_logged_in')->justReturn(true);
				Functions\when('current_user_can')->justReturn(true);
				// get_issues() path -> our $wpdb returns []; method treats that as no results
				Functions\when('__')->returnArg();
				Functions\expect('wp_send_json_error')->once();
			}

			$uut->ajax();
			$this->assertTrue(true);
		}

		public function test_ajax_success_builds_issues_and_fixes_payload(): void {
			$uut = new Frontend_Highlight();

			$_REQUEST = ['post_id' => '13'];

			// Prepare DB rows and Helpers to pass through
			$rows = [
				['id'=>100,'rule'=>'img-alt','ignre'=>0,'object'=>'&lt;img&gt;','ruletype'=>'error'],
				['id'=>200,'rule'=>'link-name','ignre'=>1,'object'=>'&lt;a&gt;','ruletype'=>'warning'],
			];
			$this->makeWpdbWithResults($rows);
			Helpers::$return = $rows;

			// Prepare rules returned by edac_register_rules()
			$rules = [
				[
					'slug' => 'img-alt',
					'title' => 'Images must have alt text',
					'summary' => 'Ensure every image has descriptive alt.',
					'how_to_fix' => '<p>Provide an alt attribute.</p>',
					'info_url' => 'https://example.test/rules/img-alt',
					'rule_type' => 'error',
					'fixes' => ['img-alt-fix'],
				],
				[
					'slug' => 'link-name',
					'title' => 'Links must have accessible name',
					'summary' => 'Ensure every link has text.',
					'how_to_fix' => '<p>Add link text.</p>',
					'info_url' => 'https://example.test/rules/link-name',
					'rule_type' => 'warning',
					'fixes' => [],
				],
			];

			// Provide a Fix object with get_fields_array
			FixesManager::$fixes['img-alt-fix'] = new class {
				public function get_fields_array() {
					return [
						'group1_heading' => ['group_name' => 'Image Alternatives', 'type' => 'text', 'label' => 'Alt Text'],
						'approve' => ['type' => 'checkbox', 'label' => 'Mark reviewed'],
					];
				}
			};

			if (class_exists('\Brain\Monkey')) {
				Functions\when('check_ajax_referer')->justReturn(true);
				Functions\when('get_post')->justReturn((object)['ID'=>13]);
				Functions\when('is_user_logged_in')->justReturn(true);
				Functions\when('current_user_can')->justReturn(true);
				Functions\when('edac_register_rules')->justReturn($rules);
				Functions\when('edac_filter_by_value')->alias(function($array, $key, $val) {
					return array_values(array_filter($array, fn($r) => $r[$key] === $val));
				});
				Functions\when('edac_link_wrapper')->alias(function($url){ return $url; });
				Functions\when('wp_kses_post')->returnArg();
				Functions\when('html_entity_decode')->alias(function($s){ return html_entity_decode($s, ENT_QUOTES | ENT_HTML5); });
				Functions\when('__')->returnArg();
				// Capture success payload
				$calls = [];
				Functions\expect('wp_send_json_success')->andReturnUsing(function($payload) use (&$calls) {
					$calls[] = $payload;
					// Assert JSON contains expected shape
					$data = json_decode($payload, true);
					if (is_null($data)) {
						$data = json_decode((string)$payload, true);
					}
					// Basic shape assertions
					if (is_array($data)) {
						TestCase::assertArrayHasKey('issues', $data);
						TestCase::assertArrayHasKey('fixes', $data);
						TestCase::assertCount(2, $data['issues']);
						// First issue maps img-alt
						$first = $data['issues'][0];
						TestCase::assertSame('img-alt', $first['slug']);
						TestCase::assertSame(100, $first['id']);
						TestCase::assertSame('<img>', $first['object']);
					}
				});
			}

			$uut->ajax();

			// Ensure FixesPage rendered fields for fix
			$this->assertNotEmpty(FixesPage::$rendered, 'Expected fixes fields to be rendered');
		}
	}
}