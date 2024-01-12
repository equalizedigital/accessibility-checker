import { settings } from './settings';
import { init as initCheckPage } from './checkPage';
import { showNotice } from './../common/helpers';



window.addEventListener('DOMContentLoaded', () => {

	const SCANNABLE_POST_TYPE = edac_editor_app.active;

	if (SCANNABLE_POST_TYPE && settings.JS_SCAN_ENABLED) {

		if (edac_editor_app.pro === '1' || edac_editor_app.basicAuth !== '1') {

			setTimeout(function () {
				initCheckPage();
			}, 250); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.

		} else {


			//Listen for dispatches from the wp data store so we can trap the update/publish event
			let saving = false;
			let autosaving = false;


			if (wp.data !== undefined && wp.data.subscribe !== undefined) {
				wp.data.subscribe(() => {

					
					if (wp.data.select('core/editor').isAutosavingPost()) {
						autosaving = true;
					}

					// Rescan the page if user saves post
					if (wp.data.select('core/editor').isSavingPost()) {
					
						saving = true;
					} else {
						if (saving) {
							saving = false;

							if (edac_editor_app.pro !== '1' || edac_editor_app.basicAuth === '1') {
								showNotice({
									msg: 'Whoops! It looks like your website is currently password protected. The free version of Accessibility Checker can only scan live websites. To scan this website for accessibility problems either remove the password protection or follow the link below to upgrade to Accessibility Checker Pro.',
									type: 'warning',
									url: 'https://equalizedigital.com/accessibility-checker/pricing/',
									label: 'Upgrade',
									closeOthers: true
								});
						
							}
						
						}
					}

				});

			} else {
				debug("Gutenberg is not enabled.");
			}


		
		}


	}


});


