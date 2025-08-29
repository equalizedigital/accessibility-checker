<?php
/**
 * Tests for ReadMoreAddTitleFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\ReadMoreAddTitleFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;
use WP_UnitTestCase;

/**
 * ReadMoreAddTitleFix test case
 */
class ReadMoreAddTitleFixTest extends WP_UnitTestCase {

	/**
	 * Test fix instance
	 *
	 * @var ReadMoreAddTitleFix
	 */
	private $fix;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->fix = new ReadMoreAddTitleFix();
		
		// Create a test post.
		$this->post_id = $this->factory->post->create(
			[
				'post_title'   => 'Test Post Title',
				'post_content' => 'This is a test post content.',
				'post_excerpt' => 'This is a test excerpt.',
			] 
		);
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_add_read_more_title' );
		delete_option( 'edac_fix_add_read_more_title_screen_reader_only' );
		
		// Clean up global variables.
		wp_reset_postdata();
		
		parent::tearDown();
	}

	/**
	 * Test that the fix implements FixInterface
	 */
	public function test_implements_fix_interface() {
		$this->assertInstanceOf( FixInterface::class, $this->fix );
	}

	/**
	 * Test get_slug method
	 */
	public function test_get_slug() {
		$this->assertEquals( 'read_more', ReadMoreAddTitleFix::get_slug() );
	}

	/**
	 * Test get_nicename method
	 */
	public function test_get_nicename() {
		$this->assertEquals( 'Add "Read" Link with Post Title', ReadMoreAddTitleFix::get_nicename() );
	}

	/**
	 * Test get_fancyname method
	 */
	public function test_get_fancyname() {
		$this->assertEquals( 'Add Title to Read More Link', ReadMoreAddTitleFix::get_fancyname() );
	}

	/**
	 * Test get_type method
	 */
	public function test_get_type() {
		$this->assertEquals( 'everywhere', ReadMoreAddTitleFix::get_type() );
	}

	/**
	 * Test register method adds settings sections
	 */
	public function test_register_adds_settings_sections() {
		$sections = [];
		$this->fix->register();
		
		$sections = apply_filters( 'edac_filter_fixes_settings_sections', $sections );
		
		$this->assertArrayHasKey( 'read_more_links', $sections );
		$this->assertEquals( 'Read More links', $sections['read_more_links']['title'] );
		$this->assertEquals( 'Add the post title and links ', $sections['read_more_links']['description'] );
		$this->assertEquals( [ $this->fix, 'read_more_section_callback' ], $sections['read_more_links']['callback'] );
	}

	/**
	 * Test get_fields_array method returns correct fields
	 */
	public function test_get_fields_array() {
		$fields = $this->fix->get_fields_array();
		
		// Test main read more field.
		$this->assertArrayHasKey( 'edac_fix_add_read_more_title', $fields );
		$main_field = $fields['edac_fix_add_read_more_title'];
		$this->assertEquals( 'checkbox', $main_field['type'] );
		$this->assertEquals( 'Add Post Title To &quot;Read More&quot;', $main_field['label'] );
		$this->assertEquals( 'add_read_more_title', $main_field['labelledby'] );
		$this->assertEquals( 'Add the post title to &quot;Read More&quot; links in post lists when your theme outputs those links.', $main_field['description'] );
		$this->assertEquals( 'read_more_links', $main_field['section'] );
		$this->assertEquals( 'read_more', $main_field['fix_slug'] );
		$this->assertEquals( 'Add "Read" Link with Post Title', $main_field['group_name'] );
		$this->assertEquals( 8663, $main_field['help_id'] );
		
		// Test screen reader only field.
		$this->assertArrayHasKey( 'edac_fix_add_read_more_title_screen_reader_only', $fields );
		$sr_field = $fields['edac_fix_add_read_more_title_screen_reader_only'];
		$this->assertEquals( 'checkbox', $sr_field['type'] );
		$this->assertEquals( 'For Screen Readers Only', $sr_field['label'] );
		$this->assertEquals( 'add_read_more_title_screen_reader_only', $sr_field['labelledby'] );
		$this->assertEquals( 'Makes the post title added to &quot;Read More&quot; links visible only to screen readers.', $sr_field['description'] );
		$this->assertEquals( 'edac_fix_add_read_more_title', $sr_field['condition'] );
		$this->assertEquals( 'read_more_links', $sr_field['section'] );
		$this->assertEquals( 'read_more', $sr_field['fix_slug'] );
	}

	/**
	 * Test run method does nothing when option is disabled
	 */
	public function test_run_does_nothing_when_disabled() {
		update_option( 'edac_fix_add_read_more_title', false );
		
		$this->fix->run();
		
		// Verify no actions/filters are registered.
		$this->assertFalse( has_action( 'the_content_more_link', [ $this->fix, 'add_title_to_read_more' ] ) );
		$this->assertFalse( has_filter( 'get_the_excerpt', [ $this->fix, 'add_title_link_to_excerpts' ] ) );
		$this->assertFalse( has_filter( 'excerpt_more', [ $this->fix, 'add_title_to_excerpt_more' ] ) );
		$this->assertFalse( has_action( 'wp_head', [ $this->fix, 'add_screen_reader_styles' ] ) );
	}

	/**
	 * Test run method registers hooks when enabled
	 */
	public function test_run_registers_hooks_when_enabled() {
		update_option( 'edac_fix_add_read_more_title', true );
		update_option( 'edac_fix_add_read_more_title_screen_reader_only', false );
		
		$this->fix->run();
		
		$this->assertNotFalse( has_action( 'the_content_more_link', [ $this->fix, 'add_title_to_read_more' ] ) );
		$this->assertNotFalse( has_filter( 'get_the_excerpt', [ $this->fix, 'add_title_link_to_excerpts' ] ) );
		$this->assertNotFalse( has_filter( 'excerpt_more', [ $this->fix, 'add_title_to_excerpt_more' ] ) );
		$this->assertFalse( has_action( 'wp_head', [ $this->fix, 'add_screen_reader_styles' ] ) );
	}

	/**
	 * Test run method registers screen reader styles when enabled
	 */
	public function test_run_registers_screen_reader_styles_when_enabled() {
		update_option( 'edac_fix_add_read_more_title', true );
		update_option( 'edac_fix_add_read_more_title_screen_reader_only', true );
		
		$this->fix->run();
		
		$this->assertNotFalse( has_action( 'wp_head', [ $this->fix, 'add_screen_reader_styles' ] ) );
	}

	/**
	 * Test add_title_to_read_more method adds post title
	 */
	public function test_add_title_to_read_more_adds_title() {
		// Set the current post in the global query.
		$this->go_to( get_permalink( $this->post_id ) );
		global $wp_query;
		$wp_query->queried_object    = get_post( $this->post_id );
		$wp_query->queried_object_id = $this->post_id;
		
		$link = '<a href="http://example.com/test-post">Read More</a>';
		$text = 'Read More';
		
		$result = $this->fix->add_title_to_read_more( $link, $text );
		
		$this->assertStringContainsString( 'Test Post Title', $result );
		$this->assertStringContainsString( 'edac-content-more-title', $result );
	}

	/**
	 * Test add_title_to_read_more preserves existing title
	 */
	public function test_add_title_to_read_more_preserves_existing_title() {
		// Set the current post in the global query.
		$this->go_to( get_permalink( $this->post_id ) );
		global $wp_query;
		$wp_query->queried_object    = get_post( $this->post_id );
		$wp_query->queried_object_id = $this->post_id;
		
		$link = '<a href="http://example.com/test-post">Read More about Test Post Title</a>';
		$text = 'Read More about Test Post Title';
		
		$result = $this->fix->add_title_to_read_more( $link, $text );
		
		// Should not add duplicate title.
		$this->assertEquals( $link, $result );
	}

	/**
	 * Test add_title_link_to_excerpts method when has_excerpt is true
	 */
	public function test_add_title_link_to_excerpts_adds_link_when_has_excerpt() {
		// Set post to have an excerpt.
		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_excerpt' => 'This is a test excerpt.',
			] 
		);
		
		// Set the current post in the global query.
		$this->go_to( get_permalink( $this->post_id ) );
		global $wp_query;
		$wp_query->queried_object    = get_post( $this->post_id );
		$wp_query->queried_object_id = $this->post_id;
		
		$excerpt = 'This is a test excerpt.';
		
		$result = $this->fix->add_title_link_to_excerpts( $excerpt );
		
		$this->assertStringContainsString( 'Test Post Title', $result );
		$this->assertStringContainsString( 'edac-content-more-title', $result );
	}

	/**
	 * Test add_title_link_to_excerpts preserves excerpt when title already present
	 */
	public function test_add_title_link_to_excerpts_preserves_when_title_present() {
		// Set post to have an excerpt with title already present.
		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_excerpt' => 'This excerpt already contains Test Post Title.',
			] 
		);
		
		// Set the current post in the global query.
		$this->go_to( get_permalink( $this->post_id ) );
		global $wp_query;
		$wp_query->queried_object    = get_post( $this->post_id );
		$wp_query->queried_object_id = $this->post_id;
		
		$excerpt = 'This excerpt already contains Test Post Title.';
		
		$result = $this->fix->add_title_link_to_excerpts( $excerpt );
		
		// Should not modify excerpt when title already present.
		$this->assertEquals( $excerpt, $result );
	}

	/**
	 * Test add_title_to_excerpt_more method
	 */
	public function test_add_title_to_excerpt_more() {
		// Set the current post in the global query.
		$this->go_to( get_permalink( $this->post_id ) );
		global $wp_query;
		$wp_query->queried_object    = get_post( $this->post_id );
		$wp_query->queried_object_id = $this->post_id;
		
		// Mock get_the_permalink.
		add_filter(
			'post_link',
			function ( $permalink, $post ) {
				if ( $post->ID === $this->post_id ) {
					return 'http://example.com/test-post/';
				}
				return $permalink;
			},
			10,
			2 
		);
		
		$result = $this->fix->add_title_to_excerpt_more();
		
		$this->assertStringContainsString( '&hellip; <a href="http://example.com/test-post/">', $result );
		$this->assertStringContainsString( 'Test Post Title', $result );
		$this->assertStringContainsString( 'edac-content-more-title', $result );
		
		remove_all_filters( 'post_link' );
	}

	/**
	 * Test add_screen_reader_styles method outputs CSS
	 */
	public function test_add_screen_reader_styles_outputs_css() {
		ob_start();
		$this->fix->add_screen_reader_styles();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '<style>', $output );
		$this->assertStringContainsString( '.edac-screen-reader-text', $output );
		$this->assertStringContainsString( 'position: absolute;', $output );
		$this->assertStringContainsString( 'clip: rect(1px, 1px, 1px, 1px);', $output );
	}

	/**
	 * Test generate_read_more_string method without screen reader styles
	 */
	public function test_generate_read_more_string_without_screen_reader() {
		update_option( 'edac_fix_add_read_more_title_screen_reader_only', false );
		
		$reflection = new \ReflectionClass( $this->fix );
		$method     = $reflection->getMethod( 'generate_read_more_string' );
		$method->setAccessible( true );
		
		$result = $method->invoke( $this->fix, $this->post_id );
		
		$this->assertStringContainsString( 'Test Post Title', $result );
		$this->assertStringContainsString( 'edac-content-more-title', $result );
		$this->assertStringNotContainsString( 'edac-screen-reader-text', $result );
	}

	/**
	 * Test generate_read_more_string method with screen reader styles
	 */
	public function test_generate_read_more_string_with_screen_reader() {
		update_option( 'edac_fix_add_read_more_title_screen_reader_only', true );
		
		$reflection = new \ReflectionClass( $this->fix );
		$method     = $reflection->getMethod( 'generate_read_more_string' );
		$method->setAccessible( true );
		
		$result = $method->invoke( $this->fix, $this->post_id );
		
		$this->assertStringContainsString( 'Test Post Title', $result );
		$this->assertStringContainsString( 'edac-content-more-title', $result );
		$this->assertStringContainsString( 'edac-screen-reader-text', $result );
	}

	/**
	 * Test generate_read_more_string method with never_use_screen_reader flag
	 */
	public function test_generate_read_more_string_with_never_use_screen_reader_flag() {
		update_option( 'edac_fix_add_read_more_title_screen_reader_only', true );
		
		$reflection = new \ReflectionClass( $this->fix );
		$method     = $reflection->getMethod( 'generate_read_more_string' );
		$method->setAccessible( true );
		
		$result = $method->invoke( $this->fix, $this->post_id, true );
		
		$this->assertStringContainsString( 'Test Post Title', $result );
		$this->assertStringContainsString( 'edac-content-more-title', $result );
		$this->assertStringNotContainsString( 'edac-screen-reader-text', $result );
	}

	/**
	 * Test read_more_section_callback method outputs description
	 */
	public function test_read_more_section_callback() {
		ob_start();
		$this->fix->read_more_section_callback();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'This fix adds the post title to the &quot;Read More&quot; links in post lists at the &quot;More&quot; block and in excerpts.', $output );
	}
}
