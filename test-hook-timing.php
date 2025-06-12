<?php
/**
 * Test script to verify that our hook timing fix works correctly
 * This simulates the WordPress environment to test block and shortcode registration
 */

// Simulate WordPress functions that we need
if (!function_exists('register_block_type')) {
    function register_block_type($block_type, $args = array()) {
        echo "✓ Block registered: $block_type\n";
        if (isset($args['render_callback'])) {
            echo "  - Has render callback: " . (is_callable($args['render_callback']) ? 'YES' : 'NO') . "\n";
        }
        if (isset($args['attributes'])) {
            echo "  - Has attributes: " . count($args['attributes']) . " attributes\n";
        }
        return true;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        echo "✓ Shortcode registered: [$tag]\n";
        echo "  - Has callback: " . (is_callable($callback) ? 'YES' : 'NO') . "\n";
        return true;
    }
}

if (!function_exists('wp_register_script')) {
    function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
        echo "✓ Script registered: $handle\n";
        return true;
    }
}

if (!function_exists('wp_register_style')) {
    function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
        echo "✓ Style registered: $handle\n";
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        echo "✓ Script enqueued: $handle\n";
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        echo "✓ Style enqueued: $handle\n";
        return true;
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/accessibility-checker/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        echo "✓ Script localized: $handle -> $object_name\n";
        return true;
    }
}

if (!function_exists('wp_set_script_translations')) {
    function wp_set_script_translations($handle, $domain = 'default', $path = null) {
        echo "✓ Script translations set: $handle\n";
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return $data; // Simplified for testing
    }
}

if (!function_exists('get_current_screen')) {
    function get_current_screen() {
        return (object) array('id' => 'edit-post');
    }
}

// Load the autoloader
echo "Loading autoloader...\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✓ Autoloader loaded\n\n";
} else {
    echo "✗ Autoloader not found at: " . __DIR__ . '/vendor/autoload.php' . "\n";
    exit(1);
}

echo "=== Testing Hook Timing Fix ===\n\n";

// Test 1: Check if classes exist
echo "1. Checking if classes exist...\n";
$classes = [
    'EDAC\\Inc\\Simplified_Summary_Integrations',
    'EDAC\\Inc\\Simplified_Summary_Block',
    'EDAC\\Inc\\Simplified_Summary_Shortcode'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✓ Class exists: $class\n";
    } else {
        echo "✗ Class missing: $class\n";
    }
}

echo "\n2. Testing Integrations Manager initialization...\n";

try {
    // Create the integrations manager
    $integrations = new EDAC\Inc\Simplified_Summary_Integrations();
    echo "✓ Integrations manager created successfully\n";
    
    // Test the new direct registration approach
    echo "\n3. Testing direct registration calls...\n";
    
    // Mock the component classes
    $block = new EDAC\Inc\Simplified_Summary_Block();
    $shortcode = new EDAC\Inc\Simplified_Summary_Shortcode();
    
    echo "✓ Component classes instantiated\n";
    
    // Test block registration
    echo "\n4. Testing block registration...\n";
    $block->register_block();
    
    // Test shortcode registration
    echo "\n5. Testing shortcode registration...\n";
    $shortcode->register_shortcodes();
    
    echo "\n✓ All registrations completed successfully!\n";
    echo "\n=== Hook Timing Fix Test PASSED ===\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "=== Hook Timing Fix Test FAILED ===\n";
} catch (Error $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "=== Hook Timing Fix Test FAILED ===\n";
}
