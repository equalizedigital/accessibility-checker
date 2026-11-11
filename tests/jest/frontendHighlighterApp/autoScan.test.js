import { setImmediate } from 'timers';
import '../../../src/frontendHighlighterApp';

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn() } ),
} ) );

/**
 * A post with no stored issues makes the panel scan itself on open. Nothing
 * awaits that scan, so a failure must not escape as an unhandled rejection.
 * Jest fails the test that was running when a rejection goes unhandled.
 */
describe( 'highlighter auto-scan', () => {
	beforeAll( () => {
		jest.useFakeTimers( { doNotFake: [ 'setImmediate', 'nextTick' ] } );

		Object.defineProperty( window.HTMLElement.prototype, 'innerText', {
			configurable: true,
			get() {
				return this.textContent;
			},
			set( value ) {
				this.textContent = value;
			},
		} );

		jest.spyOn( window, 'XMLHttpRequest' ).mockImplementation( () => ( {
			open: jest.fn(),
			status: 200,
			responseText: JSON.stringify( { success: false, data: [ { code: -3 } ] } ),
			send() {
				this.onload();
			},
		} ) );

		window.fetch = jest.fn().mockResolvedValue( { json: () => Promise.resolve( { success: true } ) } );
		window.runAccessibilityScan = jest.fn().mockResolvedValue( {} );

		const scanner = document.createElement( 'script' );
		scanner.id = 'edac-accessibility-checker-scanner-script';
		document.head.appendChild( scanner );

		window.edacFrontendHighlighterApp = {
			postID: 123,
			restNonce: 'test-nonce',
			restUrl: 'https://example.com/wp-json/accessibility-checker/v1',
			userCanEdit: true,
			loggedIn: true,
		};

		window.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	} );

	afterAll( () => {
		jest.restoreAllMocks();
		jest.clearAllTimers();
		jest.useRealTimers();
		delete window.runAccessibilityScan;
		delete window.edacFrontendHighlighterApp;
		delete window.fetch;
		document.body.innerHTML = '';
		document.head.querySelector( '#edac-accessibility-checker-scanner-script' )?.remove();
	} );

	test( 'a failed auto-scan is not left as an unhandled rejection', async () => {
		document.getElementById( 'edac-highlight-panel-toggle' ).click();
		await new Promise( ( resolve ) => setImmediate( resolve ) );

		const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
		expect( window.runAccessibilityScan ).toHaveBeenCalledTimes( 1 );
		expect( summary.classList.contains( 'edac-error' ) ).toBe( true );
	} );
} );
