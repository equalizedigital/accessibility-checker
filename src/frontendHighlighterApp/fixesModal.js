import { __ } from '@wordpress/i18n';

import { saveFixSettings } from '../common/saveFixSettingsRest';

let focusRestoreTarget = null;
const CloseEvent = new Event( 'edac-fixes-modal-closed', { bubbles: true } );

const buildFixesModalBase = () => {
	// Create the modal
	const modal = document.createElement( 'div' );
	modal.id = 'edac-fixes-modal';
	modal.classList.add( 'edac-fixes-modal' );
	modal.setAttribute( 'role', 'dialog' );
	modal.setAttribute( 'aria-modal', 'false' );
	modal.setAttribute( 'aria-labelledby', 'edac-fixes-modal-title' );
	modal.innerHTML = `
		<div class="edac-fixes-modal__content">
			<div class="edac-fixes-modal__header">
				<h2 id="edac-fixes-modal-title">${ __( 'Fix Settings', 'accessibility-checker' ) }</h2>
				<button class="edac-fixes-modal__close" aria-label="${ __( 'Close fixes modal', 'accessibility-checker' ) }">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="edac-fixes-modal__body">
			</div>
		</div>
	`;

	// create an overlay to prevent interaction with the page while the modal is open
	const overlay = document.createElement( 'div' );
	overlay.classList.add( 'edac-fixes-modal__overlay' );
	overlay.setAttribute( 'aria-hidden', 'true' );
	overlay.setAttribute( 'tabindex', '-1' );
	document.body.appendChild( overlay );
	document.body.appendChild( modal );
};

const bindListenersForFixesModal = () => {
	const button = document.querySelector( '.edac-fixes-modal__close' );
	button.addEventListener( 'click', () => {
		closeFixesModal();
	} );

	// listen to keydown events and close the modal if the escape key is pressed if the focus is within i
	document.addEventListener( 'keydown', ( event ) => {
		// if focus is within the modal
		if ( document.activeElement.closest( '.edac-fixes-modal' ) ) {
			if ( 'Escape' === event.key ) {
				closeFixesModal();
			}
		}
	} );
};

export const fixSettingsModalInit = () => {
	buildFixesModalBase();
	bindListenersForFixesModal();
};

export const openFixesModal = ( openingElement ) => {
	const modal = document.getElementById( 'edac-fixes-modal' );
	modal.classList.add( 'edac-fixes-modal--open' );
	modal.setAttribute( 'aria-hidden', 'false' );
	modal.setAttribute( 'aria-modal', 'true' );

	// get all other imediate children of the body and set their aria-hidden to true
	const bodyChildren = Array.from( document.body.children );
	bodyChildren.forEach( ( child ) => {
		if ( child !== modal || ! child.classList.contains( 'edac-fixes-modal__overlay' ) ) {
			if ( child.getAttribute( 'aria-hidden' ) !== true ) {
				child.setAttribute( 'aria-hidden', 'true' );
				child.setAttribute( 'data-hidden-by-modal', 'true' );
			}
		}
	} );

	document.body.classList.add( 'edac-fixes-modal--open' );
	focusRestoreTarget = openingElement;
	// focus on the first focusable element in the .edac-fixes-modal__body
	const firstFocusableElementInContent = modal.querySelector( '.edac-fixes-modal__body' ).querySelector( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
	if ( firstFocusableElementInContent ) {
		setTimeout( () => {
			firstFocusableElementInContent.focus();
		}, 100 );
	}
	// trap focus inside the modal
	modal.addEventListener( 'keydown', ( event ) => {
		if ( 'Tab' === event.key ) {
			const focusableElements = modal.querySelectorAll( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
			const firstFocusableElement = focusableElements[ 0 ];
			const lastFocusableElement = focusableElements[ focusableElements.length - 1 ];
			if ( event.shiftKey && document.activeElement === firstFocusableElement ) {
				event.preventDefault();
				lastFocusableElement.focus();
			} else if ( ! event.shiftKey && document.activeElement === lastFocusableElement ) {
				event.preventDefault();
				firstFocusableElement.focus();
			}
		}
	} );
};

const closeFixesModal = () => {
	const modal = document.getElementById( 'edac-fixes-modal' );
	modal.classList.remove( 'edac-fixes-modal--open' );
	modal.setAttribute( 'aria-hidden', 'true' );
	modal.setAttribute( 'aria-modal', 'false' );
	// get all other imediate children of the body and set their aria-hidden to true
	const bodyChildren = Array.from( document.body.children );
	bodyChildren.forEach( ( child ) => {
		if ( child.getAttribute( 'data-hidden-by-modal' ) === 'true' ) {
			child.removeAttribute( 'aria-hidden', 'false' );
			child.removeAttribute( 'data-hidden-by-modal', 'false' );
		}
	} );
	document.body.classList.remove( 'edac-fixes-modal--open' );
	if ( focusRestoreTarget ) {
		focusRestoreTarget.focus();
	}
	document.dispatchEvent( CloseEvent );
};

export const fillFixesModal = ( content = '', fieldsMarkup = '' ) => {
	if ( '' === fieldsMarkup ) {
		fieldsMarkup = `
			<p>There are no settings to display.</p>
		`;
	}
	// create an element from the fixes markup
	const fields = document.createElement( 'div' );
	fields.innerHTML = fieldsMarkup;

	// find a data-group-name in the fields
	const groupName = fields.querySelector( '[data-group-name]' )?.getAttribute( 'data-group-name' ) || '';

	const modal = document.getElementById( 'edac-fixes-modal' );
	const modalTitle = modal.querySelector( '#edac-fixes-modal-title' );
	const modalBody = modal.querySelector( '.edac-fixes-modal__body' );
	modalTitle.innerText = __( 'Fix settings: ', 'accessibility-checker' ) + groupName;
	modalBody.innerHTML = content;
	modalBody.appendChild( fields );

	// bind the save button
	const saveButton = modal.querySelector( '.edac-fix-settings--button--save' );
	saveButton.addEventListener( 'click', () => {
		saveFixSettings( modalBody.querySelector( '.edac-fix-settings--fields' ) );
	} );
};
