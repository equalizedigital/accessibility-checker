import '../../../src/frontendHighlighterApp';

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn(), deactivate: jest.fn() } ),
} ) );

const keptIssue = {
	id: 7,
	slug: 'kept_rule',
	rule_title: 'Kept issue',
	rule_type: 'error',
	object: '<p>Kept</p>',
};

/**
 * Let pending promise callbacks run.
 */
const flushPromises = async () => {
	for ( let i = 0; i < 10; i++ ) {
		await Promise.resolve();
	}
};

describe( 'clearing issues in the frontend highlighter', () => {
	let xhrRequests;

	beforeAll( () => {
		jest.useFakeTimers();
		// The highlight tooltips use floating-ui, which needs ResizeObserver (missing in jsdom).
		window.ResizeObserver = class {
			observe() {}
			unobserve() {}
			disconnect() {}
		};
		xhrRequests = 0;
		jest.spyOn( window, 'XMLHttpRequest' ).mockImplementation( () => ( {
			open: jest.fn(),
			status: 200,
			responseText: JSON.stringify( { success: true, data: JSON.stringify( { issues: [ keptIssue ], fixes: {} } ) } ),
			send() {
				xhrRequests++;
				this.onload();
			},
		} ) );
		jest.spyOn( window, 'confirm' ).mockReturnValue( true );
		window.edacFrontendHighlighterApp = {
			userCanEdit: true,
			loggedIn: true,
			restUrl: '/wp-json/accessibility-checker/v1',
			restNonce: 'nonce',
			postID: 1,
		};
		document.body.innerHTML = '<p>Kept</p>';
		window.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	} );

	afterAll( () => {
		jest.restoreAllMocks();
		jest.clearAllTimers();
		jest.useRealTimers();
		delete window.edacFrontendHighlighterApp;
		delete window.ResizeObserver;
		delete window.fetch;
		document.body.innerHTML = '';
	} );

	const clearWithResponse = async ( body ) => {
		window.fetch = jest.fn().mockResolvedValue( {
			ok: true,
			json: () => Promise.resolve( body ),
		} );
		document.getElementById( 'edac-highlight-clear-issues' ).click();
		await flushPromises();
	};

	test( 'reloads issues the server kept', async () => {
		const requestsBefore = xhrRequests;

		await clearWithResponse( { success: true, flushed: true, remaining: 1 } );

		expect( window.fetch ).toHaveBeenCalledWith( expect.stringContaining( '/clear-issues/1' ), expect.any( Object ) );
		expect( xhrRequests ).toBe( requestsBefore + 1 );
		expect( document.querySelector( '.edac-highlight-panel-controls-summary' ).textContent ).toContain( '1 issue found' );
	} );

	test( 'does not reload when nothing was kept', async () => {
		const requestsBefore = xhrRequests;

		await clearWithResponse( { success: true, flushed: true, remaining: 0 } );

		expect( xhrRequests ).toBe( requestsBefore );
		expect( document.querySelector( '.edac-highlight-panel-controls-summary' ).textContent ).toBe( 'Issues cleared successfully.' );
	} );
} );
