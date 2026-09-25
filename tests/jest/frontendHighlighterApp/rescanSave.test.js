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
		for ( let i = 0; i < 100; i++ ) {
			await Promise.resolve();
		}
	};

	const savedPayloads = () =>
		window.fetch.mock.calls
			.filter( ( [ url ] ) => String( url ).includes( '/post-scan-results/' ) )
			.map( ( [ , options ] ) => JSON.parse( options.body ) );

	// The issues the (mocked) highlight request returns after a rescan.
	let storedIssues = [];

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
			responseText: JSON.stringify( { success: true, data: JSON.stringify( { issues: storedIssues, fixes: {} } ) } ),
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
		storedIssues = [];
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

	test( 'clears the previous issue details when a rescan leaves no issues', async () => {
		const title = document.querySelector( '.edac-highlight-panel-description-title' );
		const content = document.querySelector( '.edac-highlight-panel-description-content' );
		const issueView = document.querySelector( '.edac-highlight-panel-controls-content-issue' );
		const emptyView = document.querySelector( '.edac-highlight-panel-controls-content-empty' );
		title.textContent = 'Missing alt text';
		content.textContent = 'This image has no alternative text.';
		issueView.style.display = 'block';
		emptyView.style.display = 'none';

		await rescan( { violations: [] } );

		expect( title.textContent ).toBe( '' );
		expect( content.textContent ).toBe( '' );
		expect( issueView.style.display ).toBe( 'none' );
		expect( emptyView.style.display ).toBe( 'block' );
	} );

	test( 'shows the first issue when a rescan still finds issues', async () => {
		storedIssues = [ { id: 7, rule_title: 'Image missing alt text', rule_type: 'error', object: '<img src="missing.png">' } ];
		const title = document.querySelector( '.edac-highlight-panel-description-title' );
		title.textContent = '';

		await rescan( { violations: [ { ruleId: 'image_alt' } ] } );

		expect( document.getElementById( 'edac-highlight-pagination' ).textContent ).toContain( '1 of 1' );
		expect( title.textContent ).toContain( 'Image missing alt text' );
	} );

	test( 'clearing issues resets the panel and shows the cleared message', async () => {
		window.confirm = jest.fn( () => true );
		window.fetch.mockResolvedValue( { ok: true, json: () => Promise.resolve( {} ) } );
		const title = document.querySelector( '.edac-highlight-panel-description-title' );
		const issueView = document.querySelector( '.edac-highlight-panel-controls-content-issue' );
		const emptyView = document.querySelector( '.edac-highlight-panel-controls-content-empty' );
		title.textContent = 'Missing alt text';
		issueView.style.display = 'block';
		emptyView.style.display = 'none';
		window.history.replaceState( null, '', '/?edac=7' );

		document.getElementById( 'edac-highlight-clear-issues' ).click();
		await settle();

		const summary = document.querySelector( '.edac-highlight-panel-controls-summary' );
		expect( window.fetch ).toHaveBeenCalledWith( expect.stringContaining( '/clear-issues/123' ), expect.anything() );
		expect( title.textContent ).toBe( '' );
		expect( issueView.style.display ).toBe( 'none' );
		expect( emptyView.style.display ).toBe( 'block' );
		expect( new URL( window.location.href ).searchParams.has( 'edac' ) ).toBe( false );
		expect( summary.textContent ).toBe( 'Issues cleared successfully.' );
		delete window.confirm;
	} );
} );
