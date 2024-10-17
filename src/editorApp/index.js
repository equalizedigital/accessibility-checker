/* global edac_editor_app */

import { init as initCheckPage } from './checkPage';

window.addEventListener( 'DOMContentLoaded', () => {
	// eslint-disable-next-line camelcase
	const SCANNABLE_POST_TYPE = edac_editor_app.active;

	if ( SCANNABLE_POST_TYPE ) {
		// eslint-disable-next-line camelcase
		if ( edac_editor_app.authOk === '1' ) {
			setTimeout( function() {
				initCheckPage();
			}, 250 ); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.
		}
	}

	document.addEventListener( 'edac-fix-settings-saved', function( event ) {
		if ( event.detail.success ) {
			// refresh the summary tab after saving fixes and running the php scan.
			initCheckPage();
		}
	} );
} );

