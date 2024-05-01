<?php
/**
 * Handler for various email opt-in actions.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\OptIn;

/**
 * Handler for email opt-in actions like producing the markup for the form and modal where needed.
 *
 * @since 1.11.0
 */
class Email_Opt_In {

	const EDAC_USER_OPTIN_META_KEY       = 'edac_email_optin';
	const EDAC_USER_OPTIN_SEEN_MODAL_KEY = 'edac_email_optin_seen_modal';

	/**
	 * Checks if the current user already opted in.
	 */
	public static function user_already_subscribed(): bool {
		return (bool) get_user_meta(
			get_current_user_id(),
			self::EDAC_USER_OPTIN_META_KEY,
			true
		);
	}

	/**
	 * Checks if the current user should see the opt-in modal.
	 *
	 * This meta is created when the plugin is activated for the user that activated.
	 *
	 * @return bool
	 */
	public static function should_show_modal(): bool {
		return ! (bool) get_user_meta(
			get_current_user_id(),
			self::EDAC_USER_OPTIN_SEEN_MODAL_KEY,
			true
		);
	}

	/**
	 * Enqueues the scripts needed for the opt-in modal.
	 *
	 * The modal relies on thickbox which relies on jQuery. The modal
	 * is triggered by a custom JS bundle that handles the focus trap.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( 'email-opt-in-form', plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/css/emailOptIn.css', false, EDAC_VERSION, 'all' );
		wp_enqueue_script( 'email-opt-in-form', plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/emailOptIn.bundle.js', false, EDAC_VERSION, true );

		wp_localize_script(
			'email-opt-in-form',
			'edac_email_opt_in_form',
			[
				'nonce'     => wp_create_nonce( 'ajax-nonce' ),
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'showModal' => self::should_show_modal(),
			]
		);

		if ( self::should_show_modal() ) {
			// user modal needs thickbox (and thus jquery).
			add_thickbox();
			// Also needs to output the markup for use in the modal.
			add_action( 'admin_footer', [ $this, 'render_modal' ] );
		}
	}

	/**
	 * Renders the opt-in modal markup that thickbox plucks content from to display.
	 *
	 * @return void
	 */
	public function render_modal(): void {
		?>
		<div id="edac-opt-in-modal" style="display:none;">
			<div class="edac-opt-in-modal-content">
				<?php self::render_form(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the actual form markup pre-filled with user data.
	 *
	 * @return void
	 */
	public static function render_form(): void {

		$current_user = wp_get_current_user();
		?>
		<div class="edac-panel edac-mt-1 edac-pb-3">
		<form method="POST" action="https://equalizedigital.activehosted.com/proc.php" id="_form_1_" class="_form _form_1 _inline-form  _dark" novalidate data-styles-version="4">
			<input type="hidden" name="u" value="1" />
			<input type="hidden" name="f" value="1" />
			<input type="hidden" name="s" />
			<input type="hidden" name="c" value="0" />
			<input type="hidden" name="m" value="0" />
			<input type="hidden" name="act" value="sub" />
			<input type="hidden" name="v" value="2" />
			<input type="hidden" name="or" value="b39789e838d79bbdbe094b6ec4b523cf" />
			<div class="_form-content">
				<div class="_form_element _x66524317 _full_width _clear" >
					<h2 class="_form-title">
						<?php esc_html_e( 'Accessibility Events &amp; News in Your Inbox', 'accessibility-checker' ); ?>
					</h2>
				</div>
				<div class="_form_element _x20909711 _full_width _clear" >
					<div class="_html-code">
						<p>
							<?php esc_html_e( 'Subscribe to Equalize Digital\'s email list to get access to free accessibility webinars and training resources.', 'accessibility-checker' ); ?>
						</p>
					</div>
				</div>
				<div class="_form_element _x35928214 _full_width " >
					<label for="email" class="_form-label">
						<?php esc_html_e( 'Email*', 'accessibility-checker' ); ?>
					</label>
					<div class="_field-wrapper">
						<input type="text" id="email" name="email"
							placeholder="<?php esc_attr_e( 'Type your email', 'accessibility-checker' ); ?>"
							value="<?php echo esc_attr( ! empty( $current_user->user_email ) ? $current_user->user_email : '' ); ?>"
							required
						/>
					</div>
				</div>
				<div class="_form_element _x31419797 _full_width edac-mt-1" >
					<label for="firstname" class="_form-label">
						<?php esc_html_e( 'First Name', 'accessibility-checker' ); ?>
					</label>
					<div class="_field-wrapper">
						<input type="text" id="firstname" name="firstname"
							placeholder="<?php esc_attr_e( 'Type your first name', 'accessibility-checker' ); ?>"
							value="<?php echo esc_attr( ! empty( $current_user->first_name ) ? $current_user->first_name : '' ); ?>"
						/>
					</div>
				</div>
				<div class="_button-wrapper _full_width edac-mt-3 edac-mb-3">
					<button id="_form_1_submit" class="_submit button button-primary" type="submit">
						Subscribe
					</button>
				</div>
				<div class="_clear-element">
				</div>
			</div>
			<div class="_form-thank-you" style="display:none;" aria-live="polite" id="polite-announcement">
			</div>
		</form>
		</div>
		<?php
	}

	/**
	 * Registers the ajax handlers for dealing with opt-in subscribe and modal showing.
	 *
	 * @return void
	 */
	public function register_ajax_handlers() {
		add_action( 'wp_ajax_edac_email_opt_in_ajax', [ $this, 'handle_email_opt_in' ] );
		add_action( 'wp_ajax_edac_email_opt_in_closed_modal_ajax', [ $this, 'handle_email_opt_in_closed_modal' ] );
	}

	/**
	 * Handle AJAX request to opt in to email
	 *
	 * Once users have opted in they should no longer see an opt-in form.
	 *
	 * @return void
	 */
	public function handle_email_opt_in() {
		update_user_meta( get_current_user_id(), 'edac_email_optin', true );
		wp_send_json( 'success' );
	}

	/**
	 * Handle AJAX request to indicate user has seen the email opt-in modal and closed it.
	 *
	 * This is used to prevent the modal from showing again for the current user.
	 *
	 * @return void
	 */
	public function handle_email_opt_in_closed_modal() {
		update_user_meta( get_current_user_id(), self::EDAC_USER_OPTIN_SEEN_MODAL_KEY, true );
		wp_send_json( 'success' );
	}
}
