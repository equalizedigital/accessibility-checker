import { getScanIframeUrl, init } from '../../../src/editorApp/checkPage';
import { showNotice } from '../../../src/common/helpers';

jest.mock( '../../../src/common/helpers', () => ( { showNotice: jest.fn() } ) );

const setCrossOriginIsolated = ( value ) => {
	Object.defineProperty( window, 'crossOriginIsolated', {
		value,
		configurable: true,
		writable: true,
	} );
};

describe( 'getScanIframeUrl', () => {
	afterEach( () => {
		delete window.crossOriginIsolated;
	} );

	test( 'returns the URL unchanged when the editor is not cross-origin isolated', () => {
		setCrossOriginIsolated( false );
		const url = 'https://example.com/?p=1&preview=true&edac_pageScanner=1';

		expect( getScanIframeUrl( url ) ).toBe( url );
	} );

	test( 'returns the URL unchanged when crossOriginIsolated is not available', () => {
		const url = 'https://example.com/?p=1&preview=true&edac_pageScanner=1';

		expect( getScanIframeUrl( url ) ).toBe( url );
	} );

	test( 'adds the isolated marker when the editor is cross-origin isolated', () => {
		setCrossOriginIsolated( true );

		const result = new URL( getScanIframeUrl( 'https://example.com/?p=1&preview=true&edac_pageScanner=1' ) );

		expect( result.searchParams.get( 'edac_isolated' ) ).toBe( '1' );
	} );

	test( 'keeps existing query parameters when adding the isolated marker', () => {
		setCrossOriginIsolated( true );

		const result = new URL( getScanIframeUrl( 'https://example.com/?p=1&preview=true&edac_pageScanner=1' ) );

		expect( result.searchParams.get( 'p' ) ).toBe( '1' );
		expect( result.searchParams.get( 'preview' ) ).toBe( 'true' );
		expect( result.searchParams.get( 'edac_pageScanner' ) ).toBe( '1' );
	} );

	test( 'does not duplicate the isolated marker if it is already present', () => {
		setCrossOriginIsolated( true );

		const result = new URL( getScanIframeUrl( 'https://example.com/?edac_pageScanner=1&edac_isolated=1' ) );

		expect( result.searchParams.getAll( 'edac_isolated' ) ).toEqual( [ '1' ] );
	} );

	test( 'falls back to the original URL if it can not be parsed', () => {
		setCrossOriginIsolated( true );
		const badUrl = 'http://[bad-host/?edac_pageScanner=1';

		expect( getScanIframeUrl( badUrl ) ).toBe( badUrl );
	} );
} );

describe( 'init', () => {
	const scanUrl = 'https://example.com/?p=1&preview=true&edac_pageScanner=1';

	let addListenerSpy;

	beforeEach( () => {
		document.body.innerHTML = '';

		// Track the listeners init() registers on `top` so they can be removed afterwards.
		addListenerSpy = jest.spyOn( top, 'addEventListener' );

		window.edac_editor_app = {
			scanUrl,
			postID: 1,
			postStatus: 'draft',
		};
		window.wp = {
			data: {
				subscribe: jest.fn(),
				select: jest.fn( () => ( {
					getEditedPostAttribute: () => 'draft',
				} ) ),
			},
		};
	} );

	afterEach( () => {
		// Undo what init() leaves behind on `top` so later tests start from a clean state.
		addListenerSpy.mock.calls
			.filter( ( [ type ] ) => type === 'edac_scan_complete' )
			.forEach( ( [ type, listener ] ) => top.removeEventListener( type, listener ) );
		addListenerSpy.mockRestore();
		top.edacScanCompleteListenerAdded = false;
		delete top.edacPostSaveStateSubscribed;

		delete window.crossOriginIsolated;
		delete window.edac_editor_app;
		delete window.wp;
		document.body.innerHTML = '';
	} );

	test( 'loads the scan URL unchanged in the iframe when the editor is not isolated', () => {
		setCrossOriginIsolated( false );

		init();

		expect( document.querySelector( 'iframe' ).getAttribute( 'src' ) ).toBe( scanUrl );
	} );

	test( 'adds the isolated marker to the iframe URL when the editor is isolated', () => {
		setCrossOriginIsolated( true );

		init();

		const src = new URL( document.querySelector( 'iframe' ).getAttribute( 'src' ) );

		expect( src.searchParams.get( 'edac_pageScanner' ) ).toBe( '1' );
		expect( src.searchParams.get( 'edac_isolated' ) ).toBe( '1' );
	} );
} );

describe( 'edac_scan_complete listener', () => {
	// A failed or timed-out scan must not be saved: its empty violation list would be
	// stored as "the page is clean" and purge every issue row the post had.
	let addListenerSpy;

	const dispatchScanComplete = ( detail ) => {
		top.dispatchEvent( new CustomEvent( 'edac_scan_complete', {
			detail: {
				iframeId: 'edac-scan-iframe',
				postId: 42,
				violations: [],
				errorMsgs: [],
				error: '',
				densityMetrics: { elementCount: 0, contentLength: 0 },
				...detail,
			},
		} ) );
	};

	beforeEach( () => {
		addListenerSpy = jest.spyOn( top, 'addEventListener' );
		jest.spyOn( console, 'error' ).mockImplementation( () => {} );
		showNotice.mockClear();

		window.edac_editor_app = {
			scanUrl: 'https://example.com/?p=1&preview=true&edac_pageScanner=1',
			postID: 1,
			edacApiUrl: 'https://example.com/wp-json/accessibility-checker/v1',
		};
		window.edac_script_vars = { restNonce: 'nonce' };
		window.wp = {
			i18n: { __: ( text ) => text },
			data: { subscribe: jest.fn(), select: jest.fn() },
		};
		window.fetch = jest.fn( () => Promise.resolve( { json: () => Promise.resolve( { success: true } ) } ) );

		init();
	} );

	afterEach( () => {
		addListenerSpy.mock.calls
			.filter( ( [ type ] ) => type === 'edac_scan_complete' )
			.forEach( ( [ type, listener ] ) => top.removeEventListener( type, listener ) );
		addListenerSpy.mockRestore();
		console.error.mockRestore(); // eslint-disable-line no-console
		top.edacScanCompleteListenerAdded = false;
		delete top.edacPostSaveStateSubscribed;
		delete window.edac_editor_app;
		delete window.edac_script_vars;
		delete window.wp;
		delete window.fetch;
		document.body.innerHTML = '';
	} );

	test( 'does not save a timed-out scan, logs it and tells the user', () => {
		dispatchScanComplete( { failed: true, error: 'timeout', errorMsgs: [ 'Scan timed out' ] } );

		expect( window.fetch ).not.toHaveBeenCalled();
		expect( console.error ).toHaveBeenCalledWith( // eslint-disable-line no-console
			expect.stringContaining( 'scan failed for post 42' ),
			'timeout',
			[ 'Scan timed out' ],
		);
		expect( showNotice ).toHaveBeenCalledWith( expect.objectContaining( { type: 'error' } ) );
	} );

	test( 'does not save a scan that threw', () => {
		dispatchScanComplete( { failed: true, error: 'axe exploded', errorMsgs: [ 'axe exploded' ] } );

		expect( window.fetch ).not.toHaveBeenCalled();
	} );

	test( 'still saves a successful scan that found no violations', () => {
		dispatchScanComplete( { violations: [] } );

		expect( window.fetch ).toHaveBeenCalledTimes( 1 );
		expect( window.fetch.mock.calls[ 0 ][ 0 ] ).toContain( '/post-scan-results/42' );
		expect( showNotice ).not.toHaveBeenCalled();
	} );

	test( 'still saves a successful scan that found violations', () => {
		dispatchScanComplete( { violations: [ { ruleId: 'image-alt' } ] } );

		expect( window.fetch ).toHaveBeenCalledTimes( 1 );
		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ).violations ).toHaveLength( 1 );
	} );
} );
