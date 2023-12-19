import { settings }  from './settings';
import { info, debug } from './helpers';
import { showNotice } from './../common/helpers';
import { init as initCheckPage } from './checkPage';


window.addEventListener('DOMContentLoaded', () => {

	const SCANNABLE_POST_TYPE = edac_editor_app.active;
			
	if(SCANNABLE_POST_TYPE && settings.JS_SCAN_ENABLED){


		if(edac_editor_app.pro === '1'){
	
			// Use checkApi from pro instead.
			setTimeout(function(){
				initCheckPage();
			}, 250); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.

		} else {

			const API_URL = edac_editor_app.edacApiUrl;
			const HEADERS = edac_editor_app.edacHeaders;
			
	
			const checkApi = async () => {
				try {
				  const response = await fetch(API_URL + '/test', {
					method: "POST",
					headers: HEADERS
				  });
			  
				  return response.status;
				} catch (error) {
				  return 401; // Default status for error
				}
			};
	

			checkApi().then((status) => {

				if (status > 400) {
		
					if (status == 401) {
		
						showNotice({
							msg: 'Whoops! It looks like your website is currently password protected. The free version of Accessibility Checker can only scan live websites. To scan this website for accessibility problems either remove the password protection or follow the link below to upgrade to Accessibility Checker Pro.',
							type: 'warning',
							url: 'https://equalizedigital.com/accessibility-checker/pricing/',
							label: 'Upgrade',
							closeOthers: true
						});
		
					} else {
						showNotice({
							msg: 'Whoops! It looks like there was a problem connecting to the WordPress REST API which is required by Accessibility Checker. Follow the link below for more information:',
							type: 'warning',
							url: 'https://developer.wordpress.org/rest-api/frequently-asked-questions',
							label: 'Rest API',
							closeOthers: true
						});
	
						debug('Error: Cannot connect to API. Status code is: ' + status);
		
					}
				} else {

					setTimeout(function(){
						initCheckPage();
					}, 250); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.
			
				}
			
			}).catch((error) => {
			
				
				showNotice({
					msg: 'Whoops! It looks like there was a problem connecting to the WordPress REST API which is required by Accessibility Checker. Follow the link below for more information:',
					type: 'warning',
					url: 'https://developer.wordpress.org/rest-api/frequently-asked-questions',
					label: 'Rest API',
					closeOthers: true
				});

				debug(error);
				
			});
		


		


		}
	

	
	}
});


