<?php
namespace EDAC\TestHelpers;

class Recorder {
    public static $events = [];
    public static function record(string $name, ...$args): void {
        self::$events[] = array_merge([$name], $args);
    }
    public static function reset(): void {
        self::$events = [];
    }
}

class FakeRestRequest implements \ArrayAccess {
    private $params;
    private $json;
    public function __construct(array $params = [], array $json = []) {
        $this->params = $params;
        $this->json   = $json;
    }
    public function offsetExists($offset): bool { return isset($this->params[$offset]); }
    public function offsetGet($offset)         { return $this->params[$offset] ?? null; }
    public function offsetSet($offset, $value): void { $this->params[$offset] = $value; }
    public function offsetUnset($offset): void { unset($this->params[$offset]); }
    public function get_json_params(): array   { return $this->json; }
}

class FakeWpRestRequest extends \WP_REST_Request {
    private $params;
    public function __construct(array $params = []) {
        $this->params = $params;
    }
    public function get_param($key) {
        return $this->params[$key] ?? null;
    }
}

class Stubs {
    public static $get_post                        = null;
    public static $get_post_type                   = null;
    public static $get_scannable_post_types        = null;
    public static $purge_post_delete               = null;
    public static $edac_remove_corrected_posts      = null;
    public static $do_action                       = null;
    public static $edac_register_rules              = null;
    public static $insert_rule_data                = null;
    public static $summary_generate                 = null;
    public static $update_post_meta                = null;
    public static $issues_summary_by_post_type     = null;
    public static $scans_stats_summary             = null;
    public static $scans_stats_summary_no_ttl      = null;
    public static $scans_stats_clear               = null;
    public static $get_post_types                  = null;
}

namespace EDAC\Inc;

// Minimal WP REST classes for isolated PHPUnit (when WP not bootstrapped)
if (!class_exists('\WP_REST_Response')) {
    class WP_REST_Response {
        private $data;
        private $status;
        public function __construct($data, $status = 200) { $this->data = $data; $this->status = $status; }
        public function get_data()    { return $this->data; }
        public function get_status()  { return $this->status; }
    }
}
if (!class_exists('\WP_REST_Request')) {
    class WP_REST_Request {}
}

// Provide stubs and shims to decouple tests from full WP runtime
function maybe_load_wp_functions_stubs(): void {
    // no-op here, but acts as a hook for future bootstrap logic.
}

// Shim WP global functions via helpers\Stubs closures (if set), else provide defaults
function get_post($id) {
    return \EDAC\TestHelpers\Stubs::$get_post
        ? (\EDAC\TestHelpers\Stubs::$get_post)($id)
        : (object)['ID' => $id, 'post_type' => 'post'];
}
function get_post_type($post) {
    return \EDAC\TestHelpers\Stubs::$get_post_type
        ? (\EDAC\TestHelpers\Stubs::$get_post_type)($post)
        : ($post->post_type ?? 'post');
}
function update_post_meta($post_id, $key, $value) {
    return \EDAC\TestHelpers\Stubs::$update_post_meta
        ? (\EDAC\TestHelpers\Stubs::$update_post_meta)($post_id, $key, $value)
        : true;
}
function get_post_types($_args = []) {
    if (\EDAC\TestHelpers\Stubs::$get_post_types) {
        return (\EDAC\TestHelpers\Stubs::$get_post_types)($_args);
    }
    return ['post' => 'post', 'page' => 'page'];
}
function current_user_can(...$args) { count($args); return true; }
function do_action($hook, ...$args) {
    if (\EDAC\TestHelpers\Stubs::$do_action) {
        (\EDAC\TestHelpers\Stubs::$do_action)($hook, ...$args);
    }
}
function time() { return 1234567890; }

// Shim plugin functions
function edac_remove_corrected_posts($post_id, $type, $pre, $ctx) {
    if (\EDAC\TestHelpers\Stubs::$edac_remove_corrected_posts) {
        (\EDAC\TestHelpers\Stubs::$edac_remove_corrected_posts)($post_id, $type, $pre, $ctx);
    }
}
function edac_register_rules() {
    if (\EDAC\TestHelpers\Stubs::$edac_register_rules) {
        return (\EDAC\TestHelpers\Stubs::$edac_register_rules)();
    }
    return [];
}

namespace EDAC\Admin;
class Settings {
    public static function get_scannable_post_types() {
        return \EDAC\TestHelpers\Stubs::$get_scannable_post_types
            ? (\EDAC\TestHelpers\Stubs::$get_scannable_post_types)()
            : ['post', 'page'];
    }
}
class Purge_Post_Data {
    public static function delete_post($post_id) {
        if (\EDAC\TestHelpers\Stubs::$purge_post_delete) {
            (\EDAC\TestHelpers\Stubs::$purge_post_delete)($post_id);
        }
    }
}
class Scans_Stats {
    public function __construct($_ttl = null) { isset($_ttl); }
    public function clear_cache() {
        if (\EDAC\TestHelpers\Stubs::$scans_stats_clear) {
            (\EDAC\TestHelpers\Stubs::$scans_stats_clear)();
        }
    }
    public function summary() {
        if (\EDAC\TestHelpers\Stubs::$scans_stats_summary) {
            return (\EDAC\TestHelpers\Stubs::$scans_stats_summary)(null);
        }
        if (\EDAC\TestHelpers\Stubs::$scans_stats_summary_no_ttl) {
            return (\EDAC\TestHelpers\Stubs::$scans_stats_summary_no_ttl)();
        }
        return [];
    }
    public function issues_summary_by_post_type($post_type) {
        return \EDAC\TestHelpers\Stubs::$issues_summary_by_post_type
            ? (\EDAC\TestHelpers\Stubs::$issues_summary_by_post_type)($post_type)
            : [];
    }
}

namespace EDAC\Inc;
class Summary_Generator {
    private $post_id;
    public function __construct($post_id) { $this->post_id = $post_id; }
    public function generate_summary() {
        if (\EDAC\TestHelpers\Stubs::$summary_generate) {
            (\EDAC\TestHelpers\Stubs::$summary_generate)($this->post_id);
        }
    }
}

namespace EDAC\Admin;
class Insert_Rule_Data {
    public function insert($post, $rule_id, $impact, $html, $landmark, $landmark_selector, $selectors) {
        if (\EDAC\TestHelpers\Stubs::$insert_rule_data) {
            (\EDAC\TestHelpers\Stubs::$insert_rule_data)($post, $rule_id, $impact, $html, $landmark, $landmark_selector, $selectors);
        }
    }
}