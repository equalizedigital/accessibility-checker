<?php
echo "Debug test starting...\n";

require_once __DIR__ . '/vendor/autoload.php';
echo "Autoloader loaded\n";

// Add minimal WordPress mocks
function add_action() { return true; }
function register_block_type() { return true; }
function add_shortcode() { return true; }
function wp_register_script() { return true; }
function wp_register_style() { return true; }
function plugin_dir_url() { return 'test'; }
function plugin_dir_path() { return 'test'; }
function wp_localize_script() { return true; }
function wp_set_script_translations() { return true; }
function sanitize_text_field($s) { return $s; }
function esc_attr($s) { return $s; }
function esc_html($s) { return $s; }
function wp_kses_post($s) { return $s; }
function get_current_screen() { return (object)['id' => 'test']; }
function is_admin() { return false; }

echo "WordPress functions mocked\n";

echo "Attempting to create integrations class...\n";
$integrations = new EDAC\Inc\Simplified_Summary_Integrations();
echo "SUCCESS: Integrations class created!\n";

echo "Debug test completed.\n";
