/* global edac_editor_app */

import { init as initCheckPage } from './checkPage';
import { __ } from '@wordpress/i18n';

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

	const rescanButton = document.getElementById( 'edac-rescan-button' );
	rescanButton.addEventListener( 'click', function() {
		setRescanButtonState( true, __( 'Rescanning...', 'accessibility-checker' ) + ' <span class="spinner is-active"></span>' );

		fetch( window.edac_editor_app.edacApiUrl + '/trigger-scan/' + window.edac_editor_app.postID, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.edac_editor_app.restNonce,
			},
			body: JSON.stringify(
				{
					id: window.edac_editor_app.postID,
					flush: true,
				}
			),
		} ).then(
			( response ) => {
				if ( response.ok ) {
					initCheckPage();
				} else {
					setRescanButtonState( false, __( 'Scan failed, retry', 'accessibility-checker' ) );
				}
			}
		);
	} );
	if ( ! top.JSSCanScavedRescanEventAdded ) {
		top.JSSCanScavedRescanEventAdded = true;
		top.addEventListener( 'edac_js_scan_save_complete', function() {
			setRescanButtonState();
		} );
	}
} );

/**
 * Set the disabled state of the rescan button and it's contents.
 * @param {boolean} state   The state to set the buttons disabled value to.
 * @param {string}  message The message that the button should contain.
 */
const setRescanButtonState = ( state, message ) => {
	const rescanButton = document.getElementById( 'edac-rescan-button' );
	rescanButton.disabled = state ?? false;
	rescanButton.innerHTML = message ?? __( 'Rescan', 'accessibility-checker' );
};
