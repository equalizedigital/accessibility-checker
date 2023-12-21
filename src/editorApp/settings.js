/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global edacEditorApp */

let debug = false;

if ( typeof ( edacEditorApp ) !== 'undefined' ) {
	debug = edacEditorApp.debug === '1';
}

export const settings = {
	JS_SCAN_ENABLED: true,
	INFO_ENABLED: debug,
	DEBUG_ENABLED: debug,
};

