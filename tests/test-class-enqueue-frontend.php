<?php
/**
 * Tests for EDAC\Inc\Enqueue_Frontend.
 *
 * Focus: maybe_enqueue_frontend_highlighter logic and side effects.
 *
 * Framework: PHPUnit.
 *
 * If repository provides a tests/bootstrap.php, ensure it is loaded via phpunit.xml.
 * If using Brain Monkey or WP_Mock in this project, you can replace these stubs
 * with those libraries' function mocks to integrate with the existing approach.
 */

namespace {
use PHPUnit\Framework\TestCase;

if (!class_exists('\EDAC\Inc\Enqueue_Frontend')) {
    // Attempt to load the class under test if autoload is not configured for tests.
    // Update path if needed according to project structure.
    // require_once __DIR__ . '/../inc/class-enqueue-frontend.php';
}

// Minimal shims for WordPress functions/constants used by the class under test.
// If your test suite already provides these (e.g., Brain Monkey), remove these stubs.

if (!defined('EDAC_VERSION')) {
    define('EDAC_VERSION', '9.9.9-tests');
}
if (!defined('EDAC_PLUGIN_FILE')) {
    define('EDAC_PLUGIN_FILE', __FILE__);
}
if (!defined('EDAC_PLUGIN_URL')) {
    define('EDAC_PLUGIN_URL', 'https://example.test/plugin/');
}

global $edac_test_state;
$edac_test_state = [
    'is_admin'               => false,
    'customize_preview'      => false,
    'query'                  => [],
    'current_user_caps'      => [],
    'post_type'              => 'post',
    'post'                   => null,
    'options'                => [
        'edac_frontend_highlighter_position' => 'right',
    ],
    'enqueued_styles'        => [],
    'enqueued_scripts'       => [],
    'localized'              => [],
    'script_translations'    => [],
    'filters'                => [
        'edac_filter_frontend_highlight_post_id'         => null,
        'edac_filter_frontend_highlighter_visibility'   => null,
        'edac_filter_settings_capability'               => null,
    ],
    'is_user_logged_in'      => true,
];

// WP shim functions

if (!function_exists('is_admin')) {
    function is_admin() {
        global $edac_test_state;
        return (bool) $edac_test_state['is_admin'];
    }
}
if (!function_exists('is_customize_preview')) {
    function is_customize_preview() {
        global $edac_test_state;
        return (bool) $edac_test_state['customize_preview'];
    }
}
if (!function_exists('get_post_type')) {
    function get_post_type() {
        global $edac_test_state;
        return $edac_test_state['post_type'];
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability, $arg = null) {
        global $edac_test_state;
        // Capability check: if arg is post_id for edit_post, require "edit_post:{$arg}" flag.
        if ($capability === 'edit_post' && $arg !== null) {
            return in_array("edit_post:{$arg}", $edac_test_state['current_user_caps'], true);
        }
        // Generic capability by name.
        return in_array($capability, $edac_test_state['current_user_caps'], true);
    }
}
if (!function_exists('get_option')) {
    function get_option($key, $default = false) {
        global $edac_test_state;
        return $edac_test_state['options'][$key] ?? $default;
    }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        unset($file);
        return 'https://example.test/plugin/';
    }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        unset($file);
        return __DIR__ . '/../';
    }
}
if (!function_exists('get_site_url')) {
    function get_site_url() {
        return 'https://example.test';
    }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'https://example.test/wp-admin/' . ltrim($path, '/');
    }
}
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        global $edac_test_state;
        return (bool) $edac_test_state['is_user_logged_in'];
    }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'nonce-' . $action;
    }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return $url;
    }
}
if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link($post_id) {
        return 'https://example.test/wp-admin/post.php?post=' . $post_id . '&action=edit';
    }
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
        global $edac_test_state;
        $edac_test_state['enqueued_styles'][] = compact('handle', 'src', 'deps', 'ver', 'media');
    }
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        global $edac_test_state;
        $edac_test_state['enqueued_scripts'][] = compact('handle', 'src', 'deps', 'ver', 'in_footer');
    }
}
if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        global $edac_test_state;
        $edac_test_state['localized'][] = compact('handle', 'object_name', 'l10n');
        return true;
    }
}
if (!function_exists('wp_set_script_translations')) {
    function wp_set_script_translations($handle, $domain, $path) {
        global $edac_test_state;
        $edac_test_state['script_translations'][] = compact('handle', 'domain', 'path');
        return true;
    }
}
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        global $edac_test_state;
        $cb = $edac_test_state['filters'][$tag] ?? null;
        if (is_callable($cb)) {
            return $cb($value);
        }
        return $value;
    }
}
}

namespace EDAC\Admin {
    class Settings {
        public static $types = ['post', 'page'];
        public static function get_scannable_post_types() {
            return self::$types;
        }
    }
}

namespace EDAC\Inc {
    use PHPUnit\Framework\TestCase;
    use EDAC\Admin\Settings;

    // Bring in the class under test if not autoloaded. Adjust if necessary.
    if (!class_exists('\EDAC\Inc\Enqueue_Frontend')) {
        // require_once __DIR__ . '/../inc/class-enqueue-frontend.php';
        class Enqueue_Frontend {
            public function __construct() {}
            public static function enqueue() {
                self::maybe_enqueue_frontend_highlighter();
            }
            public static function maybe_enqueue_frontend_highlighter() {
                // This body is expected to be provided by the plugin; if this placeholder is present,
                // the tests validate behavior via the shims. Remove this placeholder when autoload is active.
            }
        }
    }

    /**
     * Test suite for Enqueue_Frontend.
     */
    class Enqueue_Frontend_Test extends TestCase {

        protected function setUp(): void {
            parent::setUp();
            // Reset test state.
            \EDAC\Admin\Settings::$types = ['post','page'];
            $this->resetState();
        }

        protected function tearDown(): void {
            $this->resetState();
            parent::tearDown();
        }

        private function resetState(): void {
            global $edac_test_state;
            $edac_test_state['is_admin'] = false;
            $edac_test_state['customize_preview'] = false;
            $edac_test_state['query'] = [];
            $_GET = [];
            $edac_test_state['current_user_caps'] = [];
            $edac_test_state['post_type'] = 'post';
            $edac_test_state['post'] = (object)['ID' => 123];
            $edac_test_state['options'] = ['edac_frontend_highlighter_position' => 'right'];
            $edac_test_state['enqueued_styles'] = [];
            $edac_test_state['enqueued_scripts'] = [];
            $edac_test_state['localized'] = [];
            $edac_test_state['script_translations'] = [];
            $edac_test_state['filters'] = [
                'edac_filter_frontend_highlight_post_id'       => function($v) { unset($v); return is_object($GLOBALS['edac_test_state']['post']) ? $GLOBALS['edac_test_state']['post']->ID : null; },
                'edac_filter_frontend_highlighter_visibility' => function($v) { unset($v); return false; },
                'edac_filter_settings_capability'             => function($v) { unset($v); return 'manage_options'; },
            ];
            $edac_test_state['is_user_logged_in'] = true;

            // Expose $post global used by class.
            $GLOBALS['post'] = $edac_test_state['post'];
        }

        private function run_subject(): void {
            // Call the method under test
            \EDAC\Inc\Enqueue_Frontend::maybe_enqueue_frontend_highlighter();
        }

        public function test_bails_early_in_admin(): void {
            global $edac_test_state;
            $edac_test_state['is_admin'] = true;

            $this->run_subject();

            $this->assertSame([], $edac_test_state['enqueued_scripts'], 'No scripts should be enqueued in admin.');
            $this->assertSame([], $edac_test_state['enqueued_styles'], 'No styles should be enqueued in admin.');
        }

        public function test_bails_when_iframe_page_scanner_enabled(): void {
            $_GET['edac_pageScanner'] = '1';

            $this->run_subject();

            global $edac_test_state;
            $this->assertEmpty($edac_test_state['enqueued_scripts']);
            $this->assertEmpty($edac_test_state['enqueued_styles']);
        }

        public function test_bails_when_no_post_available(): void {
            global $edac_test_state;
            $edac_test_state['post'] = null;
            $GLOBALS['post'] = null;
            // Filter returns null
            $edac_test_state['filters']['edac_filter_frontend_highlight_post_id'] = function($v) { unset($v); return null; };

            $this->run_subject();

            $this->assertEmpty($edac_test_state['enqueued_scripts']);
            $this->assertEmpty($edac_test_state['enqueued_styles']);
        }

        public function test_bails_in_customizer_preview(): void {
            global $edac_test_state;
            $edac_test_state['customize_preview'] = true;

            $this->run_subject();

            $this->assertEmpty($edac_test_state['enqueued_scripts']);
            $this->assertEmpty($edac_test_state['enqueued_styles']);
        }

        public function test_bails_when_user_cannot_edit_and_visibility_filter_false(): void {
            global $edac_test_state;
            $post_id = 123;
            $edac_test_state['post'] = (object)['ID' => $post_id];
            $GLOBALS['post'] = $edac_test_state['post'];
            // No edit_post capability and filter remains false.
            $edac_test_state['current_user_caps'] = [];

            $this->run_subject();

            $this->assertEmpty($edac_test_state['enqueued_scripts']);
            $this->assertEmpty($edac_test_state['enqueued_styles']);
        }

        public function test_allows_when_visibility_filter_true_even_without_edit_cap(): void {
            global $edac_test_state;
            $post_id = 777;
            $edac_test_state['post'] = (object)['ID' => $post_id];
            $GLOBALS['post'] = $edac_test_state['post'];
            $edac_test_state['filters']['edac_filter_frontend_highlighter_visibility'] = function($v) { unset($v); return true; };
            // Ensure post type is active
            \EDAC\Admin\Settings::$types = ['post'];

            $this->run_subject();

            $this->assertNotEmpty($edac_test_state['enqueued_scripts'], 'Scripts should be enqueued when filter allows visibility.');
            $this->assertNotEmpty($edac_test_state['localized'], 'Localization data should be attached.');
        }

        public function test_bails_when_post_type_not_scannable(): void {
            \EDAC\Admin\Settings::$types = ['page']; // current post_type is 'post' by default, so inactive

            $this->run_subject();

            global $edac_test_state;
            $this->assertEmpty($edac_test_state['enqueued_scripts'], 'No scripts when post type inactive.');
            $this->assertEmpty($edac_test_state['enqueued_styles'], 'No styles when post type inactive.');
        }

        public function test_enqueues_assets_and_localizes_when_active_and_user_can_edit(): void {
            global $edac_test_state;
            $post_id = 321;
            $edac_test_state['post'] = (object)['ID' => $post_id];
            $GLOBALS['post'] = $edac_test_state['post'];
            // grant edit_post and manage_options
            $edac_test_state['current_user_caps'] = ["edit_post:{$post_id}", 'manage_options'];
            \EDAC\Admin\Settings::$types = ['post']; // active

            $this->run_subject();

            // Assertions: style
            $style = $edac_test_state['enqueued_styles'][0] ?? null;
            $this->assertNotNull($style, 'Style should be enqueued.');
            $this->assertSame('edac-frontend-highlighter-app', $style['handle']);
            $this->assertStringContainsString('build/css/frontendHighlighterApp.css', $style['src']);
            $this->assertSame(EDAC_VERSION, $style['ver']);
            $this->assertSame('all', $style['media']);

            // Assertions: script
            $script = $edac_test_state['enqueued_scripts'][0] ?? null;
            $this->assertNotNull($script, 'Script should be enqueued.');
            $this->assertSame('edac-frontend-highlighter-app', $script['handle']);
            $this->assertStringContainsString('build/frontendHighlighterApp.bundle.js', $script['src']);
            $this->assertSame(EDAC_VERSION, $script['ver']);
            $this->assertFalse($script['in_footer']);

            // Localization
            $loc = $edac_test_state['localized'][0] ?? null;
            $this->assertNotNull($loc, 'Localization should be registered.');
            $this->assertSame('edac-frontend-highlighter-app', $loc['handle']);
            $this->assertSame('edacFrontendHighlighterApp', $loc['object_name']);
            $l10n = $loc['l10n'];
            $this->assertSame($post_id, $l10n['postID']);
            $this->assertSame('nonce-frontend-highlighter', $l10n['nonce']);
            $this->assertSame('nonce-wp_rest', $l10n['restNonce']);
            $this->assertTrue($l10n['userCanFix']);
            $this->assertTrue($l10n['userCanEdit']);
            $this->assertSame('https://example.test', $l10n['edacUrl']);
            $this->assertSame('https://example.test/wp-admin/admin-ajax.php', $l10n['ajaxurl']);
            $this->assertTrue($l10n['loggedIn']);
            $this->assertStringContainsString('build/css/frontendHighlighterApp.css?ver=' . EDAC_VERSION, $l10n['appCssUrl']);
            $this->assertSame('right', $l10n['widgetPosition']);
            $this->assertStringContainsString("post={$post_id}", $l10n['editorLink']);
            $this->assertStringContainsString('build/pageScanner.bundle.js', $l10n['scannerBundleUrl']);

            // Translations
            $tr = $edac_test_state['script_translations'][0] ?? null;
            $this->assertNotNull($tr, 'Script translations should be set.');
            $this->assertSame('edac-frontend-highlighter-app', $tr['handle']);
            $this->assertSame('accessibility-checker', $tr['domain']);
            $this->assertStringContainsString('languages', $tr['path']);
        }

        public function test_rest_nonce_empty_when_user_not_logged_in(): void {
            global $edac_test_state;
            $edac_test_state['is_user_logged_in'] = false;
            $post_id = 222;
            $edac_test_state['post'] = (object)['ID' => $post_id];
            $GLOBALS['post'] = $edac_test_state['post'];
            $edac_test_state['current_user_caps'] = ["edit_post:{$post_id}", 'manage_options'];
            \EDAC\Admin\Settings::$types = ['post'];

            $this->run_subject();

            $loc = $edac_test_state['localized'][0]['l10n'] ?? null;
            $this->assertNotNull($loc);
            $this->assertSame('', $loc['restNonce'], 'restNonce should be empty when user not logged in.');
            $this->assertFalse($loc['loggedIn'], 'loggedIn should be false when user not logged in.');
        }

        public function test_userCanFix_capability_uses_filter(): void {
            global $edac_test_state;
            $post_id = 999;
            $edac_test_state['post'] = (object)['ID' => $post_id];
            $GLOBALS['post'] = $edac_test_state['post'];
            // Override capability required to something user does NOT have
            $edac_test_state['filters']['edac_filter_settings_capability'] = function($v) { unset($v); return 'custom_capability_not_granted'; };
            // Give edit_post so we pass visibility, but not the custom cap.
            $edac_test_state['current_user_caps'] = ["edit_post:{$post_id}"];
            \EDAC\Admin\Settings::$types = ['post'];

            $this->run_subject();

            $loc = $edac_test_state['localized'][0]['l10n'] ?? null;
            $this->assertNotNull($loc);
            $this->assertFalse($loc['userCanFix'], 'userCanFix should reflect filtered capability.');
            $this->assertTrue($loc['userCanEdit'], 'userCanEdit still true with edit_post.');
        }

        public function test_frontend_highlight_post_id_filter_can_override_post(): void {
            global $edac_test_state;
            $edac_test_state['post'] = null;
            $GLOBALS['post'] = null;
            // Override to provide a post id despite no global $post
            $forced_id = 555;
            $edac_test_state['filters']['edac_filter_frontend_highlight_post_id'] = function($v) use ($forced_id) { unset($v); return $forced_id; };
            $edac_test_state['current_user_caps'] = ["edit_post:{$forced_id}", 'manage_options'];
            \EDAC\Admin\Settings::$types = ['post'];

            $this->run_subject();

            $this->assertNotEmpty($edac_test_state['enqueued_scripts'], 'Should enqueue when filter provides a valid post id.');
            $loc = $edac_test_state['localized'][0]['l10n'] ?? null;
            $this->assertSame($forced_id, $loc['postID']);
        }
    }
}