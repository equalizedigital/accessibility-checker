/* eslint-disable */
const edacEditorApp = edac_editor_app;
/* eslint-enable */

import { init as initCheckPage } from './checkPage';
import { init as initApp } from './app';

window.addEventListener( 'DOMContentLoaded', () => {
	const SCANNABLE_POST_TYPE = edacEditorApp.active;

	if ( SCANNABLE_POST_TYPE ) {
		if ( edacEditorApp.authOk === '1' ) {
			setTimeout( function() {
				initCheckPage();
				initApp();
			}, 250 ); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.
		}
	}
} );

