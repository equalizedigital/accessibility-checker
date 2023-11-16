import {info, debug } from './helpers';
import { showNotice } from './../common/helpers';


const API_URL = edac_editor_app.edacApiUrl;

let HEADERS;
if(typeof(edacp_full_site_scan_app) === 'undefined'){
	HEADERS = edac_editor_app.edacHeaders;
} else {
	HEADERS = edacp_full_site_scan_app.edacpHeaders;
}



const checkApi = async () => {

	const response = await fetch( API_URL + '/test', {
		method: "POST",
		headers: HEADERS
	});

	return response.status;

}


const postData = async (url = "", data = {}) => {


	return await fetch(url, {
		method: "POST",
		headers: HEADERS,
		body: JSON.stringify(data),
	}).then((res) => {
		return res.json();
	}).catch(() => {
		return {};
	});

}

const getData = async (url = "") => {

	return await fetch(url, {
		method: "GET",
		headers: HEADERS
	}).then((res) => {
		return res.json();
	}).catch(() => {
		return {};
	});

}




const saveScanResults = (postId, violations) => {

	// Confirm api service is working.
	checkApi().then((status) => {

		if (status > 400) {

			if (status == 401) {


				showNotice({
					msg: 'Whoops! It looks like your website is currently password protected. The free version of Accessibility Checker can only scan live websites. To scan this website for accessibility problems either remove the password protection or {link}. Scan results may be stored from a previous scan.',
					type: 'warning',
					url: 'https://equalizedigital.com/accessibility-checker/pricing/',
					label: 'upgrade to Accessibility Checker Pro',
					closeOthers: true
				});

			} else {
				showNotice({
					msg: 'Whoops! It looks like there was a problem connecting to the {link} which is required by Accessibility Checker.',
					type: 'warning',
					url: 'https://developer.wordpress.org/rest-api/frequently-asked-questions',
					label: 'Rest API',
					closeOthers: true
				});

				debug('Error: Cannot connect to API. Status code is: ' + status);

			}

		} else {

			info('checkPage: Saving ' + postId + ': started');

			document.querySelector(".edac-panel").classList.add("edac-panel-loading");
      
			// Api is fine so we can send the scan results.
			postData(edac_editor_app.edacApiUrl + '/post-scan-results/' + postId, {
				violations: violations
			}).then((data) => {

		
				info('checkPage: Saving ' + postId + ': done');

				// Create and dispatch an event to tell legacy admin.js to refresh tabs. Refactor this. 
				var customEvent = new CustomEvent('edac_js_scan_save_complete');
				top.dispatchEvent(customEvent);
		

				if (!data.success) {

					info('Saving ' + postId + ': error');

					showNotice({
						msg: 'Whoops! It looks like there was a problem updating. Please try again.',
						type: 'warning'
					});

				}

				document.querySelector(".edac-panel").classList.add("edac-panel-loading");
      
			});

		};

	}).catch((error) => {
		info('Saving ' + postId + ': error');

		debug(error);
		showNotice({
			msg: 'Whoops! It looks like there was a problem updating. Please try again.',
			type: 'warning'
		});

	});

}



const injectIframe = (previewUrl, postID) => {


	// Create an iframe offscreen to load the preview of the page.

	// Gen unique id for this iframe
	const timestamp = new Date().getTime();
	const randomNumber = Math.floor(Math.random() * 1000);
	const uniqueId = 'iframe' + '_' + timestamp + '_' + randomNumber;

	// inject the iframe
	const iframe = document.createElement('iframe');
	iframe.setAttribute('id', uniqueId);
	iframe.setAttribute('src', previewUrl);
	iframe.style.width = screen.width + 'px';
	iframe.style.height = screen.height + 'px';
	iframe.style.position = 'absolute';
	iframe.style.left = '-' + screen.width + 'px';

	document.body.append(iframe);


	// Wait for the preview to load & inject the pageScanner script.
	iframe.addEventListener("load", function (e) {

		// Access the contentDocument of the iframe.
		var iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

		// Pass the postID and iframe id into the document so we can reference them from the document.
		const body = iframeDocument.querySelector('body');
		body.setAttribute('data-iframe-id', uniqueId);
		body.setAttribute('data-iframe-event-name', 'edac_scan_complete');
		body.setAttribute('data-iframe-post-id', postID);
		
		// inject the scanner app.
		var scriptElement = iframeDocument.createElement('script');
		scriptElement.src = edac_editor_app.baseurl + '/build/pageScanner.bundle.js';
		iframeDocument.head.appendChild(scriptElement);

	});

}


export const init = () => {

	//TODO: migrate to pro
//	if (
		//		(edac_editor_app.mode === 'editor-scan' || edac_editor_app.mode === 'full-scan')
//		(edac_editor_app.mode === 'editor-scan')

//	) {

		// Listen for completed scans.
		top.addEventListener('edac_scan_complete', function (event) {
			
			const postId = event.detail.postId;
			const violations = event.detail.violations;
			const iframeId = event.detail.iframeId;

			// remove the iframe.
			document.getElementById(iframeId).remove();

			// save the scan results.
			saveScanResults(postId, violations);

		});

		//Listen for dispatches from the wp data store so we can trap the update/publish event
		let saving = false;
		if (wp.data !== undefined && wp.data.subscribe !== undefined) {
			wp.data.subscribe(() => {

				// Rescan the page if user saves post
				if (wp.data.select('core/editor').isSavingPost()) {
					saving = true;
				} else {
					if (saving) {
						saving = false;

						injectIframe(edac_editor_app.scanUrl, edac_editor_app.postID);
					}
				}

			});

		} else {
			debug("Gutenberg is not enabled.");
		}


		debug('App is loading from within the editor.');

		injectIframe(edac_editor_app.scanUrl, edac_editor_app.postID);




//	}




	//TODO: migrate to pro
	/*
	if (
		(edac_editor_app.mode === 'editor-scan' && edac_editor_app.edacpApiUrl != '') || //&& edac_editor_app.pendingFullScan) ||
		(edac_editor_app.mode === 'full-scan')
	) {

		debug('App is loading either from the editor page or from the scheduled full scan page.');

		let scanInterval = setInterval(() => {

			//TODO: ?
			if (!window.top._scheduledScanRunning) {

				debug('Polling to see if there are any scans pending.');


				// Poll to see if there are any scans pending.
				getData(edac_editor_app.edacpApiUrl + '/scheduled-scan-url')
					.then((data) => {


						if (data.code !== 'rest_no_route') {

							if (data.data !== undefined) {

								if (data.data.scanUrl !== undefined) {

									info('A post needs scanning: ' + data.data.scanUrl);
								
									injectIframe( data.data.scanUrl )

								}

							}

						} else {

							info('There was a problem connecting to the API.');

							window.top._scheduledScanRunning = false;

							debug('_scheduledScanRunning: false');

						}
					});

			} else {
				debug('Waiting for previous scan to complete.');
			}

		}, SCAN_INTERVAL_IN_SECONDS * 1000);



	}

	*/


}


