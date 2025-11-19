import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

export const showNotice = ( options ) => {
	const settings = Object.assign( {}, {
		msg: '',
		type: 'warning',
		url: false,
		label: '',
		closeOthers: true,
	}, options );

	// Showing on a page with the wp option loaded.
	if ( window.wp !== undefined && window.wp.data !== undefined && window.wp.data.dispatch !== undefined ) {
		const o = { isDismissible: true };

		let msg = settings.msg;

		if ( settings.url ) {
			o.actions = [ {
				url: settings.url,
				label: settings.label,
			} ];

			msg = msg.replace( '{link}', 'follow the link below' );
		} else {
			msg = msg.replace( '{link}', '' );
		}

		if ( settings.closeOthers ) {
			document.querySelectorAll( '.components-notice' ).forEach( ( element ) => {
				element.style.display = 'none';
			} );
		}

		setTimeout( function() {
			wp.data.dispatch( 'core/notices' ).createNotice( settings.type, msg, o );
		}, 10 );
	} else {
		if ( window.edac_editor_app && window.edac_editor_app.baseurl ) {
			// output the css file build/css/editorApp.css that contains notyf styles.
			const link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.type = 'text/css';
			link.href = window.edac_editor_app.baseurl + '/build/css/editorApp.css';
			document.getElementsByTagName( 'head' )[ 0 ].appendChild( link );
		}

		let msg = settings.msg;

		if ( settings.url ) {
			msg = msg.replace(
				'{link}',
				`<a href="${ encodeURI( settings.url ) }" target="_blank" aria-label="${ settings.label }">${ settings.label }</a>`
			);
		} else {
			msg = msg.replace( '{link}', '' );
		}

		const notyf = new Notyf( {
			position: { x: 'right', y: 'top' },
			ripple: false,
			types: [
				{
					type: 'success',
					background: '#193EEE',
					duration: 2000,
					dismissible: true,
					icon: false,
				},

				{
					type: 'warning',
					background: '#454545',
					duration: 4000,
					dismissible: true,
					icon: false,
				},
				{
					type: 'error',
					background: '#AD1414',
					duration: 0,
					dismissible: true,
					icon: false,
				},
			],
		} );

		if ( settings.closeOthers ) {
			notyf.dismissAll();
		}

		notyf.open( {
			type: settings.type,
			message: msg,
		} );
	}
};

export const debounce = ( fn, wait ) => {
	let timer;
	return function( ...args ) {
		if ( timer ) {
			clearTimeout( timer );
		}
		const context = this;
		timer = setTimeout( () => {
			fn.apply( context, args );
		}, wait );
	};
};

export const isValidDateFormat = ( inputString ) => {
	// Define a regular expression pattern for the format yyyy-mm-dd
	const regexPattern = /^\d{4}-\d{2}-\d{2}$/;

	// Test the input string against the regular expression
	return regexPattern.test( inputString );
};

export const hashString = ( str ) => {
	let hash = 0;
	if ( str.length === 0 ) {
		return hash;
	}
	for ( let i = 0; i < str.length; i++ ) {
		const char = str.charCodeAt( i );
		hash = ( ( hash * 32 ) - hash ) + char;
		hash = Math.floor( hash );
	}
	return Math.abs( hash );
};
