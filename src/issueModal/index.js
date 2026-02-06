/**
 * Issue Modal Bundle
 *
 * This bundle exports the IssueDetailsModal component and related utilities
 * for use in other parts of the application (e.g., admin pages, metaboxes).
 */

// Import styles
import './sass/issue-modal.scss';

import { createElement, render } from '@wordpress/element';
import { IssueDetailsModal } from './components/IssueDetailsModal';

// Export the main component and utilities
export { IssueDetailsModal, default } from './components/IssueDetailsModal';
export { default as IssueImage, extractImageUrls } from './components/IssueImage';
export { toggleIssueDismiss } from './api';
export { openIssueModal, closeIssueModal } from './global';

const MODAL_CONTAINER_ID = 'edac-issue-modal-root';

// Global flags for pending actions - set by IssueDetailsModal, read by handleClose
let pendingRescan = false;
let pendingRefetch = false;

// Exported functions to set the pending flags from the component
export const setPendingRescan = ( value ) => {
	pendingRescan = value;
};

export const setPendingRefetch = ( value ) => {
	pendingRefetch = value;
};

const ensureModalContainer = () => {
	let container = document.getElementById( MODAL_CONTAINER_ID );

	if ( ! container ) {
		container = document.createElement( 'div' );
		container.id = MODAL_CONTAINER_ID;
		document.body.appendChild( container );
	}

	return container;
};

const defaultState = {
	isOpen: false,
	issue: null,
	rule: null,
	focusSection: null,
	onIgnore: null,
};

let modalState = { ...defaultState };
let isClosing = false; // Guard to prevent multiple close calls

const renderModal = () => {
	const container = ensureModalContainer();
	render(
		createElement( IssueDetailsModal, {
			...modalState,
			onClose: handleClose,
		} ),
		container,
	);
};

const handleClose = () => {
	if ( isClosing ) {
		return;
	}
	isClosing = true;

	if ( pendingRefetch && ! pendingRescan ) {
		const event = new CustomEvent( 'edac-ignore-updated', {
			detail: { pending: true },
		} );
		window.dispatchEvent( event );
		pendingRefetch = false;
	}

	if ( pendingRescan ) {
		const rescanEvent = new CustomEvent( 'edac-fix-settings-saved', {
			detail: { success: true },
		} );
		document.dispatchEvent( rescanEvent );
		pendingRescan = false;
	}

	setTimeout( () => {
		const container = document.getElementById( MODAL_CONTAINER_ID );
		if ( container ) {
			render( null, container );
		}
		modalState = { ...defaultState };
		isClosing = false;
	}, 0 );
};

const openIssueModal = ( { issue, rule, focusSection = null, onIgnore = null } ) => {
	isClosing = false;
	modalState = {
		...modalState,
		isOpen: true,
		issue,
		rule,
		focusSection,
		onIgnore,
	};
	renderModal();
};

const closeIssueModal = () => {
	modalState = { ...defaultState };
};

// Expose a global API for opening the issue modal.
if ( typeof window !== 'undefined' ) {
	window.edacIssueModal = {
		open: openIssueModal,
		close: closeIssueModal,
	};
}
