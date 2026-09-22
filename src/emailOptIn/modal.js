/**
 * Handle the opt-in modal for first time visitors to welcome page.
 *
 * This relies on the Thickbox library that is included in WordPress core which relies on jQuery.
 */

/* global tb_show, tb_remove */

import { createFocusTrap } from 'focus-trap';

// Ensure the global variable is defined.
window.edac_email_opt_in_form = window.edac_email_opt_in_form || {};

const getOptInModal = () => {
	const modal = document.getElementById( 'TB_window' );
	modal?.classList.add( 'edac-email-opt-in-modal' );

	return modal;
};

export const initOptInModal = () => {
	window.addEventListener( 'load', () => {
		window.addEventListener( 'mousemove', triggerModal, { once: true } );
		window.addEventListener( 'scroll', triggerModal, { once: true } );
	} );
};

const triggerModal = ( () => {
	let hasRun = false;

	return () => {
		if ( hasRun ) {
			return;
		}
		hasRun = true;

		tb_show( 'Accessibility Checker', '#TB_inline?width=600&inlineId=edac-opt-in-modal', null );
		getOptInModal();

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
	const modal = getOptInModal();
	const closeIcon = modal?.querySelector( '.tb-close-icon' );
	if ( ! modal || ! closeIcon ) {
		return false;
	}

	closeIcon.setAttribute( 'aria-hidden', 'true' );

	// Core Thickbox does not expose #TB_window as a dialog, so add the
	// semantics here once it exists. The accessible name comes from the
	// Thickbox title element that core renders.
	modal.setAttribute( 'role', 'dialog' );
	modal.setAttribute( 'aria-modal', 'true' );
	modal.setAttribute( 'aria-labelledby', 'TB_ajaxWindowTitle' );

	const focusTrap = createFocusTrap( modal );
	focusTrap.activate();

	// The focus trap only covers Tab, so also make the page behind the modal
	// inert to stop screen reader virtual cursors from reaching it.
	const inertElements = makeBackgroundInert();

	jQuery( document ).one(
		'tb_unload',
		function() {
			onModalClose( focusTrap, inertElements );
		}
	);

	return true;
};

/**
 * Make everything outside the Thickbox window and overlay inert.
 *
 * Thickbox appends its window and overlay directly to the body, so every other
 * body child is background content. Elements that were already inert are left
 * alone so closing the modal doesn't un-inert them.
 *
 * @return {Element[]} The elements that were made inert.
 */
const makeBackgroundInert = () => {
	const inertElements = Array.from( document.body.children ).filter(
		( element ) => ! [ 'TB_window', 'TB_overlay' ].includes( element.id ) &&
			! [ 'SCRIPT', 'STYLE', 'LINK' ].includes( element.tagName ) &&
			! element.hasAttribute( 'inert' )
	);

	inertElements.forEach( ( element ) => element.setAttribute( 'inert', '' ) );

	return inertElements;
};

const onModalClose = ( focusTrap, inertElements ) => {
	// Restore the background before deactivating the trap so focus can return
	// to the element that had it before the modal opened.
	inertElements.forEach( ( element ) => element.removeAttribute( 'inert' ) );
	focusTrap.deactivate();

	fetch( window.edac_email_opt_in_form.ajaxurl + '?action=edac_email_opt_in_closed_modal_ajax' )
		.then( ( r ) => r.json() );
};
