<?php
declare(strict_types=1);

namespace Tests\PHPUnit\Admin;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use EDAC\Admin\Frontend_Highlight;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
use Mockery;
use PHPUnit\Framework\TestCase;

final class Frontend_HighlightTest extends TestCase
{
    protected function setUp(): void
    {
        Monkey\setUp();

        // Common WP function stubs to prevent accidental real calls
        Functions\when('esc_html')->returnArg();
        Functions\when('__')->alias(fn($t) => $t);
        Functions\when('wp_kses_post')->alias(fn($t) => $t);
        Functions\when('wp_json_encode')->alias('json_encode');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
    }

    public function test_init_hooks_registers_ajax_action_and_respects_visibility_filter(): void
    {
        $sut = new Frontend_Highlight();

        Functions\expect('add_action')
            ->with('wp_ajax_edac_frontend_highlight_ajax', [ $sut, 'ajax' ])
            ->once();

        Filters\expectApplied('edac_filter_frontend_highlighter_visibility')
            ->once()
            ->andReturn(false);

        Functions\expect('add_action')
            ->with('wp_ajax_nopriv_edac_frontend_highlight_ajax', [ $sut, 'ajax' ])
            ->never();

        $sut->init_hooks();

        Functions\expect('add_action')
            ->with('wp_ajax_edac_frontend_highlight_ajax', [ $sut, 'ajax' ])
            ->once();
        Filters\expectApplied('edac_filter_frontend_highlighter_visibility')
            ->once()
            ->andReturn(true);
        Functions\expect('add_action')
            ->with('wp_ajax_nopriv_edac_frontend_highlight_ajax', [ $sut, 'ajax' ])
            ->once();

        $sut->init_hooks();
    }

    public function test_get_issues_returns_null_when_no_results(): void
    {
        global $wpdb;
        $wpdb = new class {
            public $prefix = 'wp_';
            public function prepare($query, ...$args) { return vsprintf($query, $args); }
            public function get_results($_prepared, $_format) {
                (void) $_prepared;
                (void) $_format;
                return [];
            }
        };

        Functions\expect('get_current_blog_id')->once()->andReturn(1);

        $sut = new Frontend_Highlight();
        $this->assertNull($sut->get_issues(123));
    }

    public function test_get_issues_filters_active_rules_and_casts_types(): void
    {
        global $wpdb;
        $wpdb = new class {
            public $prefix = 'wp_';
            public function prepare($query, ...$args) { return vsprintf($query, $args); }
            public function get_results($_prepared, $_format) {
                (void) $_prepared;
                (void) $_format;
                return [
                    ['id' => 10, 'rule' => 'img-alt', 'ignre' => 0, 'object' => '&lt;img&gt;', 'ruletype' => 'error'],
                    ['id' => 11, 'rule' => 'link-name', 'ignre' => 1, 'object' => '&lt;a&gt;', 'ruletype' => 'warning'],
                ];
            }
        };

        Functions\expect('get_current_blog_id')->once()->andReturn(2);

        Functions\expect('\\EDAC\\Admin\\Helpers::filter_results_to_only_active_rules')
            ->with(Mockery::type('array'))
            ->once()
            ->andReturnUsing(fn(array $rows) => $rows);

        $sut = new Frontend_Highlight();
        $issues = $sut->get_issues('456');
        $this->assertIsArray($issues);
        $this->assertCount(2, $issues);
        $this->assertSame(10, $issues[0]['id']);
        $this->assertSame('img-alt', $issues[0]['rule']);
    }

    public function test_ajax_fails_when_nonce_invalid(): void
    {
        $sut = new Frontend_Highlight();

        Functions\expect('check_ajax_referer')
            ->with('frontend-highlighter', 'nonce', false)
            ->andReturn(false);
        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function($_err) {
            (void) $_err;
            throw new \RuntimeException('sent_error');
        });

        $this->expectException(\RuntimeException::class);
        $sut->ajax();
    }

    public function test_ajax_requires_post_id_and_valid_post(): void
    {
        $sut = new Frontend_Highlight();

        Functions\expect('check_ajax_referer')->andReturn(true);

        $_REQUEST = [];
        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function() { throw new \RuntimeException('missing_id'); });
        $this->expectException(\RuntimeException::class);
        try { $sut->ajax(); } catch (\RuntimeException $e) {}

        $_REQUEST = ['post_id' => '999'];
        Functions\expect('get_post')->once()->andReturn(null);
        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function() { throw new \RuntimeException('post_not_found'); });

        $this->expectException(\RuntimeException::class);
        $sut->ajax();
    }

    public function test_ajax_permission_checks_for_logged_in_and_logged_out(): void
    {
        $sut = new Frontend_Highlight();

        Functions\expect('check_ajax_referer')->andReturn(true);
        $_REQUEST = ['post_id' => 42];
        $post = (object)['ID' => 42];
        Functions\expect('get_post')->andReturn($post);

        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('read_post', 42)->andReturn(false);
        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function() { throw new \RuntimeException('denied1'); });

        try { $sut->ajax(); } catch (\RuntimeException $e) {}

        Functions\expect('check_ajax_referer')->andReturn(true);
        Functions\expect('get_post')->andReturn($post);
        Functions\expect('is_user_logged_in')->andReturn(false);
        Filters\expectApplied('edac_filter_frontend_highlighter_visibility')->andReturn(true);
        Functions\expect('is_post_publicly_viewable')->with($post)->andReturn(false);
        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function() { throw new \RuntimeException('denied2'); });

        try { $sut->ajax(); } catch (\RuntimeException $e) {}

        Functions\expect('check_ajax_referer')->andReturn(true);
        Functions\expect('get_post')->andReturn($post);
        Functions\expect('is_user_logged_in')->andReturn(false);
        Filters\expectApplied('edac_filter_frontend_highlighter_visibility')->andReturn(false);
        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function() { throw new \RuntimeException('denied3'); });

        $this->expectException(\RuntimeException::class);
        $sut->ajax();
    }

    public function test_ajax_returns_error_when_no_results(): void
    {
        $sut = Mockery::mock(Frontend_Highlight::class)->makePartial();
        $sut->shouldReceive('get_issues')->once()->with(77)->andReturn(null);

        Functions\expect('check_ajax_referer')->andReturn(true);
        $_REQUEST = ['post_id' => 77];
        Functions\expect('get_post')->andReturn((object)['ID' => 77]);

        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('read_post', 77)->andReturn(true);

        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function() { throw new \RuntimeException('no_results'); });

        $this->expectException(\RuntimeException::class);
        $sut->ajax();
    }

    public function test_ajax_success_builds_issues_and_fixes_payload(): void
    {
        $sut = Mockery::mock(Frontend_Highlight::class)->makePartial();
        $_REQUEST = ['post_id' => 88];

        Functions\expect('check_ajax_referer')->andReturn(true);
        Functions\expect('get_post')->andReturn((object)['ID' => 88]);
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('read_post', 88)->andReturn(true);

        $sut->shouldReceive('get_issues')->with(88)->andReturn([
            ['id' => 1, 'rule' => 'img-alt', 'ignre' => 0, 'object' => '&lt;img&gt;', 'ruletype' => 'error'],
            ['id' => 2, 'rule' => 'link-name', 'ignre' => 1, 'object' => '&lt;a&gt;', 'ruletype' => 'warning'],
        ]);

        Functions\expect('edac_register_rules')->once()->andReturn([
            ['slug' => 'img-alt', 'rule_type' => 'error', 'title' => 'Image needs alt', 'summary' => 'Provide alt', 'how_to_fix' => '<p>fix</p>', 'info_url' => 'https://x/r1', 'fixes' => ['fixA']],
            ['slug' => 'link-name', 'rule_type' => 'warning', 'title' => 'Link needs name', 'summary' => 'Provide name', 'how_to_fix' => '', 'info_url' => 'https://x/r2', 'fixes' => ['fixB']],
        ]);

        Functions\expect('edac_filter_by_value')->andReturnUsing(function($rules, $key, $val) {
            foreach ($rules as $r) { if ($r[$key] === $val) return [ $r ]; }
            return [];
        });

        Functions\expect('edac_link_wrapper')->andReturnUsing(fn($u) => $u);
        Functions\when('html_entity_decode')->alias('html_entity_decode');

        $fixA = new class {
            public function get_fields_array() { return [
                'group_x' => ['group_name' => 'Group X', 'type' => 'checkbox', 'label' => 'A1'],
                'field_y' => ['type' => 'text', 'label' => 'A2'],
            ]; }
        };
        $fixB = new class {
            public function get_fields_array() { return [
                'only_field' => ['type' => 'checkbox', 'label' => 'B1'],
            ]; }
        };
        $fm = Mockery::mock(FixesManager::class);
        $fm->shouldReceive('get_fix')->with('fixA')->andReturn($fixA);
        $fm->shouldReceive('get_fix')->with('fixB')->andReturn($fixB);
        try {
            $refl = new \ReflectionClass(FixesManager::class);
            if ($refl->hasProperty('instance')) {
                $prop = $refl->getProperty('instance');
                $prop->setAccessible(true);
                $prop->setValue(null, $fm);
            }
        } catch (\Throwable $e) {
            // ignore if singleton internals differ
        }

        Functions\expect('\\EqualizeDigital\\AccessibilityChecker\\Admin\\AdminPage\\FixesPage::checkbox')
            ->andReturnUsing(function(array $args) { echo '<input type="checkbox" name="'.$args['name'].'">'; });
        Functions\expect('\\EqualizeDigital\\AccessibilityChecker\\Admin\\AdminPage\\FixesPage::text')
            ->andReturnUsing(function(array $args) { echo '<input type="text" name="'.$args['name'].'">'; });

        Functions\expect('wp_send_json_success')->once()->andReturnUsing(function($payload) {
            $data = json_decode($payload, true);
            TestCase::assertArrayHasKey('issues', $data);
            TestCase::assertArrayHasKey('fixes', $data);
            TestCase::assertCount(2, $data['issues']);
            TestCase::assertArrayHasKey('img-alt', $data['fixes']);
            throw new \RuntimeException('success');
        });

        $this->expectException(\RuntimeException::class);
        $sut->ajax();
    }

    public function test_ajax_skips_results_with_missing_rule_definitions_and_errors_when_no_issues_left(): void
    {
        $sut = Mockery::mock(Frontend_Highlight::class)->makePartial();
        $_REQUEST = ['post_id' => 90];

        Functions\expect('check_ajax_referer')->andReturn(true);
        Functions\expect('get_post')->andReturn((object)['ID' => 90]);
        Functions\expect('is_user_logged_in')->andReturn(true);
        Functions\expect('current_user_can')->with('read_post', 90)->andReturn(true);

        $sut->shouldReceive('get_issues')->with(90)->andReturn([
            ['id' => 3, 'rule' => 'unknown-rule', 'ignre' => 0, 'object' => '&lt;div&gt;', 'ruletype' => 'notice'],
        ]);

        Functions\expect('edac_register_rules')->once()->andReturn([
            ['slug' => 'img-alt', 'rule_type' => 'error', 'title' => 'Image needs alt', 'summary' => 'Provide alt', 'how_to_fix' => '', 'info_url' => '#'],
        ]);
        Functions\expect('edac_filter_by_value')->andReturn([]);
        Functions\expect('wp_send_json_error')->once()->andReturnUsing(function() { throw new \RuntimeException('no_issues_after_filter'); });

        $this->expectException(\RuntimeException::class);
        $sut->ajax();
    }
}