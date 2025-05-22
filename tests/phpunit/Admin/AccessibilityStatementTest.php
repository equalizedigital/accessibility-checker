<?php

namespace EDAC\Tests\Admin;

use EDAC\Admin\Accessibility_Statement;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use BrainMonkey\Functions;
use BrainMonkey\Actions;
use WP_User;
use WP_Post;

/**
 * Test class for EDAC\Admin\Accessibility_Statement
 */
class AccessibilityStatementTest extends TestCase {

    protected function tearDown(): void {
        parent::tearDown();
    }

    public function testAddPageCreatesPageWhenItDoesNotExist() {
        Functions\when('current_user_can')->justReturn(true);
        
        $mock_user = \Mockery::mock(WP_User::class);
        $mock_user->ID = 1;
        Functions\when('wp_get_current_user')->justReturn($mock_user);
        
        Functions\when('get_page_by_path')->justReturn(null);
        Functions\expect('wp_insert_post')->once()->with(\Mockery::on(function(\$arg) {
            return is_array(\$arg) &&
                   \$arg['post_title'] === __('Our Commitment to Web Accessibility', 'accessibility-checker') &&
                   \$arg['post_status'] === 'draft' &&
                   \$arg['post_author'] === 1 &&
                   \$arg['post_name'] === 'accessibility-statement' &&
                   \$arg['post_type'] === 'page';
        }));

        Accessibility_Statement::add_page();
    }

    public function testAddPageDoesNotCreatePageWhenItAlreadyExists() {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_page_by_path')->justReturn(\Mockery::mock(WP_Post::class));
        Functions\expect('wp_insert_post')->never();
        Functions\expect('wp_get_current_user')->never(); // Should not be called if page exists

        Accessibility_Statement::add_page();
    }

    public function testAddPageDoesNothingIfUserCannotActivatePlugins() {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('get_page_by_path')->never();
        Functions\expect('wp_insert_post')->never();
        Functions\expect('wp_get_current_user')->never();

        Accessibility_Statement::add_page();
    }
}
