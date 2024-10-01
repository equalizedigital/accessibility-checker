
const buildFixesModalBase = () => {
	// Create the modal
	const modal = document.createElement( 'div' );
	modal.id = 'edac-fixes-modal';
	modal.classList.add( 'edac-fixes-modal' );
	modal.setAttribute( 'role', 'dialog' );
	modal.setAttribute( 'aria-modal', 'true' );
	modal.setAttribute( 'aria-labelledby', 'edac-fixes-modal-title' );
	modal.setAttribute( 'aria-describedby', 'edac-fixes-modal-description' );
	modal.setAttribute( 'tabindex', '-1' );
	modal.innerHTML = `
		<div class="edac-fixes-modal__content">
			<div class="edac-fixes-modal__header">
				<h2 id="edac-fixes-modal-title">Fixes</h2>
				<button class="edac-fixes-modal__close" aria-label="Close fixes modal" title="Close fixes modal">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="edac-fixes-modal__body">
				<p id="edac-fixes-modal-description">This is the fixes modal.</p>
			</div>
		</div>
	`;
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

export const openFixesModal = () => {
	const modal = document.getElementById( 'edac-fixes-modal' );
	modal.classList.add( 'edac-fixes-modal--open' );
	modal.setAttribute( 'aria-hidden', 'false' );
};

const closeFixesModal = () => {
	const modal = document.getElementById( 'edac-fixes-modal' );
	modal.classList.remove( 'edac-fixes-modal--open' );
	modal.setAttribute( 'aria-hidden', 'true' );
};

export const fillFixesModal = ( title = 'Fixes', content = 'This is the fixes modal.', fieldsMarkup = '' ) => {
	if ( '' === fieldsMarkup ) {
		fieldsMarkup = `
			<p>There are no settings to display.</p>
		`;
	}
	const modal = document.getElementById( 'edac-fixes-modal' );
	const modalTitle = modal.querySelector( '#edac-fixes-modal-title' );
	const modalBody = modal.querySelector( '.edac-fixes-modal__body' );
	modalTitle.innerText = title;
	modalBody.innerHTML = content + fieldsMarkup;
};
