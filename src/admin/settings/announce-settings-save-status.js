// Settings API save notice, then the connector notice shown after enabling or disabling email reports.
const NOTICE_SELECTORS = [ '.edac-settings #setting-error-settings_updated', '#edac-connector-notice' ];

/**
 * Focus a notice, or announce it through the live region when focus can't be moved.
 *
 * Errors take focus too, because VoiceOver drops live region messages sent after
 * a reload. Only the first notice takes focus because later ones find it already moved.
 *
 * @param {Element} notice        Notice element.
 * @param {Object}  a11y          WordPress accessibility utilities.
 * @param {Window}  browserWindow Browser window used to schedule the announcement.
 */
const presentNotice = ( notice, a11y, browserWindow ) => {
	const confirmation = notice.querySelector( 'p' );
	const message = ( confirmation || notice ).textContent.trim();
	if ( ! message ) {
		return;
	}

	const isSuccess = notice.classList.contains( 'notice-success' );
	const politeness = notice.classList.contains( 'notice-error' ) ? 'assertive' : 'polite';

	// Let WordPress finish relocating notices before focusing their content.
	browserWindow.setTimeout( () => {
		if ( ! notice.isConnected ) {
			return;
		}

		if ( confirmation && document.activeElement === document.body ) {
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

/**
 * Focus a save or connector notice, or announce it when focus can't be moved.
 *
 * @param {Object} a11y          WordPress accessibility utilities.
 * @param {Window} browserWindow Browser window used to schedule the announcement.
 */
export const announceSettingsSaveStatus = ( a11y, browserWindow = window ) => {
	const notices = NOTICE_SELECTORS.map( ( selector ) => document.querySelector( selector ) ).filter( Boolean );

	if ( ! notices.length ) {
		return;
	}

	const presentAfterPageLoad = () => {
		notices.forEach( ( notice ) => presentNotice( notice, a11y, browserWindow ) );
	};

	if ( document.readyState === 'complete' ) {
		presentAfterPageLoad();
		return;
	}

	browserWindow.addEventListener( 'load', presentAfterPageLoad, { once: true } );
};

/**
 * Announce the license error shown after the license form is submitted.
 *
 * The notice stays on every admin page while the error is stored, so only
 * announce it on the redirect that follows a submit.
 *
 * @param {Object} a11y          WordPress accessibility utilities.
 * @param {Window} browserWindow Browser window used to schedule the announcement.
 */
export const announceLicenseNotice = ( a11y, browserWindow = window ) => {
	const url = new URL( browserWindow.location.href );
	if ( ! url.searchParams.has( 'edac_license_submitted' ) ) {
		return;
	}

	// Drop the flag so a reload or bookmark does not announce it again.
	url.searchParams.delete( 'edac_license_submitted' );
	browserWindow.history.replaceState( browserWindow.history.state, '', url.toString() );

	const notice = document.getElementById( 'edac-license-notice' );
	const message = notice ? notice.textContent.trim() : '';
	if ( ! message || ! a11y || typeof a11y.speak !== 'function' ) {
		return;
	}

	const announce = () => {
		// Wait for screen readers to finish reading the new page first.
		browserWindow.setTimeout( () => {
			if ( ! notice.isConnected ) {
				return;
			}

			const target = notice.querySelector( 'p' ) || notice;
			if ( document.activeElement === document.body ) {
				target.setAttribute( 'tabindex', '-1' );
				target.focus();
				if ( document.activeElement === target ) {
					return;
				}
			}

			a11y.speak( message, 'assertive' );
		}, 1000 );
	};

	if ( document.readyState === 'complete' ) {
		announce();
		return;
	}

	browserWindow.addEventListener( 'load', announce, { once: true } );
};
