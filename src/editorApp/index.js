import { settings } from './settings';
import { init as initCheckPage } from './checkPage';


window.addEventListener( 'DOMContentLoaded', () => {

	const SCANNABLE_POST_TYPE = edac_editor_app.active;

	if (SCANNABLE_POST_TYPE) {

		if (edac_editor_app.authOk === '1' ) {

			setTimeout(function () {
				initCheckPage();
			}, 250); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.
	
		}


	}


});


