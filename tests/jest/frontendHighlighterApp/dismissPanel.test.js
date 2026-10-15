/**
 * Integration test: dismissing and reopening an issue from the highlighter panel.
 */

import '../../../src/frontendHighlighterApp';

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn(), deactivate: jest.fn(), pause: jest.fn(), unpause: jest.fn(), active: true } ),
} ) );

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

describe( 'frontend highlighter dismiss controls', () => {
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
		window.edacFrontendHighlighterApp = {
			restUrl: '/wp-json/accessibility-checker/v1',
			restNonce: 'nonce',
			canDismiss: '1',
			canDismissGlobal: '',
			dismissReasons: {
				accessible: { label: 'Confirmed accessible', description: '' },
				false_positive: { label: 'False positive', description: '' },
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

	test( 'dismisses then reopens the issue in place', async () => {
		document.getElementById( 'edac-highlight-panel-toggle' ).click();
		await flush();

		const toggle = document.querySelector( '.edac-highlight-panel-description-dismiss-toggle' );
		expect( toggle ).not.toBeNull();
		expect( document.querySelector( '.edac-highlight-btn' ).classList ).toContain( 'edac-highlight-btn-error' );

		toggle.click();
		expect( document.querySelector( '.edac-highlight-dismiss-form' ).hidden ).toBe( false );

		document.querySelector( 'input[value="false_positive"]' ).checked = true;
		document.querySelector( 'textarea' ).value = 'Decorative image';

		window.fetch = jest.fn().mockResolvedValue( {
			ok: true,
			json: () => Promise.resolve( { success: true, ignre_user_name: 'admin', ignre_date: 'Today', ignre_reason: 'false_positive' } ),
		} );
		document.querySelector( '.edac-highlight-dismiss-form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		await flush();

		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ) ).toMatchObject( {
			action: 'dismiss',
			reason: 'false_positive',
			comment: 'Decorative image',
		} );
		expect( document.querySelector( '.edac-highlight-dismissed-heading' ).textContent ).toBe( 'Issue Dismissed — False positive' );
		expect( document.querySelector( '.edac-highlight-btn' ).classList ).toContain( 'edac-highlight-btn-ignored' );
		expect( document.querySelector( '.edac-highlight-panel-controls-summary' ).textContent ).toContain( '1 Dismissed' );
		expect( document.activeElement ).toBe( document.querySelector( '.edac-highlight-dismiss-reopen' ) );

		window.fetch = jest.fn().mockResolvedValue( { ok: true, json: () => Promise.resolve( { success: true } ) } );
		document.querySelector( '.edac-highlight-dismiss-reopen' ).click();
		await flush();

		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ).action ).toBe( 'undismiss' );
		expect( document.querySelector( '.edac-highlight-btn' ).classList ).toContain( 'edac-highlight-btn-error' );
		expect( document.activeElement ).toBe( document.querySelector( '.edac-highlight-panel-description-dismiss-toggle' ) );
		expect( document.querySelector( 'input[value="false_positive"]' ).checked ).toBe( true );
	} );

	test( 'shows the server error and re-enables the buttons on failure', async () => {
		window.fetch = jest.fn().mockResolvedValue( { ok: false, json: () => Promise.resolve( { message: 'Nope' } ) } );
		document.querySelector( '.edac-highlight-dismiss-form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		await flush();

		expect( document.querySelector( '.edac-highlight-dismiss-error' ).textContent ).toBe( 'Nope' );
		expect( document.querySelector( '.edac-highlight-dismiss-submit' ).disabled ).toBe( false );
	} );
} );
