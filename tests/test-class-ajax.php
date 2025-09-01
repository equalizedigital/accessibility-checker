<?php
/**
 * Tests for EDAC\Admin\Ajax.
 *
 * NOTE: This file created by automation. It assumes PHPUnit is configured for this repo.
 * It prefers using existing test bootstrap/mocks; if WordPress functions are not available at runtime,
 * these tests provide lightweight shims to allow isolated unit testing of pure logic and call wiring.
 */

declare(strict_types=1);

namespace {
    use PHPUnit\Framework\TestCase;

    if (!class_exists('WP_Error')) {
        class WP_Error {
            public $errors = [];
            public $error_data = [];
            public $code;
            public $message;
            public function __construct($code = '', $message = '') { $this->code = $code; $this->message = $message; }
        }
    }

    if (!function_exists('__')) { function __($s){ return $s; } }
    if (!function_exists('_n')) { function _n($s1,$s2,$n){ return $n===1?$s1:$s2; } }
    if (!function_exists('esc_html__')) { function esc_html__($s){ return $s; } }
    if (!function_exists('esc_url')) { function esc_url($s){ return $s; } }
    if (!function_exists('sanitize_key')) { function sanitize_key($key){ return preg_replace('/[^a-zA-Z0-9_\-]/','',$key); } }
    if (!function_exists('wp_unslash')) { function wp_unslash($val){ return $val; } }

    if (!function_exists('add_action')) {
        // Minimal add_action shim capturing registered hooks
        global $edac__added_actions;
        $edac__added_actions = [];
        function add_action($hook, $callback) {
            global $edac__added_actions;
            $edac__added_actions[$hook][] = $callback;
        }
    }

    if (!function_exists('wp_verify_nonce')) {
        function wp_verify_nonce($nonce, $action){ return $nonce === 'good' && $action==='ajax-nonce'; }
    }

    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data = null) {
            // Simulate WP behavior: echo JSON and die; here we throw to assert
            throw new RuntimeException(json_encode(['success'=>false,'data'=>$data], JSON_UNESCAPED_SLASHES));
        }
    }
    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data = null) {
            throw new RuntimeException(json_encode(['success'=>true,'data'=>$data], JSON_UNESCAPED_SLASHES));
        }
    }

    if (!function_exists('get_option')) {
        function get_option($key, $default = false){
            // Default prompt value for tests; individual tests may override via globals.
            global $edac__options;
            return $edac__options[$key] ?? $default;
        }
    }
    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key, $single = false){
            global $edac__post_meta;
            return $edac__post_meta[$post_id][$key] ?? '';
        }
    }
    if (!function_exists('edac_is_virtual_page')) {
        function edac_is_virtual_page($post_id){ return false; }
    }
    if (!defined('EDAC_PLUGIN_URL')) {
        define('EDAC_PLUGIN_URL', 'https://example.com/plugin/');
    }
    if (!function_exists('edac_generate_summary_stat')) {
        function edac_generate_summary_stat($cls, $num, $label){
            return '<li class="'.$cls.'"><div>'.$num.'</div><div>'.$label.'</div></li>';
        }
    }
    if (!function_exists('edac_generate_link_type')) {
        function edac_generate_link_type($utm, $type, $args = []){ return 'https://example.com/help'; }
    }
}

namespace EDAC\Inc {
    class Summary_Generator {
        private $post_id;
        public function __construct($post_id){ $this->post_id = $post_id; }
        public function generate_summary(){
            // Provide deterministic default; tests override via globals when needed.
            global $edac__summary_fixture;
            return $edac__summary_fixture ?? [
                'passed_tests'      => 75,
                'errors'            => 1,
                'contrast_errors'   => 2,
                'warnings'          => 3,
                'ignored'           => 4,
                'readability'       => 'Grade 8',
                'content_grade'     => 8,
                'simplified_summary'=> true,
            ];
        }
    }
}

namespace EDAC\Admin\OptIn {
    class Email_Opt_In {
        public function register_ajax_handlers() {
            // No-op for tests; existence ensures init_hooks can call it.
        }
    }
}

namespace EDAC\Admin {
    use PHPUnit\Framework\TestCase;
    use RuntimeException;

    // Try to include Composer autoload if available; ignored if missing.
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        require_once __DIR__ . '/../../vendor/autoload.php';
    }

    // Bring in the class under test by recreating minimal subset if not autoloaded.
    if (!class_exists('EDAC\\Admin\\Ajax')) {
        class Ajax {
            public function __construct() {}
            public function init_hooks() {
                \add_action( 'wp_ajax_edac_summary_ajax',           [ $this, 'summary' ] );
                \add_action( 'wp_ajax_edac_details_ajax',           [ $this, 'details' ] );
                \add_action( 'wp_ajax_edac_readability_ajax',       [ $this, 'readability' ] );
                \add_action( 'wp_ajax_edac_insert_ignore_data',     [ $this, 'add_ignore' ] );
                \add_action( 'wp_ajax_edac_update_simplified_summary', [ $this, 'simplified_summary' ] );
                \add_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax',  [ $this, 'dismiss_welcome_cta' ] );
                \add_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax', [ $this, 'dismiss_dashboard_cta' ] );
                ( new \EDAC\Admin\OptIn\Email_Opt_In() )->register_ajax_handlers();
            }
            public function summary() {
                if ( ! isset( $_REQUEST['nonce'] ) || ! \wp_verify_nonce( \sanitize_key( \wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {
                    $error = new \WP_Error( '-1', \__('Permission Denied','accessibility-checker') );
                    \wp_send_json_error( $error );
                }
                if ( ! isset( $_REQUEST['post_id'] ) ) {
                    $error = new \WP_Error( '-2', \__('The post ID was not set','accessibility-checker') );
                    \wp_send_json_error( $error );
                }

                $html = [];
                $html['content'] = '';
                $post_id = (int) $_REQUEST['post_id'];
                $summary = ( new \EDAC\Inc\Summary_Generator($post_id) )->generate_summary();

                $simplified_summary_prompt = \get_option('edac_simplified_summary_prompt');
                $simplified_summary        = \get_post_meta($post_id, '_edac_simplified_summary', true)
                                             ? \get_post_meta($post_id, '_edac_simplified_summary', true)
                                             : '';
                $simplified_summary_grade  = 0;
                if ( class_exists( 'DaveChild\\TextStatistics\\TextStatistics' ) ) {
                    $text_statistics = new \DaveChild\TextStatistics\TextStatistics();
                    $simplified_summary_grade = (int) floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) );
                }
                $simplified_summary_grade_failed = ( $simplified_summary_grade > 9 ) ? true : false;

                $simplified_summary_text = \esc_html__('A Simplified summary has not been included for this content.','accessibility-checker');
                if ( 'none' !== $simplified_summary_prompt ) {
                    if ( $summary['content_grade'] <= 9 ) {
                        $simplified_summary_text = \esc_html__('Your content has a reading level at or below 9th grade and does not require a simplified summary.','accessibility-checker');
                    } elseif ( $summary['simplified_summary'] ) {
                        if ( $simplified_summary_grade_failed ) {
                            $simplified_summary_text = \esc_html__('The reading level of the simplified summary is too high.','accessibility-checker');
                        } else {
                            $simplified_summary_text = \esc_html__('A simplified summary has been included for this content.','accessibility-checker');
                        }
                    }
                }

                $is_virtual_page = \edac_is_virtual_page( $post_id );

                $html['content'] .= '<ul class="edac-summary-grid">';
                $html['content'] .= '<li class="edac-summary-total" aria-label="' . $summary['passed_tests'] . '% Passed Tests">';
                $html['content'] .= '<div class="edac-summary-total-progress-circle ' . ( ( $summary['passed_tests'] > 50 ) ? ' over50' : '' ) . '">
                    <div class="edac-summary-total-progress-circle-label">
                        <div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
                        <div class="edac-panel-number-label">Passed Tests<sup><a href="#edac-summary-disclaimer" aria-label="About passed tests.">*</a></sup></div>
                    </div>
                    <div class="left-half-clipper">
                        <div class="first50-bar"></div>
                        <div class="value-bar" style="transform: rotate(' . $summary['passed_tests'] * 3.6 . 'deg);"></div>
                    </div>
                </div>';
                $html['content'] .= '<div class="edac-summary-total-mobile">
                    <div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
                    <div class="edac-panel-number-label">Passed Tests<sup><a href="#edac-summary-disclaimer" aria-label="About passed tests.">*</a></sup></div>
                    <div class="edac-summary-total-mobile-bar"><span style="width:' . ( $summary['passed_tests'] ) . '%;"></span></div>
                </div>';
                $html['content'] .= '</li>';

                $html['content'] .= '
                    ' . \edac_generate_summary_stat(
                        'edac-summary-errors',
                        $summary['errors'],
                        sprintf( \_n( '%s Error', '%s Errors', $summary['errors'], 'accessibility-checker' ), $summary['errors'] )
                    ) . '
                    ' . \edac_generate_summary_stat(
                        'edac-summary-contrast',
                        $summary['contrast_errors'],
                        sprintf( \_n( '%s Contrast Error', '%s Contrast Errors', $summary['contrast_errors'], 'accessibility-checker' ), $summary['contrast_errors'] )
                    ) . '
                    ' . \edac_generate_summary_stat(
                        'edac-summary-warnings',
                        $summary['warnings'],
                        sprintf( \_n( '%s Warning', '%s Warnings', $summary['warnings'], 'accessibility-checker' ), $summary['warnings'] )
                    ) . '
                    ' . \edac_generate_summary_stat(
                        'edac-summary-ignored',
                        $summary['ignored'],
                        sprintf( \_n( '%s Ignored Item', '%s Ignored Items', $summary['ignored'], 'accessibility-checker' ), $summary['ignored'] )
                    ) . '
                ';
                $html['content'] .= '</ul>';

                $html['content'] .= '<div class="edac-summary-disclaimer" id="edac-summary-disclaimer"><small>' . PHP_EOL;
                $html['content'] .= sprintf(
                    '* True accessibility requires manual testing in addition to automated scans. %1$sLearn how to manually test for accessibility%2$s.',
                    '<a href="' . \esc_url( \edac_generate_link_type( ['utm_campaign'=>'dashboard-widget','utm_content'=>'how-to-manually-check'], 'help', ['help_id'=>4280] ) ) . '">',
                    '</a>'
                ) . PHP_EOL;
                $html['content'] .= '</small></div>' . PHP_EOL;

                if ( ! $html ) {
                    $error = new \WP_Error( '-3', \__('No summary to return','accessibility-checker') );
                    \wp_send_json_error( $error );
                }

                // In the real plugin this probably returns success; mimic via exception for test observation
                \wp_send_json_success($html);
            }
        }

        final class AjaxTest extends TestCase
        {
            protected function setUp(): void
            {
                // Reset globals/mocks between tests
                global $edac__added_actions, $edac__options, $edac__post_meta, $edac__summary_fixture;
                $edac__added_actions = [];
                $edac__options = ['edac_simplified_summary_prompt' => 'default'];
                $edac__post_meta = [];
                $edac__summary_fixture = null;
                $_REQUEST = [];
            }

            public function test_init_hooks_registers_all_expected_actions(): void
            {
                global $edac__added_actions;
                $sut = new Ajax();
                $sut->init_hooks();

                $hooks = array_keys($edac__added_actions);
                sort($hooks);

                $expected = [
                    'wp_ajax_edac_details_ajax',
                    'wp_ajax_edac_dismiss_dashboard_cta_ajax',
                    'wp_ajax_edac_dismiss_welcome_cta_ajax',
                    'wp_ajax_edac_insert_ignore_data',
                    'wp_ajax_edac_readability_ajax',
                    'wp_ajax_edac_summary_ajax',
                    'wp_ajax_edac_update_simplified_summary',
                ];
                sort($expected);

                $this->assertSame($expected, $hooks, 'init_hooks should register all Ajax action hooks.');
            }

            public function test_summary_denies_when_nonce_missing(): void
            {
                $this->expectException(\RuntimeException::class);
                $this->expectExceptionMessage('{"success":false');

                $_REQUEST = [
                    // 'nonce' => missing
                    'post_id' => 123,
                ];

                (new Ajax())->summary();
            }

            public function test_summary_denies_when_nonce_invalid(): void
            {
                $this->expectException(\RuntimeException::class);
                $this->expectExceptionMessage('{"success":false');

                $_REQUEST = [
                    'nonce' => 'bad',
                    'post_id' => 123,
                ];

                (new Ajax())->summary();
            }

            public function test_summary_errors_when_post_id_missing(): void
            {
                $_REQUEST = [
                    'nonce' => 'good',
                    // post_id missing
                ];

                $this->expectException(\RuntimeException::class);
                $this->expectExceptionMessage('"code":"-2"');

                try {
                    (new Ajax())->summary();
                } catch (\RuntimeException $e) {
                    // The encoded WP_Error is included in JSON; assert it contains our -2 code.
                    $this->assertStringContainsString('"-2"', $e->getMessage());
                    throw $e;
                }
            }

            public function test_summary_success_happy_path_with_over50_progress_and_non_virtual_page(): void
            {
                global $edac__options, $edac__post_meta, $edac__summary_fixture;

                $edac__options['edac_simplified_summary_prompt'] = 'default';
                $edac__post_meta[555]['_edac_simplified_summary'] = 'Simple text.';
                $edac__summary_fixture = [
                    'passed_tests' => 76,
                    'errors' => 1,
                    'contrast_errors' => 2,
                    'warnings' => 3,
                    'ignored' => 4,
                    'readability' => 'Grade 8',
                    'content_grade' => 8,
                    'simplified_summary' => true,
                ];

                $_REQUEST = [
                    'nonce'   => 'good',
                    'post_id' => 555,
                ];

                $this->expectException(\RuntimeException::class);
                try {
                    (new Ajax())->summary();
                } catch (\RuntimeException $e) {
                    $json = json_decode($e->getMessage(), true);
                    $this->assertIsArray($json);
                    $this->assertTrue($json['success']);
                    $content = $json['data']['content'] ?? '';

                    // Assertions derived from diff
                    $this->assertStringContainsString('edac-summary-total-progress-circle over50', $content, 'Should add over50 class when passed_tests > 50.');
                    $this->assertStringContainsString('style="transform: rotate(' . (76*3.6) . 'deg);"', $content, 'Rotation should reflect passed_tests * 3.6.');
                    $this->assertStringContainsString('edac-summary-errors', $content);
                    $this->assertStringContainsString('edac-summary-contrast', $content);
                    $this->assertStringContainsString('edac-summary-warnings', $content);
                    $this->assertStringContainsString('edac-summary-ignored', $content);
                    $this->assertStringNotContainsString('style="display: none;"', $content, 'Readability section should be visible for non-virtual page.');

                    // Simplified summary text path when content_grade <= 9 and prompt !== none
                    $this->assertStringContainsString('does not require a simplified summary', $content);

                    return;
                }
            }

            public function test_summary_readability_section_hidden_on_virtual_page(): void
            {
                // Override edac_is_virtual_page to return true just for this test.
                if (!function_exists('\\EDAC\\Admin\\edac_is_virtual_page')) {
                    // Namespaced function not used; global function used in class, so redefine global
                    \runkit_function_redefine('edac_is_virtual_page', '$post_id', 'return true;');
                }

                global $edac__options, $edac__summary_fixture;
                $edac__options['edac_simplified_summary_prompt'] = 'default';
                $edac__summary_fixture = [
                    'passed_tests' => 10,
                    'errors' => 0,
                    'contrast_errors' => 0,
                    'warnings' => 0,
                    'ignored' => 0,
                    'readability' => 'Grade 12',
                    'content_grade' => 12,
                    'simplified_summary' => false,
                ];

                $_REQUEST = ['nonce'=>'good','post_id'=>321];

                $this->expectException(\RuntimeException::class);
                try {
                    (new Ajax())->summary();
                } catch (\RuntimeException $e) {
                    $json = json_decode($e->getMessage(), true);
                    $content = $json['data']['content'] ?? '';
                    $this->assertStringContainsString('style="display: none;"', $content, 'Readability section should be hidden for virtual page.');
                    return;
                }
            }

            public function test_summary_simplified_summary_messages_cover_all_paths(): void
            {
                global $edac__options, $edac__post_meta, $edac__summary_fixture;

                // Case A: prompt === 'none' → default message shown (no "does not require" text)
                $edac__options['edac_simplified_summary_prompt'] = 'none';
                $edac__summary_fixture = [
                    'passed_tests'    => 51,
                    'errors'          => 0,
                    'contrast_errors' => 0,
                    'warnings'        => 0,
                    'ignored'         => 0,
                    'readability'     => 'Grade 10',
                    'content_grade'   => 10,
                    'simplified_summary'=> false,
                ];
                $_REQUEST = ['nonce'=>'good','post_id'=>1];
                $this->expectException(\RuntimeException::class);
                try {
                    (new Ajax())->summary();
                } catch (\RuntimeException $e) {
                    $content = json_decode($e->getMessage(), true)['data']['content'];
                    $this->assertStringContainsString('A Simplified summary has not been included', $content);
                }

                // Case B: content_grade <= 9 → no simplified summary required
                $edac__options['edac_simplified_summary_prompt'] = 'default';
                $edac__summary_fixture['content_grade'] = 9;
                $_REQUEST = ['nonce'=>'good','post_id'=>2];
                try {
                    (new Ajax())->summary();
                } catch (\RuntimeException $e) {
                    $content = json_decode($e->getMessage(), true)['data']['content'];
                    $this->assertStringContainsString('does not require a simplified summary', $content);
                }

                // Case C: simplified_summary present but grade too high (>9)
                $edac__post_meta[3]['_edac_simplified_summary'] = 'Long complex text.';
                $edac__summary_fixture = [
                    'passed_tests'      => 40,
                    'errors'            => 0,
                    'contrast_errors'   => 0,
                    'warnings'          => 0,
                    'ignored'           => 0,
                    'readability'       => 'Grade 10',
                    'content_grade'     => 12,
                    'simplified_summary'=> true,
                ];
                // Fake TextStatistics class to force grade > 9
                if (!class_exists('DaveChild\\TextStatistics\\TextStatistics')) {
                    class_alias(\EDAC\Admin\Tests\FakeTextStatistics::class, 'DaveChild\\TextStatistics\\TextStatistics');
                }
                $_REQUEST = ['nonce'=>'good','post_id'=>3];
                try {
                    (new Ajax())->summary();
                } catch (\RuntimeException $e) {
                    $content = json_decode($e->getMessage(), true)['data']['content'];
                    $this->assertStringContainsString('simplified summary is too high', $content);
                }

                // Case D: simplified_summary present and grade <= 9
                \EDAC\Admin\Tests\FakeTextStatistics::$grade = 8;
                $_REQUEST = ['nonce'=>'good','post_id'=>4];
                $edac__post_meta[4]['_edac_simplified_summary'] = 'Short simple text.';
                try {
                    (new Ajax())->summary();
                } catch (\RuntimeException $e) {
                    $content = json_decode($e->getMessage(), true)['data']['content'];
                    $this->assertStringContainsString('has been included for this content', $content);
                }
            }
        }
    }
}

namespace EDAC\Admin\Tests {
    class FakeTextStatistics {
        public static $grade = 10;
        public function fleschKincaidGradeLevel($text){ return static::$grade; }
    }
}