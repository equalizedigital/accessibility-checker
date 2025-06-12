<?php
/**
 * WordPress Integration Test Script
 * 
 * This script tests the Accessibility Checker integrations in a live WordPress environment
 * Run this via WP-CLI: wp eval-file test-wordpress-integrations.php
 */

// Ensure we're in WordPress context
if (!defined('ABSPATH')) {
    echo "Error: This script must be run in WordPress context.\n";
    exit(1);
}

echo "=== ACCESSIBILITY CHECKER INTEGRATION TEST ===\n\n";

echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current Theme: " . wp_get_theme()->get('Name') . "\n\n";

// Test 1: Check if classes exist
echo "1. Testing class availability:\n";
$classes_to_test = [
    'EDAC\\Inc\\Simplified_Summary_Integrations',
    'EDAC\\Inc\\Simplified_Summary_Block',
    'EDAC\\Inc\\Simplified_Summary_Shortcode',
    'EDAC\\Inc\\Simplified_Summary_Elementor_Widget'
];

foreach ($classes_to_test as $class) {
    if (class_exists($class)) {
        echo "✓ Class $class exists\n";
    } else {
        echo "✗ Class $class does NOT exist\n";
    }
}

// Test 2: Check shortcode registration
echo "\n2. Testing shortcode registration:\n";
$shortcodes_to_test = ['accessibility_summary', 'edac_simplified_summary'];

foreach ($shortcodes_to_test as $shortcode) {
    if (shortcode_exists($shortcode)) {
        echo "✓ Shortcode [$shortcode] is registered\n";
    } else {
        echo "✗ Shortcode [$shortcode] is NOT registered\n";
    }
}

// Test 3: Check block registration
echo "\n3. Testing Gutenberg block registration:\n";
$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
if (isset($registered_blocks['accessibility-checker/simplified-summary'])) {
    echo "✓ Gutenberg block 'accessibility-checker/simplified-summary' is registered\n";
    $block = $registered_blocks['accessibility-checker/simplified-summary'];
    echo "  - Block title: " . $block->title . "\n";
    echo "  - Block category: " . $block->category . "\n";
    echo "  - Block supports: " . json_encode($block->supports) . "\n";
} else {
    echo "✗ Gutenberg block 'accessibility-checker/simplified-summary' is NOT registered\n";
    echo "  Available blocks: " . implode(', ', array_keys($registered_blocks)) . "\n";
}

// Test 4: Test shortcode functionality with a real post
echo "\n4. Testing shortcode functionality:\n";

// Create a test post with simplified summary
$test_post_id = wp_insert_post([
    'post_title' => 'Test Post for Simplified Summary',
    'post_content' => 'This is a test post to verify simplified summary functionality.',
    'post_status' => 'publish',
    'post_type' => 'post'
]);

if ($test_post_id && !is_wp_error($test_post_id)) {
    echo "✓ Created test post with ID: $test_post_id\n";
    
    // Add simplified summary meta
    update_post_meta($test_post_id, '_edac_simplified_summary', 'This is a simplified summary for testing purposes.');
    echo "✓ Added simplified summary meta to test post\n";
    
    // Test shortcodes
    foreach ($shortcodes_to_test as $shortcode) {
        if (shortcode_exists($shortcode)) {
            $shortcode_output = do_shortcode("[$shortcode post_id=\"$test_post_id\"]");
            if (!empty($shortcode_output)) {
                echo "✓ Shortcode [$shortcode] produced output (" . strlen($shortcode_output) . " chars)\n";
                echo "  Preview: " . substr(strip_tags($shortcode_output), 0, 100) . "...\n";
            } else {
                echo "✗ Shortcode [$shortcode] produced no output\n";
            }
        }
    }
    
    // Clean up test post
    wp_delete_post($test_post_id, true);
    echo "✓ Cleaned up test post\n";
} else {
    echo "✗ Failed to create test post\n";
}

// Test 5: Check if assets are enqueued
echo "\n5. Testing asset enqueuing:\n";

// Simulate block editor context
global $wp_scripts, $wp_styles;

// Check if our block script would be registered
if (wp_script_is('edac-simplified-summary-block', 'registered')) {
    echo "✓ Block script 'edac-simplified-summary-block' is registered\n";
} else {
    echo "✗ Block script 'edac-simplified-summary-block' is NOT registered\n";
}

// Check if our block style would be registered  
if (wp_style_is('edac-simplified-summary-block-style', 'registered')) {
    echo "✓ Block style 'edac-simplified-summary-block-style' is registered\n";
} else {
    echo "✗ Block style 'edac-simplified-summary-block-style' is NOT registered\n";
}

// Test 6: Check WordPress hooks
echo "\n6. Testing WordPress hooks:\n";
global $wp_filter;

$hooks_to_check = ['init', 'wp_enqueue_scripts', 'enqueue_block_editor_assets'];
foreach ($hooks_to_check as $hook) {
    if (isset($wp_filter[$hook])) {
        $hook_count = 0;
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            $hook_count += count($callbacks);
        }
        echo "✓ Hook '$hook' has $hook_count callback(s)\n";
    } else {
        echo "✗ Hook '$hook' has no callbacks\n";
    }
}

// Test 7: Check if Elementor is available
echo "\n7. Testing Elementor compatibility:\n";
if (did_action('elementor/loaded')) {
    echo "✓ Elementor is loaded\n";
    
    if (class_exists('\\Elementor\\Widgets_Manager')) {
        echo "✓ Elementor Widgets Manager is available\n";
    } else {
        echo "✗ Elementor Widgets Manager is NOT available\n";
    }
} else {
    echo "ℹ Elementor is not loaded (this is normal if Elementor is not installed)\n";
}

// Test 8: Plugin activation status
echo "\n8. Testing plugin status:\n";
if (is_plugin_active('accessibility-checker/accessibility-checker.php')) {
    echo "✓ Accessibility Checker plugin is active\n";
} else {
    echo "✗ Accessibility Checker plugin is NOT active\n";
}

// Check plugin constants
$constants_to_check = ['EDAC_VERSION', 'EDAC_PLUGIN_DIR', 'EDAC_PLUGIN_URL'];
foreach ($constants_to_check as $constant) {
    if (defined($constant)) {
        echo "✓ Constant $constant is defined: " . constant($constant) . "\n";
    } else {
        echo "✗ Constant $constant is NOT defined\n";
    }
}

// Test 9: Check file existence
echo "\n9. Testing file existence:\n";
$files_to_check = [
    EDAC_PLUGIN_DIR . 'build/simplified-summary-block.bundle.js',
    EDAC_PLUGIN_DIR . 'build/css/simplified-summary-block.css',
    EDAC_PLUGIN_DIR . 'includes/classes/class-simplified-summary-integrations.php',
    EDAC_PLUGIN_DIR . 'includes/classes/class-simplified-summary-block.php',
    EDAC_PLUGIN_DIR . 'includes/classes/class-simplified-summary-shortcode.php',
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✓ File exists: " . basename($file) . " (" . filesize($file) . " bytes)\n";
    } else {
        echo "✗ File missing: " . basename($file) . "\n";
    }
}

// Test 10: Integration initialization
echo "\n10. Testing integration initialization:\n";
if (class_exists('EDAC\\Inc\\Simplified_Summary_Integrations')) {
    try {
        $integrations = new \EDAC\Inc\Simplified_Summary_Integrations();
        echo "✓ Simplified_Summary_Integrations can be instantiated\n";
        
        if (method_exists($integrations, 'init_hooks')) {
            echo "✓ init_hooks method exists\n";
        } else {
            echo "✗ init_hooks method does NOT exist\n";
        }
    } catch (Exception $e) {
        echo "✗ Error instantiating Simplified_Summary_Integrations: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Simplified_Summary_Integrations class not available\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Summary: Integration test completed. Review the results above to identify any issues.\n";
