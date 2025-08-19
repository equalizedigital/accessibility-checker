<?php
/**
 * PHPUnit tests for EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter
 *
 * Notes:
 * - Testing library: PHPUnit
 * - These tests mock WordPress functions and globals to validate behavior.
 * - If the project uses Brain Monkey or WP_Mock, consider migrating these inline
 *   mocks to that framework for consistency.
 */

declare(strict_types=1);

namespace Tests\PhpUnit\Includes\Classes;

use PHPUnit\Framework\TestCase;

class EnqueueFrontendTest extends TestCase {

	// Captured "enqueued" assets and calls
	private array $enqueued_styles = [];
	private array $enqueued_scripts = [];
	private array $localized_scripts = [];
	private array $script_translations = [];

	// Mutable flags/state for our stubs
	private bool $flag_is_admin = false;
	private bool $flag_is_customize_preview = false;
	private array $get_option_map = [];
	private $current_post = null;
	private ?string $current_post_type = 'post';
	private array $current_user_caps = [];
	private array $applied_filters = [];
	private array $applied_filters_return = [];
	private bool $flag_is_user_logged_in = true;
	private ?string $edit_post_link = 'https://example.test/wp-admin/post.php?post=123&action=edit';
	private array $server_get = [];

	// Constants required by the code under test
	private const CONSTS = [
		'EDAC_PLUGIN_FILE' => '/path/to/plugin/main.php',
		'EDAC_PLUGIN_URL'  => 'https://example.test/wp-content/plugins/edac/',
		'EDAC_VERSION'     => '9.9.9-test',
	];

	protected function setUp(): void {
		parent::setUp();

		// Ensure constants exist
		foreach (self::CONSTS as $name => $value) {
			if (!defined($name)) {
				define($name, $value);
			}
		}

		// Reset globals and captures
		$this->enqueued_styles = [];
		$this->enqueued_scripts = [];
		$this->localized_scripts = [];
		$this->script_translations = [];
		$this->flag_is_admin = false;
		$this->flag_is_customize_preview = false;
		$this->get_option_map = [];
		$this->current_post = (object) ['ID' => 123];
		$this->current_post_type = 'post';
		$this->current_user_caps = [];
		$this->applied_filters = [];
		$this->applied_filters_return = [];
		$this->flag_is_user_logged_in = true;
		$this->edit_post_link = 'https://example.test/wp-admin/post.php?post=123&action=edit';
		$this->server_get = [];

		// Expose globals the SUT expects.
		global $post;
		$post = $this->current_post;

		// Provide minimal autoload or include for SUT if not already loaded.
		if (!class_exists('\\EDAC\\Inc\\Enqueue_Frontend')) {
			// Attempt to locate a file containing the class; if not found, fallback to defining a minimal shim that will be replaced by runtime inclusion.
			// In CI, the plugin's autoloader/bootstrap should have loaded it. Here, we ensure tests do not fatal if autoload not configured.
			// Do not define; only leave it to autoload; if missing, the test will mark skipped.
		}

		// Define function stubs in the global namespace if not defined.
		$this->defineFunctionOnce('is_admin', function (): bool {
			return $this->flag_is_admin;
		});
		$this->defineFunctionOnce('is_customize_preview', function (): bool {
			return $this->flag_is_customize_preview;
		});
		$this->defineFunctionOnce('get_option', function (string $key, $default = false) {
			return $this->get_option_map[$key] ?? $default;
		});
		$this->defineFunctionOnce('get_post_type', function () {
			return $this->current_post_type;
		});
		$this->defineFunctionOnce('wp_enqueue_style', function (string $handle, string $src, $deps = false, $ver = false, $media = 'all') {
			$this->enqueued_styles[] = compact('handle', 'src', 'deps', 'ver', 'media');
		});
		$this->defineFunctionOnce('wp_enqueue_script', function (string $handle, string $src, $deps = false, $ver = false, $in_footer = false) {
			$this->enqueued_scripts[] = compact('handle', 'src', 'deps', 'ver', 'in_footer');
		});
		$this->defineFunctionOnce('wp_localize_script', function (string $handle, string $object_name, array $l10n) {
			$this->localized_scripts[] = compact('handle', 'object_name', 'l10n');
		});
		$this->defineFunctionOnce('wp_set_script_translations', function (string $handle, string $domain, string $path) {
			$this->script_translations[] = compact('handle', 'domain', 'path');
		});
		$this->defineFunctionOnce('plugin_dir_url', function (string $file): string {
			(void) $file;
			return 'https://example.test/wp-content/plugins/edac/';
		});
		$this->defineFunctionOnce('plugin_dir_path', function (string $file): string {
			return '/var/www/html/wp-content/plugins/edac/';
		});
		$this->defineFunctionOnce('wp_create_nonce', function (string $action): string {
			return 'nonce-' . $action;
		});
		$this->defineFunctionOnce('current_user_can', function (string $capability, ...$args): bool {
			// If capability is a filter result, it can be anything; for edit_post it includes post_id.
			if ($capability === 'edit_post') {
				$post_id = $args[0] ?? null;
				return in_array("edit_post:$post_id", $this->current_user_caps, true);
			}
			return in_array($capability, $this->current_user_caps, true);
		});
		$this->defineFunctionOnce('apply_filters', function (string $tag, $value) {
			$this->applied_filters[] = [$tag, $value];
			if (array_key_exists($tag, $this->applied_filters_return)) {
				return $this->applied_filters_return[$tag];
			}
			return $value;
		});
		$this->defineFunctionOnce('esc_url_raw', function (string $url): string {
			return $url;
		});
		$this->defineFunctionOnce('get_site_url', function (): string {
			return 'https://example.test';
		});
		$this->defineFunctionOnce('admin_url', function (string $path = ''): string {
			return 'https://example.test/wp-admin/' . ltrim($path, '/');
		});
		$this->defineFunctionOnce('is_user_logged_in', function (): bool {
			return $this->flag_is_user_logged_in;
		});
		$this->defineFunctionOnce('get_edit_post_link', function (int $post_id): string {
			return $this->edit_post_link;
		});

		// Provide $_GET for pageScanner checks
		$_GET = $this->server_get;
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Helper to define a global function once for the test run.
	 */
	private function defineFunctionOnce(string $name, callable $impl): void {
		if (!function_exists($name)) {
			// Use Closure::bind to bind $this into closure's scope for state access.
			$fn = \Closure::fromCallable($impl)->bindTo($this, static::class);
			eval('function ' . $name . '(...$args) { return call_user_func_array($GLOBALS["__test_fn_' . $name . '"], $args); }');
			$GLOBALS['__test_fn_' . $name] = $fn;
		} else {
			// If already defined by the environment, we cannot redefine; tests still proceed.
		}
	}

	private function requireSUTOrSkip(): void {
		if (!class_exists('\\EDAC\\Inc\\Enqueue_Frontend')) {
			$this->markTestSkipped('EDAC\\Inc\\Enqueue_Frontend class is not autoloadable in this test environment.');
		}
	}

	public function test_does_not_enqueue_on_admin(): void {
		$this->requireSUTOrSkip();

		$this->flag_is_admin = true;

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertEmpty($this->enqueued_styles, 'No styles should be enqueued on admin.');
		$this->assertEmpty($this->enqueued_scripts, 'No scripts should be enqueued on admin.');
	}

	public function test_does_not_enqueue_when_iframe_page_scanner_param_is_set(): void {
		$this->requireSUTOrSkip();

		$this->server_get = ['edac_pageScanner' => '1'];
		$_GET = $this->server_get;

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertEmpty($this->enqueued_styles, 'No styles should be enqueued for pageScanner iframe.');
		$this->assertEmpty($this->enqueued_scripts, 'No scripts should be enqueued for pageScanner iframe.');
	}

	public function test_does_not_enqueue_when_no_global_post(): void {
		$this->requireSUTOrSkip();

		global $post;
		$post = null;

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertEmpty($this->enqueued_styles);
		$this->assertEmpty($this->enqueued_scripts);
	}

	public function test_does_not_enqueue_in_customizer_preview(): void {
		$this->requireSUTOrSkip();

		$this->flag_is_customize_preview = true;

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertEmpty($this->enqueued_styles);
		$this->assertEmpty($this->enqueued_scripts);
	}

	public function test_does_not_enqueue_when_user_cannot_edit_and_filter_false(): void {
		$this->requireSUTOrSkip();

		// Ensure filter returns false explicitly
		$this->applied_filters_return['edac_filter_frontend_highlighter_visibility'] = false;
		$this->current_user_caps = []; // user cannot edit

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertEmpty($this->enqueued_styles);
		$this->assertEmpty($this->enqueued_scripts);
	}

	public function test_enqueues_when_filter_allows_even_if_user_cannot_edit(): void {
		$this->requireSUTOrSkip();

		// Allow via filter
		$this->applied_filters_return['edac_filter_frontend_highlighter_visibility'] = true;

		// Set post type options to active
		$this->get_option_map['edac_post_types'] = ['post', 'page'];
		$this->current_post_type = 'post';

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertNotEmpty($this->enqueued_styles, 'Styles should be enqueued when filter allows visiblity.');
		$this->assertNotEmpty($this->enqueued_scripts, 'Scripts should be enqueued when filter allows visiblity.');

		// Validate script/style handles and URLs
		$style = $this->enqueued_styles[0];
		$this->assertSame('edac-frontend-highlighter-app', $style['handle']);
		$this->assertStringContainsString('build/css/frontendHighlighterApp.css', $style['src']);

		$script = $this->enqueued_scripts[0];
		$this->assertSame('edac-frontend-highlighter-app', $script['handle']);
		$this->assertStringContainsString('build/frontendHighlighterApp.bundle.js', $script['src']);

		// Localized data assertions
		$this->assertNotEmpty($this->localized_scripts);
		$loc = $this->localized_scripts[0];
		$this->assertSame('edac-frontend-highlighter-app', $loc['handle']);
		$this->assertSame('edacFrontendHighlighterApp', $loc['object_name']);
		$this->assertIsArray($loc['l10n']);
		$this->assertSame(123, $loc['l10n']['postID']);
		$this->assertSame('nonce-ajax-nonce', $loc['l10n']['nonce']);
		$this->assertSame('nonce-wp_rest', $loc['l10n']['restNonce']);
		$this->assertArrayHasKey('userCanFix', $loc['l10n']);
		$this->assertArrayHasKey('userCanEdit', $loc['l10n']);
		$this->assertSame('https://example.test', $loc['l10n']['edacUrl']);
		$this->assertSame('https://example.test/wp-admin/admin-ajax.php', $loc['l10n']['ajaxurl']);
		$this->assertTrue($loc['l10n']['loggedIn']);
		$this->assertStringContainsString('build/css/frontendHighlighterApp.css?ver=', $loc['l10n']['appCssUrl']);
		$this->assertSame('right', $loc['l10n']['widgetPosition']);
		$this->assertSame($this->edit_post_link, $loc['l10n']['editorLink']);
		$this->assertStringContainsString('build/pageScanner.bundle.js', $loc['l10n']['scannerBundleUrl']);

		// Translations
		$this->assertNotEmpty($this->script_translations);
		$tr = $this->script_translations[0];
		$this->assertSame('edac-frontend-highlighter-app', $tr['handle']);
		$this->assertSame('accessibility-checker', $tr['domain']);
		$this->assertStringEndsWith('languages', $tr['path']);
	}

	public function test_enqueues_when_user_can_edit_and_post_type_active(): void {
		$this->requireSUTOrSkip();

		$this->get_option_map['edac_post_types'] = ['post'];
		$this->current_post_type = 'post';
		$this->current_user_caps = ['manage_options', 'edit_post:123']; // ensure edit permissions

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertNotEmpty($this->enqueued_styles);
		$this->assertNotEmpty($this->enqueued_scripts);
	}

	public function test_does_not_enqueue_when_post_type_inactive(): void {
		$this->requireSUTOrSkip();

		$this->get_option_map['edac_post_types'] = ['page']; // 'post' not included
		$this->current_post_type = 'post';
		$this->current_user_caps = ['edit_post:123'];

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertEmpty($this->enqueued_styles);
		$this->assertEmpty($this->enqueued_scripts);
	}

	public function test_widget_position_falls_back_to_right_by_default(): void {
		$this->requireSUTOrSkip();

		$this->get_option_map['edac_post_types'] = ['post'];
		$this->current_post_type = 'post';
		$this->current_user_caps = ['edit_post:123'];
		// No 'edac_frontend_highlighter_position' set; should default to 'right'

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertNotEmpty($this->localized_scripts);
		$loc = $this->localized_scripts[0];
		$this->assertSame('right', $loc['l10n']['widgetPosition']);
	}

	public function test_editor_link_is_populated(): void {
		$this->requireSUTOrSkip();

		$this->get_option_map['edac_post_types'] = ['post'];
		$this->current_post_type = 'post';
		$this->current_user_caps = ['edit_post:123'];
		$this->edit_post_link = 'https://example.test/custom-edit-link?post=123';

		\EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$loc = $this->localized_scripts[0];
		$this->assertSame('https://example.test/custom-edit-link?post=123', $loc['l10n']['editorLink']);
	}
}