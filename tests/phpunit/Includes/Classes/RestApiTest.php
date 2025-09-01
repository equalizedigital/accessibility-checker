<?php
/**
 * Tests for EDAC\Inc\REST_Api.
 *
 * Framework: PHPUnit (uses WP_UnitTestCase when WordPress test suite is available).
 */

use PHPUnit\Framework\TestCase;

if (class_exists('\WP_UnitTestCase')) {
    abstract class EDAC_Base_TestCase extends \WP_UnitTestCase {}
} else {
    abstract class EDAC_Base_TestCase extends TestCase {}
}

require_once __DIR__ . '/../../../test-helpers/RestApiTestHelpers.php';

final class RestApiTest extends EDAC_Base_TestCase {

    protected function setUp(): void {
        parent::setUp();
        \EDAC\Inc\maybe_load_wp_functions_stubs();
    }

    public function test_filter_js_validation_html_appends_selector_for_empty_paragraph(): void {
        $api = new \EDAC\Inc\REST_Api();
        $html = '<p></p>';
        $violation = [
            'selector' => ['.entry-content p:nth-of-type(3)'],
        ];
        $result = $api->filter_js_validation_html($html, 'empty_paragraph_tag', $violation);
        $this->assertStringContainsString('// {{ .entry-content p:nth-of-type(3) }}', $result);
    }

    public function test_filter_js_validation_html_no_selector_does_not_append(): void {
        $api = new \EDAC\Inc\REST_Api();
        $html = '<p></p>';
        $violation = [
            'selector' => [''],
        ];
        $result = $api->filter_js_validation_html($html, 'empty_paragraph_tag', $violation);
        $this->assertSame('<p></p>', $result);
    }

    public function test_filter_js_validation_html_trims_html_for_html_has_lang(): void {
        $api = new \EDAC\Inc\REST_Api();
        $html = '<!doctype html><html lang="en"><head><title>Title</title></head><body><div>Lots of content</div></body></html>';
        $result = $api->filter_js_validation_html($html, 'html-has-lang', ['selector' => []]);
        $this->assertSame('<html lang="en">...</html>', $result);
    }

    public function test_filter_js_validation_html_trims_html_for_document_title(): void {
        $api = new \EDAC\Inc\REST_Api();
        $html = '<!doctype html><html><head><title>Title</title></head><body><h1>H</h1></body></html>';
        $result = $api->filter_js_validation_html($html, 'document-title', ['selector' => []]);
        $this->assertSame('<html>...</html>', $result);
    }

    public function test_filter_js_validation_html_does_not_change_other_rules(): void {
        $api = new \EDAC\Inc\REST_Api();
        $html = '<div><span>ok</span></div>';
        $result = $api->filter_js_validation_html($html, 'some-other-rule', ['selector' => []]);
        $this->assertSame($html, $result);
    }

    public function test_clear_issues_for_post_missing_id_returns_400(): void {
        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest([]);
        $response = $api->clear_issues_for_post($request);
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('The ID is required to be passed.', $response->get_data()['message']);
    }

    public function test_clear_issues_for_post_invalid_post_returns_400(): void {
        \EDAC\TestHelpers\Stubs::$get_post = function ($id) { return null; };
        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest(['id' => 123]);
        $response = $api->clear_issues_for_post($request);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('The post is not valid.', $response->get_data()['message']);
    }

    public function test_clear_issues_for_post_unscannable_type_returns_400(): void {
        \EDAC\TestHelpers\Stubs::$get_post = function ($id) { return (object)['ID' => $id]; };
        \EDAC\TestHelpers\Stubs::$get_post_type = function ($post) { return 'product'; };
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post', 'page']; };
        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest(['id' => 99]);
        $response = $api->clear_issues_for_post($request);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('The post type is not set to be scanned.', $response->get_data()['message']);
    }

    public function test_clear_issues_for_post_flush_deletes_and_returns_success(): void {
        \EDAC\TestHelpers\Stubs::$get_post = function ($id) { return (object)['ID' => $id]; };
        \EDAC\TestHelpers\Stubs::$get_post_type = function ($post) { return 'post'; };
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post', 'page']; };

        \EDAC\TestHelpers\Recorder::reset();
        \EDAC\TestHelpers\Stubs::$purge_post_delete = function ($id) {
            \EDAC\TestHelpers\Recorder::record('purge_delete', $id);
        };

        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest(['id' => 7], ['flush' => true]);
        $response = $api->clear_issues_for_post($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['flushed']);
        $this->assertSame(7, $data['id']);
        $this->assertSame([['purge_delete', 7]], \EDAC\TestHelpers\Recorder::$events);
    }

    public function test_clear_issues_for_post_skips_post_exists_check_when_flag_set(): void {
        // No get_post called if skip flag is provided.
        \EDAC\TestHelpers\Recorder::reset();
        \EDAC\TestHelpers\Stubs::$get_post = function ($id) {
            \EDAC\TestHelpers\Recorder::record('get_post_called');
            return null;
        };
        \EDAC\TestHelpers\Stubs::$get_post_type = function ($post) { return 'post'; };
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post']; };

        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest(['id' => 8], ['skip_post_exists_check' => true]);
        $response = $api->clear_issues_for_post($request);

        $this->assertSame(200, $response->get_status());
        $this->assertEmpty(\EDAC\TestHelpers\Recorder::$events, 'get_post should not have been called');
    }

    public function test_set_post_scan_results_missing_violations_returns_400(): void {
        \EDAC\TestHelpers\Stubs::$get_post = function ($id) { return (object)['ID' => $id]; };
        \EDAC\TestHelpers\Stubs::$get_post_type = function ($post) { return 'post'; };
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post', 'page']; };

        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest(['id' => 101]); // no violations
        $response = $api->set_post_scan_results($request);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('A required parameter is missing.', $response->get_data()['message']);
    }

    public function test_set_post_scan_results_invalid_post_returns_400(): void {
        \EDAC\TestHelpers\Stubs::$get_post = function ($id) { return null; };
        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest(['id' => 202, 'violations' => []]);
        $response = $api->set_post_scan_results($request);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('The post is not valid.', $response->get_data()['message']);
    }

    public function test_set_post_scan_results_unscannable_type_returns_400(): void {
        \EDAC\TestHelpers\Stubs::$get_post = function ($id) { return (object)['ID' => $id, 'post_type' => 'product']; };
        \EDAC\TestHelpers\Stubs::$get_post_type = function ($post) { return 'product'; };
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post', 'page']; };

        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeRestRequest(['id' => 303, 'violations' => []]);
        $response = $api->set_post_scan_results($request);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('The post type is not set to be scanned.', $response->get_data()['message']);
    }

    public function test_set_post_scan_results_inserts_records_maps_combined_rules_and_saves_meta(): void {
        // Arrange stubs for WP functions and settings
        \EDAC\TestHelpers\Recorder::reset();

        \EDAC\TestHelpers\Stubs::$get_post = function ($id) { return (object)['ID' => $id, 'post_type' => 'post']; };
        \EDAC\TestHelpers\Stubs::$get_post_type = function ($post) { return 'post'; };
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post', 'page']; };
        \EDAC\TestHelpers\Stubs::$update_post_meta = function ($post_id, $key, $value) {
            \EDAC\TestHelpers\Recorder::record('update_post_meta', $post_id, $key, $value);
            return true;
        };
        \EDAC\TestHelpers\Stubs::$edac_remove_corrected_posts = function ($post_id, $type, $pre, $ctx) {
            \EDAC\TestHelpers\Recorder::record('remove_corrected', $post_id, $type, $pre, $ctx);
        };
        \EDAC\TestHelpers\Stubs::$do_action = function ($hook, ...$args) {
            \EDAC\TestHelpers\Recorder::record('do_action', $hook, $args);
        };
        \EDAC\TestHelpers\Stubs::$edac_register_rules = function () {
            // Two rules: one direct, one combined mapping "sub-rule" -> "combined-rule"
            return [
                ['slug' => 'img-alt', 'ruleset' => 'js', 'rule_type' => 'error'],
                ['slug' => 'combined-heading', 'ruleset' => 'js', 'rule_type' => 'warning', 'combines' => ['h1-missing']],
            ];
        };

        // Stub Insert_Rule_Data::insert and Summary_Generator::generate_summary
        \EDAC\TestHelpers\Stubs::$insert_rule_data = function ($post, $rule_id, $impact, $html, $landmark, $landmark_selector, $selectors) {
            \EDAC\TestHelpers\Recorder::record('insert', $post->ID, $rule_id, $impact, $html, $landmark, $landmark_selector, $selectors);
        };
        \EDAC\TestHelpers\Stubs::$summary_generate = function ($post_id) {
            \EDAC\TestHelpers\Recorder::record('summary_generate', $post_id);
        };

        $api = new \EDAC\Inc\REST_Api();

        $violations = [
            [
                'ruleId' => 'img-alt',
                'html' => '<img src="x" />',
                'impact' => 'warning',
                'selector' => ['img[src="x"]'],
                'ancestry' => ['.entry-content'],
                'xpath' => ['/html/body/img[1]'],
            ],
            [
                'ruleId' => 'h1-missing', // maps to combined-heading
                'html' => '<html><head></head><body></body></html>',
                'impact' => 'minor',
                'selector' => [],
            ],
        ];

        $request = new \EDAC\TestHelpers\FakeRestRequest(
            ['id' => 404, 'violations' => $violations],
            ['densityMetrics' => ['elementCount' => 10, 'contentLength' => 100]]
        );

        $response = $api->set_post_scan_results($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame(404, $data['id']);
        $this->assertArrayHasKey('timestamp', $data);

        // Assertions on sequence of key events
        $events = \EDAC\TestHelpers\Recorder::$events;

        // Ensure before/after hooks were dispatched and remove_corrected called twice with pre=1 and pre=2
        $this->assertTrue($this->arrayHasEvent($events, 'do_action', 'edac_before_validate'));
        $this->assertTrue($this->arrayHasEvent($events, 'do_action', 'edac_after_validate'));
        $this->assertTrue($this->arrayHasEvent($events, 'remove_corrected', 404, 'post', 1, 'js'));
        $this->assertTrue($this->arrayHasEvent($events, 'remove_corrected', 404, 'post', 2, 'js'));

        // Two inserts: 'img-alt' uses rule_type 'error' (overrides JS impact), and 'h1-missing' maps to 'combined-heading'
        $this->assertTrue($this->arrayHasEvent($events, 'insert', 404, 'img-alt', 'error'));
        $this->assertTrue($this->arrayHasEvent($events, 'insert', 404, 'combined-heading', 'warning'));

        // Density metrics saved and last-checked timestamp saved
        $this->assertTrue($this->arrayHasEvent($events, 'update_post_meta', 404, '_edac_density_data'));
        $this->assertTrue($this->arrayHasEvent($events, 'update_post_meta', 404, '_edac_post_checked_js'));

        // Summary generated
        $this->assertTrue($this->arrayHasEvent($events, 'summary_generate', 404));
    }

    public function test_get_scans_stats_success(): void {
        \EDAC\TestHelpers\Recorder::reset();
        \EDAC\TestHelpers\Stubs::$scans_stats_summary = function ($ttl) { return ['total' => 3, 'errors' => 1, 'warnings' => 2]; };

        $api = new \EDAC\Inc\REST_Api();
        $response = $api->get_scans_stats();
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame(['total' => 3, 'errors' => 1, 'warnings' => 2], $data['stats']);
    }

    public function test_clear_cached_scans_stats_success(): void {
        \EDAC\TestHelpers\Recorder::reset();
        \EDAC\TestHelpers\Stubs::$scans_stats_clear = function () {
            \EDAC\TestHelpers\Recorder::record('scans_clear_cache');
        };
        $api = new \EDAC\Inc\REST_Api();
        $response = $api->clear_cached_scans_stats();
        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
        $this->assertTrue($this->arrayHasEvent(\EDAC\TestHelpers\Recorder::$events, 'scans_clear_cache'));
    }

    public function test_get_scans_stats_by_post_type_requires_slug(): void {
        $api = new \EDAC\Inc\REST_Api();
        $response = $api->get_scans_stats_by_post_type(new \EDAC\TestHelpers\FakeRestRequest([]));
        $this->assertSame(400, $response->get_status());
        $this->assertSame('A required parameter is missing.', $response->get_data()['message']);
    }

    public function test_get_scans_stats_by_post_type_unscannable_returns_400(): void {
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post']; };
        $api = new \EDAC\Inc\REST_Api();
        $response = $api->get_scans_stats_by_post_type(new \EDAC\TestHelpers\FakeRestRequest(['slug' => 'page']));
        $this->assertSame(400, $response->get_status());
        $this->assertSame('The post type is not set to be scanned.', $response->get_data()['message']);
    }

    public function test_get_scans_stats_by_post_type_success(): void {
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['page']; };
        \EDAC\TestHelpers\Stubs::$issues_summary_by_post_type = function ($t) { return ['type' => $t, 'errors' => 0, 'warnings' => 1]; };

        $api = new \EDAC\Inc\REST_Api();
        $response = $api->get_scans_stats_by_post_type(new \EDAC\TestHelpers\FakeRestRequest(['slug' => 'page']));
        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
        $this->assertSame(['type' => 'page', 'errors' => 0, 'warnings' => 1], $response->get_data()['stats']);
    }

    public function test_get_scans_stats_by_post_types_filters_and_aggregates(): void {
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post', 'book']; };
        \EDAC\TestHelpers\Stubs::$get_post_types = function ($args) {
            // Simulate WP get_post_types with 'public' => true
            return ['post' => 'post', 'page' => 'page', 'attachment' => 'attachment', 'book' => 'book'];
        };
        \EDAC\TestHelpers\Stubs::$issues_summary_by_post_type = function ($post_type) {
            return ['type' => $post_type, 'issues' => $post_type === 'book' ? 5 : 2];
        };

        $api = new \EDAC\Inc\REST_Api();
        $response = $api->get_scans_stats_by_post_types(new \EDAC\TestHelpers\FakeRestRequest([]));
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $stats = $data['stats'];

        // 'attachment' must be unset; post/page always present but only scannable types should have data
        $this->assertArrayHasKey('post', $stats);
        $this->assertArrayHasKey('page', $stats);
        $this->assertArrayHasKey('book', $stats);
        $this->assertFalse($stats['page']); // not in scannable list in this setup
        $this->assertIsArray($stats['post']);
        $this->assertIsArray($stats['book']);
    }

    public function test_get_site_summary_with_clear_cache_param(): void {
        \EDAC\TestHelpers\Recorder::reset();
        \EDAC\TestHelpers\Stubs::$scans_stats_summary_no_ttl = function () { return ['sitesummary' => true]; };
        \EDAC\TestHelpers\Stubs::$scans_stats_clear = function () {
            \EDAC\TestHelpers\Recorder::record('site_summary_clear_cache');
        };

        $api = new \EDAC\Inc\REST_Api();
        $request = new \EDAC\TestHelpers\FakeWpRestRequest(['clearCache' => true]);
        $response = $api->get_site_summary($request);
        $this->assertSame(200, $response->get_status());
        $this->assertTrue($this->arrayHasEvent(\EDAC\TestHelpers\Recorder::$events, 'site_summary_clear_cache'));
        $this->assertSame(['sitesummary' => true], $response->get_data()['stats']);
    }

    private function arrayHasEvent(array $events, string $name, ...$containsArgs): bool {
        foreach ($events as $e) {
            if ($e[0] !== $name) {
                continue;
            }
            if (empty($containsArgs)) {
                return true;
            }
            $hay = json_encode($e);
            $ok = true;
            foreach ($containsArgs as $needle) {
                if (false === strpos($hay, json_encode($needle, JSON_UNESCAPED_SLASHES))) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return true;
            }
        }
        return false;
    }
}

// --- Appended comprehensive tests (Framework: PHPUnit / WP_UnitTestCase if available) ---

if (!class_exists('RestApiTest_Appended')) {
final class RestApiTest_Appended extends (class_exists('\WP_UnitTestCase') ? \WP_UnitTestCase : \PHPUnit\Framework\TestCase) {

    protected function setUp(): void {
        if (method_exists(get_parent_class($this), 'setUp')) { parent::setUp(); }
        \EDAC\Inc\maybe_load_wp_functions_stubs();
        \EDAC\TestHelpers\Recorder::reset();
    }

    public function test_filter_html_preserves_unrelated_rules(): void {
        $api = new \EDAC\Inc\REST_Api();
        $original = '<section>content</section>';
        $this->assertSame($original, $api->filter_js_validation_html($original, 'aria-roles', ['selector' => []]));
    }

    public function test_clear_issues_skip_and_no_flush_returns_success_without_side_effects(): void {
        \EDAC\TestHelpers\Stubs::$get_post_type = function ($post) { return 'post'; };
        \EDAC\TestHelpers\Stubs::$get_scannable_post_types = function () { return ['post']; };
        $api = new \EDAC\Inc\REST_Api();
        $response = $api->clear_issues_for_post(new \EDAC\TestHelpers\FakeRestRequest(['id' => 55], ['skip_post_exists_check' => true]));
        $this->assertSame(200, $response->get_status());
        $this->assertFalse($response->get_data()['flushed']);
        $this->assertEmpty(\EDAC\TestHelpers\Recorder::$events);
    }
}}