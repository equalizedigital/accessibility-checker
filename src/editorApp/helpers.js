/* eslint-disable padded-blocks, no-multiple-empty-lines, no-console */

import { settings } from './settings';

export const info = ( message ) => {
	if ( settings.INFO_ENABLED ) {
		console.info( message );
	}
};

export const debug = ( message ) => {
	if ( settings.DEBUG_ENABLED ) {
		if ( location.href !== window.top.location.href ) {
			console.debug( 'DEBUG [ ' + location.href + ' ]' );
		}
		if ( typeof message !== 'object' ) {
			console.debug( 'DEBUG: ' + message );
		} else {
			console.debug( message );
		}
	}
};
