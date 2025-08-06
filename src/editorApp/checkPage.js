/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global edac_editor_app */

import { info, debug } from './helpers';
import { showNotice } from './../common/helpers';

const postData = async ( url = '', data = {} ) => {


	return await fetch( url, {
		method: 'POST',
		headers: {
			// eslint-disable-next-line camelcase
			'X-WP-Nonce': edac_script_vars.restNonce,
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( data ),
	} ).then( ( res ) => {
		return res.json();
	} ).catch( () => {
		return {};
	} );

};

const saveScanResults = ( postId, violations, densityMetrics ) => {
	document.querySelector( '.edac-panel' ).classList.add( 'edac-panel-loading' );
	// eslint-disable-next-line camelcase
	postData( edac_editor_app.edacApiUrl + '/post-scan-results/' + postId, {
		violations,
		densityMetrics,
	} ).then( ( data ) => {
		info( 'Saving ' + postId + ': done' );

		// Create and dispatch an event to tell legacy admin.js to refresh tabs. Refactor this.
		const customEvent = new CustomEvent( 'edac_js_scan_save_complete' );
		top.dispatchEvent( customEvent );

		if ( ! data.success ) {
			info( 'Saving ' + postId + ': error' );

			showNotice( {
				msg: 'Whoops! It looks like there was a problem updating. Please try again.',
				type: 'warning',
			} );
		}

		document.querySelector( '.edac-panel' ).classList.add( 'edac-panel-loading' );
	} );
};

/**
 * Inject an iframe into the page and load the previewUrl for scanning.
 *
 * @param {string} previewUrl The URL to load in the iframe.
 * @param {number} postID     The post ID to pass to the iframe.
 */
const injectIframe = ( previewUrl, postID ) => {

	// Gen unique id for this iframe
	const timestamp = new Date().getTime();
	const randomNumber = Math.floor( Math.random() * 1000 );
	const uniqueId = 'iframe' + '_' + timestamp + '_' + randomNumber;

	// inject the iframe
	const iframe = document.createElement( 'iframe' );
	iframe.setAttribute( 'id', uniqueId );
	iframe.setAttribute( 'src', previewUrl );
	iframe.style.width = screen.width + 'px';
	iframe.style.height = screen.height + 'px';
	iframe.style.position = 'absolute';
	iframe.style.left = '-' + screen.width + 'px';

	document.body.append( iframe );

	// Wait for the preview to load & inject the pageScanner script.
	iframe.addEventListener( 'load', function() {
		// Access the contentDocument of the iframe.
		const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

		// Pass the postID and iframe id into the document so we can reference them from the document.
		const body = iframeDocument.querySelector( 'body' );
		body.setAttribute( 'data-iframe-id', uniqueId );
		body.setAttribute( 'data-iframe-event-name', 'edac_scan_complete' );
		body.setAttribute( 'data-iframe-post-id', postID );

		if ( iframeDocument ) {
			if ( window?.edac_editor_app?.maxAltLength ) {
				// if the frame doesn't have window.scanOptions then create is as an object.
				if ( ! iframeDocument.defaultView.scanOptions ) {
					iframeDocument.defaultView.scanOptions = {};
				}

				// set the maxAlthLength for the scanOptions.
				iframeDocument.defaultView.scanOptions = {
					maxAltLength: window.edac_editor_app.maxAltLength,
				};
			}

			// inject the scanner app.
			const scannerScriptElement = iframeDocument.createElement( 'script' );
			// eslint-disable-next-line camelcase
			scannerScriptElement.src = edac_editor_app.baseurl + '/build/pageScanner.bundle.js?v=' + edac_editor_app.version;
			iframeDocument.head.appendChild( scannerScriptElement );
		}
	} );
};
/**
 * Check if the current post is scannable based on its status.
 *
 * @return {boolean} True if the post can be scanned, false otherwise.
 */
const isPostScannable = () => {
	// eslint-disable-next-line camelcase
	if ( ! edac_editor_app.postStatus ) {
		return false;
	}

	// eslint-disable-next-line camelcase
	const nonScannableStatuses = edac_editor_app.nonScannablePostStatuses || [];

	// Check if the current post status is NOT in the deny list
	// eslint-disable-next-line camelcase
	return ! nonScannableStatuses.includes( edac_editor_app.postStatus );
};

top.edacScanCompleteListenerAdded = false;
export const init = () => {
	// Listen for completed scans.
	if ( ! top.edacScanCompleteListenerAdded ) {
		top.edacScanCompleteListenerAdded = true;
		top.addEventListener( 'edac_scan_complete', function( event ) {
			const postId = event.detail.postId;
			const violations = event.detail.violations;
			const iframeId = event.detail.iframeId;

			// remove the iframe.
			setTimeout( function() {
				document.getElementById( iframeId )?.remove();
			}, 1000 );

			// save the scan results.
			saveScanResults( postId, violations, event?.detail?.densityMetrics );
		} );
	}

	//Listen for dispatches from the wp data store so we can trap the update/publish event
	let saving = false;
	let autosaving = false;

	top.edacPostSaveStateSubscribed = top.edacPostSaveStateSubscribed || false;
	if ( wp.data !== undefined && wp.data.subscribe !== undefined && ! top.edacPostSaveStateSubscribed ) {
		wp.data.subscribe( () => {
			top.edacPostSaveStateSubscribed = true;

			if ( wp.data.select( 'core/editor' ) === undefined ) {
				return;
			}

			if ( wp.data.select( 'core/editor' ).isAutosavingPost() ) {
				autosaving = true;
			}

			// Rescan the page if user saves post
			if ( wp.data.select( 'core/editor' ).isSavingPost() ) {
				saving = true;
			} else if ( saving ) {
				saving = false;

				if ( ! autosaving ) {
					// Check current post status after save
					const currentStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
					// eslint-disable-next-line camelcase
					const nonScannableStatuses = edac_editor_app.nonScannablePostStatuses || [];

					// Only scan if the post status is NOT in the deny list
					if ( ! nonScannableStatuses.includes( currentStatus ) ) {
						// eslint-disable-next-line camelcase
						injectIframe( edac_editor_app.scanUrl, edac_editor_app.postID );
					}
				} else {
					autosaving = false;
				}
			}
		} );
	} else {
		debug( 'Gutenberg is not enabled.' );
	}

	// Only scan immediately if the post has a scannable status
	if ( isPostScannable() ) {
		// eslint-disable-next-line camelcase
		injectIframe( edac_editor_app.scanUrl, edac_editor_app.postID );
	}
};

