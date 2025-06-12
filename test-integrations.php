<?php
/**
 * Test script to verify simplified summary integrations are loading
 */

// Set up WordPress environment
define( 'WP_USE_THEMES', false );
require_once( 'wp-config.php' );

// Check if classes exist
echo "Testing class availability:\n";
echo "- Plugin class exists: " . (class_exists('EDAC\Inc\Plugin') ? 'YES' : 'NO') . "\n";
echo "- Simplified_Summary_Integrations exists: " . (class_exists('EDAC\Inc\Simplified_Summary_Integrations') ? 'YES' : 'NO') . "\n";
echo "- Simplified_Summary_Block exists: " . (class_exists('EDAC\Inc\Simplified_Summary_Block') ? 'YES' : 'NO') . "\n";
echo "- Simplified_Summary_Shortcode exists: " . (class_exists('EDAC\Inc\Simplified_Summary_Shortcode') ? 'YES' : 'NO') . "\n";

// Check if shortcodes are registered
echo "\nShortcode registration:\n";
echo "- [accessibility_summary] registered: " . (shortcode_exists('accessibility_summary') ? 'YES' : 'NO') . "\n";
echo "- [edac_simplified_summary] registered: " . (shortcode_exists('edac_simplified_summary') ? 'YES' : 'NO') . "\n";

// Check if block is registered
echo "\nBlock registration:\n";
$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
echo "- accessibility-checker/simplified-summary registered: " . (isset($registered_blocks['accessibility-checker/simplified-summary']) ? 'YES' : 'NO') . "\n";

// Test shortcode output
echo "\nTesting shortcode output:\n";
$shortcode_test = do_shortcode('[accessibility_summary]');
echo "- [accessibility_summary] output length: " . strlen($shortcode_test) . " characters\n";
echo "- Output preview: " . substr($shortcode_test, 0, 100) . (strlen($shortcode_test) > 100 ? '...' : '') . "\n";

echo "\nDone.\n";
