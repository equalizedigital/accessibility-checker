import { getScanIframeUrl, init } from '../../../src/editorApp/checkPage';

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
