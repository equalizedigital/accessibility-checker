/**
 * Issue Modal Bundle
 *
 * This bundle exports the IssueDetailsModal component and related utilities
 * for use in other parts of the application (e.g., admin pages, metaboxes).
 */

import { createElement, render } from '@wordpress/element';
import { IssueDetailsModal } from './components/IssueDetailsModal';

// Export the main component and utilities
export { IssueDetailsModal, default } from './components/IssueDetailsModal';
export { default as IssueImage, extractImageUrls } from './components/IssueImage';
export { toggleIssueIgnore } from './api';
export { openIssueModal, closeIssueModal } from './global';

// Import styles
import './sass/issue-modal.scss';

const MODAL_CONTAINER_ID = 'edac-issue-modal-root';

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

const renderModal = () => {
	const container = ensureModalContainer();

	const handleClose = () => {
		modalState = { ...defaultState };
		renderModal();
	};

	render(
		createElement( IssueDetailsModal, {
			...modalState,
			onClose: handleClose,
		} ),
		container,
	);
};

const openIssueModal = ( { issue, rule, focusSection = null, onIgnore = null } ) => {
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
	renderModal();
};

// Expose a global API for opening the issue modal.
if ( typeof window !== 'undefined' ) {
	window.edacIssueModal = {
		open: openIssueModal,
		close: closeIssueModal,
	};
}
