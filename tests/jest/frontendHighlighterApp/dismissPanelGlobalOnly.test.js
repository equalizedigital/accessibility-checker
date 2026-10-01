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
		// Enter on a radio submits the form without a submit button being clicked.
		document.querySelector( '.edac-highlight-dismiss-form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		await flush();

		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ) ).toMatchObject( {
			action: 'dismiss',
			ignore_global: 1,
			largeBatch: true,
		} );
		expect( document.querySelector( '.edac-highlight-dismiss-reopen' ).textContent ).toBe( 'Remove Global Dismissal' );
	} );
} );
