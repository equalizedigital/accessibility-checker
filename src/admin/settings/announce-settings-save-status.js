// Settings API save notice, then the connector notice shown after enabling or disabling email reports.
const NOTICE_IDS = [ 'setting-error-settings_updated', 'edac-connector-notice' ];

/**
 * Announce the result of saving Accessibility Checker settings.
 *
 * @param {Object} a11y          WordPress accessibility utilities.
 * @param {Window} browserWindow Browser window used to schedule the announcement.
 */
export const announceSettingsSaveStatus = ( a11y, browserWindow = window ) => {
	const notice = NOTICE_IDS.map( ( id ) => document.getElementById( id ) ).find( Boolean );

	if ( ! notice || ! a11y || typeof a11y.speak !== 'function' ) {
		return;
	}

	const message = notice.textContent.trim();
	if ( ! message ) {
		return;
	}

	const politeness = notice.classList.contains( 'notice-error' ) ? 'assertive' : 'polite';
	const speakAfterPageLoad = () => {
		// Give assistive technology time to finish announcing the newly loaded page.
		browserWindow.setTimeout( () => {
			a11y.speak( message, politeness );
		}, 1000 );
	};

	if ( document.readyState === 'complete' ) {
		speakAfterPageLoad();
		return;
	}

	browserWindow.addEventListener( 'load', speakAfterPageLoad, { once: true } );
};
