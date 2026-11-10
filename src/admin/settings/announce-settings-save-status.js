/**
 * Focus a successful save confirmation, or announce the save result.
 *
 * @param {Object} a11y          WordPress accessibility utilities.
 * @param {Window} browserWindow Browser window used to schedule the announcement.
 */
export const announceSettingsSaveStatus = ( a11y, browserWindow = window ) => {
	const notice = document.querySelector( '.edac-settings #setting-error-settings_updated' );

	if ( ! notice ) {
		return;
	}

	const confirmation = notice.querySelector( 'p' );
	const message = ( confirmation || notice ).textContent.trim();
	if ( ! message ) {
		return;
	}

	const isSuccess = notice.classList.contains( 'notice-success' );
	const politeness = notice.classList.contains( 'notice-error' ) ? 'assertive' : 'polite';
	const presentAfterPageLoad = () => {
		// Let WordPress finish relocating notices before focusing their content.
		browserWindow.setTimeout( () => {
			if ( ! notice.isConnected ) {
				return;
			}

			if ( isSuccess && confirmation && document.activeElement === document.body ) {
				confirmation.setAttribute( 'tabindex', '-1' );
				confirmation.focus();

				if ( document.activeElement === confirmation ) {
					// Focus supplies the announcement; do not also queue it in a live region.
					return;
				}
			}

			if ( a11y && typeof a11y.speak === 'function' ) {
				a11y.speak( message, politeness );
			}
		}, isSuccess ? 0 : 1000 );
	};

	if ( document.readyState === 'complete' ) {
		presentAfterPageLoad();
		return;
	}

	browserWindow.addEventListener( 'load', presentAfterPageLoad, { once: true } );
};
