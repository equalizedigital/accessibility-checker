/**
 * Handle the opt-in modal for first time visitors to welcome page.
 *
 * This relies on the Thickbox library that is included in WordPress core which relies on jQuery.
 */

/* global tb_show */

import { createFocusTrap } from 'focus-trap';

// Ensure the global variable is defined.
window.edac_email_opt_in_form = window.edac_email_opt_in_form || {};

export const initOptInModal = () => {
	window.onload = function() {
		tb_show( 'Accessibility Checker', '#TB_inline?width=600&inlineId=edac-opt-in-modal', null );

		// a small delay is needed to ensure the modal is fully loaded before creating a focus trap.
		setTimeout(
			function() {
				document.getElementById( 'TB_window' ).querySelector( 'input' ).focus();

				const focusTrap = createFocusTrap( '#TB_window' );
				focusTrap.activate();

				jQuery( document ).off( 'tb_unload' ).on(
					'tb_unload',
					function() {
						onModalClose( focusTrap );
					}
				);
			},
			200
		);
	};
};

const onModalClose = ( focusTrap ) => {
	focusTrap.deactivate();

	fetch( window.edac_email_opt_in_form.ajaxurl + '?action=edac_email_opt_in_closed_modal_ajax' )
		.then( ( r ) => r.json() );
};
