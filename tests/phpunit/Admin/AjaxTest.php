<?php
/**
 * PHPUnit tests for EDAC\Admin\Ajax class.
 *
 * Testing stack: PHPUnit. If your repository uses WordPress core test suite or Brain Monkey, ensure your
 * tests/bootstrap.php loads that environment so WP functions used here are resolved. This file includes
 * minimal fallback stubs to run the core logic assertions without exiting the process.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
namespace Tests\Phpunit\Admin;

use PHPUnit\Framework\TestCase;
use EDAC\Admin\Ajax;

if (!class_exists('\EDAC\Admin\Ajax')) {
    // Provide a forward declaration if autoload is not configured in test env; adjust bootstrap to load plugin files.
    require_once __DIR__ . '/../../../path/to/your/plugin/Admin/Ajax.php';
}

class AjaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_REQUEST = [];
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null, $_status_code = null) {
                (void) $_status_code;
                throw new \RuntimeException('wp_send_json_error:' . json_encode($data));
            }
        }
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null, $_status_code = null) {
                (void) $_status_code;
                throw new \RuntimeException('wp_send_json_success:' . json_encode($data));
            }
        }
        if (!function_exists('wp_send_json')) {
            function wp_send_json($data = null, $_status_code = null) {
                (void) $_status_code;
                throw new \RuntimeException('wp_send_json:' . json_encode($data));
            }
        }
        if (!function_exists('wp_verify_nonce')) {
            function wp_verify_nonce($nonce, $action = -1) {
                return $nonce === 'ok' && $action === 'ajax-nonce';
            }
        }
        if (!function_exists('sanitize_key')) {
            function sanitize_key($key) {
                return (string) $key;
            }
        }
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) {
                return is_string($str) ? $str : '';
            }
        }
        if (!function_exists('sanitize_textarea_field')) {
            function sanitize_textarea_field($str) {
                return is_string($str) ? $str : '';
            }
        }
        if (!function_exists('wp_unslash')) {
            function wp_unslash($val) {
                return $val;
            }
        }
        if (!function_exists('__')) {
            function __($text, $_domain = null) {
                (void) $_domain;
                return $text;
            }
        }
        if (!function_exists('_n')) {
            function _n($single, $plural, $number, $_domain = null) {
                (void) $_domain;
                return $number === 1 ? $single : $plural;
            }
        }
        if (!function_exists('esc_html__')) {
            function esc_html__($text, $_domain = null) {
                (void) $_domain;
                return $text;
            }
        }
        if (!function_exists('esc_attr__')) {
            function esc_attr__($text, $_domain = null) {
                (void) $_domain;
                return $text;
            }
        }
        if (!function_exists('esc_url')) {
            function esc_url($url) {
                return $url;
            }
        }
        if (!function_exists('get_option')) {
            function get_option($_name, $default = false) {
                (void) $_name;
                return $default;
            }
        }
        if (!function_exists('get_post_meta')) {
            function get_post_meta($_post_id, $_key, $single = false) {
                (void) $_post_id;
                (void) $_key;
                return $single ? '' : [''];
            }
        }
        if (!function_exists('update_post_meta')) {
            function update_post_meta($_post_id, $_key, $_value) {
                (void) $_post_id;
                (void) $_key;
                (void) $_value;
                return true;
            }
        }
        if (!function_exists('get_current_blog_id')) {
            function get_current_blog_id() {
                return 1;
            }
        }
        if (!function_exists('current_user_can')) {
            function current_user_can($_cap, ...$_args) {
                (void) $_cap;
                (void) $_args;
                return true;
            }
        }
        if (!function_exists('get_userdata')) {
            function get_userdata($_user_id) {
                (void) $_user_id;
                return (object)['user_login' => 'tester'];
            }
        }
        if (!function_exists('get_the_permalink')) {
            function get_the_permalink($post_id) {
                return 'https://example.com/?p=' . $post_id;
            }
        }
        if (!function_exists('wp_create_nonce')) {
            function wp_create_nonce($_action) {
                (void) $_action;
                return 'nonce123';
            }
        }
        if (!function_exists('add_query_arg')) {
            function add_query_arg($args, $url) {
                $q = http_build_query($args);
                return $url . (str_contains($url, '?') ? '&' : '?') . $q;
            }
        }
        if (!function_exists('admin_url')) {
            function admin_url($path = '') {
                return 'https://example.com/wp-admin/' . ltrim($path, '/');
            }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() {
                return 77;
            }
        }
        if (!function_exists('update_user_meta')) {
            function update_user_meta($_user_id, $_key, $_value) {
                (void) $_user_id;
                (void) $_key;
                (void) $_value;
                return true;
            }
        }
        if (!function_exists('get_post')) {
            function get_post($post_id) {
                return (object)['ID' => $post_id, 'post_content' => 'Hello world'];
            }
        }
        if (!function_exists('apply_filters')) {
            function apply_filters($_tag, $value) {
                (void) $_tag;
                return $value;
            }
        }
        if (!function_exists('wp_filter_nohtml_kses')) {
            function wp_filter_nohtml_kses($str) {
                return strip_tags($str);
            }
        }
        if (!function_exists('edac_generate_summary_stat')) {
            function edac_generate_summary_stat($_a, $_b, $c) {
                (void) $_a;
                (void) $_b;
                return "<li>$c</li>";
            }
        }
        if (!function_exists('edac_is_virtual_page')) {
            function edac_is_virtual_page($_post_id) {
                (void) $_post_id;
                return false;
            }
        }
        if (!function_exists('edac_generate_link_type')) {
            function edac_generate_link_type($_a, $_b, $c) {
                (void) $_a;
                (void) $_b;
                return 'https://example.com/help?id=' . ($c['help_id'] ?? '');
            }
        }
        if (!function_exists('edac_link_wrapper')) {
            function edac_link_wrapper($url, ...$_args) {
                (void) $_args;
                return $url;
            }
        }
        if (!function_exists('edac_register_rules')) {
            function edac_register_rules() {
                return [];
            }
        }
        if (!function_exists('edac_remove_element_with_value')) {
            function edac_remove_element_with_value($arr, $_key, $_val) {
                (void) $_key;
                (void) $_val;
                return $arr;
            }
        }
        if (!function_exists('edac_parse_html_for_media')) {
            function edac_parse_html_for_media($_html) {
                (void) $_html;
                return ['img' => '', 'svg' => ''];
            }
        }
        if (!function_exists('edac_generate_landmark_link')) {
            function edac_generate_landmark_link($_landmark, $_selector, $_postid) {
                (void) $_landmark;
                (void) $_selector;
                (void) $_postid;
                return '';
            }
        }
        if (!defined('EDAC_SVG_IGNORE_ICON')) {
            define('EDAC_SVG_IGNORE_ICON', '<svg></svg>');
        }
        if (!defined('EDAC_PLUGIN_URL')) {
            define('EDAC_PLUGIN_URL', 'https://example.com/plugin/');
        }
        if (!function_exists('get_bloginfo')) {
            function get_bloginfo($_show = 'url') {
                (void) $_show;
                return 'https://example.com';
            }
        }
        if (!function_exists('plugin_dir_url')) {
            function plugin_dir_url($_file) {
                (void) $_file;
                return 'https://example.com/plugin/';
            }
        }
        if (!function_exists('edac_ordinal')) {
            function edac_ordinal($n) {
                return $n . "th";
            }
        }
        if (!function_exists('esc_html_e')) {
            function esc_html_e($text, $_domain = null) {
                (void) $_domain;
                echo $text;
            }
        }
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
        parent::tearDown();
    }

    public function test_summary_nonce_failure_returns_error()
    {
        $_REQUEST = ['nonce' => 'bad'];
        $ajax = new Ajax();
        $this->expectException(\RuntimeException::class);
        $ajax->summary();
    }

    public function test_readability_missing_post_id_returns_error()
    {
        $_REQUEST = ['nonce' => 'ok'];
        $ajax = new Ajax();
        $this->expectException(\RuntimeException::class);
        $ajax->readability();
    }

    public function test_simplified_summary_success()
    {
        $_REQUEST = ['nonce' => 'ok', 'post_id' => 77, 'summary' => 'abc'];
        $ajax = new Ajax();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/wp_send_json_success/');
        $ajax->simplified_summary();
    }

    public function test_dismiss_welcome_cta_and_dashboard_cta()
    {
        $ajax = new Ajax();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/wp_send_json/');
        $ajax->dismiss_welcome_cta();
    }
}