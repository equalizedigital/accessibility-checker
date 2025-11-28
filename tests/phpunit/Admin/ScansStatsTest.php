<?php
/**
 * Tests for the Scans_Stats class.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Scans_Stats;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;

/**
 * Test cases to verify that the Scans_Stats class calculates issue density correctly.
 */
class ScansStatsTest extends WP_UnitTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Create database tables if they don't exist.
		DatabaseHelpers::create_table();
	}

	/**
	 * Drop the table to clean up after tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		DatabaseHelpers::drop_table();
		parent::tearDown();
	}

	/**
	 * Test that average issue density is N/A when there are no scanned posts.
	 */
	public function test_avg_issue_density_is_na_when_no_posts_scanned() {
		$scans_stats = new Scans_Stats( 0 );
		$summary     = $scans_stats->summary( true );

		$this->assertEquals( 'N/A', $summary['avg_issue_density_percentage'] );
	}

	/**
	 * Test that average issue density is calculated correctly when all posts have non-ignored issues.
	 */
	public function test_avg_issue_density_with_non_ignored_issues() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Create posts with issues and issue density.
		$post1_id = $this->factory()->post->create();
		$post2_id = $this->factory()->post->create();

		// Insert non-ignored issues for post 1.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post1_id,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'img_alt_missing',
				'ruletype'     => 'error',
				'object'       => '<img src="test.jpg">',
				'recordcheck'  => 1,
				'user'         => 1,
				'ignre'        => 0,
				'ignre_global' => 0,
			]
		);

		// Insert non-ignored issues for post 2.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post2_id,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'img_alt_missing',
				'ruletype'     => 'error',
				'object'       => '<img src="test.jpg">',
				'recordcheck'  => 1,
				'user'         => 1,
				'ignre'        => 0,
				'ignre_global' => 0,
			]
		);

		// Set issue density meta for both posts.
		update_post_meta( $post1_id, '_edac_issue_density', 5.5 );
		update_post_meta( $post2_id, '_edac_issue_density', 10.5 );

		$scans_stats = new Scans_Stats( 0 );
		$summary     = $scans_stats->summary( true );

		// Average should be (5.5 + 10.5) / 2 = 8.0.
		$this->assertEquals( 8.0, $summary['avg_issue_density_percentage'] );
	}

	/**
	 * Test that average issue density excludes posts with only ignored issues.
	 *
	 * This is the main test case for the bug fix.
	 */
	public function test_avg_issue_density_excludes_posts_with_only_ignored_issues() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Create posts.
		$post_with_ignored_issues = $this->factory()->post->create();
		$post_with_active_issues  = $this->factory()->post->create();

		// Insert ignored issues for first post (ignre = 1).
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_with_ignored_issues,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'img_alt_missing',
				'ruletype'     => 'error',
				'object'       => '<img src="test.jpg">',
				'recordcheck'  => 1,
				'user'         => 1,
				'ignre'        => 1,
				'ignre_global' => 0,
			]
		);

		// Insert non-ignored issue for second post.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_with_active_issues,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'img_alt_missing',
				'ruletype'     => 'error',
				'object'       => '<img src="test.jpg">',
				'recordcheck'  => 1,
				'user'         => 1,
				'ignre'        => 0,
				'ignre_global' => 0,
			]
		);

		// Set issue density meta for both posts.
		update_post_meta( $post_with_ignored_issues, '_edac_issue_density', 15.0 );
		update_post_meta( $post_with_active_issues, '_edac_issue_density', 10.0 );

		$scans_stats = new Scans_Stats( 0 );
		$summary     = $scans_stats->summary( true );

		// Average should only include the post with active issues: 10.0.
		$this->assertEquals( 10.0, $summary['avg_issue_density_percentage'] );
	}

	/**
	 * Test that average issue density is 0 when all posts have only ignored issues.
	 *
	 * This is the specific scenario from the bug report.
	 */
	public function test_avg_issue_density_is_zero_when_all_posts_have_only_ignored_issues() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Create a post with only ignored issues.
		$post_id = $this->factory()->post->create();

		// Insert ignored issue (ignre = 1).
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_id,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'img_alt_missing',
				'ruletype'     => 'error',
				'object'       => '<img src="test.jpg">',
				'recordcheck'  => 1,
				'user'         => 1,
				'ignre'        => 1,
				'ignre_global' => 0,
			]
		);

		// Set issue density meta.
		update_post_meta( $post_id, '_edac_issue_density', 5.0 );

		$scans_stats = new Scans_Stats( 0 );
		$summary     = $scans_stats->summary( true );

		// When all issues are ignored, average density should be N/A (since no posts have active issues).
		$this->assertEquals( 'N/A', $summary['avg_issue_density_percentage'] );
	}

	/**
	 * Test that average issue density excludes posts with globally ignored issues.
	 */
	public function test_avg_issue_density_excludes_posts_with_globally_ignored_issues() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// Create posts.
		$post_with_global_ignore = $this->factory()->post->create();
		$post_with_active_issues = $this->factory()->post->create();

		// Insert globally ignored issue (ignre_global = 1).
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_with_global_ignore,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'img_alt_missing',
				'ruletype'     => 'error',
				'object'       => '<img src="test.jpg">',
				'recordcheck'  => 1,
				'user'         => 1,
				'ignre'        => 0,
				'ignre_global' => 1,
			]
		);

		// Insert non-ignored issue for second post.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_with_active_issues,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => 'img_alt_missing',
				'ruletype'     => 'error',
				'object'       => '<img src="test.jpg">',
				'recordcheck'  => 1,
				'user'         => 1,
				'ignre'        => 0,
				'ignre_global' => 0,
			]
		);

		// Set issue density meta for both posts.
		update_post_meta( $post_with_global_ignore, '_edac_issue_density', 20.0 );
		update_post_meta( $post_with_active_issues, '_edac_issue_density', 5.0 );

		$scans_stats = new Scans_Stats( 0 );
		$summary     = $scans_stats->summary( true );

		// Average should only include the post with active issues: 5.0.
		$this->assertEquals( 5.0, $summary['avg_issue_density_percentage'] );
	}
}
