<?php
/**
 * Test script to verify class loading for simplified summary components
 */

// Include the autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✓ Autoloader included\n";
} else {
    echo "✗ Autoloader not found\n";
    exit(1);
}

// Test each class
$classes_to_test = [
    'EDAC\Inc\Simplified_Summary',
    'EDAC\Inc\Simplified_Summary_Integrations',
    'EDAC\Inc\Simplified_Summary_Block',
    'EDAC\Inc\Simplified_Summary_Shortcode',
    'EDAC\Inc\Simplified_Summary_Elementor_Widget'
];

echo "\nTesting class loading:\n";
echo "====================\n";

foreach ($classes_to_test as $class) {
    if (class_exists($class)) {
        echo "✓ $class - Class found\n";
        
        // Try to instantiate (without running WordPress functions)
        try {
            $reflection = new ReflectionClass($class);
            echo "  - Constructor available: " . ($reflection->getConstructor() ? "Yes" : "No") . "\n";
            echo "  - File: " . $reflection->getFileName() . "\n";
        } catch (Exception $e) {
            echo "  - Error reflecting class: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ $class - Class not found\n";
    }
    echo "\n";
}

echo "Class loading test complete!\n";
