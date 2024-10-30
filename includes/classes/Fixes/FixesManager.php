<?php
/**
 * Manager class for fixes.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddFileSizeAndTypeToLinkedFilesFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddLabelToUnlabelledFormFieldsFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddMissingOrEmptyPageTitleFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\BlockPDFUploadsFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\CommentSearchLabelFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\HTMLLangAndDirFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\RemoveTitleIfPrefferedAccessibleNameFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\PreventLinksOpeningNewWindowFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\SkipLinkFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\TabindexFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\LinkUnderline;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\MetaViewportScalableFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\FocusOutlineFix;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\ReadMoreAddTitleFix;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;

/**
 * Manager class for fixes.
 *
 * @since 1.16.0
 */
class FixesManager {

	/**
	 * The single instance of the class.
	 *
	 * @var FixesManager|null
	 */
	private static $instance = null;

	/**
	 * Whether the theme has the accessibility-ready tag.
	 *
	 * @var bool|null
	 */
	private static $theme_is_accessibility_ready = null;

	/**
	 * The fixes.
	 *
	 * @var array
	 */
	private $fixes = [];

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		$this->maybe_enqueue_frontend_scripts();
		$this->maybe_enqueue_thickbox();

		self::$theme_is_accessibility_ready = self::is_theme_accessibility_ready();
	}

	/**
	 * Maybe enqueue the thickbox script.
	 *
	 * This powers the modal that is used to display fix settings in the editor.
	 */
	public function maybe_enqueue_thickbox() {
		add_action(
			'admin_enqueue_scripts',
			function () {
				add_thickbox();
			}
		);
	}

	/**
	 * Get the single instance of the class.
	 *
	 * @return FixesManager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Maybe enqueue the frontend scripts.
	 */
	private function maybe_enqueue_frontend_scripts() {

		if (
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
			( defined( 'DOING_CRON' ) && DOING_CRON ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		) {
			return;
		}

		// Consider adding this only if we can determine at least 1 of the fixes are enabled.
		add_action(
			'wp_enqueue_scripts',
			function () {
				wp_enqueue_script( 'edac-frontend-fixes', EDAC_PLUGIN_URL . 'build/frontendFixes.bundle.js', [], EDAC_VERSION, true );
				wp_localize_script(
					'edac-frontend-fixes',
					'edac_frontend_fixes',
					apply_filters( 'edac_filter_frontend_fixes_data', [] )
				);
				do_action( 'edac_action_enqueue_frontend_fixes' );
			}
		);
	}

	/**
	 * Load the fixes.
	 */
	private function load_fixes() {
		$fixes = apply_filters(
			'edac_filter_fixes',
			[
				SkipLinkFix::class,
				CommentSearchLabelFix::class,
				HTMLLangAndDirFix::class,
				TabindexFix::class,
				RemoveTitleIfPrefferedAccessibleNameFix::class,
				LinkUnderline::class,
				MetaViewportScalableFix::class,
				PreventLinksOpeningNewWindowFix::class,
				FocusOutlineFix::class,
				BlockPDFUploadsFix::class,
				AddFileSizeAndTypeToLinkedFilesFix::class,
				AddMissingOrEmptyPageTitleFix::class,
				AddLabelToUnlabelledFormFieldsFix::class,
			]
		);
		foreach ( $fixes as $fix ) {
			if ( is_subclass_of( $fix, '\EqualizeDigital\AccessibilityChecker\Fixes\FixInterface' ) ) {
				if ( ! isset( $this->fixes[ $fix::get_slug() ] ) ) {
					$this->fixes[ $fix::get_slug() ] = ( new $fix() );
				}
			}
		}
	}

	/**
	 * Get a fix by its slug.
	 *
	 * @param string $slug The fix slug.
	 *
	 * @return FixInterface|null
	 */
	public function get_fix( $slug ) {
		return isset( $this->fixes[ $slug ] ) ? $this->fixes[ $slug ] : null;
	}

	/**
	 * Register the fixes.
	 */
	public function register_fixes() {
		$this->load_fixes();

		foreach ( $this->fixes as $fix ) {
			$fix->register();
			$this->maybe_run_fix( $fix );
		}
	}

	/**
	 * Maybe run a fix depending on current context.
	 *
	 * @param FixInterface $fix The fix to maybe run.
	 */
	public function maybe_run_fix( $fix ) {
		if ( 'backend' === $fix::get_type() && is_admin() ) {
			$fix->run();
		} elseif ( 'frontend' === $fix::get_type() && ! is_admin() ) {
			$fix->run();
		} elseif ( 'everywhere' === $fix::get_type() ) {
			$fix->run();
		}
	}

	/**
	 * Check if the theme is accessibility ready.
	 *
	 * True if the theme has the tag, false otherwise.
	 *
	 * @return bool
	 */
	public static function is_theme_accessibility_ready() {
		if ( null !== self::$theme_is_accessibility_ready ) {
			return self::$theme_is_accessibility_ready;
		}

		$theme = wp_get_theme();
		$tags  = $theme->get( 'Tags' );

		self::$theme_is_accessibility_ready = in_array( 'accessibility-ready', $tags, true );
		return self::$theme_is_accessibility_ready;
	}

	/**
	 * Maybe show a notice if the theme is accessibility-ready.
	 */
	public static function maybe_show_accessibility_ready_conflict_notice() {
		if ( self::is_theme_accessibility_ready() ) {
			?>
			<span class="edac-notice--accessibility-ready-conflict">
				<?php esc_html_e( 'Note: This setting is not recommended for themes that are already accessibility-ready.', 'accessibility-checker' ); ?>
			</span>
			<?php
		}
	}

	/**
	 * Register the rest routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'edac/v1',
			'/fixes',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_fixes' ],
				'permission_callback' => function () {
					return current_user_can( apply_filters( 'edac_filter_settings_capability', 'manage_options' ) );
				},
			]
		);

		register_rest_route(
			'edac/v1',
			'/fixes/update',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'update_fix_settings' ],
				'permission_callback' => function () {
					return current_user_can( apply_filters( 'edac_filter_settings_capability', 'manage_options' ) );
				},
			]
		);
	}

	/**
	 * Handle the request to set a fix.
	 *
	 * @param \WP_REST_Request $request The request recieved through a rest call.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function update_fix_settings( $request ) {
		// get body of the request.
		$body = $request->get_json_params();

		// loop through body and find fixes for those items.
		foreach ( $body as $rule_slug => $settings ) {
			$fix        = $this->get_fix( $rule_slug );
			$fix_fields = $fix->get_fields_array();
			if ( ! $fix ) {
				return new \WP_Error( 'edac_fix_not_found', esc_html__( 'Fix not found', 'accessibility-checker' ), [ 'status' => 404 ] );
			}

			foreach ( $settings as $setting => $value ) {
				$sanitizer = isset( $fix_fields[ $setting ]['sanitize_callback'] ) ? $fix_fields[ $setting ]['sanitize_callback'] : [ FixesPage::class, 'sanitize_' . $fix_fields[ $setting ]['type'] ];
				if ( ! $sanitizer || ! is_callable( $sanitizer ) ) {
					// no sanitizer, do not save.
					continue;
				}
				update_option( $setting, $sanitizer( $value ) );
			}
		}

		return rest_ensure_response( [ 'enabled' => $enabled ] );
	}
}
