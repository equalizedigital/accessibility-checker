import { settings } from './settings';
import { init as initCheckPage } from './checkPage';
import { showNotice } from './../common/helpers';



window.addEventListener('DOMContentLoaded', () => {

	const SCANNABLE_POST_TYPE = edac_editor_app.active;

	if (SCANNABLE_POST_TYPE) {

		if (edac_editor_app.pro !== '1' && edac_editor_app.hasAuth === '1') {
			return;
		}

		
		if (settings.JS_SCAN_ENABLED ){
			
			setTimeout(function () {
				initCheckPage();
			}, 250); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.

		}

	}


});


