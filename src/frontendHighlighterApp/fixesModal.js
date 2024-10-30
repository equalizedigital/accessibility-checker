import { __ } from '@wordpress/i18n';

import { saveFixSettings } from '../common/saveFixSettingsRest';

let focusRestoreTarget = null;
const CloseEvent = new Event( 'edac-fixes-modal-closed', { bubbles: true } );
const changeEventListeners = [];

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
		if ( child.id === 'edac-fixes-modal' || child.classList.contains( 'edac-fixes-modal__overlay' ) ) {
			return;
		}

		if ( child.getAttribute( 'aria-hidden' ) !== true ) {
			child.setAttribute( 'aria-hidden', 'true' );
			child.setAttribute( 'data-hidden-by-modal', 'true' );
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
	// get the element from INSIDE of the modal div edac-fix-settings--clone--wrapper
	const fieldsElement = modal.querySelector( '.edac-fix-settings--clone--wrapper' );
	// get the first direct child of the fieldsElement
	const fields = fieldsElement.children[ 0 ];

	// put the fields back over the placeholder edac-fix-settings--origin-placeholder
	const originPlaceholder = document.querySelector( '.edac-fix-settings--origin-placeholder' );
	originPlaceholder.replaceWith( fields );

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
	unbindChangeEvents();
	document.dispatchEvent( CloseEvent );
};

export const fillFixesModal = ( content = '', fieldsElement = '' ) => {
	if ( '' === fieldsElement ) {
		fieldsElement = document.createElement( 'p' );
		fieldsElement.innerText = __( 'There are no settings to display.', 'accessibility-checker' );
	}
	// create an element from the fixes markup
	const fieldsWrapper = document.createElement( 'div' );
	fieldsWrapper.classList.add( 'edac-fix-settings--clone--wrapper' );
	// put the fieldsElement inside the fieldsWrapper element
	fieldsWrapper.appendChild( fieldsElement );

	// find a data-group-name in the fields
	const groupName = fieldsWrapper.querySelector( '[data-group-name]' )?.getAttribute( 'data-group-name' ) || '';

	const modal = document.getElementById( 'edac-fixes-modal' );
	const modalTitle = modal.querySelector( '#edac-fixes-modal-title' );
	const modalBody = modal.querySelector( '.edac-fixes-modal__body' );

	modalTitle.innerText = groupName;
	modalBody.innerHTML = content;
	modalBody.appendChild( fieldsWrapper );

	modal.querySelectorAll( 'input, select, textarea' ).forEach( ( field ) => {
		const changeListener = () => {
			document.dispatchEvent( new CustomEvent( 'edac-fix-settings-change' ) );
		};
		field.addEventListener( 'change', changeListener );
		changeEventListeners.push( { field, changeListener } );
	} );

	// bind the save button
	const saveButton = modal.querySelector( '.edac-fix-settings--button--save' );
	saveButton.addEventListener( 'click', () => {
		saveFixSettings( modalBody.querySelector( '.edac-fix-settings--fields' ) );
	} );

	// clear the --notice-slot when change event fires
	document.addEventListener( 'edac-fix-settings-change', () => {
		const noticeSlot = modal.querySelector( '[aria-live]' );
		noticeSlot.innerText = '';
	} );
};

/**
 * Helper function to unbind all change events.
 */
const unbindChangeEvents = () => {
	changeEventListeners.forEach( ( { field, changeListener } ) => {
		field.removeEventListener( 'change', changeListener );
	} );
};
