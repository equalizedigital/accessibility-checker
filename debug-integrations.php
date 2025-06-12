<?php
/**
 * Debug script for simplified summary integrations
 */

// Set up WordPress environment if possible
$wp_config_paths = [
    dirname(__FILE__) . '/wp-config.php',
    dirname(__FILE__) . '/../wp-config.php',
    dirname(__FILE__) . '/../../wp-config.php'
];

foreach ($wp_config_paths as $config_path) {
    if (file_exists($config_path)) {
        // Set up WordPress with minimal bootstrapping
        define('WP_USE_THEMES', false);
        define('ABSPATH', dirname($config_path) . '/');
        
        // Start output buffering to capture any WordPress output
        ob_start();
        
        try {
            require_once $config_path;
            $wp_loaded = true;
            break;
        } catch (Exception $e) {
            echo "Error loading WordPress: " . $e->getMessage() . "\n";
            $wp_loaded = false;
        }
    }
}

if (!isset($wp_loaded) || !$wp_loaded) {
    echo "Could not find or load WordPress configuration\n";
    echo "Testing without WordPress environment...\n\n";
}

// Test autoloading
echo "=== AUTOLOADING TEST ===\n";
$autoload_file = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_file)) {
    require_once $autoload_file;
    echo "✓ Composer autoload found and loaded\n";
} else {
    echo "✗ Composer autoload not found at: $autoload_file\n";
}

// Test class existence
echo "\n=== CLASS EXISTENCE TEST ===\n";
$classes = [
    'EDAC\Inc\Plugin',
    'EDAC\Inc\Simplified_Summary_Integrations',
    'EDAC\Inc\Simplified_Summary_Block',
    'EDAC\Inc\Simplified_Summary_Shortcode',
    'EDAC\Inc\Simplified_Summary'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✓ $class exists\n";
    } else {
        echo "✗ $class NOT found\n";
    }
}

// Test WordPress integration if WP is loaded
if (isset($wp_loaded) && $wp_loaded) {
    // Clear any buffered output from WordPress
    ob_clean();
    
    echo "\n=== WORDPRESS INTEGRATION TEST ===\n";
    
    // Test constants
    $constants = ['EDAC_VERSION', 'EDAC_PLUGIN_FILE', 'EDAC_PLUGIN_DIR'];
    foreach ($constants as $constant) {
        if (defined($constant)) {
            echo "✓ $constant defined: " . constant($constant) . "\n";
        } else {
            echo "✗ $constant NOT defined\n";
        }
    }
    
    // Test function existence
    $functions = ['register_block_type', 'add_shortcode', 'shortcode_exists'];
    foreach ($functions as $function) {
        if (function_exists($function)) {
            echo "✓ $function available\n";
        } else {
            echo "✗ $function NOT available\n";
        }
    }
    
    // Test if actions were added
    echo "\n=== HOOKS TEST ===\n";
    
    // Check if we can instantiate our classes
    try {
        if (class_exists('EDAC\Inc\Simplified_Summary_Integrations')) {
            $integrations = new EDAC\Inc\Simplified_Summary_Integrations();
            echo "✓ Simplified_Summary_Integrations instantiated\n";
            
            // Test hook initialization
            $integrations->init_hooks();
            echo "✓ init_hooks() called\n";
        }
    } catch (Exception $e) {
        echo "✗ Error instantiating classes: " . $e->getMessage() . "\n";
    }
    
    // Check if shortcodes are registered after init
    if (function_exists('shortcode_exists')) {
        echo "\n=== SHORTCODE REGISTRATION TEST ===\n";
        $shortcodes = ['accessibility_summary', 'edac_simplified_summary'];
        foreach ($shortcodes as $shortcode) {
            if (shortcode_exists($shortcode)) {
                echo "✓ [$shortcode] registered\n";
            } else {
                echo "✗ [$shortcode] NOT registered\n";
            }
        }
    }
    
    // Check if blocks are registered
    if (function_exists('register_block_type') && class_exists('WP_Block_Type_Registry')) {
        echo "\n=== BLOCK REGISTRATION TEST ===\n";
        $registry = WP_Block_Type_Registry::get_instance();
        if ($registry->is_registered('accessibility-checker/simplified-summary')) {
            echo "✓ accessibility-checker/simplified-summary block registered\n";
        } else {
            echo "✗ accessibility-checker/simplified-summary block NOT registered\n";
        }
    }
}

// Test file existence
echo "\n=== FILE EXISTENCE TEST ===\n";
$files = [
    'includes/classes/class-simplified-summary-integrations.php',
    'includes/classes/class-simplified-summary-block.php',
    'includes/classes/class-simplified-summary-shortcode.php',
    'includes/classes/class-simplified-summary.php',
    'build/simplified-summary-block.bundle.js',
    'build/css/simplified-summary-block.css',
    'src/blocks/simplified-summary-block.js'
];

foreach ($files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "✓ $file exists (" . filesize($full_path) . " bytes)\n";
    } else {
        echo "✗ $file NOT found\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
