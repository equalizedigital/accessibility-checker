import '../../../src/frontendHighlighterApp';

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn() } ),
} ) );

/**
 * A rescan that finds no violations must still be saved: the REST request
 * replaces the post's stored issues, so skipping the save is what leaves
 * resolved issues behind in the panel, the reports and the Issues Explorer.
 */
describe( 'highlighter rescan saving', () => {
	const settle = async () => {
		for ( let i = 0; i < 25; i++ ) {
			await Promise.resolve();
		}
	};

	const savedPayloads = () =>
		window.fetch.mock.calls
			.filter( ( [ url ] ) => String( url ).includes( '/post-scan-results/' ) )
			.map( ( [ , options ] ) => JSON.parse( options.body ) );

	const rescan = async ( scanResult ) => {
		window.runAccessibilityScan.mockResolvedValue( scanResult );
		document.getElementById( 'edac-highlight-rescan' ).click();
		await settle();
	};

	beforeAll( async () => {
		jest.useFakeTimers();

		// jsdom does not implement innerText and the rescan measures the page with it.
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
			responseText: JSON.stringify( { success: true, data: JSON.stringify( { issues: [], fixes: {} } ) } ),
			send() {
				this.onload();
			},
		} ) );

		window.fetch = jest.fn().mockResolvedValue( { json: () => Promise.resolve( { success: true } ) } );
		window.runAccessibilityScan = jest.fn().mockResolvedValue( { violations: [] } );

		// The app inserts the scanner bundle itself and waits for a load event
		// jsdom never fires. With the element already in place the rescan runs
		// the scan immediately.
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

	beforeEach( () => {
		window.fetch.mockClear();
		window.runAccessibilityScan.mockReset();
	} );

	test( 'saves an empty result so resolved issues are cleared', async () => {
		await rescan( { violations: [] } );

		const payloads = savedPayloads();
		expect( payloads ).toHaveLength( 1 );
		expect( payloads[ 0 ].violations ).toEqual( [] );
		expect( payloads[ 0 ].isSkipped ).toBe( false );
		expect( payloads[ 0 ].isFailure ).toBe( false );
	} );

	test( 'saves the violations that were found', async () => {
		await rescan( { violations: [ { ruleId: 'image_alt' } ] } );

		const payloads = savedPayloads();
		expect( payloads ).toHaveLength( 1 );
		expect( payloads[ 0 ].violations ).toHaveLength( 1 );
	} );

	test( 'announces the rescan instead of an error when nothing was found', async () => {
		await rescan( { violations: [] } );
		// announce() writes after a short delay so assistive tech picks it up.
		jest.advanceTimersByTime( 100 );
		await settle();

		const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
		expect( summary.textContent ).not.toContain( 'skipping save' );
		expect( summary.classList.contains( 'edac-error' ) ).toBe( false );
		expect( document.getElementById( 'edac-highlight-announcer' ).textContent ).toContain( 'No violations found' );
	} );
} );
