/**
 * Handle the opt-in modal for first time visitors to welcome page.
 *
 * This relies on the Thickbox library that is included in WordPress core which relies on jQuery.
 */

/* global tb_show, tb_remove */

import { createFocusTrap } from 'focus-trap';

// Ensure the global variable is defined.
window.edac_email_opt_in_form = window.edac_email_opt_in_form || {};

export const initOptInModal = () => {
	window.onload = function() {
		window.addEventListener( 'mousemove', triggerModal, { once: true } );
		window.addEventListener( 'scroll', triggerModal, { once: true } );
	};
};

const triggerModal = ( () => {
	let hasRun = false;

	return () => {
		if ( hasRun ) {
			return;
		}
		hasRun = true;

		tb_show( 'Accessibility Checker', '#TB_inline?width=600&inlineId=edac-opt-in-modal', null );

		// Loop and check for the close button before trying to bind the focus trap.
		let attempts = 0;
		const intervalId = setInterval( () => {
			if ( bindFocusTrap() ) {
				clearInterval( intervalId );
			}
			// Some browsers (firefox) have popup blocking settings that makes the modal
			// content empty and so the button will never be found. To prevent users from
			// being stuck in a modal we will close it after 10 attempts.
			if ( attempts >= 10 ) {
				clearInterval( intervalId );
				tb_remove();
				return;
			}
			attempts++;
		}, 250 );
	};
} )();

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
