<?php
/**
 * Tests for the screen reader only editor styles injected by Enqueue_Admin.
 *
 * @package accessibility-checker
 */

use EDAC\Admin\Enqueue_Admin;

/**
 * Tests for Enqueue_Admin::maybe_inject_sr_only_editor_styles().
 *
 * The enqueue side of the same feature (maybe_enqueue_sr_only_format) is
 * covered in EnqueueAdminTest; this file covers the block editor settings
 * side, which reads the built stylesheet and the per-user preference.
 */
class EnqueueAdminSrOnlyEditorStylesTest extends WP_UnitTestCase {

	/**
	 * Marker CSS written to the built stylesheet the class reads.
	 *
	 * The stylesheet path is hard coded under build/, so a file has to be
	 * written there; there is no filter that swaps the path for a temp file.
	 *
	 * @var string
	 */
	private const CSS_FIXTURE = "/* edac-sr-only-styles-fixture */\n.edac-sr-only-fixture{position:absolute;}\n";

	/**
	 * ID of the administrator the tests run as.
	 *
	 * @var int
	 */
	private $user_id = 0;

	/**
	 * Path of the stylesheet fixture this test case writes.
	 *
	 * @var string
	 */
	private $css_file = '';

	/**
	 * Whether a built stylesheet was already on disk when the fixture was written.
	 *
	 * @var bool
	 */
	private $css_file_preexisted = false;

	/**
	 * Contents of the built stylesheet before the fixture replaced them.
	 *
	 * @var string
	 */
	private $original_css_contents = '';

	/**
	 * Whether this test case had to create the fixture directory itself.
	 *
	 * @var bool
	 */
	private $created_css_directory = false;

	/**
	 * Value of $GLOBALS['pagenow'] before the test case changed it.
	 *
	 * @var string|null
	 */
	private $original_pagenow = null;

	/**
	 * Whether $GLOBALS['pagenow'] was set before the test case ran.
	 *
	 * @var bool
	 */
	private $had_pagenow = false;

	/**
	 * Value of $GLOBALS['post'] before the test case changed it.
	 *
	 * @var \WP_Post|null
	 */
	private $original_post = null;

	/**
	 * Whether $GLOBALS['post'] was set before the test case ran.
	 *
	 * @var bool
	 */
	private $had_post = false;

	/**
	 * Creates the admin user, the scannable post types and the stylesheet fixture.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->had_pagenow      = array_key_exists( 'pagenow', $GLOBALS );
		$this->original_pagenow = $this->had_pagenow ? $GLOBALS['pagenow'] : null;
		$this->had_post         = array_key_exists( 'post', $GLOBALS );
		$this->original_post    = $this->had_post ? $GLOBALS['post'] : null;

		$this->user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->user_id );

		update_option( 'edac_post_types', [ 'post', 'page' ] );

		global $wp_scripts, $wp_styles;
		$wp_scripts = new \WP_Scripts();
		$wp_styles  = new \WP_Styles();

		$this->create_stylesheet_fixture();
	}

	/**
	 * Removes the fixture and every global the tests set up.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// The stylesheet lives at the path the build writes to, so a checkout
		// that has already been built has to get its own file back. Only a
		// file this test case created is removed.
		if ( $this->css_file_preexisted ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Restoring the built stylesheet the fixture replaced.
			file_put_contents( $this->css_file, $this->original_css_contents );
		} elseif ( '' !== $this->css_file && file_exists( $this->css_file ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Removing the fixture this test case wrote.
			unlink( $this->css_file );
		}

		if ( $this->created_css_directory && is_dir( dirname( $this->css_file ) ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir -- Removing the fixture directory this test case created.
			rmdir( dirname( $this->css_file ) );
		}

		if ( $this->user_id ) {
			delete_user_meta( $this->user_id, 'show_sr_text_in_editor' );
			wp_set_current_user( 0 );
			wp_delete_user( $this->user_id );
			$this->user_id = 0;
		}

		// The globals the tests set are shared with every later test in the
		// process, so put them back exactly as this test case found them.
		if ( $this->had_pagenow ) {
			$GLOBALS['pagenow'] = $this->original_pagenow;
		} else {
			unset( $GLOBALS['pagenow'] );
		}

		if ( $this->had_post ) {
			$GLOBALS['post'] = $this->original_post;
		} else {
			unset( $GLOBALS['post'] );
		}

		parent::tearDown();

		delete_option( 'edac_post_types' );

		global $wp_scripts, $wp_styles;
		unset( $wp_scripts, $wp_styles, $GLOBALS['current_screen'] );
		unset( $_GET['page'] );
	}

	/**
	 * Writes the built stylesheet the class reads.
	 *
	 * @return void
	 */
	private function create_stylesheet_fixture(): void {
		$directory = plugin_dir_path( EDAC_PLUGIN_FILE ) . 'build/css';

		if ( ! is_dir( $directory ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir -- Creating the directory the built stylesheet fixture is read from.
			mkdir( $directory, 0777, true );
			$this->created_css_directory = true;
		}

		$this->css_file = $directory . '/srOnlyFormat.css';

		// A checkout that has already been built has a real stylesheet here.
		// Keep it, so tearDown() can put the build back instead of eating it.
		if ( file_exists( $this->css_file ) ) {
			$this->css_file_preexisted = true;
			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reading back the local built stylesheet the fixture is about to replace.
			$this->original_css_contents = (string) file_get_contents( $this->css_file );
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Writing the built stylesheet fixture this test case reads.
		file_put_contents( $this->css_file, self::CSS_FIXTURE );

		$this->assertFileExists( $this->css_file, 'The stylesheet fixture could not be written.' );
	}

	/**
	 * Puts the globals into the state a post editor request uses.
	 *
	 * @return void
	 */
	private function prepare_post_editor(): void {
		global $post, $pagenow;

		$pagenow = 'post.php';
		$post    = self::factory()->post->create_and_get();

		$GLOBALS['current_screen']                  = WP_Screen::get( 'post' );
		$GLOBALS['current_screen']->is_block_editor = true;
	}

	/**
	 * Tests the settings are returned untouched when the format should not load.
	 *
	 * @covers \EDAC\Admin\Enqueue_Admin::maybe_inject_sr_only_editor_styles
	 *
	 * @return void
	 */
	public function test_settings_are_unchanged_when_the_format_does_not_load(): void {
		global $pagenow;
		$pagenow = 'edit.php';

		$settings = [ 'bodyClassName' => 'block-editor-page' ];

		$this->assertSame( $settings, Enqueue_Admin::maybe_inject_sr_only_editor_styles( $settings ) );
	}

	/**
	 * Tests the body class the stylesheet needs, for each preference state.
	 *
	 * @param bool   $opted_in        Whether the user has opted into always showing screen reader text.
	 * @param string $body_class      Body class the editor already has.
	 * @param string $expected_class  Body class the filter should leave behind.
	 *
	 * @dataProvider provider_body_class
	 * @covers \EDAC\Admin\Enqueue_Admin::maybe_inject_sr_only_editor_styles
	 *
	 * @return void
	 */
	public function test_adds_the_always_show_body_class_only_when_it_is_needed( bool $opted_in, string $body_class, string $expected_class ): void {
		$this->prepare_post_editor();

		if ( $opted_in ) {
			update_user_meta( $this->user_id, 'show_sr_text_in_editor', true );
		}

		$settings = Enqueue_Admin::maybe_inject_sr_only_editor_styles( [ 'bodyClassName' => $body_class ] );

		$this->assertSame( $expected_class, $settings['bodyClassName'] );
		$this->assertCount( 1, $settings['styles'] );
	}

	/**
	 * Provides the opted-in state, the existing body class and the expected result.
	 *
	 * @return array<string, array{0: bool, 1: string, 2: string}>
	 */
	public function provider_body_class(): array {
		return [
			'opted in, no class yet'        => [ true, 'wp-block-editor', 'wp-block-editor sr-only-show-always' ],
			'opted in, class already there' => [ true, 'wp-block-editor sr-only-show-always', 'wp-block-editor sr-only-show-always' ],
			'not opted in'                  => [ false, 'wp-block-editor', 'wp-block-editor' ],
		];
	}

	/**
	 * Tests a usable styles list is produced whatever the settings hold.
	 *
	 * @param array $editor_settings Editor settings passed through the filter.
	 * @param int   $existing_styles Number of styles the settings already hold.
	 *
	 * @dataProvider provider_editor_settings
	 * @covers \EDAC\Admin\Enqueue_Admin::maybe_inject_sr_only_editor_styles
	 *
	 * @return void
	 */
	public function test_appends_the_stylesheet_to_a_usable_styles_list( array $editor_settings, int $existing_styles ): void {
		$this->prepare_post_editor();

		$settings = Enqueue_Admin::maybe_inject_sr_only_editor_styles( $editor_settings );
		$styles   = $settings['styles'];
		$injected = end( $styles );

		$this->assertCount( $existing_styles + 1, $styles );
		$this->assertStringStartsWith( self::CSS_FIXTURE, $injected['css'] );
		$this->assertStringContainsString( 'content: "* Screen Reader Text"', $injected['css'] );

		if ( $existing_styles ) {
			$this->assertSame( 'existing', $styles[0]['css'] );
		}
	}

	/**
	 * Provides editor settings that do not hold a usable styles list, and the count they hold.
	 *
	 * @return array<string, array{0: array, 1: int}>
	 */
	public function provider_editor_settings(): array {
		return [
			'no styles key'   => [ [], 0 ],
			'empty string'    => [ [ 'styles' => '' ], 0 ],
			'integer'         => [ [ 'styles' => 0 ], 0 ],
			'existing styles' => [ [ 'styles' => [ [ 'css' => 'existing' ] ] ], 1 ],
		];
	}

	/**
	 * Tests the stylesheet is read once and reused for the rest of the request.
	 *
	 * @covers \EDAC\Admin\Enqueue_Admin::get_sr_only_editor_styles
	 *
	 * @return void
	 */
	public function test_reads_the_stylesheet_once_per_request(): void {
		$this->prepare_post_editor();

		$first = Enqueue_Admin::maybe_inject_sr_only_editor_styles( [] );

		// With the file gone a fresh read would inject nothing, so the second
		// call can only still carry the CSS if it came from the cache.
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- Removing the fixture to prove the stylesheet is cached.
		unlink( $this->css_file );

		$second = Enqueue_Admin::maybe_inject_sr_only_editor_styles( [] );

		$this->assertSame( $first['styles'], $second['styles'] );
		$this->assertStringStartsWith( self::CSS_FIXTURE, $second['styles'][0]['css'] );
	}

	/**
	 * Tests the admin entry point enqueues its stylesheet and base script.
	 *
	 * @covers \EDAC\Admin\Enqueue_Admin::enqueue
	 *
	 * @return void
	 */
	public function test_enqueue_registers_the_admin_styles_and_script(): void {
		$this->prepare_post_editor();

		Enqueue_Admin::enqueue();

		$this->assertTrue( wp_style_is( 'edac', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'edac', 'enqueued' ) );
	}
}
