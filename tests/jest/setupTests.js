/* global global, window */
if ( typeof global.requestAnimationFrame === 'undefined' ) {
	global.requestAnimationFrame = ( callback ) => setTimeout( callback, 0 );
}
if ( typeof global.cancelAnimationFrame === 'undefined' ) {
	global.cancelAnimationFrame = ( id ) => clearTimeout( id );
}
if ( typeof global.btoa === 'undefined' ) {
	global.btoa = ( str ) => Buffer.from( String( str ), 'binary' ).toString( 'base64' );
}
if ( typeof global.atob === 'undefined' ) {
	global.atob = ( str ) => Buffer.from( String( str ), 'base64' ).toString( 'binary' );
}
beforeEach( () => {
	window.edac_sidebar_app = {
		highlightNonce: 'test-highlight-nonce',
		canManageSettings: true,
		dismissReasons: {
			accessible: { label: 'Confirmed accessible', description: 'Reviewed and verified to meet accessibility requirements.' },
			false_positive: { label: 'False Positive', description: 'This issue does not apply to this content.' },
			remediated: { label: 'Remediated', description: 'This issue has been fixed.' },
		},
	};
	window.edac_editor_app = {
		pro: '0',
		edacUrl: 'https://example.com',
	};
} );

globalThis.IS_REACT_ACT_ENVIRONMENT = true;
