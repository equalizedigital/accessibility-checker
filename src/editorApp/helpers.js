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

export const postData = async ( url = '', data = {} ) => {

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


export const getData = async ( url = '' ) => {
	const response = await fetch( url, {
		method: 'GET',
		headers: {
			// eslint-disable-next-line camelcase
			'X-WP-Nonce': edac_script_vars.restNonce,
			'Content-Type': 'application/json',
		},
	} );
	return response.json();
};
