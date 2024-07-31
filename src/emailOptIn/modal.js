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

		// create a loop that will wait to find the close button before trying to bind the focus trap
		let attempts = 0;
		const intervalId = setInterval( () => {
			if ( bindFocusTrap() || attempts >= 10 ) {
				clearInterval( intervalId );
			}
			attempts++;
		}, 250 );
	};
};

const bindFocusTrap = () => {
	const modal = document.getElementById( 'TB_window' );
	const closeIcon = modal?.querySelector( '.tb-close-icon' );
	if ( ! modal || ! closeIcon ) {
		return false;
	}

	closeIcon.setAttribute( 'aria-hidden', 'true' );

	const focusTrap = createFocusTrap( modal );
	focusTrap.activate();

	jQuery( document ).one(
		'tb_unload',
		function() {
			onModalClose( focusTrap );
		}
	);

	return true;
};

const onModalClose = ( focusTrap ) => {
	focusTrap.deactivate();

	fetch( window.edac_email_opt_in_form.ajaxurl + '?action=edac_email_opt_in_closed_modal_ajax' )
		.then( ( r ) => r.json() );
};
