<?php
/**
 * Class file for admin notices
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

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
		add_action( 'init', [ $this, 'hook_notices' ] );
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
		add_action( 'wp_ajax_edac_black_friday_notice_ajax', [ $this, 'edac_black_friday_notice_ajax' ] );
		add_action( 'admin_notices', [ $this, 'edac_gaad_notice' ] );
		add_action( 'wp_ajax_edac_gaad_notice_ajax', [ $this, 'edac_gaad_notice_ajax' ] );
		add_action( 'admin_notices', [ $this, 'edac_review_notice' ] );
		add_action( 'wp_ajax_edac_review_notice_ajax', [ $this, 'edac_review_notice_ajax' ] );
		add_action( 'admin_notices', [ $this, 'edac_password_protected_notice' ] );
		add_action( 'wp_ajax_edac_password_protected_notice_ajax', [ $this, 'edac_password_protected_notice_ajax' ] );
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

		// check if accessibility checker pro is active.
		$pro = is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' );
		if ( $pro ) {
			return;
		}

		// Get the value of the 'edac_gaad_notice_dismiss' option and sanitize it.
		$dismissed = absint( get_option( 'edac_black_friday_2023_notice_dismiss', 0 ) );

		// Check if the notice has been dismissed.
		if ( $dismissed ) {
			return;
		}

		// Show from November 20-30.
		$current_date = date_i18n( 'Ymd' ); // Use date_i18n for localization.
		$start_date   = '20231120';
		$end_date     = '20231130';

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
		$message .= esc_html__( 'Upgrade to a paid version of Accessibility Checker from November 20-30 and get 40% off! Full site scanning, site-wide open issues report, ignore logs, and more.', 'accessibility-checker' ) . '<br />';
		$message .= '<a class="button button-primary" href="' . esc_url( 'https://my.equalizedigital.com/support/pre-sale-questions/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=BlackFriday' ) . '">' . esc_html__( 'Ask a Pre-Sale Question', 'accessibility-checker' ) . '</a> ';
		$message .= '<a class="button button-primary" href="' . esc_url( 'https://equalizedigital.com/accessibility-checker/pricing/?utm_source=WPadmin&utm_medium=banner&utm_campaign=BlackFriday' ) . '">' . esc_html__( 'Upgrade Now', 'accessibility-checker' ) . '</a></p>';
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

			$error = new \WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );

		}

		$results = update_option( 'edac_black_friday_2023_notice_dismiss', true );

		if ( ! $results ) {

			$error = new \WP_Error( '-2', 'Update option wasn\'t successful' );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $results ) );
	}

	/**
	 * GAAD Notice
	 *
	 * @return string
	 */
	public function edac_gaad_notice() {

		// Define constants for start and end dates.
		define( 'EDAC_GAAD_NOTICE_START_DATE', '2024-05-16' );
		define( 'EDAC_GAAD_NOTICE_END_DATE', '2024-05-23' );

		// Check if Accessibility Checker Pro is active.
		$pro = is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' );
		if ( $pro ) {
			return;
		}

		// Get the value of the 'edac_gaad_notice_dismiss' option and sanitize it.
		$dismissed = absint( get_option( 'edac_gaad_notice_dismiss_2024', 0 ) );

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
		$message .= esc_html__( 'Use coupon code GAAD24 from May 16th-May 23rd to get access to full-site scanning and other pro features at a special discount. Not sure if upgrading is right for you?', 'accessibility-checker' ) . '<br />';
		$message .= '<a class="button button-primary" href="' . esc_url( 'https://my.equalizedigital.com/support/pre-sale-questions/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=GAAD24' ) . '">' . esc_html__( 'Ask a Pre-Sale Question', 'accessibility-checker' ) . '</a> ';
		$message .= '<a class="button button-primary" href="' . esc_url( 'https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=GAAD24' ) . '">' . esc_html__( 'Upgrade Now', 'accessibility-checker' ) . '</a></p>';
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

			$error = new \WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );

		}

		$results = update_option( 'edac_gaad_notice_dismiss_2024', true );
		// delete old meta key.
		delete_option( 'edac_gaad_notice_dismiss' );

		if ( ! $results ) {

			$error = new \WP_Error( '-2', 'Update option wasn\'t successful' );
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
				<button class="edac-review-notice-review"><?php esc_html_e( 'Write A Review', 'accessibility-checker' ); ?></button>
				<button class="edac-review-notice-remind"><?php esc_html_e( 'Remind Me In Two Weeks', 'accessibility-checker' ); ?></button>
				<button class="edac-review-notice-dismiss"><?php esc_html_e( 'Never Ask Again', 'accessibility-checker' ); ?></button>
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

			$error = new \WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['review_action'] ) ) {

			$error = new \WP_Error( '-2', 'The review action value was not set' );
			wp_send_json_error( $error );

		}

		$results = update_option( 'edac_review_notice', sanitize_text_field( $_REQUEST['review_action'] ) );

		if ( 'pause' === $_REQUEST['review_action'] ) {
			set_transient( 'edac_review_notice_reminder', true, 14 * DAY_IN_SECONDS );
		}

		if ( ! $results ) {

			$error = new \WP_Error( '-3', 'Update option wasn\'t successful' );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $results ) );
	}

	/**
	 * Password Protected Notice Text
	 *
	 * @return string
	 */
	public function edac_password_protected_notice_text() {
		/**
		 * Filter the password protected notice text.
		 *
		 * @since 1.4.0
		 *
		 * @param string $text The password protected notice text.
		 */
		return apply_filters(
			'edac_filter_password_protected_notice_text',
			sprintf(
				// translators: %s is the link to upgrade to pro, with "upgrade to pro" as the anchor text.
				esc_html__( 'Whoops! It looks like your website is currently password protected. The free version of Accessibility Checker can only scan live websites. To scan this website for accessibility problems either remove the password protection or %s. Scan results may be stored from a previous scan.', 'accessibility-checker' ),
				sprintf(
					'<a href="https://equalizedigital.com/accessibility-checker/pricing/" target="_blank" aria-label="%1$s">%2$s</a>',
					esc_attr__( 'Upgrade to accessibility checker pro. Opens in a new window.', 'accessibility-checker' ),
					esc_html__( 'upgrade to pro', 'accessibility-checker' )
				)
			)
		);
	}

	/**
	 * Password Protected Notice
	 *
	 * @return string
	 */
	public function edac_password_protected_notice() {
		if ( (bool) get_option( 'edac_password_protected' )
			&& ! (bool) get_option( 'edac_password_protected_notice_dismiss' )
		) {
			echo wp_kses( '<div class="edac_password_protected_notice notice notice-error is-dismissible"><p>' . $this->edac_password_protected_notice_text() . '</p></div>', 'post' );
			return;
		}
	}

	/**
	 * Password Protected Admin Notice Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that update option wasn't successful
	 */
	public function edac_password_protected_notice_ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );

		}

		$results = update_option( 'edac_password_protected_notice_dismiss', true );

		if ( ! $results ) {

			$error = new \WP_Error( '-2', 'Update option wasn\'t successful' );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $results ) );
	}
}
