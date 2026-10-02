/**
 * Integration test: a user who can only dismiss globally (no per-post dismiss capability).
 */

import '../../../src/frontendHighlighterApp';

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn(), deactivate: jest.fn(), pause: jest.fn(), unpause: jest.fn(), active: true } ),
} ) );

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

describe( 'frontend highlighter dismiss controls for a global-only user', () => {
	beforeAll( () => {
		document.body.innerHTML = '<img src="a.png">';
		const issues = [ {
			id: '7',
			slug: 'img_alt_missing',
			rule_title: 'Image Missing Alternative Text',
			rule_type: 'error',
			base_rule_type: 'error',
			ignored: '0',
			ignre_global: 0,
			link: 'https://example.com/help',
			object: '<img src="a.png">',
			selector: 'img',
		} ];
		jest.spyOn( window, 'XMLHttpRequest' ).mockImplementation( () => ( {
			open: jest.fn(),
			status: 200,
			responseText: JSON.stringify( { success: true, data: JSON.stringify( { issues, fixes: {} } ) } ),
			send() {
				this.onload();
			},
		} ) );
		// wp_localize_script() sends PHP false as an empty string.
		window.edacFrontendHighlighterApp = {
			restUrl: '/wp-json/accessibility-checker/v1',
			restNonce: 'nonce',
			canDismiss: '',
			canDismissGlobal: '1',
			globalIgnoreUrl: '/wp-json/accessibility-checker-pro/v1/global-ignore',
			dismissReasons: {
				accessible: { label: 'Confirmed accessible', description: '' },
			},
		};
		Element.prototype.scrollIntoView = jest.fn();
		// Floating UI's autoUpdate needs ResizeObserver, which jsdom lacks.
		window.ResizeObserver = class {
			observe() {}
			unobserve() {}
			disconnect() {}
		};
		window.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	} );

	afterAll( () => {
		jest.restoreAllMocks();
		delete window.fetch;
		delete window.edacFrontendHighlighterApp;
		document.body.innerHTML = '';
		history.replaceState( null, '', '/' );
	} );

	test( 'submitting the form sends a global dismiss, never a single one', async () => {
		document.getElementById( 'edac-highlight-panel-toggle' ).click();
		await flush();

		expect( document.querySelector( '[data-scope="single"]' ) ).toBeNull();
		expect( document.querySelector( '[data-scope="global"]' ) ).not.toBeNull();

		window.fetch = jest.fn().mockResolvedValue( {
			ok: true,
			json: () => Promise.resolve( { success: true, ignre_global: 1 } ),
		} );
		// Global is the only action, so its button is the form's submit button: Enter on a radio
		// and clicking it both go through the form's submit event, once.
		const globalButton = document.querySelector( '[data-scope="global"]' );
		expect( globalButton.type ).toBe( 'submit' );
		globalButton.click();
		await flush();

		expect( window.fetch ).toHaveBeenCalledTimes( 2 );

		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ) ).toMatchObject( {
			action: 'dismiss',
			ignore_global: 1,
			largeBatch: true,
		} );
		// Pro's global ignores table is updated after the dismiss so later scans ignore it too.
		expect( window.fetch.mock.calls[ 1 ][ 0 ] ).toBe( '/wp-json/accessibility-checker-pro/v1/global-ignore' );
		expect( JSON.parse( window.fetch.mock.calls[ 1 ][ 1 ].body ) ).toEqual( { issue_id: 7, action: 'enable' } );
		expect( document.querySelector( '.edac-highlight-dismiss-reopen' ).textContent ).toBe( 'Remove Global Dismissal' );
	} );

	test( 'removing the global dismissal also removes it from Pro\'s table', async () => {
		window.fetch.mockClear();
		document.querySelector( '.edac-highlight-dismiss-reopen' ).click();
		await flush();

		expect( window.fetch ).toHaveBeenCalledTimes( 2 );
		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ) ).toMatchObject( { action: 'undismiss', largeBatch: true } );
		expect( window.fetch.mock.calls[ 1 ][ 0 ] ).toBe( '/wp-json/accessibility-checker-pro/v1/global-ignore' );
		expect( JSON.parse( window.fetch.mock.calls[ 1 ][ 1 ].body ) ).toEqual( { issue_id: 7, action: 'disable' } );
	} );

	test( 'a failed table update is reported without undoing the dismissal', async () => {
		window.fetch = jest.fn()
			.mockResolvedValueOnce( { ok: true, json: () => Promise.resolve( { success: true, ignre_global: 1 } ) } )
			.mockResolvedValueOnce( { ok: false, json: () => Promise.resolve( {} ) } );
		document.querySelector( '[data-scope="global"]' ).click();
		await flush();

		expect( document.querySelector( '.edac-highlight-dismiss-reopen' ).textContent ).toBe( 'Remove Global Dismissal' );
		expect( document.querySelector( '.edac-highlight-dismiss-error' ).textContent ).toContain( 'could not be saved' );
	} );
} );
