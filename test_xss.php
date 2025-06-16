<?php

// --- WordPress Mocks ---
if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); // Simple mock
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); // Simple mock
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'http://example.com' . $path;
    }
}

// --- Test Specific Mocks ---
$GLOBALS['test_xss_payload'] = '';
if (!function_exists('get_search_query')) {
    function get_search_query($escaped = true) {
        global $test_xss_payload;
        return $test_xss_payload;
    }
}

// Include the interface and class files
require_once 'includes/classes/Fixes/FixInterface.php';
require_once 'includes/classes/Fixes/Fix/CommentSearchLabelFix.php';

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\CommentSearchLabelFix;

// --- Test Execution ---

$fixer = new CommentSearchLabelFix();
global $test_xss_payload;

// Test Case 1: Force new input field generation with XSS payload
echo "Test Case 1: New input field generation with XSS payload\n";
$raw_payload1 = '"><script>alert("XSS1")</script>';
$escaped_payload1 = esc_attr($raw_payload1);
$test_xss_payload = $raw_payload1;

$malformed_form_html = '';
$output_html1 = $fixer->fix_search_form_label($malformed_form_html);

echo "Output HTML (Test Case 1):\n";
echo $output_html1 . "\n\n";

if (strpos($output_html1, "value=\"{$escaped_payload1}\"") !== false) {
    echo "SUCCESS (Test Case 1): XSS1 Payload is correctly escaped.\n";
} else if (strpos($output_html1, $raw_payload1) !== false) {
    echo "FAILURE (Test Case 1): XSS1 Payload was found unescaped!\n";
    echo "Expected to find: value=\"{$escaped_payload1}\"\n";
} else {
    echo "FAILURE (Test Case 1): Escaped XSS1 payload not found. Output might be unexpected.\n";
}

// Test Case 2: Form missing a label, triggering input field regeneration (because input field also needs id for label).
// If input field exists but has no ID, fixer will create a new input field.
echo "\nTest Case 2: Form missing label AND input missing ID (triggers new input field generation)\n";
$raw_payload2 = 'another"><script>alert("XSS2")</script>';
$escaped_payload2 = esc_attr($raw_payload2);
$test_xss_payload = $raw_payload2;

// This form has an input field, but it's missing an ID.
// The fixer's preg_match for the input field `(<input[^>]*id=["\']([^"\']*)["\']...)` will fail to capture an ID.
// This means $input_matches[1] will not be set, leading to the `else` block where new input is generated.
$form_input_missing_id_html = '<form role="search" method="get" class="search-form" action="http://example.com/">
    <input type="search" class="search-field" placeholder="Search …" value="original_value" name="s" />
    <button type="submit" class="search-submit">Search</button>
</form>';

$output_html2 = $fixer->fix_search_form_label($form_input_missing_id_html);

echo "Output HTML (Test Case 2):\n";
echo $output_html2 . "\n\n";

if (strpos($output_html2, "value=\"{$escaped_payload2}\"") !== false) {
    echo "SUCCESS (Test Case 2): XSS2 Payload from get_search_query is correctly escaped when new input field is generated.\n";
} else if (strpos($output_html2, $raw_payload2) !== false) {
    echo "FAILURE (Test Case 2): XSS2 Payload (from get_search_query) was found unescaped during new input field generation!\n";
    echo "Expected to find value attribute containing: {$escaped_payload2}\n";
} else {
    echo "FAILURE (Test Case 2): Escaped XSS2 payload not found in new input field. Output might be unexpected.\n";
}


// Test Case 3: Form has input (with ID) and label, but 'for' and 'id' mismatch.
// Should PRESERVE existing input, fix label's 'for'. get_search_query() should NOT be called by fixer.
echo "\nTest Case 3: Form with mismatched label/input IDs (preserves input, fixes label)\n";
$raw_payload3_should_not_be_used = 'mismatch"><script>alert("XSS3_PAYLOAD_SHOULD_NOT_BE_USED")</script>';
$test_xss_payload = $raw_payload3_should_not_be_used; // This should not appear in the output

$original_value_for_test3 = "original_value_for_test3";
$form_mismatched_ids_html = '<form role="search" method="get" class="search-form" action="http://example.com/">
    <label for="label_id_test3" class="edac-generated-label">Search for:</label>
    <input type="search" id="input_id_test3" class="search-field" placeholder="Search …" value="' . $original_value_for_test3 . '" name="s" />
    <button type="submit" class="search-submit">Search</button>
</form>';

$output_html3 = $fixer->fix_search_form_label($form_mismatched_ids_html);

echo "Output HTML (Test Case 3):\n";
echo $output_html3 . "\n\n";

// Expected: Label 'for' attribute is corrected to 'input_id_test3'. Input field is preserved.
$expected_label_html3 = '<label for="input_id_test3"';
$expected_input_value_html3 = "value=\"{$original_value_for_test3}\""; // Original value preserved

if (strpos($output_html3, $raw_payload3_should_not_be_used) !== false) {
    echo "FAILURE (Test Case 3): XSS3 Payload was incorrectly injected! Fixer's get_search_query was called.\n";
} else if (strpos($output_html3, $expected_label_html3) !== false && strpos($output_html3, $expected_input_value_html3) !== false) {
    echo "SUCCESS (Test Case 3): Input field preserved with original value. Label 'for' attribute corrected. Fixer's get_search_query not called.\n";
} else {
    echo "FAILURE (Test Case 3): Output not as expected. Label or input value incorrect.\n";
    echo "Expected label contains: {$expected_label_html3}\n";
    echo "Expected input value: {$expected_input_value_html3}\n";
}


// Test Case 4: A valid form that should NOT trigger regeneration.
// The get_search_query() inside the fixer should NOT be called.
echo "\nTest Case 4: Valid form, should not trigger regeneration or call fixer's get_search_query()\n";
$original_value_in_valid_form_xss_attempt = 'original_"><script>alert("XSS_ORIGINAL")</script>';
$escaped_original_value_xss_attempt = esc_attr($original_value_in_valid_form_xss_attempt);

$test_xss_payload = 'payload_for_fixer_get_search_query_SHOULD_NOT_BE_USED_CASE4';

$valid_form_html = '<form role="search" method="get" class="search-form" action="http://example.com/">
    <label for="search_id_valid" class="edac-generated-label">Search for:</label>
    <input type="search" id="search_id_valid" class="search-field" placeholder="Search …" value="' . $escaped_original_value_xss_attempt . '" name="s" />
    <button type="submit" class="search-submit">Search</button>
</form>';

$output_html4 = $fixer->fix_search_form_label($valid_form_html);

echo "Output HTML (Test Case 4):\n";
echo $output_html4 . "\n\n";

if (strpos($output_html4, $test_xss_payload) !== false) {
    echo "FAILURE (Test Case 4): Payload for fixer's get_search_query was incorrectly used!\n";
} else if (strpos($output_html4, "value=\"{$escaped_original_value_xss_attempt}\"") !== false && $output_html4 === $valid_form_html) {
    // Check if the output is identical to input, as no change should happen.
    echo "SUCCESS (Test Case 4): Valid form preserved, original (escaped) value is intact. Fixer's get_search_query not called. Form unchanged.\n";
} else {
    echo "FAILURE (Test Case 4): Valid form output is unexpected or was modified.\n";
    echo "Expected value attribute: value=\"{$escaped_original_value_xss_attempt}\"\n";
     if ($output_html4 !== $valid_form_html) echo "Form HTML was altered!\n";
}

?>
