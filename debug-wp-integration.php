<?php
/**
 * WordPress Integration Debug Script
 * 
 * This script tests the Accessibility Checker integrations in WordPress
 */

// Start WordPress session
if (!function_exists('wp_head')) {
    echo "WordPress is not initialized. Need to test in WordPress context.\n";
    
    // Let's create a comprehensive test that checks everything
    echo "=== ACCESSIBILITY CHECKER INTEGRATION DEBUG ===\n\n";
    
    // Check file existence
    $files_to_check = [
        '/Users/stevejones/Documents/Repositories/accessibility-checker/includes/classes/class-simplified-summary-integrations.php',
        '/Users/stevejones/Documents/Repositories/accessibility-checker/includes/classes/class-simplified-summary-block.php', 
        '/Users/stevejones/Documents/Repositories/accessibility-checker/includes/classes/class-simplified-summary-shortcode.php',
        '/Users/stevejones/Documents/Repositories/accessibility-checker/includes/classes/class-simplified-summary-elementor-widget.php',
        '/Users/stevejones/Documents/Repositories/accessibility-checker/build/simplified-summary-block.bundle.js',
        '/Users/stevejones/Documents/Repositories/accessibility-checker/build/css/simplified-summary-block.css',
    ];
    
    echo "1. Checking file existence:\n";
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            echo "✓ $file exists (" . filesize($file) . " bytes)\n";
        } else {
            echo "✗ $file missing\n";
        }
    }
    
    // Check class loading
    echo "\n2. Testing class loading:\n";
    
    // Test if we can include the main plugin file
    $plugin_file = '/Users/stevejones/Documents/Repositories/accessibility-checker/accessibility-checker.php';
    if (file_exists($plugin_file)) {
        echo "✓ Plugin file exists\n";
        
        // Check if composer autoload exists
        $autoload_file = '/Users/stevejones/Documents/Repositories/accessibility-checker/vendor/autoload.php';
        if (file_exists($autoload_file)) {
            echo "✓ Composer autoload exists\n";
            
            // Include autoload to test class loading
            require_once $autoload_file;
            
            // Test if classes can be loaded
            $classes_to_test = [
                'EDAC\\Inc\\Simplified_Summary_Integrations',
                'EDAC\\Inc\\Simplified_Summary_Block',
                'EDAC\\Inc\\Simplified_Summary_Shortcode',
                'EDAC\\Inc\\Simplified_Summary_Elementor_Widget'
            ];
            
            foreach ($classes_to_test as $class) {
                if (class_exists($class)) {
                    echo "✓ Class $class can be loaded\n";
                } else {
                    echo "✗ Class $class cannot be loaded\n";
                }
            }
        } else {
            echo "✗ Composer autoload missing - run 'composer dump-autoload'\n";
        }
    } else {
        echo "✗ Plugin file missing\n";
    }
    
    // Check WordPress constants and functions that might be needed
    echo "\n3. WordPress integration requirements:\n";
    
    $wp_functions = [
        'add_action', 'add_filter', 'register_block_type', 'add_shortcode', 
        'wp_enqueue_script', 'wp_enqueue_style', 'get_post_meta', 'is_admin'
    ];
    
    foreach ($wp_functions as $func) {
        if (function_exists($func)) {
            echo "✓ WordPress function $func is available\n";
        } else {
            echo "✗ WordPress function $func is NOT available\n";
        }
    }
    
    // Check if WP constants are defined
    $wp_constants = ['ABSPATH', 'WP_PLUGIN_DIR', 'WP_CONTENT_DIR'];
    foreach ($wp_constants as $const) {
        if (defined($const)) {
            echo "✓ WordPress constant $const is defined\n";
        } else {
            echo "✗ WordPress constant $const is NOT defined\n";
        }
    }
    
    echo "\n4. Manual WordPress shortcode test:\n";
    
    // Try to manually test shortcode functionality
    if (function_exists('add_shortcode')) {
        // Simulate shortcode registration
        add_shortcode('test_accessibility_summary', function($atts) {
            return "Test shortcode working! Post ID: " . (isset($atts['post_id']) ? $atts['post_id'] : 'not set');
        });
        
        if (function_exists('do_shortcode')) {
            $shortcode_test = do_shortcode('[test_accessibility_summary post_id="123"]');
            echo "✓ Test shortcode output: $shortcode_test\n";
        } else {
            echo "✗ do_shortcode function not available\n";
        }
    } else {
        echo "✗ add_shortcode function not available\n";
    }
    
    echo "\n=== END DEBUG ===\n";
    exit;
}

// If we're in WordPress context
echo "WordPress is initialized. Running integration tests...\n";

// Test shortcode
if (shortcode_exists('accessibility_summary')) {
    echo "✓ [accessibility_summary] shortcode is registered\n";
    $test_output = do_shortcode('[accessibility_summary post_id="1"]');
    echo "Shortcode test output: $test_output\n";
} else {
    echo "✗ [accessibility_summary] shortcode is NOT registered\n";
}

if (shortcode_exists('edac_simplified_summary')) {
    echo "✓ [edac_simplified_summary] shortcode is registered\n";
    $test_output = do_shortcode('[edac_simplified_summary post_id="1"]');
    echo "Shortcode test output: $test_output\n";
} else {
    echo "✗ [edac_simplified_summary] shortcode is NOT registered\n";
}

// Test block registration
$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
if (isset($registered_blocks['accessibility-checker/simplified-summary'])) {
    echo "✓ Gutenberg block 'accessibility-checker/simplified-summary' is registered\n";
} else {
    echo "✗ Gutenberg block 'accessibility-checker/simplified-summary' is NOT registered\n";
}

// Test class existence
$classes_to_test = [
    'EDAC\\Inc\\Simplified_Summary_Integrations',
    'EDAC\\Inc\\Simplified_Summary_Block',
    'EDAC\\Inc\\Simplified_Summary_Shortcode',
    'EDAC\\Inc\\Simplified_Summary_Elementor_Widget'
];

foreach ($classes_to_test as $class) {
    if (class_exists($class)) {
        echo "✓ Class $class exists in WordPress\n";
    } else {
        echo "✗ Class $class does NOT exist in WordPress\n";
    }
}

// Test if the main integrations class was initialized
global $wp_filter;
if (isset($wp_filter['init'])) {
    echo "✓ WordPress 'init' action has hooks\n";
    $init_hooks = $wp_filter['init'];
    // Look for our integration hooks
    foreach ($init_hooks->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                is_object($callback['function'][0]) && 
                get_class($callback['function'][0]) === 'EDAC\\Inc\\Simplified_Summary_Integrations') {
                echo "✓ Found Simplified_Summary_Integrations in init hooks\n";
                break 2;
            }
        }
    }
}

echo "\nIntegration debug complete.\n";
