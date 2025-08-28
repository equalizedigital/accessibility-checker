<?php
/**
 * Class file for admin notices
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Class that handles admin notices
 */
class Admin_Notices {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
	}

	/**
	 * Initialize class hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {

		add_action( 'in_admin_header', [ $this, 'edac_remove_admin_notices' ], 1000 );
		add_action( 'in_admin_header', [ $this, 'hook_notices' ], 1001 );
		// Ajax handlers for notices.
		add_action( 'wp_ajax_edac_black_friday_notice_ajax', [ $this, 'edac_black_friday_notice_ajax' ] );
		add_action( 'wp_ajax_edac_gaad_notice_ajax', [ $this, 'edac_gaad_notice_ajax' ] );
		add_action( 'wp_ajax_edac_review_notice_ajax', [ $this, 'edac_review_notice_ajax' ] );
		// Save fixes transient on save.
		add_action( 'updated_option', [ $this, 'set_fixes_transient_on_save' ] );
	}

	/**
	 * Hook Notices
	 *
	 * @since 1.9.3
	 *
	 * @return void
	 */
	public function hook_notices() {
		if ( ! Helpers::current_user_can_see_widgets_and_notices() ) {
			return;
		}

		add_action( 'admin_notices', [ $this, 'edac_black_friday_notice' ] );
		add_action( 'admin_notices', [ $this, 'edac_gaad_notice' ] );
		add_action( 'admin_notices', [ $this, 'edac_review_notice' ] );
	}

	/**
	 * Remove Admin Notices
	 *
	 * @return void
	 */
	public function edac_remove_admin_notices() {

		$current_screen = get_current_screen();
		$screens        = [
			'toplevel_page_accessibility_checker',
			'accessibility-checker_page_accessibility_checker_issues',
			'accessibility-checker_page_accessibility_checker_ignored',
			'accessibility-checker_page_accessibility_checker_settings',
		];

		/**
		 * Filter the screens where admin notices should be removed.
		 *
		 * @since 1.14.0
		 *
		 * @param array $screens The screens where admin notices should be removed.
		 */
		$screens = apply_filters( 'edac_filter_remove_admin_notices_screens', $screens );

		if ( in_array( $current_screen->id, $screens, true ) ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	}

	/**
	 * Black Friday Admin Notice
	 *
	 * @return void
	 */
	public function edac_black_friday_notice() {

		// Check if Accessibility Checker Pro is active.
		if ( defined( 'EDACP_VERSION' ) ) {
			return;
		}

		// Check if the notice has been dismissed.
		if ( absint( get_option( 'edac_black_friday_2024_notice_dismiss', 0 ) ) ) {
			return;
		}

		// Show from November 25 to December 03.
		$current_date = date_i18n( 'Ymd' ); // Use date_i18n for localization.
		$start_date   = '20241125';
		$end_date     = '20241203';

		if ( $current_date >= $start_date && $current_date <= $end_date ) {

			// Get the promotional message from a separate function/file.
			$message = $this->edac_get_black_friday_message();

			// Output the message with appropriate sanitization.
			echo wp_kses_post( $message );

		}
	}

	/**
	 * Get Black Friday Promo Message
	 *
	 * @return string
	 */
	public function edac_get_black_friday_message() {

		// Construct the promotional message.
		$message  = '<div class="edac_black_friday_notice notice notice-info is-dismissible">';
		$message .= '<p><strong>' . esc_html__( '🎉 Black Friday special! 🎉', 'accessibility-checker' ) . '</strong><br />';
		$message .= esc_html__( 'Upgrade to a paid version of Accessibility Checker from November 25th to December 3rd and get 30% off! Full site scanning, site-wide open issues report, ignore logs, and more.', 'accessibility-checker' ) . '<br />';
		$message .= '<a class="button button-primary" href="' . esc_url( edac_link_wrapper( 'https://my.equalizedigital.com/support/pre-sale-questions/', 'admin-notice', 'BF2025-presale', false ) ) . '">' . esc_html__( 'Ask a Pre-Sale Question', 'accessibility-checker' ) . '</a> ';
		$message .= '<a class="button button-primary" href="' . esc_url( edac_link_wrapper( 'https://equalizedigital.com/accessibility-checker/pricing/', 'admin-notice', 'BF2025-pricing', false ) ) . '">' . esc_html__( 'Upgrade Now', 'accessibility-checker' ) . '</a></p>';
		$message .= '</div>';

		return $message;
	}

	/**
	 * Black Friday Admin Notice Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that update option wasn't successful
	 */
	public function edac_black_friday_notice_ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$results = update_option( 'edac_black_friday_2024_notice_dismiss', true );

		if ( ! $results ) {

			$error = new \WP_Error( '-2', __( 'Update option wasn\'t successful', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $results ) );
	}

	/**
	 * GAAD Notice
	 *
	 * @return void
	 */
	public function edac_gaad_notice() {

		// Define constants for start and end dates.
		define( 'EDAC_GAAD_NOTICE_START_DATE', '2025-05-13' );
		define( 'EDAC_GAAD_NOTICE_END_DATE', '2025-05-21' );

		// Check if Accessibility Checker Pro is active.
		if ( defined( 'EDACP_VERSION' ) ) {
			return;
		}

		// Get the value of the 'edac_gaad_notice_dismiss' option and sanitize it.
		$dismissed = absint( get_option( 'edac_gaad_notice_dismiss_2025', 0 ) );

		// Check if the notice has been dismissed.
		if ( $dismissed ) {
			return;
		}

		// Get the current date in the 'Y-m-d' format.
		$current_date = gmdate( 'Y-m-d' );

		// Check if the current date is within the specified range.
		if ( $current_date >= EDAC_GAAD_NOTICE_START_DATE && $current_date <= EDAC_GAAD_NOTICE_END_DATE ) {

			// Get the promotional message from a separate function/file.
			$message = $this->edac_get_gaad_promo_message();

			// Output the message with appropriate sanitization.
			echo wp_kses_post( $message );

		}
	}

	/**
	 * Get GAAD Promo Message
	 *
	 * @return string
	 */
	public function edac_get_gaad_promo_message() {

		// Construct the promotional message.
		$message  = '<div class="edac_gaad_notice notice notice-info is-dismissible">';
		$message .= '<p><strong>' . esc_html__( '🎉 Get 25% off Accessibility Checker Pro in honor of Global Accessibility Awareness Day! 🎉', 'accessibility-checker' ) . '</strong><br />';
		$message .= esc_html__( 'Use coupon code GAAD25 from May 13th-May 21st to get access to full-site scanning and other pro features at a special discount. Not sure if upgrading is right for you?', 'accessibility-checker' ) . '<br />';
		$message .= '<a class="button button-primary" href="' . esc_url( edac_link_wrapper( 'https://equalizedigital.com/contact/', 'admin-notice', 'GAAD25-presale', false ) ) . '">' . esc_html__( 'Ask a Pre-Sale Question', 'accessibility-checker' ) . '</a> ';
		$message .= '<a class="button button-primary" href="' . esc_url( edac_link_wrapper( 'https://equalizedigital.com/accessibility-checker/pricing/', 'admin-notice', 'GAAD25-pricing', false ) ) . '">' . esc_html__( 'Upgrade Now', 'accessibility-checker' ) . '</a></p>';
		$message .= '</div>';

		return $message;
	}

	/**
	 * GAAD Admin Notice Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that update option wasn't successful
	 */
	public function edac_gaad_notice_ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$results = update_option( 'edac_gaad_notice_dismiss_2025', true );
		// Delete old meta keys if they exist.
		delete_option( 'edac_gaad_notice_dismiss_2024' );
		delete_option( 'edac_gaad_notice_dismiss' );

		if ( ! $results ) {

			$error = new \WP_Error( '-2', __( 'Update option wasn\'t successful', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $results ) );
	}

	/**
	 * Review Admin Notice
	 *
	 * @return void
	 */
	public function edac_review_notice() {

		$option             = 'edac_review_notice';
		$edac_review_notice = get_option( $option );

		// exit if option is set to stop.
		if ( 'stop' === $edac_review_notice ) {
			return;
		}

		$transient                   = 'edac_review_notice_reminder';
		$edac_review_notice_reminder = get_transient( $transient );

		// first time if notice has never been shown wait 14 days.
		if ( false === $edac_review_notice_reminder && empty( $edac_review_notice ) ) {
			// if option isn't set and plugin has been active for more than 14 days show notice. This is for current users.
			if ( edac_days_active() > 14 ) {
				update_option( $option, 'play' );
			} else {
				// if plugin has been active less than 14 days set transient for 14 days.
				set_transient( $transient, true, 14 * DAY_IN_SECONDS );
				// set option to pause.
				update_option( $option, 'pause' );
			}
		}

		// if transient has expired and option is set to pause update option to play.
		if ( false === $edac_review_notice_reminder && 'pause' === $edac_review_notice ) {
			update_option( $option, 'play' );
		}

		// if option is not set to play exit.
		if ( get_option( $option ) !== 'play' ) {
			return;
		}

		?>
		<div class="notice notice-info edac-review-notice">
			<p>
				<?php esc_html_e( "Hello! Thank you for using Accessibility Checker as part of your accessibility toolkit. Since you've been using it for a while, would you please write a 5-star review of Accessibility Checker in the WordPress plugin directory? This will help increase our visibility so more people can learn about the importance of web accessibility. Thanks so much!", 'accessibility-checker' ); ?>
			</p>
			<p>
				<button class="edac-review-notice-review button button-small button-primary"><?php esc_html_e( 'Write A Review', 'accessibility-checker' ); ?></button>
				<button class="edac-review-notice-remind button button-small"><?php esc_html_e( 'Remind Me In Two Weeks', 'accessibility-checker' ); ?></button>
				<button class="edac-review-notice-dismiss button button-small"><?php esc_html_e( 'Never Ask Again', 'accessibility-checker' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Review Admin Notice Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the review action value was not specified
	 *  - '-3' means that update option wasn't successful
	 */
	public function edac_review_notice_ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['review_action'] ) ) {

			$error = new \WP_Error( '-2', __( 'The review action value was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$results = update_option( 'edac_review_notice', sanitize_text_field( $_REQUEST['review_action'] ) );

		if ( 'pause' === $_REQUEST['review_action'] ) {
			set_transient( 'edac_review_notice_reminder', true, 14 * DAY_IN_SECONDS );
		}

		if ( ! $results ) {

			$error = new \WP_Error( '-3', __( 'Update option wasn\'t successful', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $results ) );
	}


	/**
	 * Save a transient to indicate that the fixes settings have been updated.
	 *
	 * @param string $option    The option name that was saved.
	 *
	 * @return void
	 */
	public function set_fixes_transient_on_save( $option ) {
		if ( ! class_exists( 'EqualizeDigital\AccessibilityChecker\Fixes\FixesManager' ) ) {
			return;
		}
		$fixes_settings = FixesManager::get_instance()->get_fixes_settings();
		$options_keys   = [];
		foreach ( $fixes_settings as $fix ) {
			$options_keys = array_merge( $options_keys, array_keys( $fix['fields'] ) );
		}
		if ( in_array( $option, $options_keys, true ) ) {
			// Set a custom transient as a flag.
			set_transient( 'edac_fixes_settings_saved', true, 60 );
		}
	}
}
