<?php
/**
 * Test script for simplified summary components
 */

// Set up WordPress environment for testing
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

// Define WordPress constants
define('WP_USE_THEMES', false);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Require WordPress functions
require_once('/Applications/MAMP/htdocs/wordpress/wp-config.php');
require_once('/Applications/MAMP/htdocs/wordpress/wp-load.php');

// Test post data (use an existing post ID)
$test_post_id = 1; // Replace with an actual post ID

echo "Testing Simplified Summary Components\n";
echo "=====================================\n\n";

// Test 1: Check if classes exist
echo "1. Class Availability Test:\n";
$classes_to_test = [
    'EDAC\Inc\Simplified_Summary',
    'EDAC\Inc\Simplified_Summary_Shortcode',
    'EDAC\Inc\Simplified_Summary_Block',
    'EDAC\Inc\Simplified_Summary_Integrations'
];

foreach ($classes_to_test as $class) {
    echo "   - $class: " . (class_exists($class) ? "✓ Available" : "✗ Not Available") . "\n";
}

// Test 2: Test shortcode registration
echo "\n2. Shortcode Registration Test:\n";
$shortcodes = ['accessibility_summary', 'edac_summary'];
foreach ($shortcodes as $shortcode) {
    echo "   - [$shortcode]: " . (shortcode_exists($shortcode) ? "✓ Registered" : "✗ Not Registered") . "\n";
}

// Test 3: Test simplified summary output
echo "\n3. Simplified Summary Output Test:\n";
if (class_exists('EDAC\Inc\Simplified_Summary')) {
    $simplified_summary = new EDAC\Inc\Simplified_Summary();
    $markup = $simplified_summary->simplified_summary_markup($test_post_id);
    echo "   - Post ID $test_post_id: " . (empty($markup) ? "✗ No output" : "✓ Output generated") . "\n";
    if (!empty($markup)) {
        echo "   - Sample output: " . substr(strip_tags($markup), 0, 100) . "...\n";
    }
} else {
    echo "   - ✗ Simplified_Summary class not available\n";
}

// Test 4: Test shortcode output
echo "\n4. Shortcode Output Test:\n";
if (shortcode_exists('accessibility_summary')) {
    $shortcode_output = do_shortcode("[accessibility_summary post_id=\"$test_post_id\"]");
    echo "   - [accessibility_summary]: " . (empty($shortcode_output) ? "✗ No output" : "✓ Output generated") . "\n";
} else {
    echo "   - ✗ accessibility_summary shortcode not registered\n";
}

// Test 5: Check if block is registered
echo "\n5. Block Registration Test:\n";
if (function_exists('get_registered_block_types')) {
    $blocks = get_registered_block_types();
    $block_registered = isset($blocks['accessibility-checker/simplified-summary']);
    echo "   - accessibility-checker/simplified-summary: " . ($block_registered ? "✓ Registered" : "✗ Not Registered") . "\n";
} else {
    echo "   - ✗ Block registration functions not available\n";
}

echo "\nTest Complete!\n";
