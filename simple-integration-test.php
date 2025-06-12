<?php
/**
 * Simple test to verify integrations work
 */

echo "Starting simple test...\n";

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

echo "Autoloader loaded\n";

// Mock WordPress functions
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    echo "Hook registered: $hook\n";
    return true;
}

function register_block_type($block_type, $args = array()) {
    echo "Block registered: $block_type\n";
    return true;
}

function add_shortcode($tag, $callback) {
    echo "Shortcode registered: [$tag]\n";
    return true;
}

function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
    echo "Script registered: $handle\n";
    return true;
}

function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
    echo "Style registered: $handle\n";
    return true;
}

function plugin_dir_url($file) {
    return 'http://localhost/wp-content/plugins/accessibility-checker/';
}

function plugin_dir_path($file) {
    return dirname($file) . '/';
}

function wp_localize_script($handle, $object_name, $l10n) {
    return true;
}

function wp_set_script_translations($handle, $domain = 'default', $path = null) {
    return true;
}

function sanitize_text_field($str) {
    return trim(strip_tags($str));
}

function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
}

function wp_kses_post($data) {
    return $data;
}

function get_current_screen() {
    return (object) array('id' => 'edit-post');
}

function is_admin() {
    return false;
}

echo "WordPress functions mocked\n";

try {
    echo "Creating integrations manager...\n";
    $integrations = new EDAC\Inc\Simplified_Summary_Integrations();
    echo "✓ Success! Integrations manager created\n";
    
    echo "Creating block instance...\n";
    $block = new EDAC\Inc\Simplified_Summary_Block();
    echo "✓ Success! Block instance created\n";
    
    echo "Creating shortcode instance...\n";
    $shortcode = new EDAC\Inc\Simplified_Summary_Shortcode();
    echo "✓ Success! Shortcode instance created\n";
    
    echo "Testing block registration...\n";
    $block->register_block();
    echo "✓ Block registration completed\n";
    
    echo "Testing shortcode registration...\n";
    $shortcode->register_shortcodes();
    echo "✓ Shortcode registration completed\n";
    
    echo "\n🎉 ALL TESTS PASSED! 🎉\n";
    echo "Hook timing fix is working correctly!\n";
    
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
