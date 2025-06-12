<?php
/**
 * Simple class loading test for Simplified Summary components
 */

// Test if we can load the classes without WordPress
echo "Testing Simplified Summary Component Class Loading\n";
echo "================================================\n\n";

$base_path = __DIR__ . '/includes/classes/';
$test_files = [
    'class-simplified-summary-shortcode.php',
    'class-simplified-summary-elementor-widget.php', 
    'class-simplified-summary-integrations.php',
    'class-simplified-summary-block.php'
];

foreach ($test_files as $file) {
    $full_path = $base_path . $file;
    echo "Testing: $file\n";
    
    if (!file_exists($full_path)) {
        echo "   ✗ File not found\n";
        continue;
    }
    
    // Test syntax
    $syntax_check = shell_exec("php -l \"$full_path\" 2>&1");
    if (strpos($syntax_check, 'No syntax errors detected') !== false) {
        echo "   ✓ Syntax valid\n";
    } else {
        echo "   ✗ Syntax error: " . trim($syntax_check) . "\n";
        continue;
    }
    
    // Check for required namespace
    $content = file_get_contents($full_path);
    if (strpos($content, 'namespace EDAC\Inc;') !== false) {
        echo "   ✓ Namespace correct\n";
    } else {
        echo "   ✗ Missing or incorrect namespace\n";
    }
    
    // Check for class definition
    $class_name = str_replace(['class-', '.php'], ['', ''], $file);
    $class_name = str_replace('-', '_', $class_name);
    $class_name = ucwords($class_name, '_');
    
    if (strpos($content, "class $class_name") !== false) {
        echo "   ✓ Class $class_name defined\n";
    } else {
        echo "   ✗ Class $class_name not found\n";
    }
    
    echo "\n";
}

// Test built assets
echo "Testing Built Assets\n";
echo "==================\n\n";

$build_files = [
    'build/simplified-summary-block.bundle.js',
    'build/css/simplified-summary-block.css'
];

foreach ($build_files as $file) {
    echo "Testing: $file\n";
    
    if (file_exists($file)) {
        $size = filesize($file);
        echo "   ✓ File exists (Size: " . number_format($size) . " bytes)\n";
        
        if ($size > 0) {
            echo "   ✓ File not empty\n";
        } else {
            echo "   ✗ File is empty\n";
        }
    } else {
        echo "   ✗ File not found\n";
    }
    echo "\n";
}

echo "Class loading test complete!\n";
