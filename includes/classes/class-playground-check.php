<?php
/**
 * Class file for Plugin Check
 *
 * @package Accessibility_Checker
 */

namespace EDAC;

/**
 * Plugin Check class.
 */
class Playground_Check {

	/**
	 * Flag to determine if the plugin should be loaded.
	 *
	 * @var boolean
	 */
	public $should_load = true;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		$this->check_site_url_and_maybe_exit();
	}

	/**
	 * Check the site's URL and set the should_load property accordingly.
	 */
	private function check_site_url_and_maybe_exit() {
		$site_url = get_site_url();

		if ( strpos( $site_url, 'playground.wordpress.net' ) !== false ) {
			// This is the playground site, show an admin notice.
			add_action( 'admin_notices', array( $this, 'show_playground_notice' ) );

			// Set should_load to false.
			$this->should_load = false;
		}
	}

	/**
	 * Display an admin notice for the playground site.
	 */
	public function show_playground_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Unable to load live preview. WordPress Playground is not compatible with the Accessibility Checker Plugin.', 'accessibility-checker' ); ?></p>
		</div>
		<?php
	}
}
